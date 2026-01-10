<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\VendorCommissionResource;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\VendorCommission;

class VendorCommissionController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all commissions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = VendorCommission::query();

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->forVendor($request->input('vendor_id'));
        }

        // Filter by order
        if ($request->filled('order_id')) {
            $query->forOrder($request->input('order_id'));
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->forProduct($request->input('product_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter unpaid
        if ($request->boolean('unpaid_only')) {
            $query->unpaid();
        }

        // Filter ready for payout
        if ($request->boolean('ready_for_payout')) {
            $query->readyForPayout();
        }

        // Filter by payout
        if ($request->filled('payout_id')) {
            $query->inPayout($request->input('payout_id'));
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->byDateRange($request->input('start_date'), $request->input('end_date'));
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);
        $commissions = $query->paginate($perPage);

        return $this->successResponse(
            VendorCommissionResource::collection($commissions),
            [
                'current_page' => $commissions->currentPage(),
                'last_page' => $commissions->lastPage(),
                'per_page' => $commissions->perPage(),
                'total' => $commissions->total(),
            ]
        );
    }

    /**
     * Get a single commission.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $commission = VendorCommission::findOrFail($id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $commission->load($includes);
        }

        return $this->successResponse(
            new VendorCommissionResource($commission)
        );
    }

    /**
     * Approve a commission.
     */
    public function approve(int $id): JsonResponse
    {
        $commission = VendorCommission::findOrFail($id);

        $commission->approve();

        return $this->successResponse(
            new VendorCommissionResource($commission),
            null,
            'Commission approved successfully'
        );
    }

    /**
     * Dispute a commission.
     */
    public function dispute(Request $request, int $id): JsonResponse
    {
        $commission = VendorCommission::findOrFail($id);

        $data = $request->validate([
            'reason' => 'required|string',
        ]);

        $commission->dispute($data['reason']);

        return $this->successResponse(
            new VendorCommissionResource($commission),
            null,
            'Commission disputed successfully'
        );
    }

    /**
     * Resolve a commission dispute.
     */
    public function resolveDispute(Request $request, int $id): JsonResponse
    {
        $commission = VendorCommission::findOrFail($id);

        $data = $request->validate([
            'resolution' => 'required|string',
        ]);

        $commission->resolveDispute($data['resolution']);

        return $this->successResponse(
            new VendorCommissionResource($commission),
            null,
            'Commission dispute resolved successfully'
        );
    }

    /**
     * Refund a commission.
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $commission = VendorCommission::findOrFail($id);

        $data = $request->validate([
            'amount' => 'nullable|numeric|min:0',
        ]);

        $commission->refund($data['amount'] ?? null);

        return $this->successResponse(
            new VendorCommissionResource($commission),
            null,
            'Commission refunded successfully'
        );
    }

    /**
     * Cancel a commission.
     */
    public function cancel(int $id): JsonResponse
    {
        $commission = VendorCommission::findOrFail($id);

        $commission->cancel();

        return $this->successResponse(
            new VendorCommissionResource($commission),
            null,
            'Commission cancelled successfully'
        );
    }

    protected function successResponse(mixed $data = null, ?array $pagination = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $response = [
            'status' => $status,
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $status);
    }
}
