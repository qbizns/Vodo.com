<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\BulkOrderActionRequest;
use VodoCommerce\Http\Requests\CancelOrderRequest;
use VodoCommerce\Http\Requests\ExportOrdersRequest;
use VodoCommerce\Http\Requests\UpdateOrderStatusRequest;
use VodoCommerce\Http\Resources\OrderTimelineEventResource;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderStatusHistory;
use VodoCommerce\Models\OrderTimelineEvent;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\OrderExportService;

class OrderManagementController extends Controller
{
    public function __construct(protected OrderExportService $exportService)
    {
    }

    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    public function timeline(int $orderId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $order = Order::where('store_id', $store->id)->findOrFail($orderId);

        $events = $order->timeline()->paginate(50);

        return $this->successResponse(
            OrderTimelineEventResource::collection($events),
            $this->paginationMeta($events)
        );
    }

    public function cancel(CancelOrderRequest $request, int $orderId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $order = Order::where('store_id', $store->id)->findOrFail($orderId);

        if (!$order->canBeCancelled()) {
            return $this->errorResponse('Order cannot be cancelled in its current state', 422);
        }

        $order->cancel(
            $request->input('reason'),
            'admin',
            auth()->id()
        );

        return $this->successResponse(
            null,
            null,
            'Order cancelled successfully'
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $orderId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $order = Order::where('store_id', $store->id)->findOrFail($orderId);

        $oldStatus = $order->status;
        $newStatus = $request->input('status');

        $order->update(['status' => $newStatus]);

        // Record status change in history
        OrderStatusHistory::record(
            $order,
            $newStatus,
            $oldStatus,
            $request->input('note'),
            'admin',
            auth()->id()
        );

        // Add timeline event
        OrderTimelineEvent::createEvent(
            $order,
            'status_changed',
            'Order Status Changed',
            "Status changed from {$oldStatus} to {$newStatus}",
            ['old_status' => $oldStatus, 'new_status' => $newStatus],
            'admin',
            auth()->id()
        );

        return $this->successResponse(
            null,
            null,
            'Order status updated successfully'
        );
    }

    public function export(ExportOrdersRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();

        $filePath = $this->exportService->exportOrders(
            $store,
            $request->validated(),
            $request->input('format', 'csv')
        );

        return $this->successResponse([
            'file_path' => $filePath,
            'download_url' => url('storage/' . $filePath),
        ], null, 'Orders exported successfully');
    }

    public function bulkAction(BulkOrderActionRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();
        $action = $request->input('action');
        $orderIds = $request->input('order_ids');

        $orders = Order::where('store_id', $store->id)
            ->whereIn('id', $orderIds)
            ->get();

        if ($orders->count() !== count($orderIds)) {
            return $this->errorResponse('Some orders were not found', 404);
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        switch ($action) {
            case 'cancel':
                $reason = $request->input('reason', 'Bulk cancellation');
                foreach ($orders as $order) {
                    if ($order->canBeCancelled()) {
                        $order->cancel($reason, 'admin', auth()->id());
                        $results['success']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Order {$order->order_number} cannot be cancelled";
                    }
                }
                break;

            case 'export':
                $filePath = $this->exportService->bulkExportAndMark($store, $orderIds);
                return $this->successResponse([
                    'file_path' => $filePath,
                    'download_url' => url('storage/' . $filePath),
                    'exported_count' => $orders->count(),
                ], null, 'Orders exported successfully');

            case 'mark_as_exported':
                foreach ($orders as $order) {
                    $this->exportService->markAsExported($order);
                    $results['success']++;
                }
                break;

            default:
                return $this->errorResponse('Invalid bulk action', 422);
        }

        return $this->successResponse($results, null, 'Bulk action completed');
    }
}
