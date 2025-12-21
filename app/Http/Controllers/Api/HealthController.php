<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * Health Check Controller - System health and monitoring endpoints.
 *
 * Provides:
 * - Basic health check (for load balancers)
 * - Detailed health check (with component status)
 * - Ready check (for Kubernetes)
 * - Live check (for Kubernetes)
 * - Metrics endpoint
 */
class HealthController extends Controller
{
    /**
     * Basic health check - returns 200 if application is running.
     * Use for load balancer health checks.
     *
     * GET /api/health
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detailed health check - checks all critical services.
     * Use for monitoring dashboards.
     *
     * GET /api/health/details
     */
    public function details(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        // Check Redis if configured
        if (config('database.redis.default.host')) {
            $checks['redis'] = $this->checkRedis();
        }

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Readiness check - indicates if app is ready to serve traffic.
     * Use for Kubernetes readiness probe.
     *
     * GET /api/health/ready
     */
    public function ready(): JsonResponse
    {
        $dbHealthy = $this->checkDatabase()['status'] === 'healthy';
        $cacheHealthy = $this->checkCache()['status'] === 'healthy';

        $ready = $dbHealthy && $cacheHealthy;

        return response()->json([
            'ready' => $ready,
            'timestamp' => now()->toIso8601String(),
        ], $ready ? 200 : 503);
    }

    /**
     * Liveness check - indicates if app is alive (not deadlocked).
     * Use for Kubernetes liveness probe.
     *
     * GET /api/health/live
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'alive' => true,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * System metrics for monitoring.
     *
     * GET /api/health/metrics
     */
    public function metrics(): JsonResponse
    {
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'uptime' => $this->getUptime(),
            'memory' => [
                'usage' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
            ],
            'laravel' => [
                'version' => app()->version(),
            ],
            'database' => [
                'connections' => $this->getDatabaseConnectionCount(),
            ],
            'cache' => [
                'driver' => config('cache.default'),
                'hits' => Cache::get('cache_hits', 0),
                'misses' => Cache::get('cache_misses', 0),
            ],
            'queue' => [
                'driver' => config('queue.default'),
                'pending' => $this->getQueueSize(),
                'failed' => $this->getFailedJobCount(),
            ],
        ]);
    }

    /**
     * Check database connectivity.
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
                'driver' => config('database.default'),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'driver' => config('database.default'),
            ];
        }
    }

    /**
     * Check cache connectivity.
     */
    protected function checkCache(): array
    {
        try {
            $key = 'health_check_' . uniqid();
            $start = microtime(true);

            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($value !== 'test') {
                throw new \RuntimeException('Cache read/write mismatch');
            }

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
                'driver' => config('cache.default'),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'driver' => config('cache.default'),
            ];
        }
    }

    /**
     * Check Redis connectivity.
     */
    protected function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage accessibility.
     */
    protected function checkStorage(): array
    {
        try {
            $start = microtime(true);
            $testFile = 'health_check_' . uniqid() . '.txt';

            Storage::put($testFile, 'test');
            $content = Storage::get($testFile);
            Storage::delete($testFile);

            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($content !== 'test') {
                throw new \RuntimeException('Storage read/write mismatch');
            }

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
                'driver' => config('filesystems.default'),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'driver' => config('filesystems.default'),
            ];
        }
    }

    /**
     * Check queue system.
     */
    protected function checkQueue(): array
    {
        try {
            $driver = config('queue.default');

            // For sync driver, always healthy
            if ($driver === 'sync') {
                return [
                    'status' => 'healthy',
                    'driver' => $driver,
                    'note' => 'Using sync driver (no queue)',
                ];
            }

            return [
                'status' => 'healthy',
                'driver' => $driver,
                'pending' => $this->getQueueSize(),
                'failed' => $this->getFailedJobCount(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'driver' => config('queue.default'),
            ];
        }
    }

    /**
     * Get application uptime.
     */
    protected function getUptime(): array
    {
        $startTime = Cache::get('app_start_time');

        if (!$startTime) {
            $startTime = now();
            Cache::forever('app_start_time', $startTime);
        }

        $uptime = now()->diffInSeconds($startTime);

        return [
            'started_at' => $startTime->toIso8601String(),
            'uptime_seconds' => $uptime,
            'uptime_human' => $this->formatUptime($uptime),
        ];
    }

    /**
     * Format uptime in human readable form.
     */
    protected function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }

    /**
     * Parse memory limit to bytes.
     */
    protected function parseMemoryLimit(string $limit): int
    {
        $limit = strtolower(trim($limit));

        if ($limit === '-1') {
            return -1;
        }

        $units = ['k' => 1024, 'm' => 1048576, 'g' => 1073741824];
        $unit = substr($limit, -1);

        if (isset($units[$unit])) {
            return (int) $limit * $units[$unit];
        }

        return (int) $limit;
    }

    /**
     * Get database connection count.
     */
    protected function getDatabaseConnectionCount(): int
    {
        try {
            $driver = config('database.default');

            if ($driver === 'mysql') {
                $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
                return (int) ($result[0]->Value ?? 0);
            }

            if ($driver === 'pgsql') {
                $result = DB::select("SELECT count(*) as count FROM pg_stat_activity WHERE datname = current_database()");
                return (int) ($result[0]->count ?? 0);
            }

            return 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Get pending queue job count.
     */
    protected function getQueueSize(): int
    {
        try {
            return Queue::size();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Get failed job count.
     */
    protected function getFailedJobCount(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
