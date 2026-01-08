<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Http\Resources\LowStockAlertResource;
use VodoCommerce\Models\LowStockAlert;
use VodoCommerce\Models\Store;

class LowStockAlertController extends Controller
{
    protected Store $store;

    public function __construct()
    {
        $this->store = resolve_store();
    }

    /**
     * List low stock alerts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LowStockAlert::where('store_id', $this->store->id)
            ->with(['location', 'product', 'variant']);

        if ($request->boolean('unresolved_only')) {
            $query->unresolved();
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('severity')) {
            // Filter by severity - this would need to be done after fetching
            // since severity is calculated dynamically
        }

        $alerts = $query->orderBy('is_resolved', 'asc')
            ->orderBy('current_quantity', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => LowStockAlertResource::collection($alerts),
            'pagination' => [
                'current_page' => $alerts->currentPage(),
                'total' => $alerts->total(),
                'per_page' => $alerts->perPage(),
                'last_page' => $alerts->lastPage(),
            ],
        ]);
    }

    /**
     * Get alert statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_alerts' => LowStockAlert::where('store_id', $this->store->id)->count(),
            'unresolved_alerts' => LowStockAlert::where('store_id', $this->store->id)->unresolved()->count(),
            'critical_alerts' => LowStockAlert::where('store_id', $this->store->id)->unresolved()->critical()->count(),
            'resolved_today' => LowStockAlert::where('store_id', $this->store->id)
                ->whereDate('resolved_at', today())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Resolve an alert.
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        $alert = LowStockAlert::where('store_id', $this->store->id)
            ->findOrFail($id);

        if ($alert->is_resolved) {
            return response()->json([
                'success' => false,
                'message' => 'Alert is already resolved',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $alert->resolve($request->notes);

        return response()->json([
            'success' => true,
            'message' => 'Alert resolved successfully',
            'data' => new LowStockAlertResource($alert->fresh(['location', 'product', 'variant'])),
        ]);
    }

    /**
     * Bulk resolve alerts.
     */
    public function bulkResolve(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'alert_ids' => ['required', 'array', 'min:1'],
            'alert_ids.*' => ['integer', 'exists:commerce_low_stock_alerts,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resolved = 0;
        foreach ($request->alert_ids as $alertId) {
            $alert = LowStockAlert::where('store_id', $this->store->id)
                ->where('id', $alertId)
                ->where('is_resolved', false)
                ->first();

            if ($alert) {
                $alert->resolve($request->notes);
                $resolved++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$resolved} alert(s) resolved successfully",
            'data' => [
                'resolved_count' => $resolved,
            ],
        ]);
    }
}
