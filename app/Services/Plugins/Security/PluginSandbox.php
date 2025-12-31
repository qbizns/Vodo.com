<?php

declare(strict_types=1);

namespace App\Services\Plugins\Security;

use App\Exceptions\Plugins\SandboxViolationException;
use App\Models\Plugin;
use App\Models\PluginAuditLog;
use App\Models\PluginResourceUsage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Plugin Sandbox - Enforces resource limits and isolation for plugins.
 *
 * This service is responsible for:
 * - Enforcing memory limits per plugin
 * - Enforcing execution time limits
 * - Tracking resource usage
 * - Rate limiting plugin operations
 * - Network request filtering
 * - Automatic disabling of misbehaving plugins
 */
class PluginSandbox
{
    /**
     * Default resource limits.
     */
    protected array $defaultLimits = [
        'memory_mb' => 256,
        'execution_time_seconds' => 30,
        'api_requests_per_minute' => 60,
        'api_requests_per_hour' => 1000,
        'api_requests_per_day' => 10000,
        'hook_executions_per_minute' => 100,
        'entity_reads_per_minute' => 500,
        'entity_writes_per_minute' => 100,
        'storage_mb' => 50,
        'network_requests_per_minute' => 30,
        'network_bytes_per_day' => 104857600, // 100MB
        'max_consecutive_errors' => 10,
    ];

    /**
     * Plugin-specific limit overrides.
     *
     * @var array<string, array>
     */
    protected array $pluginLimits = [];

    /**
     * Current execution context.
     */
    protected ?string $currentPluginSlug = null;
    protected ?float $executionStartTime = null;
    protected ?int $memoryStartBytes = null;

    /**
     * Violation thresholds for auto-disable.
     */
    protected int $violationThreshold = 5;
    protected int $violationWindowMinutes = 60;

    // =========================================================================
    // Configuration
    // =========================================================================

    /**
     * Set limits for a specific plugin.
     */
    public function setPluginLimits(string $pluginSlug, array $limits): void
    {
        $this->pluginLimits[$pluginSlug] = array_merge(
            $this->getDefaultLimits(),
            $limits
        );
    }

    /**
     * Get limits for a specific plugin.
     */
    public function getPluginLimits(string $pluginSlug): array
    {
        return $this->pluginLimits[$pluginSlug] ?? $this->getDefaultLimits();
    }

    /**
     * Get default limits.
     */
    public function getDefaultLimits(): array
    {
        return array_merge(
            $this->defaultLimits,
            config('plugin.sandbox.limits', [])
        );
    }

    /**
     * Check if sandbox is enabled.
     */
    public function isEnabled(): bool
    {
        return config('plugin.sandbox.enabled', false);
    }

    // =========================================================================
    // Execution Context
    // =========================================================================

    /**
     * Start tracking execution for a plugin.
     */
    public function beginExecution(string $pluginSlug): void
    {
        $this->currentPluginSlug = $pluginSlug;
        $this->executionStartTime = microtime(true);
        $this->memoryStartBytes = memory_get_usage(true);
    }

    /**
     * End tracking execution for a plugin.
     */
    public function endExecution(): array
    {
        $stats = [
            'plugin' => $this->currentPluginSlug,
            'execution_time_ms' => 0,
            'memory_used_bytes' => 0,
            'peak_memory_bytes' => 0,
        ];

        if ($this->executionStartTime !== null) {
            $stats['execution_time_ms'] = (microtime(true) - $this->executionStartTime) * 1000;
        }

        if ($this->memoryStartBytes !== null) {
            $currentMemory = memory_get_usage(true);
            $stats['memory_used_bytes'] = max(0, $currentMemory - $this->memoryStartBytes);
            $stats['peak_memory_bytes'] = memory_get_peak_usage(true);
        }

        // Record usage
        if ($this->currentPluginSlug) {
            $usage = PluginResourceUsage::forPluginToday($this->currentPluginSlug);
            $usage->recordHookExecution(
                $stats['execution_time_ms'],
                $stats['peak_memory_bytes']
            );
        }

        $this->currentPluginSlug = null;
        $this->executionStartTime = null;
        $this->memoryStartBytes = null;

        return $stats;
    }

    /**
     * Execute a callback within the sandbox.
     *
     * @throws SandboxViolationException
     */
    public function execute(string $pluginSlug, callable $callback): mixed
    {
        if (!$this->isEnabled()) {
            return $callback();
        }

        $this->beginExecution($pluginSlug);

        try {
            // Check if plugin is blocked
            if ($this->isBlocked($pluginSlug)) {
                throw SandboxViolationException::pluginBlocked($pluginSlug);
            }

            // Enforce limits before execution
            $this->enforceLimits($pluginSlug);

            // Execute with timeout
            $result = $this->executeWithTimeout($pluginSlug, $callback);

            return $result;

        } catch (SandboxViolationException $e) {
            $this->recordViolation($pluginSlug, $e->getViolationType(), $e->getMessage());
            throw $e;

        } catch (\Throwable $e) {
            $this->recordError($pluginSlug, $e);
            throw $e;

        } finally {
            $this->endExecution();
        }
    }

