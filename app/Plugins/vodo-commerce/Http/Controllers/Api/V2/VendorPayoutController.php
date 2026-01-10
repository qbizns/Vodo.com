<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\VendorPayoutResource;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\Vendor;
use VodoCommerce\Models\VendorPayout;

class VendorPayoutController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all payouts.
     */
    public function index(Request $request): JsonResponse
    {
        $query = VendorPayout::query();

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->forVendor($request->input('vendor_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by payout method
        if ($request->filled('payout_method')) {
            $query->byMethod($request->input('payout_method'));
        }

        // Filter by period
        if ($request->filled('period_start') && $request->filled('period_end')) {
            $query->byPeriod($request->input('period_start'), $request->input('period_end'));
        }

        // Filter by payout number
        if ($request->filled('payout_number')) {
            $query->byNumber($request->input('payout_number'));
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
        $payouts = $query->paginate($perPage);

        return $this->successResponse(
            VendorPayoutResource::collection($payouts),
            [
                'current_page' => $payouts->currentPage(),
                'last_page' => $payouts->lastPage(),
                'per_page' => $payouts->perPage(),
                'total' => $payouts->total(),
            ]
        );
    }

    /**
     * Get a single payout.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $payout = VendorPayout::findOrFail($id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $payout->load($includes);
        }

        return $this->successResponse(
            new VendorPayoutResource($payout)
        );
    }

    /**
     * Create a new payout.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id' => 'required|exists:commerce_vendors,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'payout_method' => 'nullable|in:bank_transfer,paypal,stripe,check,manual',
            'platform_fees' => 'nullable|numeric|min:0',
            'adjustments' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        // Get vendor
        $vendor = Vendor::findOrFail($data['vendor_id']);

        // Get approved unpaid commissions for the period
        $commissions = $vendor->commissions()
            ->approved()
            ->whereNull('payout_id')
            ->whereBetween('created_at', [$data['period_start'], $data['period_end']])
            ->get();

        if ($commissions->isEmpty()) {
            return response()->json([
                'status' => 422,
                'success' => false,
                'message' => 'No unpaid commissions found for this period',
            ], 422);
        }

        // Calculate totals
        $grossAmount = $commissions->sum('vendor_earnings');
        $platformFees = $data['platform_fees'] ?? 0;
        $adjustments = $data['adjustments'] ?? 0;
        $netAmount = $grossAmount - $platformFees + $adjustments;

        // Create payout
        $payout = VendorPayout::create([
            'vendor_id' => $data['vendor_id'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'gross_amount' => $grossAmount,
            'platform_fees' => $platformFees,
            'adjustments' => $adjustments,
            'net_amount' => $netAmount,
            'currency' => 'USD',
            'commission_count' => $commissions->count(),
            'order_count' => $commissions->pluck('order_id')->unique()->count(),
            'payout_method' => $data['payout_method'] ?? $vendor->payout_method,
            'notes' => $data['notes'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);

        // Link commissions to payout
        $commissions->each(function ($commission) use ($payout) {
            $commission->update(['payout_id' => $payout->id]);
        });

        return $this->successResponse(
            new VendorPayoutResource($payout->load('commissions')),
            null,
            'Payout created successfully',
            201
        );
    }

    /**
     * Update a payout.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $payout = VendorPayout::findOrFail($id);

        $data = $request->validate([
            'platform_fees' => 'sometimes|numeric|min:0',
            'adjustments' => 'sometimes|numeric',
            'notes' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $payout->update($data);

        // Recalculate totals if fees or adjustments changed
        if (isset($data['platform_fees']) || isset($data['adjustments'])) {
            $payout->update([
                'net_amount' => $payout->calculateNetAmount(),
            ]);
        }

        return $this->successResponse(
            new VendorPayoutResource($payout),
            null,
            'Payout updated successfully'
        );
    }

    /**
     * Mark payout as processing.
     */
    public function markAsProcessing(Request $request, int $id): JsonResponse
    {
        $payout = VendorPayout::findOrFail($id);

        $payout->markAsProcessing($request->user()?->id);

        return $this->successResponse(
            new VendorPayoutResource($payout),
            null,
            'Payout marked as processing'
        );
    }

    /**
     * Mark payout as completed.
     */
    public function markAsCompleted(Request $request, int $id): JsonResponse
    {
        $payout = VendorPayout::findOrFail($id);

        $data = $request->validate([
            'transaction_id' => 'nullable|string|max:255',
        ]);

        $payout->markAsCompleted($data['transaction_id'] ?? null);

        return $this->successResponse(
            new VendorPayoutResource($payout),
            null,
            'Payout marked as completed'
        );
    }

    /**
     * Mark payout as failed.
     */
    public function markAsFailed(Request $request, int $id): JsonResponse
    {
        $payout = VendorPayout::findOrFail($id);

        $data = $request->validate([
            'reason' => 'required|string',
        ]);

        $payout->markAsFailed($data['reason']);

        return $this->successResponse(
            new VendorPayoutResource($payout),
            null,
            'Payout marked as failed'
        );
    }

    /**
     * Cancel a payout.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $payout = VendorPayout::findOrFail($id);

        $data = $request->validate([
            'reason' => 'required|string',
        ]);

        $payout->cancel($data['reason']);

        return $this->successResponse(
            new VendorPayoutResource($payout),
            null,
            'Payout cancelled successfully'
        );
    }

    /**
     * Retry a failed payout.
     */
    public function retry(int $id): JsonResponse
    {
        $payout = VendorPayout::findOrFail($id);

        if (!$payout->isFailed()) {
            return response()->json([
                'status' => 422,
                'success' => false,
                'message' => 'Only failed payouts can be retried',
            ], 422);
        }

        $payout->retry();

        return $this->successResponse(
            new VendorPayoutResource($payout),
            null,
            'Payout retry initiated'
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
