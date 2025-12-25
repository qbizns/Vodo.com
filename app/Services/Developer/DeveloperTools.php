<?php

declare(strict_types=1);

namespace App\Services\Developer;

use App\Contracts\DeveloperToolsContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Developer Tools
 *
 * Provides debugging, profiling, and development utilities.
 * Essential for development and troubleshooting.
 *
 * @example Start profiling
 * ```php
 * $tools->startTimer('my-operation');
 * // ... do work ...
 * $elapsed = $tools->stopTimer('my-operation');
 * ```
 *
 * @example Log debug info
 * ```php
 * $tools->log('api', 'Request processed', ['endpoint' => '/users', 'time' => 123]);
 * ```
 */
class DeveloperTools implements DeveloperToolsContract
{
    /**
     * Active timers.
     *
     * @var array<string, float>
     */
    protected array $timers = [];

    /**
     * Completed timer results.
     *
     * @var array<string, float>
     */
    protected array $timerResults = [];

    /**
     * Query log.
     *
     * @var array
     */
    protected array $queries = [];

    /**
     * Debug log.
     *
     * @var array
     */
    protected array $debugLog = [];

    /**
     * Dump log.
     *
     * @var array
     */
    protected array $dumps = [];

    /**
     * Is profiling enabled.
     */
    protected bool $profilingEnabled = false;

    public function __construct()
    {
        $this->profilingEnabled = config('app.debug', false);
    }

    public function log(string $channel, string $message, array $context = []): void
    {
        $entry = [
            'timestamp' => now()->toIso8601String(),
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
        ];

        $this->debugLog[] = $entry;

        // Also log to Laravel log
        if ($this->profilingEnabled) {
            Log::channel('debug')->debug("[{$channel}] {$message}", $context);
        }
    }

    public function startTimer(string $name): void
    {
        $this->timers[$name] = microtime(true);
    }

    public function stopTimer(string $name): float
    {
        if (!isset($this->timers[$name])) {
            return 0.0;
        }

        $elapsed = (microtime(true) - $this->timers[$name]) * 1000; // Convert to ms
        $this->timerResults[$name] = $elapsed;
        unset($this->timers[$name]);

        return $elapsed;
    }

    public function getProfilingData(): array
    {
        return [
            'timers' => $this->timerResults,
            'queries' => [
                'count' => count($this->queries),
                'total_time' => array_sum(array_column($this->queries, 'time')),
                'queries' => $this->queries,
            ],
            'memory' => [
                'current' => $this->formatBytes(memory_get_usage(true)),
                'peak' => $this->formatBytes(memory_get_peak_usage(true)),
            ],
            'debug_log' => $this->debugLog,
            'dumps' => $this->dumps,
        ];
    }

    public function traceQuery(string $sql, array $bindings, float $time): void
    {
        if (!$this->profilingEnabled) {
            return;
        }

        $this->queries[] = [
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'timestamp' => now()->toIso8601String(),
            'backtrace' => $this->getBacktrace(),
        ];
    }

    public function getQueryLog(): Collection
    {
        return collect($this->queries);
    }

    public function dump(mixed $value, ?string $label = null): void
    {
        $this->dumps[] = [
            'label' => $label,
            'type' => gettype($value),
            'value' => $this->formatValue($value),
            'timestamp' => now()->toIso8601String(),
            'backtrace' => $this->getBacktrace(1),
        ];
    }

