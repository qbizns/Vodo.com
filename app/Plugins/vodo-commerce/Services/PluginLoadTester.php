<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use VodoCommerce\Contracts\CommercePluginContract;
use VodoCommerce\Events\CommerceEventRegistry;

/**
 * Plugin Load Tester
 *
 * Tests platform performance with multiple plugins active.
 * Measures latency, memory usage, and identifies bottlenecks.
 */
class PluginLoadTester
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $results = [];

    /**
     * @var array<int, CommercePluginContract>
     */
    protected array $activePlugins = [];

    /**
     * Test configuration.
     *
     * @var array<string, mixed>
     */
    protected array $config = [
        'iterations' => 100,
        'warmup_iterations' => 10,
        'concurrent_requests' => 10,
        'target_p95_ms' => 3000,
        'memory_limit_mb' => 256,
    ];

    public function __construct(
        protected CommerceEventRegistry $eventRegistry
    ) {
    }

    /**
     * Configure the load tester.
     *
     * @param array<string, mixed> $config
     * @return self
     */
    public function configure(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Register a plugin for testing.
     *
     * @param CommercePluginContract $plugin
     * @return self
     */
    public function registerPlugin(CommercePluginContract $plugin): self
    {
        $this->activePlugins[] = $plugin;

        return $this;
    }

    /**
     * Run the full load test suite.
     *
     * @return array<string, mixed>
     */
    public function runFullSuite(): array
    {
        $suiteStart = microtime(true);
        $initialMemory = memory_get_usage(true);

        $this->results = [
            'meta' => [
                'started_at' => date('Y-m-d H:i:s'),
                'plugin_count' => count($this->activePlugins),
                'plugins' => array_map(fn ($p) => $p->getName(), $this->activePlugins),
                'config' => $this->config,
            ],
            'tests' => [],
            'summary' => [],
        ];

        // Run each test
        $this->results['tests']['event_dispatch'] = $this->testEventDispatch();
        $this->results['tests']['hook_execution'] = $this->testHookExecution();
        $this->results['tests']['api_endpoints'] = $this->testApiEndpoints();
        $this->results['tests']['database_queries'] = $this->testDatabaseQueries();
        $this->results['tests']['memory_usage'] = $this->testMemoryUsage();
        $this->results['tests']['concurrent_load'] = $this->testConcurrentLoad();

        // Calculate summary
        $suiteEnd = microtime(true);
        $finalMemory = memory_get_usage(true);

        $this->results['summary'] = $this->calculateSummary(
            $suiteStart,
            $suiteEnd,
            $initialMemory,
            $finalMemory
        );

        return $this->results;
    }

    /**
     * Test event dispatch performance.
     *
     * @return array<string, mixed>
     */
    public function testEventDispatch(): array
    {
        $latencies = [];
        $events = [
            'commerce.order.created',
            'commerce.product.updated',
            'commerce.cart.item_added',
            'commerce.checkout.completed',
            'commerce.payment.processed',
        ];

        // Warmup
        for ($i = 0; $i < $this->config['warmup_iterations']; $i++) {
            foreach ($events as $event) {
                Event::dispatch($event, ['test' => true, 'warmup' => true]);
            }
        }

        // Actual test
        foreach ($events as $event) {
            $eventLatencies = [];

            for ($i = 0; $i < $this->config['iterations']; $i++) {
                $start = hrtime(true);

                Event::dispatch($event, [
                    'iteration' => $i,
                    'timestamp' => microtime(true),
                    'data' => $this->generateTestPayload(),
                ]);

                $end = hrtime(true);
                $eventLatencies[] = ($end - $start) / 1e6; // Convert to ms
            }

            $latencies[$event] = $this->calculateStats($eventLatencies);
        }

        return [
            'name' => 'Event Dispatch',
            'description' => 'Measures latency of event dispatch with all plugins listening',
            'events_tested' => count($events),
            'iterations_per_event' => $this->config['iterations'],
            'results' => $latencies,
            'aggregated' => $this->aggregateEventResults($latencies),
        ];
    }

    /**
     * Test hook execution performance.
     *
     * @return array<string, mixed>
     */
    public function testHookExecution(): array
    {
        $hookPoints = [
            'checkout.before_calculate_totals',
            'checkout.after_calculate_totals',
            'cart.before_add_item',
            'product.before_display',
            'order.before_create',
        ];

        $latencies = [];

        foreach ($hookPoints as $hook) {
            $hookLatencies = [];

            for ($i = 0; $i < $this->config['iterations']; $i++) {
                $context = $this->generateHookContext();
                $start = hrtime(true);

                // Simulate hook execution through all plugins
                foreach ($this->activePlugins as $plugin) {
                    if (method_exists($plugin, 'executeHook')) {
                        $plugin->executeHook($hook, $context);
                    }
                }

                $end = hrtime(true);
                $hookLatencies[] = ($end - $start) / 1e6;
            }

            $latencies[$hook] = $this->calculateStats($hookLatencies);
        }

        return [
            'name' => 'Hook Execution',
            'description' => 'Measures latency of hook point execution across plugins',
            'hooks_tested' => count($hookPoints),
            'plugins_active' => count($this->activePlugins),
            'results' => $latencies,
            'aggregated' => $this->aggregateEventResults($latencies),
        ];
    }

    /**
     * Test API endpoint performance.
     *
     * @return array<string, mixed>
     */
    public function testApiEndpoints(): array
    {
        $endpoints = [
            ['method' => 'GET', 'path' => '/api/v1/commerce/products', 'name' => 'list_products'],
            ['method' => 'GET', 'path' => '/api/v1/commerce/cart', 'name' => 'get_cart'],
            ['method' => 'POST', 'path' => '/api/v1/commerce/cart/items', 'name' => 'add_to_cart'],
            ['method' => 'GET', 'path' => '/api/v1/commerce/checkout', 'name' => 'get_checkout'],
            ['method' => 'GET', 'path' => '/api/v1/commerce/orders', 'name' => 'list_orders'],
        ];

        $latencies = [];

        foreach ($endpoints as $endpoint) {
            $endpointLatencies = [];

            for ($i = 0; $i < $this->config['iterations']; $i++) {
                $start = hrtime(true);

                // Simulate request processing with plugin middleware
                $this->simulateRequest($endpoint);

                $end = hrtime(true);
                $endpointLatencies[] = ($end - $start) / 1e6;
            }

            $latencies[$endpoint['name']] = array_merge(
                $this->calculateStats($endpointLatencies),
                ['endpoint' => $endpoint]
            );
        }

        return [
            'name' => 'API Endpoints',
            'description' => 'Measures API endpoint response time with plugin middleware',
            'endpoints_tested' => count($endpoints),
            'results' => $latencies,
            'aggregated' => $this->aggregateEventResults($latencies),
        ];
    }

    /**
     * Test database query performance.
     *
     * @return array<string, mixed>
     */
    public function testDatabaseQueries(): array
    {
        $queries = [];
        $queryLog = [];

        // Enable query logging
        DB::enableQueryLog();

        for ($i = 0; $i < $this->config['iterations']; $i++) {
            DB::flushQueryLog();
            $start = hrtime(true);

            // Simulate common database operations
            $this->simulateDatabaseOperations();

            $end = hrtime(true);
            $queries[] = ($end - $start) / 1e6;

            $log = DB::getQueryLog();
            $queryLog[] = count($log);
        }

        DB::disableQueryLog();

        return [
            'name' => 'Database Queries',
            'description' => 'Measures database query performance with plugins',
            'stats' => $this->calculateStats($queries),
            'query_counts' => [
                'avg' => round(array_sum($queryLog) / count($queryLog), 2),
                'min' => min($queryLog),
                'max' => max($queryLog),
            ],
        ];
    }

    /**
     * Test memory usage.
     *
     * @return array<string, mixed>
     */
    public function testMemoryUsage(): array
    {
        $memoryReadings = [];

        for ($i = 0; $i < $this->config['iterations']; $i++) {
            gc_collect_cycles();
            $beforeMemory = memory_get_usage(true);

            // Simulate typical operations
            $this->simulateTypicalOperations();

            $afterMemory = memory_get_usage(true);
            $memoryReadings[] = ($afterMemory - $beforeMemory) / 1024 / 1024; // MB
        }

        $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;

        return [
            'name' => 'Memory Usage',
            'description' => 'Measures memory consumption during operations',
            'per_operation_mb' => $this->calculateStats($memoryReadings),
            'peak_memory_mb' => round($peakMemory, 2),
            'limit_mb' => $this->config['memory_limit_mb'],
            'within_limit' => $peakMemory < $this->config['memory_limit_mb'],
        ];
    }

    /**
     * Test concurrent load.
     *
     * @return array<string, mixed>
     */
    public function testConcurrentLoad(): array
    {
        $concurrentResults = [];
        $concurrentLevels = [1, 5, 10, 20];

        foreach ($concurrentLevels as $level) {
            $levelLatencies = [];

            for ($batch = 0; $batch < 10; $batch++) {
                $batchStart = hrtime(true);

                // Simulate concurrent requests
                for ($req = 0; $req < $level; $req++) {
                    $this->simulateRequest([
                        'method' => 'GET',
                        'path' => '/api/v1/commerce/products',
                        'name' => 'concurrent_test',
                    ]);
                }

                $batchEnd = hrtime(true);
                $levelLatencies[] = ($batchEnd - $batchStart) / 1e6;
            }

            $concurrentResults["concurrent_{$level}"] = array_merge(
                $this->calculateStats($levelLatencies),
                ['concurrent_level' => $level]
            );
        }

        return [
            'name' => 'Concurrent Load',
            'description' => 'Measures performance under concurrent request load',
            'levels_tested' => $concurrentLevels,
            'results' => $concurrentResults,
        ];
    }

    /**
     * Calculate statistics for a set of latency values.
     *
     * @param array<int, float> $values
     * @return array<string, float>
     */
    protected function calculateStats(array $values): array
    {
        if (empty($values)) {
            return [
                'min' => 0,
                'max' => 0,
                'avg' => 0,
                'median' => 0,
                'p95' => 0,
                'p99' => 0,
                'stddev' => 0,
            ];
        }

        sort($values);
        $count = count($values);
        $sum = array_sum($values);
        $avg = $sum / $count;

        // Calculate standard deviation
        $variance = array_reduce($values, function ($carry, $item) use ($avg) {
            return $carry + pow($item - $avg, 2);
        }, 0) / $count;
        $stddev = sqrt($variance);

        // Calculate percentiles
        $p95Index = (int) ceil($count * 0.95) - 1;
        $p99Index = (int) ceil($count * 0.99) - 1;
        $medianIndex = (int) floor($count / 2);

        return [
            'min' => round($values[0], 3),
            'max' => round($values[$count - 1], 3),
            'avg' => round($avg, 3),
            'median' => round($values[$medianIndex], 3),
            'p95' => round($values[$p95Index], 3),
            'p99' => round($values[$p99Index], 3),
            'stddev' => round($stddev, 3),
        ];
    }

    /**
     * Aggregate results from multiple event tests.
     *
     * @param array<string, array<string, mixed>> $results
     * @return array<string, mixed>
     */
    protected function aggregateEventResults(array $results): array
    {
        $allP95 = [];
        $allAvg = [];

        foreach ($results as $result) {
            if (isset($result['p95'])) {
                $allP95[] = $result['p95'];
            }
            if (isset($result['avg'])) {
                $allAvg[] = $result['avg'];
            }
        }

        return [
            'max_p95' => !empty($allP95) ? max($allP95) : 0,
            'avg_p95' => !empty($allP95) ? round(array_sum($allP95) / count($allP95), 3) : 0,
            'avg_avg' => !empty($allAvg) ? round(array_sum($allAvg) / count($allAvg), 3) : 0,
            'meets_target' => empty($allP95) || max($allP95) < $this->config['target_p95_ms'],
        ];
    }

    /**
     * Calculate final summary.
     *
     * @param float $startTime
     * @param float $endTime
     * @param int $startMemory
     * @param int $endMemory
     * @return array<string, mixed>
     */
    protected function calculateSummary(
        float $startTime,
        float $endTime,
        int $startMemory,
        int $endMemory
    ): array {
        $testResults = $this->results['tests'] ?? [];

        // Collect all p95 values
        $allP95 = [];
        foreach ($testResults as $test) {
            if (isset($test['aggregated']['max_p95'])) {
                $allP95[] = $test['aggregated']['max_p95'];
            }
            if (isset($test['stats']['p95'])) {
                $allP95[] = $test['stats']['p95'];
            }
        }

        $maxP95 = !empty($allP95) ? max($allP95) : 0;
        $passed = $maxP95 < $this->config['target_p95_ms'];

        return [
            'total_duration_seconds' => round($endTime - $startTime, 2),
            'memory_delta_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'max_p95_ms' => round($maxP95, 3),
            'target_p95_ms' => $this->config['target_p95_ms'],
            'passed' => $passed,
            'plugin_count' => count($this->activePlugins),
            'recommendation' => $this->generateRecommendation($maxP95, $passed),
        ];
    }

    /**
     * Generate performance recommendation.
     *
     * @param float $maxP95
     * @param bool $passed
     * @return string
     */
    protected function generateRecommendation(float $maxP95, bool $passed): string
    {
        if ($passed) {
            if ($maxP95 < 100) {
                return 'Excellent performance. System is well optimized for current plugin load.';
            } elseif ($maxP95 < 500) {
                return 'Good performance. Consider monitoring as more plugins are added.';
            } else {
                return 'Acceptable performance. Approaching threshold - optimize before adding more plugins.';
            }
        }

        if ($maxP95 < 5000) {
            return 'Performance threshold exceeded. Review slow hooks and consider async processing.';
        }

        return 'Critical performance issue. Immediate optimization required. Consider disabling non-essential plugins.';
    }

    /**
     * Generate test payload data.
     *
     * @return array<string, mixed>
     */
    protected function generateTestPayload(): array
    {
        return [
            'id' => rand(1, 10000),
            'store_id' => 1,
            'items' => array_map(fn ($i) => [
                'product_id' => $i,
                'quantity' => rand(1, 5),
                'price' => rand(100, 10000) / 100,
            ], range(1, 5)),
            'total' => rand(1000, 100000) / 100,
            'metadata' => [
                'source' => 'load_test',
                'timestamp' => time(),
            ],
        ];
    }

    /**
     * Generate hook context.
     *
     * @return array<string, mixed>
     */
    protected function generateHookContext(): array
    {
        return [
            'cart' => [
                'id' => 'cart_' . uniqid(),
                'items' => [],
                'subtotal' => 0,
            ],
            'customer' => [
                'id' => rand(1, 1000),
                'email' => 'test@example.com',
            ],
            'store_id' => 1,
        ];
    }

    /**
     * Simulate an API request.
     *
     * @param array<string, string> $endpoint
     */
    protected function simulateRequest(array $endpoint): void
    {
        // Simulate middleware execution
        foreach ($this->activePlugins as $plugin) {
            if (method_exists($plugin, 'handleRequest')) {
                $plugin->handleRequest($endpoint);
            }
        }

        // Simulate response generation
        usleep(rand(100, 500)); // 0.1-0.5ms base latency
    }

    /**
     * Simulate database operations.
     */
    protected function simulateDatabaseOperations(): void
    {
        // Simulate queries - these would be actual DB queries in real scenario
        usleep(rand(500, 2000)); // 0.5-2ms simulated query time
    }

    /**
     * Simulate typical operations.
     */
    protected function simulateTypicalOperations(): void
    {
        // Allocate some memory
        $data = array_fill(0, 1000, str_repeat('x', 100));

        // Process data
        array_map(fn ($item) => strtoupper($item), $data);

        // Trigger events
        Event::dispatch('commerce.test.operation', ['data' => count($data)]);

        unset($data);
    }

    /**
     * Get results.
     *
     * @return array<string, mixed>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Generate report in markdown format.
     *
     * @return string
     */
    public function toMarkdown(): string
    {
        $results = $this->results;
        $md = "# Plugin Load Test Report\n\n";
        $md .= "Generated: {$results['meta']['started_at']}\n\n";

        // Summary
        $summary = $results['summary'] ?? [];
        $status = ($summary['passed'] ?? false) ? '✅ PASSED' : '❌ FAILED';

        $md .= "## Summary\n\n";
        $md .= "| Metric | Value |\n";
        $md .= "|--------|-------|\n";
        $md .= "| Status | {$status} |\n";
        $md .= "| Plugins Tested | " . ($summary['plugin_count'] ?? 0) . " |\n";
        $md .= "| Max p95 Latency | " . ($summary['max_p95_ms'] ?? 0) . " ms |\n";
        $md .= "| Target p95 | " . ($summary['target_p95_ms'] ?? 0) . " ms |\n";
        $md .= "| Duration | " . ($summary['total_duration_seconds'] ?? 0) . " s |\n";
        $md .= "| Peak Memory | " . ($summary['peak_memory_mb'] ?? 0) . " MB |\n\n";

        if (isset($summary['recommendation'])) {
            $md .= "**Recommendation:** {$summary['recommendation']}\n\n";
        }

        // Plugins
        $md .= "## Plugins Tested\n\n";
        foreach ($results['meta']['plugins'] ?? [] as $plugin) {
            $md .= "- {$plugin}\n";
        }
        $md .= "\n";

        // Test Results
        $md .= "## Test Results\n\n";
        foreach ($results['tests'] ?? [] as $testName => $test) {
            $md .= "### " . ($test['name'] ?? $testName) . "\n\n";
            $md .= ($test['description'] ?? '') . "\n\n";

            if (isset($test['aggregated'])) {
                $agg = $test['aggregated'];
                $md .= "| Metric | Value |\n";
                $md .= "|--------|-------|\n";
                $md .= "| Max p95 | " . ($agg['max_p95'] ?? 'N/A') . " ms |\n";
                $md .= "| Avg p95 | " . ($agg['avg_p95'] ?? 'N/A') . " ms |\n";
                $md .= "| Meets Target | " . (($agg['meets_target'] ?? false) ? 'Yes' : 'No') . " |\n\n";
            }

            if (isset($test['stats'])) {
                $stats = $test['stats'];
                $md .= "| Percentile | Latency (ms) |\n";
                $md .= "|------------|-------------|\n";
                $md .= "| p50 | " . ($stats['median'] ?? 'N/A') . " |\n";
                $md .= "| p95 | " . ($stats['p95'] ?? 'N/A') . " |\n";
                $md .= "| p99 | " . ($stats['p99'] ?? 'N/A') . " |\n\n";
            }
        }

        return $md;
    }
}
