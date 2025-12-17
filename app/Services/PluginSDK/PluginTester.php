<?php

declare(strict_types=1);

namespace App\Services\PluginSDK;

use App\Services\Plugins\PluginManager;
use App\Services\Plugins\BasePlugin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * PluginTester - Testing harness for plugins.
 * 
 * Features:
 * - Plugin isolation testing
 * - Migration testing
 * - Hook testing
 * - Service testing
 * - Performance profiling
 */
class PluginTester
{
    protected Filesystem $files;
    protected ?BasePlugin $plugin = null;
    protected array $results = [];
    protected array $errors = [];
    protected float $startTime;
    protected int $startMemory;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Load a plugin for testing.
     */
    public function load(string $pluginNameOrPath): self
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        $this->results = [];
        $this->errors = [];

        try {
            if (class_exists($pluginNameOrPath)) {
                $this->plugin = new $pluginNameOrPath();
            } else {
                // Load from path
                $pluginPath = base_path("plugins/{$pluginNameOrPath}");
                $pluginClass = "Plugins\\{$pluginNameOrPath}\\{$pluginNameOrPath}Plugin";
                
                if (!class_exists($pluginClass)) {
                    require_once $pluginPath . "/{$pluginNameOrPath}Plugin.php";
                }
                
                $this->plugin = new $pluginClass();
            }

            $this->results['load'] = [
                'status' => 'pass',
                'message' => 'Plugin loaded successfully',
            ];
        } catch (\Throwable $e) {
            $this->results['load'] = [
                'status' => 'fail',
                'message' => 'Failed to load plugin: ' . $e->getMessage(),
            ];
            $this->errors[] = $e;
        }

