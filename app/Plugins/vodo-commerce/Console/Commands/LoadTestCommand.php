<?php

declare(strict_types=1);

namespace VodoCommerce\Console\Commands;

use Illuminate\Console\Command;
use VodoCommerce\Contracts\CommercePluginContract;
use VodoCommerce\Events\CommerceEventRegistry;
use VodoCommerce\Services\PluginLoadTester;

/**
 * Plugin Load Test Command
 *
 * Runs performance tests with multiple plugins active.
 */
class LoadTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commerce:load-test
                            {--plugins=10 : Number of mock plugins to test with}
                            {--iterations=100 : Number of iterations per test}
                            {--target=3000 : Target p95 latency in milliseconds}
                            {--output= : Output file for report (markdown)}
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run load tests with multiple plugins active';

    public function __construct(
        protected CommerceEventRegistry $eventRegistry
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pluginCount = (int) $this->option('plugins');
        $iterations = (int) $this->option('iterations');
        $targetP95 = (int) $this->option('target');

        $this->info("Commerce Platform Load Test");
        $this->info("===========================");
        $this->newLine();
        $this->info("Configuration:");
        $this->line("  Plugins: {$pluginCount}");
        $this->line("  Iterations: {$iterations}");
        $this->line("  Target p95: {$targetP95}ms");
        $this->newLine();

        // Create load tester
        $tester = new PluginLoadTester($this->eventRegistry);
        $tester->configure([
            'iterations' => $iterations,
            'target_p95_ms' => $targetP95,
        ]);

        // Register mock plugins
        $this->info("Registering {$pluginCount} mock plugins...");
        $plugins = $this->createMockPlugins($pluginCount);
        foreach ($plugins as $plugin) {
            $tester->registerPlugin($plugin);
        }
        $this->info("Registered " . count($plugins) . " plugins.");
        $this->newLine();

        // Run tests
        $this->info("Running load tests...");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(6);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');

        $progressBar->setMessage('Testing event dispatch...');
        $progressBar->start();

        $results = $tester->runFullSuite();

        $progressBar->setMessage('Complete!');
        $progressBar->finish();
        $this->newLine(2);

        // Display results
        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
        } else {
            $this->displayResults($results);
        }

        // Save to file if requested
        if ($outputFile = $this->option('output')) {
            $markdown = $tester->toMarkdown();
            file_put_contents($outputFile, $markdown);
            $this->info("Report saved to: {$outputFile}");
        }

        // Return exit code based on pass/fail
        $passed = $results['summary']['passed'] ?? false;

        return $passed ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Create mock plugins for testing.
     *
     * @param int $count
     * @return array<int, CommercePluginContract>
     */
    protected function createMockPlugins(int $count): array
    {
        $plugins = [];
        $types = ['payment', 'shipping', 'tax', 'analytics', 'marketing', 'inventory', 'crm', 'reporting'];

        for ($i = 1; $i <= $count; $i++) {
            $type = $types[($i - 1) % count($types)];
            $plugins[] = new MockLoadTestPlugin(
                name: "MockPlugin{$i}",
                type: $type,
                latencyMs: rand(1, 10),
                memoryKb: rand(100, 500)
            );
        }

        return $plugins;
    }

    /**
     * Display test results.
     *
     * @param array<string, mixed> $results
     */
    protected function displayResults(array $results): void
    {
        $summary = $results['summary'] ?? [];

        // Summary table
        $this->info("Test Summary");
        $this->info("============");
        $this->newLine();

        $passed = $summary['passed'] ?? false;
        $status = $passed ? '<fg=green>PASSED</>' : '<fg=red>FAILED</>';

        $this->line("Status:         {$status}");
        $this->line("Plugins Tested: " . ($summary['plugin_count'] ?? 0));
        $this->line("Max p95:        " . ($summary['max_p95_ms'] ?? 0) . " ms");
        $this->line("Target p95:     " . ($summary['target_p95_ms'] ?? 0) . " ms");
        $this->line("Duration:       " . ($summary['total_duration_seconds'] ?? 0) . " seconds");
        $this->line("Peak Memory:    " . ($summary['peak_memory_mb'] ?? 0) . " MB");
        $this->newLine();

        if (isset($summary['recommendation'])) {
            $this->info("Recommendation:");
            $this->line($summary['recommendation']);
            $this->newLine();
        }

        // Test results table
        $this->info("Test Results");
        $this->info("============");
        $this->newLine();

        $rows = [];
        foreach ($results['tests'] ?? [] as $testName => $test) {
            $p95 = 'N/A';
            $meetsTarget = true;

            if (isset($test['aggregated']['max_p95'])) {
                $p95 = $test['aggregated']['max_p95'] . ' ms';
                $meetsTarget = $test['aggregated']['meets_target'] ?? true;
            } elseif (isset($test['stats']['p95'])) {
                $p95 = $test['stats']['p95'] . ' ms';
            }

            $rows[] = [
                $test['name'] ?? $testName,
                $p95,
                $meetsTarget ? '<fg=green>Yes</>' : '<fg=red>No</>',
            ];
        }

        $this->table(['Test', 'p95 Latency', 'Meets Target'], $rows);
    }
}

/**
 * Mock plugin for load testing.
 */
class MockLoadTestPlugin implements CommercePluginContract
{
    protected bool $isEnabled = true;

    public function __construct(
        protected string $name,
        protected string $type,
        protected int $latencyMs,
        protected int $memoryKb
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function boot(): void
    {
        // Simulate boot time
        usleep($this->latencyMs * 100);
    }

    public function register(): void
    {
        // Simulate registration
        usleep($this->latencyMs * 50);
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function enable(): void
    {
        $this->isEnabled = true;
    }

    public function disable(): void
    {
        $this->isEnabled = false;
    }

    public function executeHook(string $hook, array $context): array
    {
        // Simulate hook processing time
        usleep($this->latencyMs * 100);

        // Simulate memory usage
        $data = str_repeat('x', $this->memoryKb * 1024);
        unset($data);

        return $context;
    }

    public function handleRequest(array $endpoint): void
    {
        // Simulate request handling
        usleep($this->latencyMs * 50);
    }

    public function getMetadata(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'version' => '1.0.0',
            'latency_ms' => $this->latencyMs,
            'memory_kb' => $this->memoryKb,
        ];
    }
}
