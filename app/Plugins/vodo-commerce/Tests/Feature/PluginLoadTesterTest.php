<?php

declare(strict_types=1);

namespace VodoCommerce\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use VodoCommerce\Contracts\CommercePluginContract;
use VodoCommerce\Events\CommerceEventRegistry;
use VodoCommerce\Services\PluginLoadTester;

/**
 * Tests for the Plugin Load Tester.
 */
class PluginLoadTesterTest extends TestCase
{
    protected PluginLoadTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $eventRegistry = $this->createMock(CommerceEventRegistry::class);
        $this->tester = new PluginLoadTester($eventRegistry);
    }

    #[Test]
    public function it_can_be_configured(): void
    {
        $this->tester->configure([
            'iterations' => 50,
            'target_p95_ms' => 1000,
        ]);

        // Configuration should be accepted without errors
        $this->assertInstanceOf(PluginLoadTester::class, $this->tester);
    }

    #[Test]
    public function it_registers_plugins(): void
    {
        $plugin = $this->createMockPlugin('TestPlugin');

        $result = $this->tester->registerPlugin($plugin);

        $this->assertInstanceOf(PluginLoadTester::class, $result);
    }

    #[Test]
    public function it_runs_full_test_suite(): void
    {
        // Configure for fast test
        $this->tester->configure([
            'iterations' => 5,
            'warmup_iterations' => 2,
        ]);

        // Register mock plugins
        for ($i = 1; $i <= 3; $i++) {
            $this->tester->registerPlugin($this->createMockPlugin("Plugin{$i}"));
        }

        $results = $this->tester->runFullSuite();

        // Verify structure
        $this->assertArrayHasKey('meta', $results);
        $this->assertArrayHasKey('tests', $results);
        $this->assertArrayHasKey('summary', $results);

        // Verify meta
        $this->assertEquals(3, $results['meta']['plugin_count']);
        $this->assertContains('Plugin1', $results['meta']['plugins']);

        // Verify tests
        $this->assertArrayHasKey('event_dispatch', $results['tests']);
        $this->assertArrayHasKey('hook_execution', $results['tests']);
        $this->assertArrayHasKey('api_endpoints', $results['tests']);
        $this->assertArrayHasKey('database_queries', $results['tests']);
        $this->assertArrayHasKey('memory_usage', $results['tests']);
        $this->assertArrayHasKey('concurrent_load', $results['tests']);

        // Verify summary
        $this->assertArrayHasKey('passed', $results['summary']);
        $this->assertArrayHasKey('max_p95_ms', $results['summary']);
        $this->assertArrayHasKey('recommendation', $results['summary']);
    }

    #[Test]
    public function it_tests_event_dispatch(): void
    {
        $this->tester->configure(['iterations' => 5, 'warmup_iterations' => 2]);
        $this->tester->registerPlugin($this->createMockPlugin('TestPlugin'));

        $results = $this->tester->runFullSuite();
        $eventTest = $results['tests']['event_dispatch'];

        $this->assertEquals('Event Dispatch', $eventTest['name']);
        $this->assertArrayHasKey('results', $eventTest);
        $this->assertArrayHasKey('aggregated', $eventTest);
        $this->assertArrayHasKey('max_p95', $eventTest['aggregated']);
    }

    #[Test]
    public function it_tests_hook_execution(): void
    {
        $this->tester->configure(['iterations' => 5, 'warmup_iterations' => 2]);
        $this->tester->registerPlugin($this->createMockPlugin('TestPlugin'));

        $results = $this->tester->runFullSuite();
        $hookTest = $results['tests']['hook_execution'];

        $this->assertEquals('Hook Execution', $hookTest['name']);
        $this->assertArrayHasKey('hooks_tested', $hookTest);
        $this->assertArrayHasKey('plugins_active', $hookTest);
    }

    #[Test]
    public function it_tests_memory_usage(): void
    {
        $this->tester->configure(['iterations' => 5, 'warmup_iterations' => 2]);

        $results = $this->tester->runFullSuite();
        $memoryTest = $results['tests']['memory_usage'];

        $this->assertEquals('Memory Usage', $memoryTest['name']);
        $this->assertArrayHasKey('per_operation_mb', $memoryTest);
        $this->assertArrayHasKey('peak_memory_mb', $memoryTest);
        $this->assertArrayHasKey('within_limit', $memoryTest);
    }

    #[Test]
    public function it_calculates_statistics_correctly(): void
    {
        $this->tester->configure(['iterations' => 10, 'warmup_iterations' => 1]);
        $this->tester->registerPlugin($this->createMockPlugin('TestPlugin'));

        $results = $this->tester->runFullSuite();

        // Check that stats are calculated
        foreach ($results['tests']['event_dispatch']['results'] as $event => $stats) {
            $this->assertArrayHasKey('min', $stats);
            $this->assertArrayHasKey('max', $stats);
            $this->assertArrayHasKey('avg', $stats);
            $this->assertArrayHasKey('median', $stats);
            $this->assertArrayHasKey('p95', $stats);
            $this->assertArrayHasKey('p99', $stats);
            $this->assertArrayHasKey('stddev', $stats);

            // Min should be <= avg <= max
            $this->assertLessThanOrEqual($stats['avg'], $stats['max']);
            $this->assertGreaterThanOrEqual($stats['min'], $stats['avg']);
        }
    }

    #[Test]
    public function it_generates_markdown_report(): void
    {
        $this->tester->configure(['iterations' => 5, 'warmup_iterations' => 2]);
        $this->tester->registerPlugin($this->createMockPlugin('TestPlugin'));

        $this->tester->runFullSuite();
        $markdown = $this->tester->toMarkdown();

        $this->assertIsString($markdown);
        $this->assertStringContainsString('# Plugin Load Test Report', $markdown);
        $this->assertStringContainsString('## Summary', $markdown);
        $this->assertStringContainsString('## Plugins Tested', $markdown);
        $this->assertStringContainsString('## Test Results', $markdown);
        $this->assertStringContainsString('TestPlugin', $markdown);
    }

    #[Test]
    public function it_determines_pass_fail_based_on_target(): void
    {
        // Test with very high target (should pass)
        $this->tester->configure([
            'iterations' => 5,
            'warmup_iterations' => 2,
            'target_p95_ms' => 10000, // 10 seconds - should easily pass
        ]);

        $results = $this->tester->runFullSuite();
        $this->assertTrue($results['summary']['passed']);

        // Test with impossibly low target (should fail)
        $strictTester = new PluginLoadTester(
            $this->createMock(CommerceEventRegistry::class)
        );
        $strictTester->configure([
            'iterations' => 5,
            'warmup_iterations' => 2,
            'target_p95_ms' => 0.001, // 0.001ms - impossible to achieve
        ]);
        $strictTester->registerPlugin($this->createMockPlugin('SlowPlugin', 50));

        $strictResults = $strictTester->runFullSuite();
        $this->assertFalse($strictResults['summary']['passed']);
    }

    #[Test]
    public function it_handles_multiple_plugins(): void
    {
        $this->tester->configure(['iterations' => 5, 'warmup_iterations' => 2]);

        // Register 10 plugins
        for ($i = 1; $i <= 10; $i++) {
            $this->tester->registerPlugin($this->createMockPlugin("Plugin{$i}"));
        }

        $results = $this->tester->runFullSuite();

        $this->assertEquals(10, $results['meta']['plugin_count']);
        $this->assertCount(10, $results['meta']['plugins']);
    }

    #[Test]
    public function it_provides_recommendations(): void
    {
        $this->tester->configure(['iterations' => 5, 'warmup_iterations' => 2]);

        $results = $this->tester->runFullSuite();

        $this->assertArrayHasKey('recommendation', $results['summary']);
        $this->assertNotEmpty($results['summary']['recommendation']);
    }

    /**
     * Create a mock plugin for testing.
     *
     * @param string $name
     * @param int $latencyMs
     * @return CommercePluginContract
     */
    protected function createMockPlugin(string $name, int $latencyMs = 1): CommercePluginContract
    {
        $plugin = $this->createMock(CommercePluginContract::class);

        $plugin->method('getName')->willReturn($name);
        $plugin->method('getVersion')->willReturn('1.0.0');
        $plugin->method('getType')->willReturn('general');
        $plugin->method('isEnabled')->willReturn(true);

        $plugin->method('executeHook')->willReturnCallback(function ($hook, $context) use ($latencyMs) {
            usleep($latencyMs * 100);
            return $context;
        });

        $plugin->method('getMetadata')->willReturn([
            'name' => $name,
            'type' => 'general',
            'version' => '1.0.0',
        ]);

        return $plugin;
    }
}
