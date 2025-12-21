<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;

/**
 * Production Readiness Check Command
 *
 * Validates that the application is properly configured for production.
 * Checks security settings, performance optimizations, and service connectivity.
 *
 * Usage:
 *   php artisan production:check        # Run all checks
 *   php artisan production:check --fix  # Attempt to fix issues
 */
class ProductionCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'production:check
                            {--fix : Attempt to automatically fix issues}
                            {--json : Output results as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Check if the application is ready for production deployment';

    /**
     * Check results.
     */
    protected array $results = [
        'passed' => [],
        'warnings' => [],
        'failed' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running production readiness checks...');
        $this->newLine();

        // Environment checks
        $this->checkEnvironment();

        // Security checks
        $this->checkSecurity();

        // Performance checks
        $this->checkPerformance();

        // Service connectivity checks
        $this->checkServices();

        // Output results
        if ($this->option('json')) {
            $this->outputJson();
        } else {
            $this->outputTable();
        }

        // Return appropriate exit code
        if (!empty($this->results['failed'])) {
            $this->newLine();
            $this->error('Production readiness check FAILED. Fix the issues above before deploying.');
            return Command::FAILURE;
        }

        if (!empty($this->results['warnings'])) {
            $this->newLine();
            $this->warn('Production readiness check passed with warnings.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('All production readiness checks PASSED!');
        return Command::SUCCESS;
    }

    /**
     * Check environment configuration.
     */
    protected function checkEnvironment(): void
    {
        $this->info('Checking environment...');

        // APP_ENV must be production
        if (config('app.env') === 'production') {
            $this->pass('APP_ENV', 'Set to production');
        } else {
            $this->fail('APP_ENV', 'Must be "production", currently: ' . config('app.env'));
        }

        // APP_DEBUG must be false
        if (config('app.debug') === false) {
            $this->pass('APP_DEBUG', 'Disabled');
        } else {
            $this->fail('APP_DEBUG', 'Must be disabled in production');
        }

        // APP_KEY must be set
        if (!empty(config('app.key'))) {
            $this->pass('APP_KEY', 'Set');
        } else {
            $this->fail('APP_KEY', 'Not set - run "php artisan key:generate"');
        }

        // APP_URL must not be localhost
        $url = config('app.url');
        if ($url && !str_contains($url, 'localhost') && !str_contains($url, '127.0.0.1')) {
            $this->pass('APP_URL', $url);
        } else {
            $this->warn('APP_URL', 'Should be set to production URL, currently: ' . $url);
        }

        // Check log level
        $logLevel = config('logging.channels.' . config('logging.default') . '.level', config('logging.level'));
        if (in_array($logLevel, ['warning', 'error', 'critical', 'alert', 'emergency'])) {
            $this->pass('LOG_LEVEL', $logLevel);
        } else {
            $this->warn('LOG_LEVEL', "Should be 'warning' or higher in production, currently: {$logLevel}");
        }
    }

    /**
     * Check security configuration.
     */
    protected function checkSecurity(): void
    {
        $this->info('Checking security...');

        // Session encryption
        if (config('session.encrypt') === true) {
            $this->pass('SESSION_ENCRYPT', 'Enabled');
        } else {
            $this->fail('SESSION_ENCRYPT', 'Must be enabled in production');
        }

        // Secure cookies
        if (config('session.secure') === true) {
            $this->pass('SESSION_SECURE_COOKIE', 'Enabled');
        } else {
            $this->warn('SESSION_SECURE_COOKIE', 'Should be enabled when using HTTPS');
        }

        // HTTP-only cookies
        if (config('session.http_only') === true) {
            $this->pass('SESSION_HTTP_ONLY', 'Enabled');
        } else {
            $this->fail('SESSION_HTTP_ONLY', 'Must be enabled to prevent XSS cookie access');
        }

        // Same-site cookies
        $sameSite = config('session.same_site');
        if (in_array($sameSite, ['strict', 'lax'])) {
            $this->pass('SESSION_SAME_SITE', $sameSite);
        } else {
            $this->warn('SESSION_SAME_SITE', "Should be 'lax' or 'strict', currently: {$sameSite}");
        }

        // HTTPS enforcement
        if (config('app.force_https', false) === true) {
            $this->pass('FORCE_HTTPS', 'Enabled');
        } else {
            $this->warn('FORCE_HTTPS', 'Consider enabling HTTPS enforcement');
        }

        // Check for debug routes
        if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            if (config('telescope.enabled') === false) {
                $this->pass('TELESCOPE', 'Disabled');
            } else {
                $this->warn('TELESCOPE', 'Should be disabled in production');
            }
        }
    }

    /**
     * Check performance configuration.
     */
    protected function checkPerformance(): void
    {
        $this->info('Checking performance...');

        // Config cache
        if (app()->configurationIsCached()) {
            $this->pass('CONFIG_CACHE', 'Cached');
        } else {
            $this->warn('CONFIG_CACHE', 'Not cached - run "php artisan config:cache"');
        }

        // Route cache
        if (app()->routesAreCached()) {
            $this->pass('ROUTE_CACHE', 'Cached');
        } else {
            $this->warn('ROUTE_CACHE', 'Not cached - run "php artisan route:cache"');
        }

        // View cache
        $viewCachePath = config('view.compiled');
        $cachedViews = glob($viewCachePath . '/*.php');
        if (count($cachedViews) > 0) {
            $this->pass('VIEW_CACHE', count($cachedViews) . ' views compiled');
        } else {
            $this->warn('VIEW_CACHE', 'No views cached - run "php artisan view:cache"');
        }

        // OPcache
        if (function_exists('opcache_get_status')) {
            $status = @opcache_get_status();
            if ($status && $status['opcache_enabled']) {
                $this->pass('OPCACHE', 'Enabled');
            } else {
                $this->warn('OPCACHE', 'Not enabled - enable for better performance');
            }
        } else {
            $this->warn('OPCACHE', 'Extension not loaded');
        }

        // Cache driver
        $cacheDriver = config('cache.default');
        if (in_array($cacheDriver, ['redis', 'memcached'])) {
            $this->pass('CACHE_DRIVER', $cacheDriver);
        } else {
            $this->warn('CACHE_DRIVER', "Using '{$cacheDriver}' - consider Redis for production");
        }

        // Session driver
        $sessionDriver = config('session.driver');
        if (in_array($sessionDriver, ['redis', 'memcached', 'database'])) {
            $this->pass('SESSION_DRIVER', $sessionDriver);
        } else {
            $this->warn('SESSION_DRIVER', "Using '{$sessionDriver}' - consider Redis for production");
        }

        // Queue driver
        $queueDriver = config('queue.default');
        if (in_array($queueDriver, ['redis', 'sqs', 'database'])) {
            $this->pass('QUEUE_DRIVER', $queueDriver);
        } else {
            $this->warn('QUEUE_DRIVER', "Using '{$queueDriver}' - consider Redis for production");
        }
    }

    /**
     * Check service connectivity.
     */
    protected function checkServices(): void
    {
        $this->info('Checking services...');

        // Database
        try {
            DB::connection()->getPdo();
            $this->pass('DATABASE', 'Connected');
        } catch (\Exception $e) {
            $this->fail('DATABASE', 'Connection failed: ' . $e->getMessage());
        }

        // Cache
        try {
            Cache::put('_health_check', true, 10);
            if (Cache::get('_health_check') === true) {
                $this->pass('CACHE', 'Working');
                Cache::forget('_health_check');
            } else {
                $this->fail('CACHE', 'Read/write failed');
            }
        } catch (\Exception $e) {
            $this->fail('CACHE', 'Error: ' . $e->getMessage());
        }

        // Redis (if configured)
        if (config('database.redis.default')) {
            try {
                Redis::ping();
                $this->pass('REDIS', 'Connected');
            } catch (\Exception $e) {
                $this->warn('REDIS', 'Not available: ' . $e->getMessage());
            }
        }

        // Storage
        try {
            $testFile = '.health_check_' . time();
            Storage::put($testFile, 'test');
            if (Storage::exists($testFile)) {
                Storage::delete($testFile);
                $this->pass('STORAGE', 'Writable');
            } else {
                $this->fail('STORAGE', 'Write verification failed');
            }
        } catch (\Exception $e) {
            $this->fail('STORAGE', 'Error: ' . $e->getMessage());
        }

        // Check disk space
        $storagePath = storage_path();
        $freeSpace = disk_free_space($storagePath);
        $freeSpaceGB = round($freeSpace / 1024 / 1024 / 1024, 2);

        if ($freeSpaceGB > 5) {
            $this->pass('DISK_SPACE', "{$freeSpaceGB} GB free");
        } elseif ($freeSpaceGB > 1) {
            $this->warn('DISK_SPACE', "{$freeSpaceGB} GB free - consider expanding");
        } else {
            $this->fail('DISK_SPACE', "{$freeSpaceGB} GB free - critically low!");
        }
    }

    /**
     * Record a passed check.
     */
    protected function pass(string $check, string $message): void
    {
        $this->results['passed'][] = ['check' => $check, 'message' => $message];
    }

    /**
     * Record a warning.
     */
    protected function warn(string $check, string $message): void
    {
        $this->results['warnings'][] = ['check' => $check, 'message' => $message];
    }

    /**
     * Record a failed check.
     */
    protected function fail(string $check, string $message): void
    {
        $this->results['failed'][] = ['check' => $check, 'message' => $message];
    }

    /**
     * Output results as a table.
     */
    protected function outputTable(): void
    {
        $this->newLine();

        // Passed checks
        if (!empty($this->results['passed'])) {
            $this->info('PASSED:');
            foreach ($this->results['passed'] as $result) {
                $this->line("  <fg=green>✓</> {$result['check']}: {$result['message']}");
            }
            $this->newLine();
        }

        // Warnings
        if (!empty($this->results['warnings'])) {
            $this->warn('WARNINGS:');
            foreach ($this->results['warnings'] as $result) {
                $this->line("  <fg=yellow>!</> {$result['check']}: {$result['message']}");
            }
            $this->newLine();
        }

        // Failed checks
        if (!empty($this->results['failed'])) {
            $this->error('FAILED:');
            foreach ($this->results['failed'] as $result) {
                $this->line("  <fg=red>✗</> {$result['check']}: {$result['message']}");
            }
        }

        // Summary
        $this->newLine();
        $total = count($this->results['passed']) + count($this->results['warnings']) + count($this->results['failed']);
        $this->info("Summary: {$total} checks - " .
            count($this->results['passed']) . " passed, " .
            count($this->results['warnings']) . " warnings, " .
            count($this->results['failed']) . " failed");
    }

    /**
     * Output results as JSON.
     */
    protected function outputJson(): void
    {
        $this->line(json_encode([
            'status' => empty($this->results['failed']) ? 'pass' : 'fail',
            'results' => $this->results,
            'summary' => [
                'passed' => count($this->results['passed']),
                'warnings' => count($this->results['warnings']),
                'failed' => count($this->results['failed']),
            ],
        ], JSON_PRETTY_PRINT));
    }
}
