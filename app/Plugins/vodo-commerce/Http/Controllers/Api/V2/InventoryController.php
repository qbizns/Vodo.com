<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Http\Resources\InventoryItemResource;
use VodoCommerce\Http\Resources\StockMovementResource;
use VodoCommerce\Models\InventoryItem;
use VodoCommerce\Models\StockMovement;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\InventoryService;

class InventoryController extends Controller
{
    protected Store $store;
    protected InventoryService $inventoryService;

    public function __construct()
    {
        $this->store = resolve_store();
        $this->inventoryService = new InventoryService($this->store);
    }

    /**
     * List inventory items.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::query()
            ->whereHas('location', function ($q) {
                $q->where('store_id', $this->store->id);
            })
            ->with(['location', 'product', 'variant']);

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->boolean('low_stock_only')) {
            $query->lowStock();
        }

        if ($request->boolean('out_of_stock_only')) {
            $query->outOfStock();
        }

        $items = $query->orderBy('product_id', 'asc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => InventoryItemResource::collection($items),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    /**
     * Get inventory summary for a product.
     */
    public function summary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', 'exists:commerce_products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:commerce_product_variants,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $summary = $this->inventoryService->getInventorySummary(
            $request->product_id,
            $request->variant_id
        );

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Add stock to a location.
     */
    public function addStock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location_id' => ['required', 'integer', 'exists:commerce_inventory_locations,id'],
            'product_id' => ['required', 'integer', 'exists:commerce_products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:commerce_product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $item = $this->inventoryService->addStock(
                $request->location_id,
                $request->product_id,
                $request->variant_id,
                $request->quantity,
                $request->reason,
                null,
                null,
                $request->unit_cost
            );

            $item->load(['location', 'product', 'variant']);

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully',
                'data' => new InventoryItemResource($item),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove stock from a location.
     */
    public function removeStock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location_id' => ['required', 'integer', 'exists:commerce_inventory_locations,id'],
            'product_id' => ['required', 'integer', 'exists:commerce_products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:commerce_product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $item = $this->inventoryService->removeStock(
                $request->location_id,
                $request->product_id,
                $request->variant_id,
                $request->quantity,
                $request->reason
            );

            $item->load(['location', 'product', 'variant']);

            return response()->json([
                'success' => true,
                'message' => 'Stock removed successfully',
                'data' => new InventoryItemResource($item),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Adjust stock quantity.
     */
    public function adjustStock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location_id' => ['required', 'integer', 'exists:commerce_inventory_locations,id'],
            'product_id' => ['required', 'integer', 'exists:commerce_products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:commerce_product_variants,id'],
            'new_quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $item = $this->inventoryService->adjustStock(
                $request->location_id,
                $request->product_id,
                $request->variant_id,
                $request->new_quantity,
                $request->reason
            );

            $item->load(['location', 'product', 'variant']);

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'data' => new InventoryItemResource($item),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update reorder settings.
     */
    public function updateReorderSettings(Request $request, int $id): JsonResponse
    {
        $item = InventoryItem::whereHas('location', function ($q) {
            $q->where('store_id', $this->store->id);
        })->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'reorder_point' => ['nullable', 'integer', 'min:0'],
            'reorder_quantity' => ['nullable', 'integer', 'min:1'],
            'bin_location' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $item->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Reorder settings updated successfully',
            'data' => new InventoryItemResource($item->fresh(['location', 'product', 'variant'])),
        ]);
    }

    /**
     * Get stock movements history.
     */
    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::where('store_id', $this->store->id)
            ->with(['location', 'product', 'variant']);

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $movements = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => StockMovementResource::collection($movements),
            'pagination' => [
                'current_page' => $movements->currentPage(),
                'total' => $movements->total(),
                'per_page' => $movements->perPage(),
                'last_page' => $movements->lastPage(),
            ],
        ]);
    }
}
