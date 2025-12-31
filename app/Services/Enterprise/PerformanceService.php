<?php

declare(strict_types=1);

namespace App\Services\Enterprise;

use App\Models\Enterprise\PerformanceMetric;
use App\Models\Enterprise\HealthCheck;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Performance Service
 *
 * Monitors and records application performance metrics.
 */
class PerformanceService
{
    /**
     * Record a performance metric.
     */
    public function record(
        string $metric,
        float $value,
        string $unit = 'ms',
        ?int $tenantId = null,
        ?string $endpoint = null,
        array $tags = [],
        array $metadata = []
    ): PerformanceMetric {
        return PerformanceMetric::create([
            'tenant_id' => $tenantId,
            'metric' => $metric,
            'endpoint' => $endpoint,
            'value' => $value,
            'unit' => $unit,
            'tags' => $tags ?: null,
            'metadata' => $metadata ?: null,
            'recorded_at' => now(),
        ]);
    }

    /**
     * Record response time.
     */
    public function recordResponseTime(
        float $milliseconds,
        string $endpoint,
        ?int $tenantId = null,
        array $metadata = []
    ): void {
        $this->record('response_time', $milliseconds, 'ms', $tenantId, $endpoint, [], $metadata);
    }

    /**
     * Record query count.
     */
    public function recordQueryCount(
        int $count,
        string $endpoint,
        ?int $tenantId = null
    ): void {
        $this->record('query_count', $count, 'count', $tenantId, $endpoint);
    }

    /**
     * Record memory usage.
     */
    public function recordMemoryUsage(?int $tenantId = null): void
    {
        $this->record('memory_usage', memory_get_peak_usage(true), 'bytes', $tenantId);
    }

    /**
     * Get performance statistics.
     */
    public function getStats(?int $tenantId = null, int $minutes = 60): array
    {
        $query = PerformanceMetric::recent($minutes);

        if ($tenantId) {
            $query->byTenant($tenantId);
        }

        $responseTime = (clone $query)->byMetric('response_time');
        $queryCount = (clone $query)->byMetric('query_count');

        return [
            'response_time' => [
                'avg' => round($responseTime->avg('value'), 2),
                'min' => round($responseTime->min('value'), 2),
                'max' => round($responseTime->max('value'), 2),
                'p95' => $this->calculatePercentile($responseTime, 95),
                'p99' => $this->calculatePercentile($responseTime, 99),
            ],
            'query_count' => [
                'avg' => round($queryCount->avg('value'), 2),
                'max' => (int) $queryCount->max('value'),
            ],
            'request_count' => $responseTime->count(),
            'period_minutes' => $minutes,
        ];
    }

    /**
     * Get slowest endpoints.
     */
    public function getSlowestEndpoints(int $limit = 10, int $minutes = 60): array
    {
        return PerformanceMetric::byMetric('response_time')
            ->recent($minutes)
            ->selectRaw('endpoint, AVG(value) as avg_time, MAX(value) as max_time, COUNT(*) as requests')
            ->groupBy('endpoint')
            ->orderByDesc('avg_time')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Run health checks.
     */
    public function runHealthChecks(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $overallStatus = 'healthy';
        foreach ($checks as $check) {
            if ($check['status'] === 'unhealthy') {
                $overallStatus = 'unhealthy';
                break;
            }
            if ($check['status'] === 'degraded') {
                $overallStatus = 'degraded';
            }
        }

        return [
            'status' => $overallStatus,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Check database health.
     */
    protected function checkDatabase(): array
    {
        $startTime = microtime(true);

        try {
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $startTime) * 1000;

            $status = $responseTime < 100 ? 'healthy' : ($responseTime < 500 ? 'degraded' : 'unhealthy');

            $this->saveHealthCheck('database', $status, null, $responseTime);

            return [
                'status' => $status,
                'response_time_ms' => round($responseTime, 2),
            ];
        } catch (\Throwable $e) {
            $this->saveHealthCheck('database', 'unhealthy', $e->getMessage());

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache health.
     */
    protected function checkCache(): array
    {
        $startTime = microtime(true);

        try {
            $testKey = 'health_check_' . uniqid();
            Cache::put($testKey, 'ok', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($value !== 'ok') {
                $this->saveHealthCheck('cache', 'unhealthy', 'Cache read/write mismatch');
                return ['status' => 'unhealthy', 'error' => 'Cache read/write mismatch'];
            }

            $status = $responseTime < 50 ? 'healthy' : ($responseTime < 200 ? 'degraded' : 'unhealthy');

            $this->saveHealthCheck('cache', $status, null, $responseTime);

            return [
                'status' => $status,
                'response_time_ms' => round($responseTime, 2),
            ];
        } catch (\Throwable $e) {
            $this->saveHealthCheck('cache', 'unhealthy', $e->getMessage());

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health.
     */
    protected function checkStorage(): array
    {
        try {
            $storagePath = storage_path('app');
            $freeSpace = disk_free_space($storagePath);
            $totalSpace = disk_total_space($storagePath);
            $usedPercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            $status = $usedPercentage < 80 ? 'healthy' : ($usedPercentage < 95 ? 'degraded' : 'unhealthy');

            $this->saveHealthCheck('storage', $status);

            return [
                'status' => $status,
                'used_percentage' => round($usedPercentage, 2),
                'free_bytes' => $freeSpace,
            ];
        } catch (\Throwable $e) {
            $this->saveHealthCheck('storage', 'unhealthy', $e->getMessage());

            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue health.
     */
    protected function checkQueue(): array
    {
        try {
            // Check queue size (implementation depends on queue driver)
            $queueSize = 0; // Would query actual queue

            $status = $queueSize < 1000 ? 'healthy' : ($queueSize < 10000 ? 'degraded' : 'unhealthy');

            $this->saveHealthCheck('queue', $status);

            return [
                'status' => $status,
                'pending_jobs' => $queueSize,
            ];
        } catch (\Throwable $e) {
            $this->saveHealthCheck('queue', 'degraded', $e->getMessage());

            return [
                'status' => 'degraded',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Save health check result.
     */
    protected function saveHealthCheck(
        string $name,
        string $status,
        ?string $message = null,
        ?float $responseTime = null
    ): void {
        HealthCheck::create([
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'response_time_ms' => $responseTime,
            'checked_at' => now(),
        ]);
    }

    /**
     * Calculate percentile.
     */
    protected function calculatePercentile($query, int $percentile): float
    {
        $values = $query->orderBy('value')->pluck('value')->toArray();

        if (empty($values)) {
            return 0;
        }

        $index = (int) ceil((count($values) * $percentile) / 100) - 1;
        return round($values[$index] ?? 0, 2);
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        try {
            // This would vary based on cache driver
            return [
                'driver' => config('cache.default'),
                'hits' => 0,
                'misses' => 0,
                'size_bytes' => 0,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Cleanup old metrics.
     */
    public function cleanup(int $retentionDays = 7): int
    {
        $deleted = PerformanceMetric::where('recorded_at', '<', now()->subDays($retentionDays))->delete();
        $deleted += HealthCheck::where('checked_at', '<', now()->subDays($retentionDays))->delete();

        return $deleted;
    }
}
