<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PluginSDK\PluginTester;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Command to test a plugin.
 */
class PluginTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'plugin:test 
                            {plugin : The plugin name}
                            {--sandbox : Run in sandbox mode (isolated)}
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Test a plugin';

    /**
     * Execute the console command.
     */
    public function handle(PluginTester $tester): int
    {
        $pluginName = Str::studly($this->argument('plugin'));

        $this->info("Testing plugin: {$pluginName}");
        $this->newLine();

        try {
            $tester->load($pluginName);
            $tester->runAll();

            if ($this->option('sandbox')) {
                $tester->testInSandbox();
            }

            $results = $tester->getResults();

            if ($this->option('json')) {
                $this->line(json_encode($results, JSON_PRETTY_PRINT));
            } else {
                $this->displayResults($results);
            }

            return $results['summary']['success'] ? self::SUCCESS : self::FAILURE;

        } catch (\Throwable $e) {
            $this->error("Test failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Display test results.
     */
    protected function displayResults(array $results): void
    {
        $this->line("Plugin: {$results['plugin']}");
        $this->line(str_repeat('─', 50));
        $this->newLine();

        foreach ($results['tests'] as $name => $result) {
            $icon = match ($result['status']) {
                'pass' => '<fg=green>✓</>',
                'fail' => '<fg=red>✗</>',
                'skip' => '<fg=yellow>○</>',
                'warning' => '<fg=yellow>⚠</>',
                default => '?',
            };

            $this->line("  {$icon} {$name}");
            if ($result['status'] !== 'pass' && !empty($result['message'])) {
                $this->line("    <fg=gray>{$result['message']}</>");
            }
        }

        $this->newLine();
        $this->line(str_repeat('─', 50));

        $summary = $results['summary'];
        $color = $summary['success'] ? 'green' : 'red';
        
        $this->line(sprintf(
            "  <fg={$color}>%d passed</>, %d failed, %d skipped",
            $summary['passed'],
            $summary['failed'],
            $summary['skipped']
        ));

        $this->line(sprintf(
            "  Duration: %sms | Memory: %sMB",
            $results['performance']['duration_ms'],
            $results['performance']['memory_mb']
        ));

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error("Errors:");
            foreach ($results['errors'] as $error) {
                $this->line("  - {$error['message']}");
                $this->line("    <fg=gray>{$error['file']}:{$error['line']}</>");
            }
        }
    }
}
