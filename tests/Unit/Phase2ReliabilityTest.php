<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Plugins\CircuitBreaker;
use App\Services\Plugins\PluginAutoloader;
use App\Services\Registry\RegistryBatch;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Phase 2: Platform Reliability Tests
 *
 * Tests for:
 * - Task 2.1: Registry Transaction Wrapper
 * - Task 2.2: Hook Circuit Breaker
 * - Task 2.3: Central Plugin Autoloader
 * - Task 2.4: Plugin Health Monitoring
 */
class Phase2ReliabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================================
    // Task 2.1: Registry Transaction Wrapper Tests
    // =========================================================================

    public function test_registry_batch_can_be_created(): void
    {
        $batch = new RegistryBatch('test-plugin');

        $this->assertInstanceOf(RegistryBatch::class, $batch);
        $this->assertFalse($batch->isCommitted());
        $this->assertFalse($batch->isRolledBack());
    }

    public function test_registry_batch_tracks_operations(): void
    {
        $batch = new RegistryBatch('test-plugin');
        $batch->addOperation('entity', 'register', ['name' => 'test', 'config' => []]);
        $batch->addOperation('view', 'registerView', ['slug' => 'test_view', 'type' => 'list']);

        $ops = $batch->getOperations();

        $this->assertCount(2, $ops);
        $this->assertEquals('entity', $ops[0]['registry']);
        $this->assertEquals('view', $ops[1]['registry']);
    }

    public function test_registry_batch_can_rollback(): void
    {
        $batch = new RegistryBatch('test-plugin');
        $batch->addOperation('entity', 'register', ['name' => 'test']);

        $batch->rollback();

        $this->assertTrue($batch->isRolledBack());
        $this->assertFalse($batch->isCommitted());
    }

    // =========================================================================
    // Task 2.2: Hook Circuit Breaker Tests
    // =========================================================================

    public function test_circuit_breaker_starts_closed(): void
    {
        $breaker = new CircuitBreaker();

        $this->assertFalse($breaker->isOpen('test-hook'));
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $breaker->getState('test-hook'));
    }

    public function test_circuit_breaker_opens_after_failures(): void
    {
        $breaker = new CircuitBreaker();
        $hookKey = 'test-plugin:failing-hook';

        // Record failures up to threshold (default 5)
        for ($i = 0; $i < 5; $i++) {
            $breaker->recordFailure($hookKey);
        }

        $this->assertTrue($breaker->isOpen($hookKey));
        $this->assertEquals(CircuitBreaker::STATE_OPEN, $breaker->getState($hookKey));
    }

    public function test_circuit_breaker_can_reset(): void
    {
        $breaker = new CircuitBreaker();
        $hookKey = 'test-plugin:resetable-hook';

        // Open the circuit
        for ($i = 0; $i < 5; $i++) {
            $breaker->recordFailure($hookKey);
        }

        $this->assertTrue($breaker->isOpen($hookKey));

        // Reset
        $breaker->reset($hookKey);

        $this->assertFalse($breaker->isOpen($hookKey));
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $breaker->getState($hookKey));
    }

    public function test_circuit_breaker_records_success(): void
    {
        $breaker = new CircuitBreaker();
        $hookKey = 'test-plugin:success-hook';

        // Record some failures but not enough to open
        $breaker->recordFailure($hookKey);
        $breaker->recordFailure($hookKey);

        // Record success - should reset failures
        $breaker->recordSuccess($hookKey);

        // Circuit should still be closed
        $this->assertFalse($breaker->isOpen($hookKey));
    }

    public function test_circuit_breaker_can_force_open(): void
    {
        $breaker = new CircuitBreaker();
        $hookKey = 'test-plugin:force-open-hook';

        $breaker->forceOpen($hookKey, 'Manual override for testing');

        $this->assertTrue($breaker->isOpen($hookKey));
        $this->assertEquals(CircuitBreaker::STATE_OPEN, $breaker->getState($hookKey));
    }

    public function test_circuit_breaker_metrics(): void
    {
        $breaker = new CircuitBreaker();
        $hookKey = 'test-plugin:metrics-hook';

        $breaker->recordFailure($hookKey);
        $breaker->recordFailure($hookKey);

        $metrics = $breaker->getMetrics($hookKey);

        $this->assertEquals($hookKey, $metrics['key']);
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $metrics['state']);
        $this->assertEquals(2, $metrics['failure_count']);
    }

    public function test_circuit_breaker_hook_key_generation(): void
    {
        $key1 = CircuitBreaker::hookKey('my_action', 'my-plugin');
        $key2 = CircuitBreaker::hookKey('my_action');

        $this->assertEquals('my-plugin:my_action', $key1);
        $this->assertEquals('core:my_action', $key2);
    }

    // =========================================================================
    // Task 2.3: Central Plugin Autoloader Tests
    // =========================================================================

    public function test_autoloader_can_add_namespace(): void
    {
        $autoloader = new PluginAutoloader();

        $autoloader->addNamespace('TestPlugin\\', '/path/to/plugin');

        $namespaces = $autoloader->getNamespaces();

        $this->assertArrayHasKey('TestPlugin\\', $namespaces);
        $this->assertEquals('/path/to/plugin', $namespaces['TestPlugin\\']);
    }

    public function test_autoloader_normalizes_namespace(): void
    {
        $autoloader = new PluginAutoloader();

        // Without trailing backslash
        $autoloader->addNamespace('MyPlugin', '/path/to/myplugin');

        $namespaces = $autoloader->getNamespaces();

        // Should be normalized with trailing backslash
        $this->assertArrayHasKey('MyPlugin\\', $namespaces);
    }

    public function test_autoloader_can_remove_namespace(): void
    {
        $autoloader = new PluginAutoloader();

        $autoloader->addNamespace('RemovablePlugin\\', '/path/to/removable');
        $autoloader->removeNamespace('RemovablePlugin');

        $namespaces = $autoloader->getNamespaces();

        $this->assertArrayNotHasKey('RemovablePlugin\\', $namespaces);
    }

    public function test_autoloader_can_check_if_class_loadable(): void
    {
        $autoloader = new PluginAutoloader();

        // Add a namespace pointing to a non-existent path
        $autoloader->addNamespace('FakePlugin\\', '/nonexistent/path');

        // Should return false since the file doesn't exist
        $this->assertFalse($autoloader->canLoad('FakePlugin\\SomeClass'));
    }

    public function test_autoloader_stats(): void
    {
        $autoloader = new PluginAutoloader();

        $autoloader->addNamespace('Plugin1\\', '/path/1');
        $autoloader->addNamespace('Plugin2\\', '/path/2');

        $stats = $autoloader->getStats();

        $this->assertEquals(2, $stats['namespace_count']);
        $this->assertEquals(0, $stats['load_count']);
        $this->assertArrayHasKey('failed_lookups', $stats);
    }

    public function test_autoloader_register_plugin(): void
    {
        $autoloader = new PluginAutoloader();

        $autoloader->registerPlugin('my-plugin', '/plugins/my-plugin');

        $namespaces = $autoloader->getNamespaces();

        $this->assertArrayHasKey('App\\Plugins\\my-plugin\\', $namespaces);
    }

    public function test_autoloader_unregister_plugin(): void
    {
        $autoloader = new PluginAutoloader();

        $autoloader->registerPlugin('temp-plugin', '/plugins/temp-plugin');
        $autoloader->unregisterPlugin('temp-plugin');

        $namespaces = $autoloader->getNamespaces();

        // Should not have any namespaces for this plugin
        foreach (array_keys($namespaces) as $ns) {
            $this->assertStringNotContainsString('temp-plugin', $ns);
        }
    }

    // =========================================================================
    // Task 2.4: Plugin Health Monitoring Tests
    // =========================================================================

    public function test_plugin_health_status_constants(): void
    {
        $this->assertEquals('healthy', \App\Services\Plugins\PluginHealthMonitor::STATUS_HEALTHY);
        $this->assertEquals('degraded', \App\Services\Plugins\PluginHealthMonitor::STATUS_DEGRADED);
        $this->assertEquals('unhealthy', \App\Services\Plugins\PluginHealthMonitor::STATUS_UNHEALTHY);
        $this->assertEquals('disabled', \App\Services\Plugins\PluginHealthMonitor::STATUS_DISABLED);
    }
}
