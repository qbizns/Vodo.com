<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Store;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;

class OrderService
{
    public function __construct(
        protected Store $store,
        protected PaymentGatewayRegistry $paymentGateways,
        protected ShippingCarrierRegistry $shippingCarriers
    ) {
    }

    public function find(int $orderId): ?Order
    {
        return Order::where('store_id', $this->store->id)
            ->with(['items', 'customer'])
            ->find($orderId);
    }

    public function findByNumber(string $orderNumber): ?Order
    {
        return Order::where('store_id', $this->store->id)
            ->where('order_number', $orderNumber)
            ->with(['items', 'customer'])
            ->first();
    }

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::where('store_id', $this->store->id)
            ->with(['customer']);

        $this->applyFilters($query, $filters);

        $sortBy = $filters['sort_by'] ?? 'placed_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['fulfillment_status'])) {
            $query->where('fulfillment_status', $filters['fulfillment_status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['customer_email'])) {
            $query->where('customer_email', 'like', "%{$filters['customer_email']}%");
        }

        if (!empty($filters['date_from'])) {
            $query->where('placed_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('placed_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $oldStatus = $order->status;

        $order->update(['status' => $status]);

        if ($status === Order::STATUS_COMPLETED && $oldStatus !== Order::STATUS_COMPLETED) {
            $order->update([
                'completed_at' => now(),
                'fulfillment_status' => Order::FULFILLMENT_FULFILLED,
            ]);
        }

        do_action('commerce.order.status_changed', $order, $oldStatus, $status);

        return $order->fresh();
    }

    public function cancel(Order $order, ?string $reason = null): Order
    {
        if (!$order->canBeCancelled()) {
            throw new \InvalidArgumentException('This order cannot be cancelled');
        }

        $order->cancel($reason);

        do_action('commerce.order.cancelled', $order, $reason);

        return $order->fresh();
    }

    public function refund(Order $order, ?float $amount = null, ?string $reason = null): array
    {
        if (!$order->canBeRefunded()) {
            throw new \InvalidArgumentException('This order cannot be refunded');
        }

        $gateway = $this->paymentGateways->get($order->payment_method);

        if (!$gateway) {
            throw new \InvalidArgumentException('Payment gateway not available for refund');
        }

        $refundAmount = $amount ?? (float) $order->total;

        $result = $gateway->refund(
            transactionId: $order->payment_reference,
            amount: $refundAmount,
            currency: $order->currency,
            reason: $reason
        );

        if ($result->success) {
            $meta = $order->meta ?? [];
            $meta['refunds'][] = [
                'amount' => $refundAmount,
                'reason' => $reason,
                'reference' => $result->refundId,
                'refunded_at' => now()->toIso8601String(),
            ];

            $isFullRefund = $refundAmount >= (float) $order->total;

            $order->update([
                'payment_status' => $isFullRefund ? Order::PAYMENT_REFUNDED : $order->payment_status,
                'status' => $isFullRefund ? Order::STATUS_REFUNDED : $order->status,
                'meta' => $meta,
            ]);

            do_action('commerce.order.refunded', $order, $refundAmount);
        }

        return [
            'success' => $result->success,
            'refund_id' => $result->refundId,
            'message' => $result->message,
        ];
    }

    public function addNote(Order $order, string $note, bool $isInternal = true): Order
    {
        $meta = $order->meta ?? [];
        $meta['notes'][] = [
            'content' => $note,
            'is_internal' => $isInternal,
            'created_at' => now()->toIso8601String(),
        ];

        $order->update(['meta' => $meta]);

        return $order->fresh();
    }

    public function createShipment(Order $order, string $carrierId, array $items = []): array
    {
        $carrier = $this->shippingCarriers->get($carrierId);

        if (!$carrier) {
            throw new \InvalidArgumentException("Shipping carrier '{$carrierId}' not found");
        }

        $address = new \VodoCommerce\Contracts\ShippingAddress(
            firstName: $order->shipping_address['first_name'] ?? '',
            lastName: $order->shipping_address['last_name'] ?? '',
            address1: $order->shipping_address['address1'] ?? '',
            city: $order->shipping_address['city'] ?? '',
            postalCode: $order->shipping_address['postal_code'] ?? '',
            country: $order->shipping_address['country'] ?? '',
            address2: $order->shipping_address['address2'] ?? null,
            state: $order->shipping_address['state'] ?? null,
            phone: $order->shipping_address['phone'] ?? null,
            company: $order->shipping_address['company'] ?? null,
        );

        // If no items specified, ship all
        if (empty($items)) {
            $items = $order->items->map(fn($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'weight' => $item->product?->weight ?? 0,
                'dimensions' => $item->product?->dimensions ?? [],
            ])->toArray();
        }

        $shipment = $carrier->createShipment(
            orderId: (string) $order->id,
            address: $address,
            items: $items,
            serviceCode: $order->shipping_method
        );

        // Store shipment info
        $meta = $order->meta ?? [];
        $meta['shipments'][] = [
            'carrier' => $carrier->getName(),
            'carrier_id' => $carrierId,
            'shipment_id' => $shipment->shipmentId,
            'tracking_number' => $shipment->trackingNumber,
            'tracking_url' => $shipment->trackingUrl,
            'label_url' => $shipment->labelUrl,
            'created_at' => now()->toIso8601String(),
        ];

        // Update fulfillment status
        $allItemsShipped = $this->checkAllItemsShipped($order, $meta['shipments'] ?? []);

        $order->update([
            'meta' => $meta,
            'fulfillment_status' => $allItemsShipped ? Order::FULFILLMENT_FULFILLED : Order::FULFILLMENT_PARTIAL,
        ]);

        do_action('commerce.order.shipped', $order, $shipment);

        return [
            'shipment_id' => $shipment->shipmentId,
            'tracking_number' => $shipment->trackingNumber,
            'tracking_url' => $shipment->trackingUrl,
            'label_url' => $shipment->labelUrl,
        ];
    }

    protected function checkAllItemsShipped(Order $order, array $shipments): bool
    {
        // Simplified check - in real implementation would track per-item fulfillment
        return count($shipments) > 0;
    }

    public function getTrackingInfo(Order $order): array
    {
        $shipments = $order->meta['shipments'] ?? [];
        $tracking = [];

        foreach ($shipments as $shipment) {
            $carrier = $this->shippingCarriers->get($shipment['carrier_id']);
            if (!$carrier) {
                continue;
            }

            try {
                $info = $carrier->trackShipment($shipment['tracking_number']);
                $tracking[] = [
                    'carrier' => $shipment['carrier'],
                    'tracking_number' => $shipment['tracking_number'],
                    'status' => $info->status,
                    'estimated_delivery' => $info->estimatedDelivery?->toIso8601String(),
                    'events' => $info->events,
                ];
            } catch (\Exception $e) {
                $tracking[] = [
                    'carrier' => $shipment['carrier'],
                    'tracking_number' => $shipment['tracking_number'],
                    'status' => 'unknown',
                    'error' => 'Unable to fetch tracking info',
                ];
            }
        }

        return $tracking;
    }

    public function getStats(array $filters = []): array
    {
        $query = Order::where('store_id', $this->store->id);

        if (!empty($filters['date_from'])) {
            $query->where('placed_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('placed_at', '<=', $filters['date_to']);
        }

        return [
            'total_orders' => (clone $query)->count(),
            'total_revenue' => (clone $query)->where('payment_status', Order::PAYMENT_PAID)->sum('total'),
            'average_order_value' => (clone $query)->where('payment_status', Order::PAYMENT_PAID)->avg('total') ?? 0,
            'orders_by_status' => (clone $query)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'pending_orders' => (clone $query)->pending()->count(),
            'unfulfilled_orders' => (clone $query)->unfulfilled()->count(),
        ];
    }

    public function getRevenueByPeriod(string $period = 'day', int $limit = 30): array
    {
        $format = match ($period) {
            'hour' => '%Y-%m-%d %H:00:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%W',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return Order::where('store_id', $this->store->id)
            ->where('payment_status', Order::PAYMENT_PAID)
            ->select(
                DB::raw("DATE_FORMAT(placed_at, '{$format}') as period"),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
