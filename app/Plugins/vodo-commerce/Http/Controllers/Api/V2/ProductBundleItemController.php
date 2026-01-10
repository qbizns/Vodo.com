<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Resources\ProductBundleItemResource;
use VodoCommerce\Models\ProductBundleItem;
use VodoCommerce\Models\Store;

class ProductBundleItemController
{
    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * List all bundle items.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductBundleItem::query();

        // Filter by bundle
        if ($request->filled('bundle_id')) {
            $query->forBundle($request->input('bundle_id'));
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->forProduct($request->input('product_id'));
        }

        // Filter required items
        if ($request->boolean('required_only')) {
            $query->required();
        }

        // Filter optional items
        if ($request->boolean('optional_only')) {
            $query->optional();
        }

        // With relationships
        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        // Sort
        if ($request->boolean('ordered')) {
            $query->ordered();
        } else {
            $sortBy = $request->input('sort_by', 'sort_order');
            $sortDir = $request->input('sort_dir', 'asc');
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = $request->input('per_page', 15);
        $items = $query->paginate($perPage);

        return $this->successResponse(
            ProductBundleItemResource::collection($items),
            [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ]
        );
    }

    /**
     * Get a single bundle item.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $item = ProductBundleItem::findOrFail($id);

        if ($request->filled('include')) {
            $includes = explode(',', $request->input('include'));
            $item->load($includes);
        }

        return $this->successResponse(
            new ProductBundleItemResource($item)
        );
    }

    /**
     * Update a bundle item.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = ProductBundleItem::findOrFail($id);

        $data = $request->validate([
            'quantity' => 'sometimes|required|integer|min:1',
            'is_required' => 'nullable|boolean',
            'price_override' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percentage',
            'sort_order' => 'nullable|integer',
            'meta' => 'nullable|array',
        ]);

        $item->update($data);

        return $this->successResponse(
            new ProductBundleItemResource($item),
            null,
            'Bundle item updated successfully'
        );
    }

    /**
     * Delete a bundle item.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = ProductBundleItem::findOrFail($id);

        $item->delete();

        return $this->successResponse(
            null,
            null,
            'Bundle item removed successfully'
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
