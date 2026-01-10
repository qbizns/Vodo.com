<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\ProductBadgeResource;
use VodoCommerce\Models\ProductBadge;
use VodoCommerce\Models\Store;

class ProductBadgeController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all product badges.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = ProductBadge::where('store_id', $store->id);

        // Filter by product
        if ($request->filled('product_id')) {
            $query->forProduct($request->input('product_id'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->ofType($request->input('type'));
        }

        // Filter active only
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filter displayable only
        if ($request->boolean('displayable_only')) {
            $query->displayable();
        }

        // Filter expired
        if ($request->boolean('expired_only')) {
            $query->expired();
        }

        // Filter scheduled
        if ($request->boolean('scheduled_only')) {
            $query->scheduled();
        }

        // Filter auto-apply
        if ($request->boolean('auto_apply_only')) {
            $query->autoApply();
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'priority');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);
        $badges = $query->paginate($perPage);

        return $this->successResponse(
            ProductBadgeResource::collection($badges),
            [
                'current_page' => $badges->currentPage(),
                'last_page' => $badges->lastPage(),
                'per_page' => $badges->perPage(),
                'total' => $badges->total(),
            ]
        );
    }

    /**
     * Get a single badge.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = ProductBadge::where('store_id', $store->id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $badge = $query->findOrFail($id);

        return $this->successResponse(
            new ProductBadgeResource($badge)
        );
    }

    /**
     * Create a new badge.
     */
    public function store(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $data = $request->validate([
            'product_id' => 'required|exists:commerce_products,id',
            'label' => 'required|string|max:255',
            'slug' => 'required|string',
            'type' => 'required|in:new,sale,featured,limited,hot,bestseller,exclusive,custom',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string',
            'position' => 'nullable|in:top_left,top_right,bottom_left,bottom_right',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'auto_apply' => 'nullable|boolean',
            'conditions' => 'nullable|array',
            'meta' => 'nullable|array',
        ]);

        $data['store_id'] = $store->id;

        $badge = ProductBadge::create($data);

        return $this->successResponse(
            new ProductBadgeResource($badge),
            null,
            'Product badge created successfully',
            201
        );
    }

    /**
     * Update a badge.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $badge = ProductBadge::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'label' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:new,sale,featured,limited,hot,bestseller,exclusive,custom',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string',
            'position' => 'nullable|in:top_left,top_right,bottom_left,bottom_right',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'auto_apply' => 'nullable|boolean',
            'conditions' => 'nullable|array',
            'meta' => 'nullable|array',
        ]);

        $badge->update($data);

        return $this->successResponse(
            new ProductBadgeResource($badge),
            null,
            'Product badge updated successfully'
        );
    }

    /**
     * Delete a badge.
     */
    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $badge = ProductBadge::where('store_id', $store->id)->findOrFail($id);

        $badge->delete();

        return $this->successResponse(
            null,
            null,
            'Product badge deleted successfully'
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
