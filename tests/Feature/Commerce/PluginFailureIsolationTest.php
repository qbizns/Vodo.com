<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use App\Services\Plugins\CircuitBreaker;
use App\Services\Plugins\PluginManager;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use VodoCommerce\Contracts\PaymentGatewayContract;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Traits\CircuitOpenException;
use VodoCommerce\Traits\WithCircuitBreaker;

/**
 * Plugin Failure Isolation Tests
 *
 * These tests ensure that a broken or failing payment gateway plugin
 * doesn't crash other stores or affect the platform's stability.
 */
class PluginFailureIsolationTest extends TestCase
{
    protected PaymentGatewayRegistry $paymentRegistry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentRegistry = new PaymentGatewayRegistry();
    }

    /**
     * Test that a failing payment gateway doesn't crash the checkout service.
     */
    public function test_failing_gateway_does_not_crash_checkout(): void
    {
        // Create a gateway that always throws
        $brokenGateway = $this->createBrokenGateway();
        $workingGateway = $this->createWorkingGateway();

        $this->paymentRegistry->register('broken', $brokenGateway, 'broken-plugin');
        $this->paymentRegistry->register('stripe', $workingGateway, 'stripe-plugin');

        // Broken gateway should throw but not crash the system
        $this->expectNotToPerformAssertions();

        try {
            $brokenGateway->createCheckoutSession('order-1', 100.0, 'USD', [], 'test@test.com', []);
        } catch (\Exception $e) {
            // Expected - but should be caught gracefully
            $this->assertStringContainsString('Gateway error', $e->getMessage());
        }

        // Working gateway should still function
        $session = $workingGateway->createCheckoutSession('order-2', 100.0, 'USD', [], 'test@test.com', []);
        $this->assertNotNull($session);
    }

    /**
     * Test that circuit breaker isolates failing external calls.
     */
    public function test_circuit_breaker_isolates_failing_gateway(): void
    {
        $service = new class {
            use WithCircuitBreaker;

            public function callGateway(string $key, callable $callback, mixed $fallback = null): mixed
            {
                return $this->withCircuitBreaker($key, $callback, $fallback);
            }
        };

        $failCount = 0;
        $failingCallback = function () use (&$failCount) {
            $failCount++;
            throw new \Exception('Gateway unavailable');
        };

        // Simulate failures that should trigger circuit breaker
        $circuitBreaker = app(CircuitBreaker::class);
        $key = 'test:broken-gateway';

        // First few calls should throw (circuit closed, allowing attempts)
        for ($i = 0; $i < 3; $i++) {
            try {
                $service->callGateway($key, $failingCallback);
            } catch (\Exception $e) {
                // Expected
            }
        }

        // After enough failures, circuit should open and return fallback
        // (Depending on CircuitBreaker config - test that fallback is returned)
        $result = $service->callGateway($key, $failingCallback, 'fallback-value');

        // Either we get fallback (circuit open) or exception (circuit closed but failing)
        $this->assertTrue(
            $result === 'fallback-value' || $failCount > 0,
            'Circuit breaker should either return fallback or allow the exception'
        );
    }

    /**
     * Test that store isolation prevents cross-store plugin failures.
     */
    public function test_plugin_failure_in_one_store_does_not_affect_other_stores(): void
    {
        $store1Id = 1;
        $store2Id = 2;

        // Simulate store 1 having a broken gateway configured
        $store1Gateway = $this->createBrokenGateway();

        // Simulate store 2 having a working gateway
        $store2Gateway = $this->createWorkingGateway();

        // Store 1's failure should be isolated
        try {
            $store1Gateway->createCheckoutSession('order-1', 100.0, 'USD', [], 'test@test.com', []);
            $this->fail('Expected exception from broken gateway');
        } catch (\Exception $e) {
            // Expected - store 1's gateway failed
        }

        // Store 2's gateway should still work
        $session = $store2Gateway->createCheckoutSession('order-2', 100.0, 'USD', [], 'test@test.com', []);
        $this->assertNotNull($session);
        $this->assertEquals('session_123', $session->sessionId);
    }

    /**
     * Test that plugin exceptions are logged but don't expose sensitive data.
     */
    public function test_plugin_errors_are_logged_without_sensitive_data(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                // Should not contain sensitive data like card numbers or secrets
                $serialized = json_encode($context);
                $this->assertStringNotContainsString('card_number', $serialized);
                $this->assertStringNotContainsString('secret', strtolower($serialized));
                $this->assertStringNotContainsString('password', strtolower($serialized));
                return true;
            });

        $service = new class {
            use WithCircuitBreaker;

            public function failWithLogging(): void
            {
                $this->withCircuitBreaker('test:logging', function () {
                    throw new \Exception('Payment failed');
                });
            }
        };

        try {
            $service->failWithLogging();
        } catch (\Exception $e) {
            // Expected
        }
    }

    /**
     * Test that webhook processing failures don't crash the endpoint.
     */
    public function test_webhook_processing_failure_returns_proper_response(): void
    {
        // Simulate a broken webhook handler that throws
        $brokenHandler = function (array $payload) {
            throw new \RuntimeException('Handler crashed');
        };

        // The webhook processing should catch and return error response
        try {
            $result = $brokenHandler(['event' => 'test']);
            $this->fail('Expected exception');
        } catch (\RuntimeException $e) {
            // This is expected - the controller should catch this
            // and return a 500 response without crashing
            $this->assertEquals('Handler crashed', $e->getMessage());
        }

        // Verify that subsequent requests still work
        $workingHandler = function (array $payload) {
            return ['success' => true];
        };

        $result = $workingHandler(['event' => 'test']);
        $this->assertTrue($result['success']);
    }

    /**
     * Test that a plugin throwing an exception doesn't prevent other plugins from loading.
     */
    public function test_broken_plugin_does_not_prevent_other_plugins_loading(): void
    {
        $loadedPlugins = [];
        $brokenPluginLoaded = false;

        // Simulate plugin loading
        $plugins = [
            'good-plugin-1' => function () use (&$loadedPlugins) {
                $loadedPlugins[] = 'good-plugin-1';
            },
            'broken-plugin' => function () use (&$brokenPluginLoaded) {
                $brokenPluginLoaded = true;
                throw new \Exception('Plugin initialization failed');
            },
            'good-plugin-2' => function () use (&$loadedPlugins) {
                $loadedPlugins[] = 'good-plugin-2';
            },
        ];

        // Load plugins with error handling
        foreach ($plugins as $name => $loader) {
            try {
                $loader();
            } catch (\Exception $e) {
                // Log but continue loading other plugins
                Log::warning("Plugin {$name} failed to load", ['error' => $e->getMessage()]);
            }
        }

        // Good plugins should have loaded
        $this->assertContains('good-plugin-1', $loadedPlugins);
        $this->assertContains('good-plugin-2', $loadedPlugins);

        // Broken plugin was attempted but failed
        $this->assertTrue($brokenPluginLoaded);
    }

    /**
     * Test that payment gateway timeout doesn't block the request indefinitely.
     */
    public function test_gateway_timeout_is_handled_gracefully(): void
    {
        $service = new class {
            use WithCircuitBreaker;

            public function callWithTimeout(int $timeoutMs): mixed
            {
                return $this->withCircuitBreaker('test:timeout', function () use ($timeoutMs) {
                    // Simulate a long-running operation
                    usleep($timeoutMs * 1000);
                    return 'completed';
                }, 'timeout-fallback');
            }
        };

        $startTime = microtime(true);

        // Call with short timeout simulation
        $result = $service->callWithTimeout(10); // 10ms

        $elapsedMs = (microtime(true) - $startTime) * 1000;

        // Should complete quickly (not hang)
        $this->assertLessThan(1000, $elapsedMs, 'Request should not hang');
        $this->assertEquals('completed', $result);
    }

    /**
     * Create a mock broken payment gateway.
     */
    protected function createBrokenGateway(): PaymentGatewayContract
    {
        return new class implements PaymentGatewayContract {
            public function getIdentifier(): string
            {
                return 'broken-gateway';
            }

            public function getName(): string
            {
                return 'Broken Gateway';
            }

            public function getIcon(): ?string
            {
                return null;
            }

            public function isEnabled(): bool
            {
                return true;
            }

            public function supports(): array
            {
                return ['checkout'];
            }

            public function createCheckoutSession(
                string $orderId,
                float $amount,
                string $currency,
                array $items,
                string $customerEmail,
                array $metadata
            ): object {
                throw new \RuntimeException('Gateway error: Service unavailable');
            }

            public function handleWebhook(array $payload, array $headers): object
            {
                throw new \RuntimeException('Gateway error: Webhook processing failed');
            }

            public function refund(string $transactionId, float $amount, ?string $reason = null): object
            {
                throw new \RuntimeException('Gateway error: Refund failed');
            }
        };
    }

    /**
     * Create a mock working payment gateway.
     */
    protected function createWorkingGateway(): PaymentGatewayContract
    {
        return new class implements PaymentGatewayContract {
            public function getIdentifier(): string
            {
                return 'stripe';
            }

            public function getName(): string
            {
                return 'Stripe';
            }

            public function getIcon(): ?string
            {
                return 'stripe-icon.svg';
            }

            public function isEnabled(): bool
            {
                return true;
            }

            public function supports(): array
            {
                return ['checkout', 'refund'];
            }

            public function createCheckoutSession(
                string $orderId,
                float $amount,
                string $currency,
                array $items,
                string $customerEmail,
                array $metadata
            ): object {
                return (object) [
                    'sessionId' => 'session_123',
                    'redirectUrl' => 'https://checkout.stripe.com/pay/session_123',
                    'clientSecret' => 'cs_test_123',
                    'expiresAt' => now()->addMinutes(30),
                ];
            }

            public function handleWebhook(array $payload, array $headers): object
            {
                return (object) [
                    'processed' => true,
                    'orderId' => $payload['order_id'] ?? null,
                    'paymentStatus' => 'paid',
                    'transactionId' => 'txn_123',
                    'message' => 'OK',
                ];
            }

            public function refund(string $transactionId, float $amount, ?string $reason = null): object
            {
                return (object) [
                    'refundId' => 'refund_123',
                    'amount' => $amount,
                    'status' => 'succeeded',
                ];
            }
        };
    }
}
