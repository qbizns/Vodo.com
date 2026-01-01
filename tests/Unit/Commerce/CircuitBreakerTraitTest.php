<?php

declare(strict_types=1);

namespace Tests\Unit\Commerce;

use App\Services\Plugins\CircuitBreaker;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use VodoCommerce\Traits\CircuitOpenException;
use VodoCommerce\Traits\WithCircuitBreaker;

class CircuitBreakerTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock CircuitBreaker for tests
        $this->mockCircuitBreaker = $this->createMock(CircuitBreaker::class);
        app()->instance(CircuitBreaker::class, $this->mockCircuitBreaker);
    }

    public function test_executes_callback_when_circuit_closed(): void
    {
        $this->mockCircuitBreaker->method('isOpen')->willReturn(false);
        $this->mockCircuitBreaker->expects($this->once())->method('recordSuccess');

        $service = new class {
            use WithCircuitBreaker;

            public function call(string $key, callable $callback): mixed
            {
                return $this->withCircuitBreaker($key, $callback);
            }
        };

        $result = $service->call('test:service', fn() => 'success');

        $this->assertEquals('success', $result);
    }

    public function test_returns_fallback_when_circuit_open(): void
    {
        $this->mockCircuitBreaker->method('isOpen')->willReturn(true);

        $service = new class {
            use WithCircuitBreaker;

            public function call(string $key, callable $callback, mixed $fallback = null): mixed
            {
                return $this->withCircuitBreaker($key, $callback, $fallback);
            }
        };

        $result = $service->call('test:service', fn() => 'success', 'fallback-value');

        $this->assertEquals('fallback-value', $result);
    }

    public function test_throws_exception_when_circuit_open_and_throw_enabled(): void
    {
        $this->mockCircuitBreaker->method('isOpen')->willReturn(true);

        $service = new class {
            use WithCircuitBreaker;

            public function call(string $key, callable $callback): mixed
            {
                return $this->withCircuitBreaker($key, $callback, null, true);
            }
        };

        $this->expectException(CircuitOpenException::class);
        $this->expectExceptionMessage('Circuit breaker is open for: payment:stripe');

        $service->call('payment:stripe', fn() => 'success');
    }

    public function test_records_failure_on_exception(): void
    {
        $this->mockCircuitBreaker->method('isOpen')->willReturn(false);
        $this->mockCircuitBreaker->expects($this->once())->method('recordFailure');

        $service = new class {
            use WithCircuitBreaker;

            public function call(string $key, callable $callback): mixed
            {
                return $this->withCircuitBreaker($key, $callback);
            }
        };

        $this->expectException(\RuntimeException::class);

        $service->call('test:failing', function () {
            throw new \RuntimeException('Service unavailable');
        });
    }

    public function test_generates_correct_circuit_key(): void
    {
        $service = new class {
            use WithCircuitBreaker;

            public function getKey(string $service, string $provider): string
            {
                return $this->getCircuitKey($service, $provider);
            }
        };

        $key = $service->getKey('payment', 'stripe');

        $this->assertEquals('commerce:payment:stripe', $key);
    }

    public function test_with_circuit_breaker_collect_handles_multiple_operations(): void
    {
        $this->mockCircuitBreaker->method('isOpen')->willReturn(false);
        $this->mockCircuitBreaker->method('recordSuccess');

        $service = new class {
            use WithCircuitBreaker;

            public function collect(array $operations): array
            {
                return $this->withCircuitBreakerCollect($operations);
            }
        };

        $results = $service->collect([
            'carrier1' => fn() => ['rate' => 10.00],
            'carrier2' => fn() => ['rate' => 15.00],
            'carrier3' => fn() => ['rate' => 12.50],
        ]);

        $this->assertCount(3, $results);
        $this->assertEquals(['rate' => 10.00], $results['carrier1']);
        $this->assertEquals(['rate' => 15.00], $results['carrier2']);
        $this->assertEquals(['rate' => 12.50], $results['carrier3']);
    }

    public function test_with_circuit_breaker_collect_skips_failed_operations(): void
    {
        $callCount = 0;
        $this->mockCircuitBreaker->method('isOpen')->willReturnCallback(function ($key) {
            return $key === 'carrier2'; // Carrier2 circuit is open
        });
        $this->mockCircuitBreaker->method('recordSuccess');

        $service = new class {
            use WithCircuitBreaker;

            public function collect(array $operations): array
            {
                return $this->withCircuitBreakerCollect($operations);
            }
        };

        $results = $service->collect([
            'carrier1' => fn() => ['rate' => 10.00],
            'carrier2' => fn() => ['rate' => 15.00], // Will be skipped
            'carrier3' => fn() => ['rate' => 12.50],
        ]);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('carrier1', $results);
        $this->assertArrayNotHasKey('carrier2', $results);
        $this->assertArrayHasKey('carrier3', $results);
    }

    public function test_with_circuit_breaker_collect_continues_after_exception(): void
    {
        $this->mockCircuitBreaker->method('isOpen')->willReturn(false);
        $this->mockCircuitBreaker->method('recordSuccess');
        $this->mockCircuitBreaker->method('recordFailure');

        $service = new class {
            use WithCircuitBreaker;

            public function collect(array $operations): array
            {
                return $this->withCircuitBreakerCollect($operations);
            }
        };

        $results = $service->collect([
            'carrier1' => fn() => ['rate' => 10.00],
            'carrier2' => function () {
                throw new \Exception('API error');
            },
            'carrier3' => fn() => ['rate' => 12.50],
        ]);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('carrier1', $results);
        $this->assertArrayNotHasKey('carrier2', $results);
        $this->assertArrayHasKey('carrier3', $results);
    }

    public function test_circuit_open_exception_has_correct_code(): void
    {
        $exception = new CircuitOpenException('Test message');

        $this->assertEquals(503, $exception->getCode());
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function test_logs_warning_when_circuit_open(): void
    {
        $this->mockCircuitBreaker->method('isOpen')->willReturn(true);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Circuit breaker open')
                    && $context['key'] === 'test:service';
            });

        $service = new class {
            use WithCircuitBreaker;

            public function call(string $key): mixed
            {
                return $this->withCircuitBreaker($key, fn() => 'success', 'fallback');
            }
        };

        $service->call('test:service');
    }

    public function test_logs_warning_when_external_call_fails(): void
    {
        $this->mockCircuitBreaker->method('isOpen')->willReturn(false);
        $this->mockCircuitBreaker->method('recordFailure');

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'External call failed')
                    && $context['key'] === 'test:failing'
                    && str_contains($context['error'], 'Connection timeout');
            });

        $service = new class {
            use WithCircuitBreaker;

            public function call(string $key): mixed
            {
                return $this->withCircuitBreaker($key, function () {
                    throw new \Exception('Connection timeout');
                });
            }
        };

        try {
            $service->call('test:failing');
        } catch (\Exception $e) {
            // Expected
        }
    }
}
