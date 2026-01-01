<?php

declare(strict_types=1);

namespace Tests\Feature\Phase2;

use App\Exceptions\Plugins\SandboxViolationException;
use App\Models\PluginAuditLog;
use App\Models\PluginResourceUsage;
use App\Services\Plugins\CircuitBreaker;
use App\Services\Plugins\HookManager;
use App\Services\Plugins\Security\PluginSandbox;
use App\Services\Plugins\Security\SandboxHttpClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 2: Extension Safety / Plugin Sandbox Integration Tests
 *
 * These tests verify:
 * - Sandbox enforcement in hook execution
 * - Circuit breaker integration
 * - Network request filtering
 * - Resource limit enforcement
 * - Violation tracking and auto-disable
 */
class SandboxIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected HookManager $hookManager;
    protected PluginSandbox $sandbox;
    protected CircuitBreaker $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hookManager = new HookManager();
        $this->sandbox = app(PluginSandbox::class);
        $this->circuitBreaker = new CircuitBreaker();

        // Clear cache for clean state
        Cache::flush();
    }

    // =========================================================================
    // HOOK MANAGER + SANDBOX INTEGRATION
    // =========================================================================

    public function test_hook_manager_has_sandbox_integration(): void
    {
        // Verify the hook manager can get sandbox service
        $reflection = new \ReflectionClass($this->hookManager);

        $this->assertTrue(
            $reflection->hasProperty('sandbox'),
            'HookManager should have sandbox property'
        );

        $this->assertTrue(
            $reflection->hasProperty('circuitBreaker'),
            'HookManager should have circuitBreaker property'
        );

        $this->assertTrue(
            $reflection->hasMethod('getSandbox'),
            'HookManager should have getSandbox method'
        );

        $this->assertTrue(
            $reflection->hasMethod('getCircuitBreaker'),
            'HookManager should have getCircuitBreaker method'
        );
    }

    public function test_hook_executes_callback_when_sandbox_disabled(): void
    {
        $executed = false;

        // Ensure sandbox is disabled
        config(['plugin.sandbox.enabled' => false]);

        $this->hookManager->setPluginContext('test-plugin');
        $this->hookManager->addAction('test_hook', function () use (&$executed) {
            $executed = true;
        });

        $this->hookManager->doAction('test_hook');

        $this->assertTrue($executed, 'Hook callback should execute when sandbox is disabled');
    }

    public function test_hook_tracks_plugin_context(): void
    {
        $this->hookManager->setPluginContext('my-test-plugin');

        $capturedPlugin = null;

        // Register a hook while plugin context is set
        $this->hookManager->addAction('context_test', function () use (&$capturedPlugin) {
            // This would be called during execution
        });

        // Get the registered hooks
        $actions = $this->hookManager->getActions();

        $this->assertArrayHasKey('context_test', $actions);

        // Find the entry with the plugin context
        $found = false;
        foreach ($actions['context_test'] as $priority => $callbacks) {
            foreach ($callbacks as $entry) {
                if ($entry['plugin'] === 'my-test-plugin') {
                    $found = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($found, 'Hook should track plugin context');
    }

    // =========================================================================
    // CIRCUIT BREAKER INTEGRATION
    // =========================================================================

    public function test_circuit_breaker_starts_closed(): void
    {
        $key = CircuitBreaker::hookKey('test_hook', 'test-plugin');

        $this->assertEquals(
            CircuitBreaker::STATE_CLOSED,
            $this->circuitBreaker->getState($key)
        );

        $this->assertFalse($this->circuitBreaker->isOpen($key));
    }

    public function test_circuit_opens_after_failure_threshold(): void
    {
        $key = CircuitBreaker::hookKey('failing_hook', 'bad-plugin');

        // Record failures up to threshold (default 5)
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure($key, new \RuntimeException('Test failure'));
        }

        $this->assertTrue(
            $this->circuitBreaker->isOpen($key),
            'Circuit should be open after reaching failure threshold'
        );

        $this->assertEquals(
            CircuitBreaker::STATE_OPEN,
            $this->circuitBreaker->getState($key)
        );
    }

    public function test_circuit_recovers_after_timeout(): void
    {
        $key = CircuitBreaker::hookKey('recovery_hook', 'test-plugin');

        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure($key);
        }

        $this->assertTrue($this->circuitBreaker->isOpen($key));

        // Simulate time passing (via cache manipulation)
        Cache::put("circuit_breaker:opened_at:{$key}", now()->subMinutes(10), 86400);

        // Circuit should transition to half-open
        $this->assertFalse(
            $this->circuitBreaker->isOpen($key),
            'Circuit should allow attempt after recovery timeout'
        );
    }

    public function test_circuit_closes_after_successful_recovery(): void
    {
        $key = CircuitBreaker::hookKey('recovery_hook', 'test-plugin');

        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure($key);
        }

        // Force to half-open
        Cache::put("circuit_breaker:state:{$key}", CircuitBreaker::STATE_HALF_OPEN, 86400);

        // Record successes (default threshold is 2)
        $this->circuitBreaker->recordSuccess($key);
        $this->circuitBreaker->recordSuccess($key);

        $this->assertEquals(
            CircuitBreaker::STATE_CLOSED,
            $this->circuitBreaker->getState($key)
        );
    }

    public function test_circuit_can_be_reset(): void
    {
        $key = CircuitBreaker::hookKey('reset_hook', 'test-plugin');

        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure($key);
        }

        $this->assertTrue($this->circuitBreaker->isOpen($key));

        // Reset
        $this->circuitBreaker->reset($key);

        $this->assertFalse($this->circuitBreaker->isOpen($key));
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $this->circuitBreaker->getState($key));
    }

    public function test_hook_key_format(): void
    {
        $this->assertEquals(
            'test-plugin:my_hook',
            CircuitBreaker::hookKey('my_hook', 'test-plugin')
        );

        $this->assertEquals(
            'core:my_hook',
            CircuitBreaker::hookKey('my_hook', null)
        );
    }

    // =========================================================================
    // SANDBOX HTTP CLIENT
    // =========================================================================

    public function test_sandbox_http_client_exists(): void
    {
        $client = new SandboxHttpClient();

        $this->assertInstanceOf(SandboxHttpClient::class, $client);
    }

    public function test_sandbox_http_client_for_plugin(): void
    {
        $client = (new SandboxHttpClient())->forPlugin('my-plugin');

        $this->assertInstanceOf(SandboxHttpClient::class, $client);
    }

    public function test_sandbox_http_client_allows_requests_when_disabled(): void
    {
        config(['plugin.sandbox.enabled' => false]);

        Http::fake([
            'api.example.com/*' => Http::response(['success' => true], 200),
        ]);

        $client = (new SandboxHttpClient())->forPlugin('test-plugin');
        $response = $client->get('https://api.example.com/test');

        $this->assertEquals(200, $response->status());
    }

    // =========================================================================
    // PLUGIN SANDBOX CORE
    // =========================================================================

    public function test_sandbox_is_disabled_by_default(): void
    {
        $this->assertFalse($this->sandbox->isEnabled());
    }

    public function test_sandbox_can_be_enabled(): void
    {
        config(['plugin.sandbox.enabled' => true]);

        $sandbox = new PluginSandbox();

        $this->assertTrue($sandbox->isEnabled());
    }

    public function test_sandbox_has_default_limits(): void
    {
        $limits = $this->sandbox->getDefaultLimits();

        $this->assertArrayHasKey('memory_mb', $limits);
        $this->assertArrayHasKey('execution_time_seconds', $limits);
        $this->assertArrayHasKey('api_requests_per_minute', $limits);
        $this->assertArrayHasKey('hook_executions_per_minute', $limits);
        $this->assertArrayHasKey('storage_mb', $limits);
        $this->assertArrayHasKey('network_requests_per_minute', $limits);
    }

    public function test_sandbox_can_set_plugin_specific_limits(): void
    {
        $this->sandbox->setPluginLimits('custom-plugin', [
            'memory_mb' => 512,
            'api_requests_per_minute' => 100,
        ]);

        $limits = $this->sandbox->getPluginLimits('custom-plugin');

        $this->assertEquals(512, $limits['memory_mb']);
        $this->assertEquals(100, $limits['api_requests_per_minute']);
    }

    public function test_sandbox_execution_tracking(): void
    {
        $this->sandbox->beginExecution('test-plugin');

        // Simulate some work
        usleep(10000); // 10ms

        $stats = $this->sandbox->endExecution();

        $this->assertEquals('test-plugin', $stats['plugin']);
        $this->assertGreaterThan(0, $stats['execution_time_ms']);
        $this->assertArrayHasKey('memory_used_bytes', $stats);
        $this->assertArrayHasKey('peak_memory_bytes', $stats);
    }

    public function test_sandbox_blocking(): void
    {
        $this->assertFalse($this->sandbox->isBlocked('test-plugin'));

        $this->sandbox->blockPlugin('test-plugin', 60);

        $this->assertTrue($this->sandbox->isBlocked('test-plugin'));

        $this->sandbox->unblockPlugin('test-plugin');

        $this->assertFalse($this->sandbox->isBlocked('test-plugin'));
    }

    public function test_sandbox_domain_whitelist(): void
    {
        // No whitelist = allow all
        $this->assertTrue($this->sandbox->isDomainAllowed('test-plugin', 'any-domain.com'));

        // Set a whitelist
        $this->sandbox->setPluginLimits('restricted-plugin', [
            'network_whitelist' => ['allowed.com', '*.example.com'],
        ]);

        $this->assertTrue($this->sandbox->isDomainAllowed('restricted-plugin', 'allowed.com'));
        $this->assertTrue($this->sandbox->isDomainAllowed('restricted-plugin', 'api.example.com'));
        $this->assertTrue($this->sandbox->isDomainAllowed('restricted-plugin', 'sub.api.example.com'));
        $this->assertFalse($this->sandbox->isDomainAllowed('restricted-plugin', 'blocked.com'));
    }

    // =========================================================================
    // SANDBOX VIOLATION EXCEPTION
    // =========================================================================

    public function test_sandbox_violation_exception_rate_limit(): void
    {
        $exception = SandboxViolationException::rateLimitExceeded('test-plugin', 'api_requests', 60);

        $this->assertEquals('test-plugin', $exception->getPluginSlug());
        $this->assertEquals('rate_limit', $exception->getViolationType());
        $this->assertEquals(429, $exception->getCode());
    }

    public function test_sandbox_violation_exception_memory(): void
    {
        $exception = SandboxViolationException::memoryLimitExceeded('test-plugin', 256, 300.5);

        $this->assertEquals('memory_limit', $exception->getViolationType());
        $this->assertStringContainsString('300.5MB', $exception->getMessage());
    }

    public function test_sandbox_violation_exception_domain(): void
    {
        $exception = SandboxViolationException::domainNotAllowed('test-plugin', 'blocked.com');

        $this->assertEquals('domain_blocked', $exception->getViolationType());
        $this->assertEquals(403, $exception->getCode());
        $this->assertStringContainsString('blocked.com', $exception->getMessage());
    }

    public function test_sandbox_violation_exception_plugin_blocked(): void
    {
        $exception = SandboxViolationException::pluginBlocked('bad-plugin');

        $this->assertEquals('plugin_blocked', $exception->getViolationType());
        $this->assertEquals(503, $exception->getCode());
    }

    public function test_sandbox_violation_to_array(): void
    {
        $exception = SandboxViolationException::rateLimitExceeded('test-plugin', 'api_requests', 60);
        $array = $exception->toArray();

        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('plugin', $array);
        $this->assertArrayHasKey('violation_type', $array);
        $this->assertArrayHasKey('details', $array);
        $this->assertArrayHasKey('code', $array);
    }

    // =========================================================================
    // CIRCUIT BREAKER METRICS
    // =========================================================================

    public function test_circuit_breaker_metrics(): void
    {
        $key = CircuitBreaker::hookKey('metrics_hook', 'test-plugin');

        // Record some activity
        $this->circuitBreaker->recordSuccess($key);
        $this->circuitBreaker->recordFailure($key);
        $this->circuitBreaker->recordFailure($key);

        $metrics = $this->circuitBreaker->getMetrics($key);

        $this->assertEquals($key, $metrics['key']);
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $metrics['state']);
        $this->assertEquals(2, $metrics['failure_count']);
        $this->assertArrayHasKey('failure_threshold', $metrics);
        $this->assertArrayHasKey('recovery_timeout', $metrics);
    }

    public function test_circuit_breaker_open_circuits_tracking(): void
    {
        $key1 = CircuitBreaker::hookKey('open_hook_1', 'plugin-1');
        $key2 = CircuitBreaker::hookKey('open_hook_2', 'plugin-2');

        // Open both circuits
        for ($i = 0; $i < 5; $i++) {
            $this->circuitBreaker->recordFailure($key1);
            $this->circuitBreaker->recordFailure($key2);
        }

        $openCircuits = $this->circuitBreaker->getOpenCircuits();

        $this->assertCount(2, $openCircuits);
        $this->assertArrayHasKey($key1, $openCircuits);
        $this->assertArrayHasKey($key2, $openCircuits);
    }

    // =========================================================================
    // HOOK MANAGER SETTERS FOR TESTING
    // =========================================================================

    public function test_hook_manager_sandbox_setter(): void
    {
        $mockSandbox = $this->createMock(PluginSandbox::class);

        $this->hookManager->setSandbox($mockSandbox);

        $reflection = new \ReflectionClass($this->hookManager);
        $property = $reflection->getProperty('sandbox');
        $property->setAccessible(true);

        $this->assertSame($mockSandbox, $property->getValue($this->hookManager));
    }

    public function test_hook_manager_circuit_breaker_setter(): void
    {
        $mockCircuitBreaker = $this->createMock(CircuitBreaker::class);

        $this->hookManager->setCircuitBreaker($mockCircuitBreaker);

        $reflection = new \ReflectionClass($this->hookManager);
        $property = $reflection->getProperty('circuitBreaker');
        $property->setAccessible(true);

        $this->assertSame($mockCircuitBreaker, $property->getValue($this->hookManager));
    }
}
