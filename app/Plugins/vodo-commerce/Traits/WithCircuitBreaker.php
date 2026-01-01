<?php

declare(strict_types=1);

namespace VodoCommerce\Traits;

use App\Services\Plugins\CircuitBreaker;
use Illuminate\Support\Facades\Log;

/**
 * WithCircuitBreaker - Wraps external service calls with circuit breaker protection.
 *
 * Prevents cascading failures when external services (payment gateways,
 * shipping carriers, tax providers) are unavailable or misbehaving.
 *
 * Usage:
 * class CheckoutService {
 *     use WithCircuitBreaker;
 *
 *     public function getShippingRates() {
 *         return $this->withCircuitBreaker(
 *             'shipping:carrier_name',
 *             fn() => $carrier->getRates($address),
 *             [] // fallback value on circuit open
 *         );
 *     }
 * }
 */
trait WithCircuitBreaker
{
    /**
     * Circuit breaker instance.
     */
    protected ?CircuitBreaker $circuitBreaker = null;

    /**
     * Get the circuit breaker instance.
     */
    protected function getCircuitBreaker(): CircuitBreaker
    {
        if ($this->circuitBreaker === null) {
            $this->circuitBreaker = app()->bound(CircuitBreaker::class)
                ? app(CircuitBreaker::class)
                : new CircuitBreaker();
        }

        return $this->circuitBreaker;
    }

    /**
     * Execute a callback with circuit breaker protection.
     *
     * @template T
     * @param string $key Unique identifier for the circuit (e.g., 'payment:stripe')
     * @param callable(): T $callback The operation to execute
     * @param T $fallback Value to return if circuit is open
     * @param bool $throwOnOpen Whether to throw exception when circuit is open
     * @return T
     * @throws CircuitOpenException If $throwOnOpen is true and circuit is open
     */
    protected function withCircuitBreaker(
        string $key,
        callable $callback,
        mixed $fallback = null,
        bool $throwOnOpen = false
    ): mixed {
        $breaker = $this->getCircuitBreaker();

        // Check if circuit is open
        if ($breaker->isOpen($key)) {
            Log::warning('Circuit breaker open, skipping external call', [
                'key' => $key,
            ]);

            if ($throwOnOpen) {
                throw new CircuitOpenException(
                    "Circuit breaker is open for: {$key}. Service temporarily unavailable."
                );
            }

            return $fallback;
        }

        try {
            $result = $callback();
            $breaker->recordSuccess($key);
            return $result;
        } catch (\Throwable $e) {
            $breaker->recordFailure($key, $e);

            Log::warning('External call failed, circuit breaker recorded failure', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Execute multiple callbacks with circuit breaker, collecting successful results.
     *
     * Useful for fetching rates from multiple carriers where some may fail.
     *
     * @template T
     * @param array<string, callable(): T> $operations Array of key => callback
     * @return array<string, T> Results from successful operations
     */
    protected function withCircuitBreakerCollect(array $operations): array
    {
        $results = [];

        foreach ($operations as $key => $callback) {
            try {
                $results[$key] = $this->withCircuitBreaker($key, $callback);
            } catch (\Throwable $e) {
                // Log but continue with other operations
                Log::debug('Operation skipped due to failure', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get the circuit breaker key for an external service.
     */
    protected function getCircuitKey(string $service, string $provider): string
    {
        return "commerce:{$service}:{$provider}";
    }
}

/**
 * Exception thrown when a circuit breaker is open.
 */
class CircuitOpenException extends \RuntimeException
{
    public function __construct(string $message = 'Circuit breaker is open', int $code = 503, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