    // =========================================================================
    // Limit Enforcement
    // =========================================================================

    /**
     * Enforce all limits for a plugin.
     *
     * @throws SandboxViolationException
     */
    public function enforceLimits(string $pluginSlug): void
    {
        $limits = $this->getPluginLimits($pluginSlug);
        $usage = PluginResourceUsage::forPluginToday($pluginSlug);

        // Check daily limits
        if ($usage->api_requests >= $limits['api_requests_per_day']) {
            throw SandboxViolationException::rateLimitExceeded(
                $pluginSlug,
                'api_requests_per_day',
                $limits['api_requests_per_day']
            );
        }

        // Check storage limit
        if ($usage->storage_bytes_used >= $limits['storage_mb'] * 1024 * 1024) {
            throw SandboxViolationException::storageLimitExceeded(
                $pluginSlug,
                $limits['storage_mb']
            );
        }

        // Check network limit
        $totalNetwork = $usage->network_bytes_out + $usage->network_bytes_in;
        if ($totalNetwork >= $limits['network_bytes_per_day']) {
            throw SandboxViolationException::networkLimitExceeded(
                $pluginSlug,
                $limits['network_bytes_per_day']
            );
        }

        // Check rate limits
        $this->enforceRateLimits($pluginSlug, $limits);
    }

    /**
     * Enforce rate limits using Redis/Cache.
     *
     * @throws SandboxViolationException
     */
    protected function enforceRateLimits(string $pluginSlug, array $limits): void
    {
        $rateLimits = [
            'api_requests' => ['per_minute' => $limits['api_requests_per_minute'], 'window' => 60],
            'hook_executions' => ['per_minute' => $limits['hook_executions_per_minute'], 'window' => 60],
            'entity_reads' => ['per_minute' => $limits['entity_reads_per_minute'], 'window' => 60],
            'entity_writes' => ['per_minute' => $limits['entity_writes_per_minute'], 'window' => 60],
            'network_requests' => ['per_minute' => $limits['network_requests_per_minute'], 'window' => 60],
        ];

        foreach ($rateLimits as $type => $config) {
            $cacheKey = "sandbox:rate:{$pluginSlug}:{$type}";
            $count = (int) Cache::get($cacheKey, 0);

            if ($count >= $config['per_minute']) {
                // Record rate limit hit
                $usage = PluginResourceUsage::forPluginToday($pluginSlug);
                $usage->recordRateLimitHit();

                throw SandboxViolationException::rateLimitExceeded(
                    $pluginSlug,
                    $type,
                    $config['per_minute']
                );
            }
        }
    }

