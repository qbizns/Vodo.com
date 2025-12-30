<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Plugins\PluginHealthMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Plugin Health Controller - API endpoints for plugin health monitoring.
 *
 * Phase 2, Task 2.4: Plugin Health Monitoring
 *
 * Provides endpoints for:
 * - All plugins health status
 * - Single plugin health status
 * - Circuit breaker reset
 * - Aggregate metrics
 */
class PluginHealthController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected PluginHealthMonitor $healthMonitor
    ) {}

    /**
     * Get health status for all plugins.
     *
     * GET /api/v1/plugins/health
     *
     * Query params:
     *   - fresh: bool (skip cache)
     *   - status: string (filter by status)
     */
    public function index(Request $request): JsonResponse
    {
        $fresh = $request->boolean('fresh', false);
        $health = $this->healthMonitor->getAllHealth($fresh);

        // Filter by status if requested
        if ($status = $request->query('status')) {
            $health['plugins'] = array_filter(
                $health['plugins'],
                fn($plugin) => $plugin['status'] === $status
            );
        }

        return response()->json($health);
    }

    /**
     * Get health status for a single plugin.
     *
     * GET /api/v1/plugins/{slug}/health
     */
    public function show(string $slug): JsonResponse
    {
        $health = $this->healthMonitor->getPluginHealth($slug);

        if (!$health) {
            return response()->json([
                'error' => 'Plugin not found',
                'slug' => $slug,
            ], 404);
        }

        return response()->json($health);
    }

    /**
     * Reset circuit breaker for a plugin.
     *
     * POST /api/v1/plugins/{slug}/reset
     */
    public function reset(string $slug): JsonResponse
    {
        $result = $this->healthMonitor->resetCircuitBreaker($slug);

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    /**
     * Get aggregate health metrics.
     *
     * GET /api/v1/plugins/health/metrics
     */
    public function metrics(): JsonResponse
    {
        $metrics = $this->healthMonitor->getAggregateMetrics();

        return response()->json($metrics);
    }

    /**
     * Get only unhealthy plugins.
     *
     * GET /api/v1/plugins/health/unhealthy
     */
    public function unhealthy(): JsonResponse
    {
        $unhealthy = $this->healthMonitor->getUnhealthyPlugins();

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'count' => $unhealthy->count(),
            'plugins' => $unhealthy->toArray(),
        ]);
    }

    /**
     * Clear health cache.
     *
     * POST /api/v1/plugins/health/refresh
     */
    public function refresh(): JsonResponse
    {
        $this->healthMonitor->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Health cache cleared',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