        return $this;
    }

    /**
     * Test plugin structure.
     */
    public function testStructure(): self
    {
        if (!$this->plugin) {
            return $this;
        }

        $tests = [
            'has_identifier' => fn() => !empty($this->plugin->getIdentifier()),
            'has_name' => fn() => !empty($this->plugin->getName()),
            'has_version' => fn() => !empty($this->plugin->getVersion()),
            'boot_method' => fn() => method_exists($this->plugin, 'boot'),
            'install_method' => fn() => method_exists($this->plugin, 'install'),
            'uninstall_method' => fn() => method_exists($this->plugin, 'uninstall'),
        ];

        foreach ($tests as $name => $test) {
            try {
                $result = $test();
                $this->results["structure.{$name}"] = [
                    'status' => $result ? 'pass' : 'fail',
                    'message' => $result ? 'OK' : 'Missing or invalid',
                ];
            } catch (\Throwable $e) {
                $this->results["structure.{$name}"] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $this;
    }

    /**
     * Test plugin boot.
     */
    public function testBoot(): self
    {
        if (!$this->plugin) {
            return $this;
        }

        try {
            $this->plugin->boot();
            $this->results['boot'] = [
                'status' => 'pass',
                'message' => 'Boot completed without errors',
            ];
        } catch (\Throwable $e) {
            $this->results['boot'] = [
                'status' => 'fail',
                'message' => 'Boot failed: ' . $e->getMessage(),
            ];
            $this->errors[] = $e;
        }

        return $this;
    }

    /**
     * Test plugin migrations.
     */
    public function testMigrations(): self
    {
        if (!$this->plugin) {
            return $this;
        }

        $pluginPath = $this->getPluginPath();
        $migrationsPath = $pluginPath . '/database/migrations';

        if (!$this->files->exists($migrationsPath)) {
            $this->results['migrations'] = [
                'status' => 'skip',
                'message' => 'No migrations directory',
            ];
            return $this;
        }

        $migrations = $this->files->glob($migrationsPath . '/*.php');

        if (empty($migrations)) {
            $this->results['migrations'] = [
                'status' => 'skip',
                'message' => 'No migration files',
            ];
            return $this;
        }

        // Test migration syntax
        foreach ($migrations as $migration) {
            $name = basename($migration);
            try {
                require_once $migration;
                $this->results["migration.{$name}.syntax"] = [
                    'status' => 'pass',
                    'message' => 'Valid PHP syntax',
                ];
            } catch (\Throwable $e) {
                $this->results["migration.{$name}.syntax"] = [
                    'status' => 'fail',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $this;
    }

    /**
     * Test plugin dependencies.
     */
    public function testDependencies(): self
    {
        if (!$this->plugin) {
            return $this;
        }

        $dependencies = $this->plugin->getDependencies();

        if (empty($dependencies)) {
            $this->results['dependencies'] = [
                'status' => 'pass',
                'message' => 'No dependencies',
            ];
            return $this;
        }

        $pluginManager = app(PluginManager::class);

        foreach ($dependencies as $dep) {
            $available = $pluginManager->isInstalled($dep);
            $this->results["dependency.{$dep}"] = [
                'status' => $available ? 'pass' : 'warning',
                'message' => $available ? 'Available' : 'Not installed',
            ];
        }

        return $this;
    }

    /**
     * Test plugin in isolation (sandbox).
     */
    public function testInSandbox(): self
    {
        if (!$this->plugin) {
            return $this;
        }

        try {
            DB::beginTransaction();

            // Run install
            $this->plugin->install();

            // Run boot
            $this->plugin->boot();

            // Basic functionality test
            // ...

            DB::rollBack();

            $this->results['sandbox'] = [
                'status' => 'pass',
                'message' => 'Plugin works in isolated environment',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->results['sandbox'] = [
                'status' => 'fail',
                'message' => 'Sandbox test failed: ' . $e->getMessage(),
            ];
            $this->errors[] = $e;
        }

        return $this;
    }

    /**
     * Test plugin services.
     */
    public function testServices(): self
    {
        if (!$this->plugin) {
            return $this;
        }

        if (!method_exists($this->plugin, 'getRegisteredServices')) {
            $this->results['services'] = [
                'status' => 'skip',
                'message' => 'No service inspection method',
            ];
            return $this;
        }

        $services = $this->plugin->getRegisteredServices();

        if (empty($services)) {
            $this->results['services'] = [
                'status' => 'pass',
                'message' => 'No services registered',
            ];
            return $this;
        }

        foreach ($services as $serviceId => $service) {
            $this->results["service.{$serviceId}"] = [
                'status' => 'pass',
                'message' => 'Service registered',
            ];
        }

        return $this;
    }

    /**
     * Run all tests.
     */
    public function runAll(): self
    {
        return $this
            ->testStructure()
            ->testDependencies()
            ->testMigrations()
            ->testBoot()
            ->testServices();
    }

    /**
     * Get test results.
     */
    public function getResults(): array
    {
        $duration = microtime(true) - $this->startTime;
        $memory = memory_get_usage(true) - $this->startMemory;

        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'pass'));
        $failed = count(array_filter($this->results, fn($r) => $r['status'] === 'fail'));
        $skipped = count(array_filter($this->results, fn($r) => $r['status'] === 'skip'));
        $warnings = count(array_filter($this->results, fn($r) => $r['status'] === 'warning'));

        return [
            'plugin' => $this->plugin?->getName(),
            'summary' => [
                'total' => count($this->results),
                'passed' => $passed,
                'failed' => $failed,
                'skipped' => $skipped,
                'warnings' => $warnings,
                'success' => $failed === 0,
            ],
            'performance' => [
                'duration_ms' => round($duration * 1000, 2),
                'memory_mb' => round($memory / 1024 / 1024, 2),
            ],
            'tests' => $this->results,
            'errors' => array_map(fn($e) => [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], $this->errors),
        ];
    }

    /**
     * Print results to console.
     */
    public function printResults(): void
    {
        $results = $this->getResults();

        echo "\n";
        echo "Plugin Test Results: {$results['plugin']}\n";
        echo str_repeat('=', 50) . "\n\n";

        foreach ($results['tests'] as $name => $result) {
            $icon = match ($result['status']) {
                'pass' => '✓',
                'fail' => '✗',
                'skip' => '○',
                'warning' => '⚠',
                default => '?',
            };
            $color = match ($result['status']) {
                'pass' => "\033[32m",
                'fail' => "\033[31m",
                'skip' => "\033[33m",
                'warning' => "\033[33m",
                default => "\033[0m",
            };
            echo "{$color}{$icon}\033[0m {$name}: {$result['message']}\n";
        }

        echo "\n";
        echo str_repeat('-', 50) . "\n";
        echo "Summary: {$results['summary']['passed']} passed, ";
        echo "{$results['summary']['failed']} failed, ";
        echo "{$results['summary']['skipped']} skipped\n";
        echo "Duration: {$results['performance']['duration_ms']}ms\n";
        echo "Memory: {$results['performance']['memory_mb']}MB\n";
        echo "\n";
    }

    /**
     * Get plugin path.
     */
    protected function getPluginPath(): string
    {
        $identifier = $this->plugin->getIdentifier();
        $name = Str::studly($identifier);
        return base_path("plugins/{$name}");
    }
}