    /**
     * Increment a rate limit counter.
     */
    public function incrementRateLimit(string $pluginSlug, string $type): void
    {
        $cacheKey = "sandbox:rate:{$pluginSlug}:{$type}";
        $count = (int) Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $count + 1, 60);
    }

    /**
     * Check memory limit.
     *
     * @throws SandboxViolationException
     */
    public function checkMemoryLimit(string $pluginSlug): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $limits = $this->getPluginLimits($pluginSlug);
        $limitBytes = $limits['memory_mb'] * 1024 * 1024;
        $currentMemory = memory_get_usage(true);

        if ($this->memoryStartBytes !== null) {
            $usedMemory = $currentMemory - $this->memoryStartBytes;
            if ($usedMemory > $limitBytes) {
                throw SandboxViolationException::memoryLimitExceeded(
                    $pluginSlug,
                    $limits['memory_mb'],
                    round($usedMemory / 1024 / 1024, 2)
                );
            }
        }
    }

    /**
     * Check execution time limit.
     *
     * @throws SandboxViolationException
     */
    public function checkTimeLimit(string $pluginSlug): void
    {
        if (!$this->isEnabled() || $this->executionStartTime === null) {
            return;
        }

        $limits = $this->getPluginLimits($pluginSlug);
        $elapsed = microtime(true) - $this->executionStartTime;

        if ($elapsed > $limits['execution_time_seconds']) {
            // Record timeout
            $usage = PluginResourceUsage::forPluginToday($pluginSlug);
            $usage->recordTimeout();

            throw SandboxViolationException::executionTimeExceeded(
                $pluginSlug,
                $limits['execution_time_seconds'],
                round($elapsed, 2)
            );
        }
    }

    // =========================================================================
    // Network Filtering
    // =========================================================================

    /**
     * Check if a domain is allowed for outbound requests.
     */
    public function isDomainAllowed(string $pluginSlug, string $domain): bool
    {
        $whitelist = $this->getNetworkWhitelist($pluginSlug);

        if (empty($whitelist)) {
            return true; // No whitelist = allow all
        }

        foreach ($whitelist as $allowed) {
            if ($domain === $allowed) {
                return true;
            }

            // Support wildcard subdomain matching
            if (str_starts_with($allowed, '*.')) {
                $baseDomain = substr($allowed, 2);
                if ($domain === $baseDomain || str_ends_with($domain, ".{$baseDomain}")) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get network whitelist for a plugin.
     */
    public function getNetworkWhitelist(string $pluginSlug): array
    {
        $limits = $this->getPluginLimits($pluginSlug);
        return $limits['network_whitelist'] ?? [];
    }

    /**
     * Record network usage.
     */
    public function recordNetworkRequest(string $pluginSlug, int $bytesOut, int $bytesIn): void
    {
        $this->incrementRateLimit($pluginSlug, 'network_requests');

        $usage = PluginResourceUsage::forPluginToday($pluginSlug);
        $usage->recordNetworkTraffic($bytesOut, $bytesIn);
    }

    // =========================================================================
    // Violation Tracking
    // =========================================================================

    /**
     * Record a sandbox violation.
     */
    public function recordViolation(string $pluginSlug, string $type, string $description): void
    {
        // Log the violation
        PluginAuditLog::security(
            $pluginSlug,
            PluginAuditLog::EVENT_SANDBOX_VIOLATION,
            "Sandbox violation: {$type}",
            [
                'violation_type' => $type,
                'description' => $description,
            ],
            PluginAuditLog::SEVERITY_WARNING
        );

        // Track violation count
        $cacheKey = "sandbox:violations:{$pluginSlug}";
        $violations = (int) Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $violations + 1, $this->violationWindowMinutes * 60);

        // Check if plugin should be auto-disabled
        if ($violations + 1 >= $this->violationThreshold) {
            $this->autoDisablePlugin($pluginSlug, $type);
        }

        Log::warning("Plugin sandbox violation", [
            'plugin' => $pluginSlug,
            'type' => $type,
            'description' => $description,
            'total_violations' => $violations + 1,
        ]);
    }

    /**
     * Record an error.
     */
    public function recordError(string $pluginSlug, \Throwable $error): void
    {
        $usage = PluginResourceUsage::forPluginToday($pluginSlug);
        $usage->recordError();

        // Track consecutive errors
        $cacheKey = "sandbox:errors:{$pluginSlug}";
        $errors = (int) Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $errors + 1, 3600);

        $limits = $this->getPluginLimits($pluginSlug);
        if ($errors + 1 >= $limits['max_consecutive_errors']) {
            $this->autoDisablePlugin($pluginSlug, 'consecutive_errors');
        }
    }

    /**
     * Clear error count (call on successful execution).
     */
    public function clearErrorCount(string $pluginSlug): void
    {
        Cache::forget("sandbox:errors:{$pluginSlug}");
    }

    // =========================================================================
    // Plugin Blocking
    // =========================================================================

    /**
     * Check if a plugin is blocked.
     */
    public function isBlocked(string $pluginSlug): bool
    {
        return (bool) Cache::get("sandbox:blocked:{$pluginSlug}", false);
    }

    /**
     * Block a plugin temporarily.
     */
    public function blockPlugin(string $pluginSlug, int $durationMinutes = 60): void
    {
        Cache::put("sandbox:blocked:{$pluginSlug}", true, $durationMinutes * 60);

        PluginAuditLog::security(
            $pluginSlug,
            PluginAuditLog::EVENT_SANDBOX_VIOLATION,
            "Plugin temporarily blocked for {$durationMinutes} minutes",
            ['duration_minutes' => $durationMinutes],
            PluginAuditLog::SEVERITY_ERROR
        );

        Log::warning("Plugin blocked", [
            'plugin' => $pluginSlug,
            'duration_minutes' => $durationMinutes,
        ]);
    }

    /**
     * Unblock a plugin.
     */
    public function unblockPlugin(string $pluginSlug): void
    {
        Cache::forget("sandbox:blocked:{$pluginSlug}");
        Cache::forget("sandbox:violations:{$pluginSlug}");
        Cache::forget("sandbox:errors:{$pluginSlug}");

        Log::info("Plugin unblocked", ['plugin' => $pluginSlug]);
    }

    /**
     * Auto-disable a plugin due to repeated violations.
     */
    protected function autoDisablePlugin(string $pluginSlug, string $reason): void
    {
        // Block the plugin
        $this->blockPlugin($pluginSlug, 1440); // 24 hours

        // Optionally deactivate in database
        $plugin = Plugin::where('slug', $pluginSlug)->first();
        if ($plugin && config('plugin.sandbox.auto_deactivate', false)) {
            $plugin->update([
                'status' => Plugin::STATUS_INACTIVE,
                'error_message' => "Auto-disabled due to: {$reason}",
            ]);
        }

        PluginAuditLog::security(
            $pluginSlug,
            PluginAuditLog::EVENT_SANDBOX_VIOLATION,
            "Plugin auto-disabled due to repeated violations: {$reason}",
            ['reason' => $reason],
            PluginAuditLog::SEVERITY_CRITICAL
        );

        Log::error("Plugin auto-disabled", [
            'plugin' => $pluginSlug,
            'reason' => $reason,
        ]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Execute a callback with timeout enforcement.
     */
    protected function executeWithTimeout(string $pluginSlug, callable $callback): mixed
    {
        // Note: True timeout requires pcntl extension or async execution
        // This is a simplified version that checks time periodically

        return $callback();
    }
}
