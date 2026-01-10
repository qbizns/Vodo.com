<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\ProductBundleResource;
use VodoCommerce\Models\ProductBundle;
use VodoCommerce\Models\Store;

class ProductBundleController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all product bundles.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = ProductBundle::where('store_id', $store->id);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by pricing type
        if ($request->filled('pricing_type')) {
            $query->where('pricing_type', $request->input('pricing_type'));
        }

        // Filter in stock
        if ($request->boolean('in_stock_only')) {
            $query->inStock();
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'sort_order');
        $sortDir = $request->input('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);
        $bundles = $query->paginate($perPage);

        return $this->successResponse(
            ProductBundleResource::collection($bundles),
            [
                'current_page' => $bundles->currentPage(),
                'last_page' => $bundles->lastPage(),
                'per_page' => $bundles->perPage(),
                'total' => $bundles->total(),
            ]
        );
    }

    /**
     * Get a single product bundle.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $query = ProductBundle::where('store_id', $store->id);

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $bundle = $query->findOrFail($id);

        return $this->successResponse(
            new ProductBundleResource($bundle)
        );
    }

    /**
     * Create a new product bundle.
     */
    public function store(Request $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:commerce_product_bundles,slug',
            'description' => 'nullable|string',
            'pricing_type' => 'required|in:fixed,calculated,discounted',
            'fixed_price' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'allow_partial_purchase' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'min_items' => 'nullable|integer|min:1',
            'max_items' => 'nullable|integer',
            'track_inventory' => 'nullable|boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'image_url' => 'nullable|url',
            'sort_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $data['store_id'] = $store->id;

        $bundle = ProductBundle::create($data);

        return $this->successResponse(
            new ProductBundleResource($bundle),
            null,
            'Product bundle created successfully',
            201
        );
    }

    /**
     * Update a product bundle.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $bundle = ProductBundle::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|unique:commerce_product_bundles,slug,' . $id,
            'description' => 'nullable|string',
            'pricing_type' => 'sometimes|required|in:fixed,calculated,discounted',
            'fixed_price' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'allow_partial_purchase' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'min_items' => 'nullable|integer|min:1',
            'max_items' => 'nullable|integer',
            'track_inventory' => 'nullable|boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'image_url' => 'nullable|url',
            'sort_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta' => 'nullable|array',
        ]);

        $bundle->update($data);

        return $this->successResponse(
            new ProductBundleResource($bundle),
            null,
            'Product bundle updated successfully'
        );
    }

    /**
     * Delete a product bundle.
     */
    public function destroy(int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $bundle = ProductBundle::where('store_id', $store->id)->findOrFail($id);

        $bundle->delete();

        return $this->successResponse(
            null,
            null,
            'Product bundle deleted successfully'
        );
    }

    /**
     * Add item to bundle.
     */
    public function addItem(Request $request, int $id): JsonResponse
    {
        $store = $this->getCurrentStore();

        $bundle = ProductBundle::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'product_id' => 'required|exists:commerce_products,id',
            'product_variant_id' => 'nullable|exists:commerce_product_variants,id',
            'quantity' => 'required|integer|min:1',
            'is_required' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'price_override' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'sort_order' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $item = $bundle->items()->create($data);

        return $this->successResponse(
            new ProductBundleResource($bundle->load('items')),
            null,
            'Item added to bundle successfully',
            201
        );
    }

    /**
     * Remove item from bundle.
     */
    public function removeItem(int $bundleId, int $itemId): JsonResponse
    {
        $store = $this->getCurrentStore();

        $bundle = ProductBundle::where('store_id', $store->id)->findOrFail($bundleId);

        $item = $bundle->items()->findOrFail($itemId);
        $item->delete();

        return $this->successResponse(
            new ProductBundleResource($bundle->load('items')),
            null,
            'Item removed from bundle successfully'
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

    protected function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
