<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\AnalyticsService;

class DashboardController extends Controller
{
    protected Store $store;
    protected AnalyticsService $analytics;

    public function __construct()
    {
        $this->store = resolve_store();
        $this->analytics = new AnalyticsService($this->store);
    }

    /**
     * Get dashboard overview metrics.
     */
    public function overview(Request $request): JsonResponse
    {
        $period = $request->input('period', 'today');

        $metrics = $this->analytics->getDashboardMetrics($period);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Get revenue metrics.
     */
    public function revenue(Request $request): JsonResponse
    {
        $period = $request->input('period', 'today');
        $dateRange = $this->analytics->getDateRange($period);

        $revenue = $this->analytics->getRevenue($dateRange);

        return response()->json([
            'success' => true,
            'data' => $revenue,
        ]);
    }

    /**
     * Get orders metrics.
     */
    public function orders(Request $request): JsonResponse
    {
        $period = $request->input('period', 'today');
        $dateRange = $this->analytics->getDateRange($period);

        $orders = $this->analytics->getOrdersMetrics($dateRange);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get customers metrics.
     */
    public function customers(Request $request): JsonResponse
    {
        $period = $request->input('period', 'today');
        $dateRange = $this->analytics->getDateRange($period);

        $customers = $this->analytics->getCustomersMetrics($dateRange);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * Get products metrics.
     */
    public function products(Request $request): JsonResponse
    {
        $period = $request->input('period', 'today');
        $dateRange = $this->analytics->getDateRange($period);

        $products = $this->analytics->getProductsMetrics($dateRange);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get inventory metrics.
     */
    public function inventory(): JsonResponse
    {
        $inventory = $this->analytics->getInventoryMetrics();

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }
}
