<?php

declare(strict_types=1);

namespace App\Services\Plugins;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CircuitBreaker - Prevents cascading failures from misbehaving hooks.
 *
 * Phase 2, Task 2.2: Hook Circuit Breaker
 *
 * The circuit breaker pattern automatically disables failing hooks
 * after a threshold of failures, preventing one bad plugin from
 * affecting the entire application.
 *
 * States:
 * - CLOSED: Normal operation, hook executes
 * - OPEN: Hook is disabled due to failures
 * - HALF_OPEN: Testing if hook has recovered
 *
 * Usage:
 *   $breaker = app(CircuitBreaker::class);
 *
 *   if ($breaker->isOpen($hookKey)) {
 *       return; // Skip execution
 *   }
 *
 *   try {
 *       $callback();
 *       $breaker->recordSuccess($hookKey);
 *   } catch (\Throwable $e) {
 *       $breaker->recordFailure($hookKey, $e);
 *   }
 */
class CircuitBreaker
{
    /**
     * Circuit states.
     */
    public const STATE_CLOSED = 'closed';
    public const STATE_OPEN = 'open';
    public const STATE_HALF_OPEN = 'half_open';

    /**
     * Cache key prefix.
     */
    protected const CACHE_PREFIX = 'circuit_breaker:';

    /**
     * Default failure threshold before opening circuit.
     */
    protected int $failureThreshold;

    /**
     * Recovery timeout in seconds.
     */
    protected int $recoveryTimeout;

    /**
     * Success threshold to close circuit from half-open.
     */
    protected int $successThreshold;

    /**
     * Failure window in seconds.
     */
    protected int $failureWindow;

    /**
     * Create a new circuit breaker instance.
     */
    public function __construct()
    {
        $this->failureThreshold = config('platform.circuit_breaker.failure_threshold', 5);
        $this->recoveryTimeout = config('platform.circuit_breaker.recovery_timeout', 300);
        $this->successThreshold = config('platform.circuit_breaker.success_threshold', 2);
        $this->failureWindow = config('platform.circuit_breaker.failure_window', 60);
    }

    /**
     * Check if the circuit is open (hook should be skipped).
     */
    public function isOpen(string $key): bool
    {
        $state = $this->getState($key);

        if ($state === self::STATE_CLOSED) {
            return false;
        }

        if ($state === self::STATE_OPEN) {
            // Check if recovery timeout has passed
            if ($this->canAttemptRecovery($key)) {
                $this->transitionToHalfOpen($key);
                return false; // Allow one attempt
            }
            return true;
        }

        // HALF_OPEN: Allow the attempt
        return false;
    }

