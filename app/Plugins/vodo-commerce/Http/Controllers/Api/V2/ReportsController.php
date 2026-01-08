<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\AnalyticsService;

class ReportsController extends Controller
{
    protected Store $store;
    protected AnalyticsService $analytics;

    public function __construct()
    {
        $this->store = resolve_store();
        $this->analytics = new AnalyticsService($this->store);
    }

    /**
     * Get sales report.
     */
    public function sales(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'group_by' => ['sometimes', 'string', 'in:hour,day,week,month,year'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $report = $this->analytics->getSalesReport(
            $request->start_date,
            $request->end_date,
            $request->input('group_by', 'day')
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get best selling products report.
     */
    public function bestSellers(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $period = $request->input('period');

        $dateRange = $period ? $this->analytics->getDateRange($period) : null;

        $products = $this->analytics->getBestSellingProducts($limit, $dateRange);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get revenue by payment method report.
     */
    public function revenueByPaymentMethod(Request $request): JsonResponse
    {
        $period = $request->input('period');
        $dateRange = $period ? $this->analytics->getDateRange($period) : null;

        $revenue = $this->analytics->getRevenueByPaymentMethod($dateRange);

        return response()->json([
            'success' => true,
            'data' => $revenue,
        ]);
    }

    /**
     * Get customer lifetime value report.
     */
    public function customerLifetimeValue(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $customers = $this->analytics->getCustomerLifetimeValue($limit);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * Get inventory turnover report.
     */
    public function inventoryTurnover(Request $request): JsonResponse
    {
        $period = $request->input('period', 'last_30_days');
        $dateRange = $this->analytics->getDateRange($period);

        $turnover = $this->analytics->getInventoryTurnover($dateRange);

        return response()->json([
            'success' => true,
            'data' => $turnover,
        ]);
    }
}
