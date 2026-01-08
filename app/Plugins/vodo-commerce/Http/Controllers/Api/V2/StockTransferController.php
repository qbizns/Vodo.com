<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use VodoCommerce\Http\Resources\StockTransferResource;
use VodoCommerce\Models\StockTransfer;
use VodoCommerce\Models\Store;

class StockTransferController extends Controller
{
    protected Store $store;

    public function __construct()
    {
        $this->store = resolve_store();
    }

    /**
     * List stock transfers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockTransfer::where('store_id', $this->store->id)
            ->with(['fromLocation', 'toLocation', 'items.product', 'items.variant']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_location_id')) {
            $query->where('from_location_id', $request->from_location_id);
        }

        if ($request->has('to_location_id')) {
            $query->where('to_location_id', $request->to_location_id);
        }

        $transfers = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => StockTransferResource::collection($transfers),
            'pagination' => [
                'current_page' => $transfers->currentPage(),
                'total' => $transfers->total(),
                'per_page' => $transfers->perPage(),
                'last_page' => $transfers->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single transfer.
     */
    public function show(int $id): JsonResponse
    {
        $transfer = StockTransfer::where('store_id', $this->store->id)
            ->with(['fromLocation', 'toLocation', 'items.product', 'items.variant'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new StockTransferResource($transfer),
        ]);
    }

    /**
     * Create a new transfer.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_location_id' => ['required', 'integer', 'exists:commerce_inventory_locations,id', 'different:to_location_id'],
            'to_location_id' => ['required', 'integer', 'exists:commerce_inventory_locations,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:commerce_products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:commerce_product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Generate transfer number
        $transferNumber = 'TRF-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        $transfer = StockTransfer::create([
            'store_id' => $this->store->id,
            'transfer_number' => $transferNumber,
            'from_location_id' => $request->from_location_id,
            'to_location_id' => $request->to_location_id,
            'status' => StockTransfer::STATUS_PENDING,
            'notes' => $request->notes,
            'requested_at' => now(),
        ]);

        // Add items
        foreach ($request->items as $itemData) {
            $transfer->items()->create([
                'product_id' => $itemData['product_id'],
                'variant_id' => $itemData['variant_id'] ?? null,
                'quantity_requested' => $itemData['quantity'],
                'quantity_shipped' => 0,
                'quantity_received' => 0,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        $transfer->load(['fromLocation', 'toLocation', 'items.product', 'items.variant']);

        return response()->json([
            'success' => true,
            'message' => 'Stock transfer created successfully',
            'data' => new StockTransferResource($transfer),
        ], 201);
    }

    /**
     * Approve a transfer.
     */
    public function approve(int $id): JsonResponse
    {
        $transfer = StockTransfer::where('store_id', $this->store->id)
            ->findOrFail($id);

        if ($transfer->status !== StockTransfer::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending transfers can be approved',
            ], 422);
        }

        $transfer->approve();

        return response()->json([
            'success' => true,
            'message' => 'Transfer approved successfully',
            'data' => new StockTransferResource($transfer->fresh(['fromLocation', 'toLocation', 'items.product', 'items.variant'])),
        ]);
    }

    /**
     * Ship a transfer.
     */
    public function ship(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::where('store_id', $this->store->id)
            ->with('items')
            ->findOrFail($id);

        if (!$transfer->canBeShipped()) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer cannot be shipped',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'tracking_number' => ['nullable', 'string'],
            'carrier' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:commerce_stock_transfer_items,id'],
            'items.*.quantity_shipped' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update shipped quantities
        foreach ($request->items as $itemData) {
            $item = $transfer->items()->findOrFail($itemData['id']);
            $item->update(['quantity_shipped' => $itemData['quantity_shipped']]);
        }

        $transfer->ship($request->tracking_number, $request->carrier);

        return response()->json([
            'success' => true,
            'message' => 'Transfer shipped successfully',
            'data' => new StockTransferResource($transfer->fresh(['fromLocation', 'toLocation', 'items.product', 'items.variant'])),
        ]);
    }

    /**
     * Receive a transfer.
     */
    public function receive(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::where('store_id', $this->store->id)
            ->with('items')
            ->findOrFail($id);

        if (!$transfer->canBeReceived()) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer cannot be received',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:commerce_stock_transfer_items,id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Build received quantities array
        $receivedQuantities = collect($request->items)->pluck('quantity_received', 'id')->toArray();

        $transfer->receive($receivedQuantities);

        return response()->json([
            'success' => true,
            'message' => 'Transfer received successfully',
            'data' => new StockTransferResource($transfer->fresh(['fromLocation', 'toLocation', 'items.product', 'items.variant'])),
        ]);
    }

    /**
     * Cancel a transfer.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::where('store_id', $this->store->id)
            ->findOrFail($id);

        if (!$transfer->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer cannot be cancelled',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $transfer->cancel($request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Transfer cancelled successfully',
            'data' => new StockTransferResource($transfer->fresh(['fromLocation', 'toLocation', 'items.product', 'items.variant'])),
        ]);
    }
}
