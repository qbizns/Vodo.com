<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\CreateFulfillmentRequest;
use VodoCommerce\Http\Requests\UpdateFulfillmentRequest;
use VodoCommerce\Http\Resources\OrderFulfillmentResource;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderFulfillment;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\OrderFulfillmentService;

class OrderFulfillmentController extends Controller
{
    public function __construct(protected OrderFulfillmentService $fulfillmentService)
    {
    }

    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    public function index(Request $request, int $orderId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $order = Order::where('store_id', $store->id)->findOrFail($orderId);

        $query = $order->fulfillments()->with('items.orderItem')->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $fulfillments = $query->paginate($perPage);

        return $this->successResponse(
            OrderFulfillmentResource::collection($fulfillments),
            $this->paginationMeta($fulfillments)
        );
    }

    public function store(CreateFulfillmentRequest $request, int $orderId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $order = Order::where('store_id', $store->id)->findOrFail($orderId);

        $fulfillment = $this->fulfillmentService->createFulfillment(
            $order,
            $request->input('items'),
            $request->except('items')
        );

        return $this->successResponse(
            new OrderFulfillmentResource($fulfillment),
            null,
            'Fulfillment created successfully',
            201
        );
    }

    public function show(int $fulfillmentId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $fulfillment = OrderFulfillment::where('store_id', $store->id)
            ->with('items.orderItem', 'order')
            ->findOrFail($fulfillmentId);

        return $this->successResponse(
            new OrderFulfillmentResource($fulfillment)
        );
    }

    public function update(UpdateFulfillmentRequest $request, int $fulfillmentId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $fulfillment = OrderFulfillment::where('store_id', $store->id)->findOrFail($fulfillmentId);

        $fulfillment->update($request->validated());

        return $this->successResponse(
            new OrderFulfillmentResource($fulfillment->fresh()),
            null,
            'Fulfillment updated successfully'
        );
    }

    public function ship(Request $request, int $fulfillmentId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $fulfillment = OrderFulfillment::where('store_id', $store->id)->findOrFail($fulfillmentId);

        $updatedFulfillment = $this->fulfillmentService->shipFulfillment(
            $fulfillment,
            $request->all()
        );

        return $this->successResponse(
            new OrderFulfillmentResource($updatedFulfillment),
            null,
            'Fulfillment marked as shipped'
        );
    }

    public function markAsDelivered(int $fulfillmentId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $fulfillment = OrderFulfillment::where('store_id', $store->id)->findOrFail($fulfillmentId);

        $updatedFulfillment = $this->fulfillmentService->markAsDelivered($fulfillment);

        return $this->successResponse(
            new OrderFulfillmentResource($updatedFulfillment),
            null,
            'Fulfillment marked as delivered'
        );
    }

    public function destroy(int $fulfillmentId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $fulfillment = OrderFulfillment::where('store_id', $store->id)->findOrFail($fulfillmentId);

        $fulfillment->delete();

        return $this->successResponse(
            null,
            null,
            'Fulfillment deleted successfully'
        );
    }
}
