<?php

declare(strict_types=1);

namespace App\Services\Integration\Action;

use App\Contracts\Integration\ConnectorRegistryContract;
use App\Contracts\Integration\CredentialVaultContract;
use App\Contracts\Integration\ActionContract;
use App\Models\Integration\ActionExecution;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Action Engine
 *
 * Executes actions on external services with retry logic, rate limiting,
 * error handling, and execution tracking.
 *
 * @example Execute an action
 * ```php
 * $result = $engine->execute('slack', 'send_message', $connectionId, [
 *     'channel' => '#general',
 *     'text' => 'Hello from automation!',
 * ]);
 * ```
 *
 * @example Execute with retry
 * ```php
 * $result = $engine->executeWithRetry('gmail', 'send_email', $connectionId, $input, [
 *     'max_attempts' => 3,
 *     'backoff' => 'exponential',
 * ]);
 * ```
 */
class ActionEngine
{
    /**
     * Default retry configuration.
     */
    protected array $defaultRetryConfig = [
        'max_attempts' => 3,
        'backoff' => 'exponential', // 'fixed', 'linear', 'exponential'
        'base_delay' => 1000, // milliseconds
        'max_delay' => 30000, // milliseconds
        'retryable_codes' => [408, 429, 500, 502, 503, 504],
    ];

    public function __construct(
        protected ConnectorRegistryContract $connectorRegistry,
        protected CredentialVaultContract $credentialVault
    ) {}

    // =========================================================================
    // EXECUTION
    // =========================================================================

    /**
     * Execute an action.
     */
    public function execute(
        string $connectorName,
        string $actionName,
        string $connectionId,
        array $input,
        array $context = []
    ): array {
        $action = $this->connectorRegistry->getAction($connectorName, $actionName);

        if (!$action) {
            throw new \InvalidArgumentException(
                "Action not found: {$connectorName}.{$actionName}"
            );
        }

        // Create execution record
        $execution = $this->createExecution($connectorName, $actionName, $connectionId, $input, $context);

        try {
            // Check rate limits
            $this->checkRateLimits($connectorName, $connectionId);

            // Validate input
            $validationErrors = $action->validateInput($input);
            if (!empty($validationErrors)) {
                throw new \App\Exceptions\Integration\ValidationException(
                    'Input validation failed',
                    $validationErrors
                );
            }

            // Get credentials
            $credentials = $this->credentialVault->retrieve($connectionId);

            if (!$credentials) {
                throw new \App\Exceptions\Integration\CredentialNotFoundException(
                    "Credentials not found: {$connectionId}"
                );
            }

            // Execute action
            $startTime = microtime(true);
            $result = $action->execute($credentials, $input);
            $duration = (microtime(true) - $startTime) * 1000;

            // Update execution record
            $this->markSuccess($execution, $result, $duration);

            // Fire hook
            do_action('action_executed', $connectorName, $actionName, $result);

            return [
                'success' => true,
                'execution_id' => $execution->id,
                'data' => $result,
                'duration_ms' => $duration,
            ];

        } catch (\Exception $e) {
            $this->markFailed($execution, $e);

            // Fire hook
            do_action('action_failed', $connectorName, $actionName, $e);

            throw $e;
        }
    }

    /**
     * Execute with automatic retry.
     */
    public function executeWithRetry(
        string $connectorName,
        string $actionName,
        string $connectionId,
        array $input,
        array $retryConfig = [],
        array $context = []
    ): array {
        $config = array_merge($this->defaultRetryConfig, $retryConfig);
        $attempts = 0;
        $lastException = null;

        while ($attempts < $config['max_attempts']) {
            $attempts++;

            try {
                return $this->execute($connectorName, $actionName, $connectionId, $input, array_merge($context, [
                    'attempt' => $attempts,
                    'max_attempts' => $config['max_attempts'],
                ]));

            } catch (\Exception $e) {
                $lastException = $e;

                // Check if retryable
                if (!$this->isRetryable($e, $config)) {
                    throw $e;
                }

                // Don't sleep on last attempt
                if ($attempts < $config['max_attempts']) {
                    $delay = $this->calculateDelay($attempts, $config);
                    usleep($delay * 1000); // Convert to microseconds
                }
            }
        }

        throw $lastException ?? new \RuntimeException('Max retry attempts exceeded');
    }

