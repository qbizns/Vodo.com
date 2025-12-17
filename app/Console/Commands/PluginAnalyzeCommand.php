<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PluginSDK\PluginAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Command to analyze a plugin for issues.
 */
class PluginAnalyzeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'plugin:analyze 
                            {plugin : The plugin name}
                            {--json : Output as JSON}
                            {--fix : Attempt to auto-fix issues}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze a plugin for issues, security problems, and best practices';

    /**
     * Execute the console command.
     */
    public function handle(PluginAnalyzer $analyzer): int
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $pluginPath = base_path("plugins/{$pluginName}");

        $this->info("Analyzing plugin: {$pluginName}");
        $this->newLine();

        $results = $analyzer->analyze($pluginPath);

        if (isset($results['error'])) {
            $this->error($results['error']);
            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
            return $results['score'] >= 70 ? self::SUCCESS : self::FAILURE;
        }

        $this->displayResults($results);

        return $results['score'] >= 70 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Display analysis results.
     */
    protected function displayResults(array $results): void
    {
        // Score
        $score = $results['score'];
        $scoreColor = match (true) {
            $score >= 80 => 'green',
            $score >= 60 => 'yellow',
            default => 'red',
        };

        $this->line(str_repeat('─', 50));
        $this->line(sprintf("  Score: <fg={$scoreColor};options=bold>%d/100</>", $score));
        $this->line(str_repeat('─', 50));
        $this->newLine();

        // Issues
        if (!empty($results['issues'])) {
            $this->error("Issues (" . count($results['issues']) . "):");
            foreach ($results['issues'] as $issue) {
                $severity = $issue['severity'] ?? 'warning';
                $icon = $severity === 'error' ? '✗' : '⚠';
                $color = $severity === 'error' ? 'red' : 'yellow';
                
                $this->line(sprintf(
                    "  <fg={$color}>{$icon}</> [%s] %s",
                    $issue['type'],
                    $issue['message']
                ));
                
                if (isset($issue['file'])) {
                    $this->line("    <fg=gray>File: {$issue['file']}</>");
                }
            }
            $this->newLine();
        }

        // Warnings
        if (!empty($results['warnings'])) {
            $this->warn("Warnings (" . count($results['warnings']) . "):");
            foreach ($results['warnings'] as $warning) {
                $this->line(sprintf(
                    "  <fg=yellow>⚠</> [%s] %s",
                    $warning['type'],
                    $warning['message']
                ));
                
                if (isset($warning['file'])) {
                    $this->line("    <fg=gray>File: {$warning['file']}</>");
                }
            }
            $this->newLine();
        }

        // Info
        if (!empty($results['info'])) {
            $this->info("Info (" . count($results['info']) . "):");
            foreach ($results['info'] as $info) {
                $this->line(sprintf(
                    "  <fg=blue>ℹ</> [%s] %s",
                    $info['type'],
                    $info['message']
                ));
            }
            $this->newLine();
        }

        // Summary
        $this->line(str_repeat('─', 50));
        $this->line(sprintf(
            "  %d issues, %d warnings, %d info",
            count($results['issues']),
            count($results['warnings']),
            count($results['info'])
        ));

        if ($score >= 80) {
            $this->info("  ✓ Plugin passes quality checks");
        } elseif ($score >= 60) {
            $this->warn("  ⚠ Plugin has some issues to address");
        } else {
            $this->error("  ✗ Plugin needs significant improvements");
        }
    }
}
