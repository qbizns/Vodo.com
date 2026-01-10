<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\VendorResource;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\Vendor;

class VendorController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all vendors.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = Vendor::query()->where('store_id', $store->id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter verified vendors
        if ($request->boolean('verified_only')) {
            $query->verified();
        }

        // Filter active vendors
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filter by minimum rating
        if ($request->filled('min_rating')) {
            $query->withMinRating((float) $request->input('min_rating'));
        }

        // Filter by commission type
        if ($request->filled('commission_type')) {
            $query->byCommissionType($request->input('commission_type'));
        }

        // Filter by payout schedule
        if ($request->filled('payout_schedule')) {
            $query->byPayoutSchedule($request->input('payout_schedule'));
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // Top rated vendors
        if ($request->boolean('top_rated')) {
            $limit = $request->input('top_rated_limit', 10);
            $query->topRated($limit);
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
        $vendors = $query->paginate($perPage);

        return $this->successResponse(
            VendorResource::collection($vendors),
            [
                'current_page' => $vendors->currentPage(),
                'last_page' => $vendors->lastPage(),
                'per_page' => $vendors->perPage(),
                'total' => $vendors->total(),
            ]
        );
    }

    /**
     * Get a single vendor.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $vendor = Vendor::where('store_id', $store->id)->findOrFail($id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $vendor->load($includes);
        }

        return $this->successResponse(
            new VendorResource($vendor)
        );
    }

    /**
     * Create a new vendor.
     */
    public function store(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'business_name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:commerce_vendors,slug',
            'description' => 'nullable|string',
            'logo' => 'nullable|string|max:255',
            'banner' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'tax_id' => 'nullable|string|max:50',
            'business_registration_number' => 'nullable|string|max:50',
            'verification_documents' => 'nullable|array',
            'commission_type' => 'nullable|in:flat,percentage,tiered',
            'commission_value' => 'nullable|numeric|min:0',
            'commission_tiers' => 'nullable|array',
            'payout_method' => 'nullable|in:bank_transfer,paypal,stripe,manual',
            'payout_schedule' => 'nullable|in:daily,weekly,biweekly,monthly',
            'minimum_payout_amount' => 'nullable|numeric|min:0',
            'payout_details' => 'nullable|array',
            'shipping_policy' => 'nullable|array',
            'return_policy' => 'nullable|array',
            'terms_and_conditions' => 'nullable|array',
            'meta' => 'nullable|array',
        ]);

        $data['store_id'] = $store->id;

        $vendor = Vendor::create($data);

        return $this->successResponse(
            new VendorResource($vendor),
            null,
            'Vendor created successfully',
            201
        );
    }

    /**
     * Update a vendor.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $vendor = Vendor::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'business_name' => 'sometimes|required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:commerce_vendors,slug,' . $id,
            'description' => 'nullable|string',
            'logo' => 'nullable|string|max:255',
            'banner' => 'nullable|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'tax_id' => 'nullable|string|max:50',
            'business_registration_number' => 'nullable|string|max:50',
            'verification_documents' => 'nullable|array',
            'commission_type' => 'nullable|in:flat,percentage,tiered',
            'commission_value' => 'nullable|numeric|min:0',
            'commission_tiers' => 'nullable|array',
            'payout_method' => 'nullable|in:bank_transfer,paypal,stripe,manual',
            'payout_schedule' => 'nullable|in:daily,weekly,biweekly,monthly',
            'minimum_payout_amount' => 'nullable|numeric|min:0',
            'payout_details' => 'nullable|array',
            'shipping_policy' => 'nullable|array',
            'return_policy' => 'nullable|array',
            'terms_and_conditions' => 'nullable|array',
            'meta' => 'nullable|array',
        ]);

        $vendor->update($data);

        return $this->successResponse(
            new VendorResource($vendor),
            null,
            'Vendor updated successfully'
        );
    }

    /**
     * Delete a vendor.
     */
    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $vendor = Vendor::where('store_id', $store->id)->findOrFail($id);

        $vendor->delete();

        return $this->successResponse(
            null,
            null,
            'Vendor deleted successfully'
        );
    }

    /**
     * Approve a vendor.
     */
    public function approve(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $vendor = Vendor::where('store_id', $store->id)->findOrFail($id);

        $vendor->approve();

        return $this->successResponse(
            new VendorResource($vendor),
            null,
            'Vendor approved successfully'
        );
    }

    /**
     * Activate a vendor.
     */
    public function activate(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $vendor = Vendor::where('store_id', $store->id)->findOrFail($id);

        $vendor->activate();

        return $this->successResponse(
            new VendorResource($vendor),
            null,
            'Vendor activated successfully'
        );
    }

    /**
     * Suspend a vendor.
     */
    public function suspend(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $vendor = Vendor::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'reason' => 'nullable|string',
        ]);

        $vendor->suspend($data['reason'] ?? null);

        return $this->successResponse(
            new VendorResource($vendor),
            null,
            'Vendor suspended successfully'
        );
    }

    /**
     * Reject a vendor.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $vendor = Vendor::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'reason' => 'required|string',
        ]);

        $vendor->reject($data['reason']);

        return $this->successResponse(
            new VendorResource($vendor),
            null,
            'Vendor rejected successfully'
        );
    }

    /**
     * Verify a vendor.
     */
    public function verify(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $vendor = Vendor::where('store_id', $store->id)->findOrFail($id);

        $vendor->verify();

        return $this->successResponse(
            new VendorResource($vendor),
            null,
            'Vendor verified successfully'
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