    /**
     * Execute bulk actions.
     */
    public function executeBulk(
        string $connectorName,
        string $actionName,
        string $connectionId,
        array $items,
        array $options = []
    ): array {
        $action = $this->connectorRegistry->getAction($connectorName, $actionName);

        if (!$action) {
            throw new \InvalidArgumentException(
                "Action not found: {$connectorName}.{$actionName}"
            );
        }

        // Use native bulk if supported
        if ($action->supportsBulk()) {
            $credentials = $this->credentialVault->retrieve($connectionId);
            return $action->executeBulk($credentials, $items);
        }

        // Fall back to sequential execution
        $results = [];
        $errors = [];

        $stopOnError = $options['stop_on_error'] ?? false;
        $batchSize = $options['batch_size'] ?? 10;
        $batchDelay = $options['batch_delay'] ?? 100; // ms

        $batches = array_chunk($items, $batchSize);

        foreach ($batches as $batchIndex => $batch) {
            // Delay between batches (except first)
            if ($batchIndex > 0 && $batchDelay > 0) {
                usleep($batchDelay * 1000);
            }

            foreach ($batch as $index => $item) {
                $itemIndex = ($batchIndex * $batchSize) + $index;

                try {
                    $result = $this->execute(
                        $connectorName,
                        $actionName,
                        $connectionId,
                        $item,
                        ['bulk_index' => $itemIndex]
                    );

                    $results[$itemIndex] = [
                        'success' => true,
                        'data' => $result['data'],
                    ];

                } catch (\Exception $e) {
                    $errors[$itemIndex] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];

                    if ($stopOnError) {
                        break 2;
                    }
                }
            }
        }