    /**
     * Record a successful execution.
     */
    public function recordSuccess(string $key): void
    {
        $state = $this->getState($key);

        if ($state === self::STATE_HALF_OPEN) {
            $successCount = $this->incrementSuccessCount($key);

            if ($successCount >= $this->successThreshold) {
                $this->transitionToClosed($key);
                Log::info("CircuitBreaker: Circuit closed after recovery", [
                    'key' => $key,
                    'success_count' => $successCount,
                ]);
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset failure count on success
            $this->resetFailures($key);
        }
    }

    /**
     * Record a failed execution.
     */
    public function recordFailure(string $key, ?\Throwable $exception = null): void
    {
        $state = $this->getState($key);
        $failureCount = $this->incrementFailureCount($key);

        $context = [
            'key' => $key,
            'failure_count' => $failureCount,
            'threshold' => $this->failureThreshold,
            'state' => $state,
        ];

        if ($exception) {
            $context['exception'] = $exception->getMessage();
            $context['exception_class'] = get_class($exception);
        }

        Log::warning("CircuitBreaker: Failure recorded", $context);

        if ($state === self::STATE_HALF_OPEN) {
            // Any failure in half-open goes back to open
            $this->transitionToOpen($key);
            Log::warning("CircuitBreaker: Circuit reopened after half-open failure", [
                'key' => $key,
            ]);
        } elseif ($state === self::STATE_CLOSED && $failureCount >= $this->failureThreshold) {
            $this->transitionToOpen($key);
            Log::error("CircuitBreaker: Circuit opened due to failures", [
                'key' => $key,
                'failure_count' => $failureCount,
                'threshold' => $this->failureThreshold,
            ]);
        }
    }

    /**
     * Get the current state of a circuit.
     */
    public function getState(string $key): string
    {
        return Cache::get($this->stateKey($key), self::STATE_CLOSED);
    }

    /**
     * Force a circuit to close (reset).
     */
    public function reset(string $key): void
    {
        Cache::forget($this->stateKey($key));
        Cache::forget($this->failureKey($key));
        Cache::forget($this->successKey($key));
        Cache::forget($this->lastFailureKey($key));
        Cache::forget($this->openedAtKey($key));

        Log::info("CircuitBreaker: Circuit reset", ['key' => $key]);
    }

    /**
     * Force a circuit to open (disable hook).
     */
    public function forceOpen(string $key, ?string $reason = null): void
    {
        $this->transitionToOpen($key);

        Log::warning("CircuitBreaker: Circuit force opened", [
            'key' => $key,
            'reason' => $reason,
        ]);
    }

    /**
     * Get metrics for a circuit.
     */
    public function getMetrics(string $key): array
    {
        return [
            'key' => $key,
            'state' => $this->getState($key),
            'failure_count' => (int) Cache::get($this->failureKey($key), 0),
            'success_count' => (int) Cache::get($this->successKey($key), 0),
            'last_failure_at' => Cache::get($this->lastFailureKey($key)),
            'opened_at' => Cache::get($this->openedAtKey($key)),
            'failure_threshold' => $this->failureThreshold,
            'recovery_timeout' => $this->recoveryTimeout,
        ];
    }

    /**
     * Get all open circuits.
     *
     * @return array<string, array>
     */
    public function getOpenCircuits(): array
    {
        // This requires tracking all keys, which we do via a separate cache key
        $trackedKeys = Cache::get(self::CACHE_PREFIX . 'tracked_keys', []);
        $openCircuits = [];

        foreach ($trackedKeys as $key) {
            if ($this->getState($key) === self::STATE_OPEN) {
                $openCircuits[$key] = $this->getMetrics($key);
            }
        }

        return $openCircuits;
    }

    /**
     * Check if enough time has passed to attempt recovery.
     */
    protected function canAttemptRecovery(string $key): bool
    {
        $openedAt = Cache::get($this->openedAtKey($key));

        if (!$openedAt) {
            return true;
        }

        return now()->diffInSeconds($openedAt) >= $this->recoveryTimeout;
    }

    /**
     * Transition to closed state.
     */
    protected function transitionToClosed(string $key): void
    {
        Cache::put($this->stateKey($key), self::STATE_CLOSED, 86400);
        Cache::forget($this->failureKey($key));
        Cache::forget($this->successKey($key));
        Cache::forget($this->openedAtKey($key));
    }

    /**
     * Transition to open state.
     */
    protected function transitionToOpen(string $key): void
    {
        Cache::put($this->stateKey($key), self::STATE_OPEN, 86400);
        Cache::put($this->openedAtKey($key), now(), 86400);
        Cache::forget($this->successKey($key));

        $this->trackKey($key);
    }

    /**
     * Transition to half-open state.
     */
    protected function transitionToHalfOpen(string $key): void
    {
        Cache::put($this->stateKey($key), self::STATE_HALF_OPEN, 86400);
        Cache::forget($this->successKey($key));
    }

    /**
     * Increment failure count.
     */
    protected function incrementFailureCount(string $key): int
    {
        $failureKey = $this->failureKey($key);
        $count = (int) Cache::get($failureKey, 0) + 1;

        Cache::put($failureKey, $count, $this->failureWindow);
        Cache::put($this->lastFailureKey($key), now(), 86400);

        $this->trackKey($key);

        return $count;
    }

    /**
     * Increment success count (for half-open state).
     */
    protected function incrementSuccessCount(string $key): int
    {
        $successKey = $this->successKey($key);
        $count = (int) Cache::get($successKey, 0) + 1;

        Cache::put($successKey, $count, $this->recoveryTimeout);

        return $count;
    }

    /**
     * Reset failure count.
     */
    protected function resetFailures(string $key): void
    {
        Cache::forget($this->failureKey($key));
    }

    /**
     * Track a key for reporting.
     */
    protected function trackKey(string $key): void
    {
        $trackedKey = self::CACHE_PREFIX . 'tracked_keys';
        $tracked = Cache::get($trackedKey, []);

        if (!in_array($key, $tracked)) {
            $tracked[] = $key;
            Cache::put($trackedKey, $tracked, 86400);
        }
    }

    // Cache key helpers
    protected function stateKey(string $key): string
    {
        return self::CACHE_PREFIX . "state:{$key}";
    }

    protected function failureKey(string $key): string
    {
        return self::CACHE_PREFIX . "failures:{$key}";
    }

    protected function successKey(string $key): string
    {
        return self::CACHE_PREFIX . "successes:{$key}";
    }

    protected function lastFailureKey(string $key): string
    {
        return self::CACHE_PREFIX . "last_failure:{$key}";
    }

    protected function openedAtKey(string $key): string
    {
        return self::CACHE_PREFIX . "opened_at:{$key}";
    }

    /**
     * Create a hook key from hook name and plugin.
     */
    public static function hookKey(string $hook, ?string $plugin = null): string
    {
        if ($plugin) {
            return "{$plugin}:{$hook}";
        }
        return "core:{$hook}";
    }
}
