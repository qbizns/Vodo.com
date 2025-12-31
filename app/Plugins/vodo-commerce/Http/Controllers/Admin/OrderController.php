<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Store;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Services\OrderService;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $orderService = $this->getOrderService($store);

        $filters = $request->only([
            'status',
            'payment_status',
            'fulfillment_status',
            'search',
            'date_from',
            'date_to',
            'sort_by',
            'sort_dir',
        ]);

        $orders = $orderService->list($filters, 20);

        return view('vodo-commerce::admin.orders.index', [
            'store' => $store,
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => [
                'pending' => 'Pending',
                'processing' => 'Processing',
                'on_hold' => 'On Hold',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
                'refunded' => 'Refunded',
                'failed' => 'Failed',
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $orderService = $this->getOrderService($store);
        $order = $orderService->find($id);

        if (!$order) {
            abort(404);
        }

        $trackingInfo = $orderService->getTrackingInfo($order);

        return view('vodo-commerce::admin.orders.show', [
            'store' => $store,
            'order' => $order,
            'trackingInfo' => $trackingInfo,
            'shippingCarriers' => app(ShippingCarrierRegistry::class)->allEnabled(),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $request->validate([
            'status' => 'required|in:pending,processing,on_hold,completed,cancelled,refunded,failed',
        ]);

        $orderService = $this->getOrderService($store);
        $order = $orderService->find($id);

        if (!$order) {
            abort(404);
        }

        $orderService->updateStatus($order, $request->input('status'));

        return back()->with('success', 'Order status updated successfully');
    }

    public function cancel(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $orderService = $this->getOrderService($store);
        $order = $orderService->find($id);

        if (!$order) {
            abort(404);
        }

        try {
            $orderService->cancel($order, $request->input('reason'));

            return back()->with('success', 'Order cancelled successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function refund(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        $orderService = $this->getOrderService($store);
        $order = $orderService->find($id);

        if (!$order) {
            abort(404);
        }

        try {
            $result = $orderService->refund(
                $order,
                $request->input('amount') ? (float) $request->input('amount') : null,
                $request->input('reason')
            );

            if ($result['success']) {
                return back()->with('success', 'Refund processed successfully');
            }

            return back()->with('error', $result['message']);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function addNote(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $request->validate([
            'note' => 'required|string|max:1000',
            'is_internal' => 'boolean',
        ]);

        $orderService = $this->getOrderService($store);
        $order = $orderService->find($id);

        if (!$order) {
            abort(404);
        }

        $orderService->addNote(
            $order,
            $request->input('note'),
            $request->boolean('is_internal', true)
        );

        return back()->with('success', 'Note added successfully');
    }

    public function createShipment(Request $request, int $id)
    {
        $store = $this->getCurrentStore($request);

        if (!$store) {
            return redirect()->route('commerce.admin.dashboard');
        }

        $request->validate([
            'carrier_id' => 'required|string',
            'items' => 'nullable|array',
        ]);

        $orderService = $this->getOrderService($store);
        $order = $orderService->find($id);

        if (!$order) {
            abort(404);
        }

        try {
            $result = $orderService->createShipment(
                $order,
                $request->input('carrier_id'),
                $request->input('items', [])
            );

            return back()->with('success', 'Shipment created. Tracking: ' . $result['tracking_number']);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    protected function getCurrentStore(Request $request): ?Store
    {
        $tenantId = $request->user()?->tenant_id;

        if (!$tenantId) {
            return null;
        }

        return Store::where('tenant_id', $tenantId)->first();
    }

    protected function getOrderService(Store $store): OrderService
    {
        return new OrderService(
            $store,
            app(PaymentGatewayRegistry::class),
            app(ShippingCarrierRegistry::class)
        );
    }
}
