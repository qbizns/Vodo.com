<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\CreateRefundRequest;
use VodoCommerce\Http\Requests\ProcessRefundRequest;
use VodoCommerce\Http\Resources\OrderRefundResource;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderRefund;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\OrderRefundService;

class OrderRefundController extends Controller
{
    public function __construct(protected OrderRefundService $refundService)
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

        $query = $order->refunds()->with('items.orderItem')->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $refunds = $query->paginate($perPage);

        return $this->successResponse(
            OrderRefundResource::collection($refunds),
            $this->paginationMeta($refunds)
        );
    }

    public function store(CreateRefundRequest $request, int $orderId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $order = Order::where('store_id', $store->id)->findOrFail($orderId);

        if (!$this->refundService->canRefund($order)) {
            return $this->errorResponse('Order cannot be refunded', 422);
        }

        $refund = $this->refundService->createRefund(
            $order,
            $request->input('items'),
            $request->except('items')
        );

        return $this->successResponse(
            new OrderRefundResource($refund),
            null,
            'Refund created successfully',
            201
        );
    }

    public function show(int $refundId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $refund = OrderRefund::where('store_id', $store->id)
            ->with('items.orderItem', 'order')
            ->findOrFail($refundId);

        return $this->successResponse(
            new OrderRefundResource($refund)
        );
    }

    public function approve(int $refundId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $refund = OrderRefund::where('store_id', $store->id)->findOrFail($refundId);

        if (!$refund->isPending()) {
            return $this->errorResponse('Only pending refunds can be approved', 422);
        }

        $updatedRefund = $this->refundService->approveRefund($refund);

        return $this->successResponse(
            new OrderRefundResource($updatedRefund),
            null,
            'Refund approved successfully'
        );
    }

    public function reject(ProcessRefundRequest $request, int $refundId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $refund = OrderRefund::where('store_id', $store->id)->findOrFail($refundId);

        if (!$refund->isPending()) {
            return $this->errorResponse('Only pending refunds can be rejected', 422);
        }

        $updatedRefund = $this->refundService->rejectRefund(
            $refund,
            $request->input('reason', 'Refund rejected')
        );

        return $this->successResponse(
            new OrderRefundResource($updatedRefund),
            null,
            'Refund rejected'
        );
    }

    public function process(int $refundId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $refund = OrderRefund::where('store_id', $store->id)->findOrFail($refundId);

        if (!$refund->isApproved()) {
            return $this->errorResponse('Only approved refunds can be processed', 422);
        }

        $updatedRefund = $this->refundService->processRefund($refund);

        return $this->successResponse(
            new OrderRefundResource($updatedRefund),
            null,
            'Refund processed successfully'
        );
    }

    public function destroy(int $refundId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $refund = OrderRefund::where('store_id', $store->id)->findOrFail($refundId);

        if (!$refund->isPending()) {
            return $this->errorResponse('Only pending refunds can be deleted', 422);
        }

        $refund->delete();

        return $this->successResponse(
            null,
            null,
            'Refund deleted successfully'
        );
    }
}