        return [
            'total' => count($items),
            'successful' => count($results),
            'failed' => count($errors),
            'results' => $results,
            'errors' => $errors,
        ];
    }

    // =========================================================================
    // RATE LIMITING
    // =========================================================================

    /**
     * Check rate limits before execution.
     */
    protected function checkRateLimits(string $connectorName, string $connectionId): void
    {
        $connector = $this->connectorRegistry->get($connectorName);

        if (!$connector) {
            return;
        }

        $limits = $connector->getRateLimits();

        if (empty($limits)) {
            return;
        }

        $key = "integration:{$connectorName}:{$connectionId}";
        $maxAttempts = $limits['requests'] ?? 100;
        $decaySeconds = $limits['per_seconds'] ?? 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw new \App\Exceptions\Integration\RateLimitException(
                "Rate limit exceeded. Retry in {$seconds} seconds.",
                $seconds
            );
        }

        RateLimiter::hit($key, $decaySeconds);
    }

    /**
     * Get remaining rate limit.
     */
    public function getRemainingRateLimit(string $connectorName, string $connectionId): array
    {
        $connector = $this->connectorRegistry->get($connectorName);

        if (!$connector) {
            return ['remaining' => -1, 'limit' => -1];
        }

        $limits = $connector->getRateLimits();
        $key = "integration:{$connectorName}:{$connectionId}";
        $maxAttempts = $limits['requests'] ?? 100;

        return [
            'remaining' => RateLimiter::remaining($key, $maxAttempts),
            'limit' => $maxAttempts,
            'reset_in' => RateLimiter::availableIn($key),
        ];
    }

    // =========================================================================
    // RETRY LOGIC
    // =========================================================================

    /**
     * Check if an exception is retryable.
     */
    protected function isRetryable(\Exception $e, array $config): bool
    {
        // Check specific exception types
        if ($e instanceof \App\Exceptions\Integration\RateLimitException) {
            return true;
        }

        if ($e instanceof \App\Exceptions\Integration\TemporaryException) {
            return true;
        }

        // Check HTTP status codes
        if ($e instanceof \App\Exceptions\Integration\ApiRequestException) {
            $code = $e->getCode();
            return in_array($code, $config['retryable_codes']);
        }

        return false;
    }

    /**
     * Calculate delay before retry.
     */
    protected function calculateDelay(int $attempt, array $config): int
    {
        $baseDelay = $config['base_delay'];
        $maxDelay = $config['max_delay'];

        $delay = match ($config['backoff']) {
            'fixed' => $baseDelay,
            'linear' => $baseDelay * $attempt,
            'exponential' => $baseDelay * pow(2, $attempt - 1),
            default => $baseDelay,
        };

        // Add jitter (±10%)
        $jitter = $delay * 0.1;
        $delay += random_int((int)-$jitter, (int)$jitter);

        return min($delay, $maxDelay);
    }

    // =========================================================================
    // EXECUTION TRACKING
    // =========================================================================

    /**
     * Create execution record.
     */
    protected function createExecution(
        string $connectorName,
        string $actionName,
        string $connectionId,
        array $input,
        array $context
    ): ActionExecution {
        return ActionExecution::create([
            'id' => Str::uuid()->toString(),
            'connector_name' => $connectorName,
            'action_name' => $actionName,
            'connection_id' => $connectionId,
            'input' => $this->sanitizeForStorage($input),
            'context' => $context,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark execution as successful.
     */
    protected function markSuccess(ActionExecution $execution, array $result, float $duration): void
    {
        $execution->update([
            'status' => 'success',
            'output' => $this->sanitizeForStorage($result),
            'duration_ms' => $duration,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark execution as failed.
     */
    protected function markFailed(ActionExecution $execution, \Exception $e): void
    {
        $execution->update([
            'status' => 'failed',
            'error' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'type' => get_class($e),
            ],
            'completed_at' => now(),
        ]);
    }

    /**
     * Sanitize data for storage (remove sensitive info).
     */
    protected function sanitizeForStorage(array $data): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'api_key', 'apikey', 'authorization'];

        return array_map(function ($value, $key) use ($sensitiveKeys) {
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys)) {
                return '[REDACTED]';
            }

            if (is_array($value)) {
                return $this->sanitizeForStorage($value);
            }

            return $value;
        }, $data, array_keys($data));
    }

    // =========================================================================
    // QUERYING
    // =========================================================================

    /**
     * Get execution history.
     */
    public function getExecutions(array $filters = []): Collection
    {
        $query = ActionExecution::query();

        if (isset($filters['connector'])) {
            $query->where('connector_name', $filters['connector']);
        }

        if (isset($filters['action'])) {
            $query->where('action_name', $filters['action']);
        }

        if (isset($filters['connection_id'])) {
            $query->where('connection_id', $filters['connection_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['since'])) {
            $query->where('created_at', '>=', $filters['since']);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 100)
            ->get();
    }

    /**
     * Get execution by ID.
     */
    public function getExecution(string $executionId): ?ActionExecution
    {
        return ActionExecution::find($executionId);
    }

    /**
     * Get execution statistics.
     */
    public function getStatistics(string $connectorName, ?string $actionName = null, ?string $period = null): array
    {
        $query = ActionExecution::where('connector_name', $connectorName);

        if ($actionName) {
            $query->where('action_name', $actionName);
        }

        if ($period) {
            $since = match ($period) {
                'hour' => now()->subHour(),
                'day' => now()->subDay(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                default => now()->subDay(),
            };
            $query->where('created_at', '>=', $since);
        }

        $total = $query->count();
        $successful = (clone $query)->where('status', 'success')->count();
        $failed = (clone $query)->where('status', 'failed')->count();
        $avgDuration = (clone $query)->where('status', 'success')->avg('duration_ms');

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'avg_duration_ms' => round($avgDuration ?? 0, 2),
        ];
    }

    // =========================================================================
    // TESTING
    // =========================================================================

    /**
     * Test an action with sample data.
     */
    public function test(
        string $connectorName,
        string $actionName,
        string $connectionId,
        array $input
    ): array {
        $action = $this->connectorRegistry->getAction($connectorName, $actionName);

        if (!$action) {
            throw new \InvalidArgumentException(
                "Action not found: {$connectorName}.{$actionName}"
            );
        }

        // Validate input first
        $validationErrors = $action->validateInput($input);
        if (!empty($validationErrors)) {
            return [
                'success' => false,
                'step' => 'validation',
                'errors' => $validationErrors,
            ];
        }

        // Try execution
        try {
            $result = $this->execute($connectorName, $actionName, $connectionId, $input, [
                'test' => true,
            ]);

            return [
                'success' => true,
                'data' => $result['data'],
                'sample_output' => $action->getSampleOutput(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'step' => 'execution',
                'error' => $e->getMessage(),
                'sample_output' => $action->getSampleOutput(),
            ];
        }
    }
}