    public function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => App::version(),
            'environment' => App::environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => App::getLocale(),
            'server' => [
                'os' => PHP_OS,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? base_path(),
            ],
            'database' => [
                'driver' => config('database.default'),
                'connection' => $this->getDatabaseInfo(),
            ],
            'cache' => [
                'driver' => config('cache.default'),
            ],
            'session' => [
                'driver' => config('session.driver'),
            ],
            'queue' => [
                'driver' => config('queue.default'),
            ],
        ];
    }

    public function getRegisteredServices(): Collection
    {
        $container = App::getBindings();

        return collect($container)->keys()->sort()->values();
    }

    public function getPluginInfo(): Collection
    {
        try {
            $pluginManager = app('plugin.manager');

            return collect($pluginManager->all())->map(fn($plugin) => [
                'name' => $plugin->getName(),
                'version' => $plugin->getVersion(),
                'enabled' => $plugin->isEnabled(),
                'path' => $plugin->getPath(),
            ]);
        } catch (\Exception $e) {
            return collect();
        }
    }

    public function validateConfiguration(): array
    {
        $issues = [];
        $warnings = [];

        // Check app key
        if (empty(config('app.key'))) {
            $issues[] = 'Application key is not set';
        }

        // Check debug mode in production
        if (App::environment('production') && config('app.debug')) {
            $warnings[] = 'Debug mode is enabled in production';
        }

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $issues[] = 'Database connection failed: ' . $e->getMessage();
        }

        // Check storage permissions
        if (!is_writable(storage_path())) {
            $issues[] = 'Storage directory is not writable';
        }

        // Check cache
        try {
            Cache::put('dev_tools_test', 'test', 1);
            Cache::forget('dev_tools_test');
        } catch (\Exception $e) {
            $warnings[] = 'Cache driver issue: ' . $e->getMessage();
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    public function generateDiagnosticReport(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'system' => $this->getSystemInfo(),
            'configuration' => $this->validateConfiguration(),
            'plugins' => $this->getPluginInfo()->toArray(),
            'services' => $this->getRegisteredServices()->count(),
            'profiling' => $this->getProfilingData(),
            'routes' => $this->getRouteInfo(),
        ];
    }

    public function clearCaches(array $types = []): array
    {
        $results = [];

        if (empty($types) || in_array('config', $types)) {
            try {
                Artisan::call('config:clear');
                $results['config'] = 'cleared';
            } catch (\Exception $e) {
                $results['config'] = 'failed: ' . $e->getMessage();
            }
        }

        if (empty($types) || in_array('cache', $types)) {
            try {
                Cache::flush();
                $results['cache'] = 'cleared';
            } catch (\Exception $e) {
                $results['cache'] = 'failed: ' . $e->getMessage();
            }
        }

        if (empty($types) || in_array('route', $types)) {
            try {
                Artisan::call('route:clear');
                $results['route'] = 'cleared';
            } catch (\Exception $e) {
                $results['route'] = 'failed: ' . $e->getMessage();
            }
        }

        if (empty($types) || in_array('view', $types)) {
            try {
                Artisan::call('view:clear');
                $results['view'] = 'cleared';
            } catch (\Exception $e) {
                $results['view'] = 'failed: ' . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Enable query logging.
     */
    public function enableQueryLogging(): void
    {
        $this->profilingEnabled = true;
        DB::listen(function ($query) {
            $this->traceQuery($query->sql, $query->bindings, $query->time);
        });
    }

    /**
     * Disable query logging.
     */
    public function disableQueryLogging(): void
    {
        $this->profilingEnabled = false;
    }

    /**
     * Get backtrace info.
     */
    protected function getBacktrace(int $skip = 2): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $skip + 3);

        return array_slice($trace, $skip, 3);
    }

    /**
     * Format value for dumping.
     */
    protected function formatValue(mixed $value): mixed
    {
        if (is_object($value)) {
            return [
                'class' => get_class($value),
                'properties' => get_object_vars($value),
            ];
        }

        if (is_array($value) && count($value) > 100) {
            return [
                'type' => 'array',
                'count' => count($value),
                'sample' => array_slice($value, 0, 10),
            ];
        }

        return $value;
    }

    /**
     * Format bytes to human readable.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get database connection info.
     */
    protected function getDatabaseInfo(): array
    {
        try {
            $connection = DB::connection();

            return [
                'driver' => $connection->getDriverName(),
                'database' => $connection->getDatabaseName(),
                'host' => config('database.connections.' . config('database.default') . '.host'),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get route information.
     */
    protected function getRouteInfo(): array
    {
        try {
            $routes = app('router')->getRoutes();

            return [
                'total' => count($routes),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Reset all profiling data.
     */
    public function reset(): void
    {
        $this->timers = [];
        $this->timerResults = [];
        $this->queries = [];
        $this->debugLog = [];
        $this->dumps = [];
    }
}
