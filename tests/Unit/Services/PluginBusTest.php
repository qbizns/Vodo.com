<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\PluginBus\PluginBus;
use App\Services\PluginBus\ServiceNotFoundException;

class PluginBusTest extends TestCase
{
    protected PluginBus $bus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bus = new PluginBus();
    }

    // =========================================================================
    // Service Registration Tests
    // =========================================================================

    public function test_can_register_service(): void
    {
        $this->bus->provide('test.service.action', fn() => 'result');

        $this->assertTrue($this->bus->hasService('test.service.action'));
    }

    public function test_can_call_registered_service(): void
    {
        $this->bus->provide('math.add', fn($params) => $params['a'] + $params['b']);

        $result = $this->bus->call('math.add', ['a' => 5, 'b' => 3]);

        $this->assertEquals(8, $result);
    }

    public function test_throws_on_invalid_service_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->bus->provide('InvalidServiceId', fn() => null);
    }

    public function test_throws_on_missing_service(): void
    {
        $this->expectException(ServiceNotFoundException::class);

        $this->bus->call('nonexistent.service');
    }

    public function test_service_tracks_plugin_context(): void
    {
        $this->bus->setPluginContext('test-plugin');
        $this->bus->provide('test.service', fn() => 'result');

        $services = $this->bus->getServices();

        $this->assertEquals('test-plugin', $services['test.service']['plugin']);
    }

    public function test_get_services_by_namespace(): void
    {
        $this->bus->provide('accounting.journal.create', fn() => null);
        $this->bus->provide('accounting.journal.delete', fn() => null);
        $this->bus->provide('sales.order.create', fn() => null);

        $accounting = $this->bus->getServicesByNamespace('accounting');

        $this->assertCount(2, $accounting);
        $this->assertArrayHasKey('accounting.journal.create', $accounting);
        $this->assertArrayHasKey('accounting.journal.delete', $accounting);
    }

    // =========================================================================
    // Event System Tests
    // =========================================================================

    public function test_can_subscribe_to_events(): void
    {
        $received = null;

        $this->bus->subscribe('test.event', function ($data) use (&$received) {
            $received = $data;
        });

        $this->bus->publish('test.event', ['key' => 'value']);

        $this->assertNotNull($received);
        $this->assertEquals(['key' => 'value'], $received['payload']);
    }

    public function test_event_handlers_respect_priority(): void
    {
        $order = [];

        $this->bus->subscribe('test.event', function () use (&$order) {
            $order[] = 'normal';
        }, 10);

        $this->bus->subscribe('test.event', function () use (&$order) {
            $order[] = 'early';
        }, 5);

        $this->bus->subscribe('test.event', function () use (&$order) {
            $order[] = 'late';
        }, 15);

        $this->bus->publish('test.event');

        $this->assertEquals(['early', 'normal', 'late'], $order);
    }

    public function test_event_includes_publisher_info(): void
    {
        $received = null;

        $this->bus->setPluginContext('publisher-plugin');
        $this->bus->subscribe('test.event', function ($data) use (&$received) {
            $received = $data;
        });

        $this->bus->publish('test.event');

        $this->assertEquals('publisher-plugin', $received['publisher']);
    }

    // =========================================================================
    // Dependency Tests
    // =========================================================================

    public function test_can_declare_dependency(): void
    {
        $this->bus->declareDependency('plugin-a', 'other.service', true);

        $result = $this->bus->checkDependencies('plugin-a');

        $this->assertFalse($result['satisfied']);
        $this->assertContains('other.service', $result['missing']);
    }

    public function test_dependency_satisfied_when_service_exists(): void
    {
        $this->bus->provide('other.service', fn() => null);
        $this->bus->declareDependency('plugin-a', 'other.service', true);

        $result = $this->bus->checkDependencies('plugin-a');

        $this->assertTrue($result['satisfied']);
        $this->assertEmpty($result['missing']);
    }

    public function test_optional_dependency_does_not_fail(): void
    {
        $this->bus->declareDependency('plugin-a', 'optional.service', false);

        $result = $this->bus->checkDependencies('plugin-a');

        $this->assertTrue($result['satisfied']);
        $this->assertContains('optional.service', $result['optional_missing']);
    }

    // =========================================================================
    // Plugin Removal Tests
    // =========================================================================

    public function test_remove_plugin_clears_services(): void
    {
        $this->bus->setPluginContext('test-plugin');
        $this->bus->provide('test.service.one', fn() => null);
        $this->bus->provide('test.service.two', fn() => null);

        $removed = $this->bus->removePlugin('test-plugin');

        $this->assertEquals(2, $removed);
        $this->assertFalse($this->bus->hasService('test.service.one'));
        $this->assertFalse($this->bus->hasService('test.service.two'));
    }

    // =========================================================================
    // Dependency Graph Tests
    // =========================================================================

    public function test_get_dependency_graph(): void
    {
        $this->bus->setPluginContext('plugin-a');
        $this->bus->provide('plugin_a.service', fn() => null);

        $this->bus->setPluginContext('plugin-b');
        $this->bus->provide('plugin_b.service', fn() => null);

        $this->bus->declareDependency('plugin-b', 'plugin_a.service', true);

        $graph = $this->bus->getDependencyGraph();

        $this->assertArrayHasKey('nodes', $graph);
        $this->assertArrayHasKey('edges', $graph);
        $this->assertGreaterThan(0, count($graph['nodes']));
    }
}
