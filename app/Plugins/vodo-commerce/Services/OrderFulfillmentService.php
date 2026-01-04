<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderFulfillment;
use VodoCommerce\Models\OrderTimelineEvent;

class OrderFulfillmentService
{
    /**
     * Create a new fulfillment for an order.
     */
    public function createFulfillment(Order $order, array $items, array $data = []): OrderFulfillment
    {
        $fulfillment = $order->fulfillments()->create([
            'store_id' => $order->store_id,
            'tracking_number' => $data['tracking_number'] ?? null,
            'carrier' => $data['carrier'] ?? null,
            'status' => 'pending',
            'estimated_delivery' => $data['estimated_delivery'] ?? null,
            'tracking_url' => $data['tracking_url'] ?? null,
            'notes' => $data['notes'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);

        // Attach items to fulfillment
        foreach ($items as $item) {
            $fulfillment->items()->create([
                'order_item_id' => $item['order_item_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        // Add timeline event
        OrderTimelineEvent::createEvent(
            $order,
            'fulfillment_created',
            'Fulfillment Created',
            "Fulfillment created with {$fulfillment->getItemsCount()} item(s)",
            ['fulfillment_id' => $fulfillment->id]
        );

        do_action('commerce.fulfillment.created', $fulfillment);

        return $fulfillment->load('items.orderItem');
    }

    /**
     * Mark a fulfillment as shipped with tracking information.
     */
    public function shipFulfillment(OrderFulfillment $fulfillment, array $trackingData): OrderFulfillment
    {
        $fulfillment->markAsShipped(
            $trackingData['tracking_number'] ?? null,
            $trackingData['carrier'] ?? null
        );

        if (isset($trackingData['tracking_url'])) {
            $fulfillment->tracking_url = $trackingData['tracking_url'];
        }

        if (isset($trackingData['estimated_delivery'])) {
            $fulfillment->estimated_delivery = $trackingData['estimated_delivery'];
        }

        $fulfillment->save();

        // Add timeline event
        OrderTimelineEvent::createEvent(
            $fulfillment->order,
            'fulfillment_shipped',
            'Fulfillment Shipped',
            "Tracking number: {$fulfillment->tracking_number}",
            [
                'fulfillment_id' => $fulfillment->id,
                'tracking_number' => $fulfillment->tracking_number,
                'carrier' => $fulfillment->carrier,
            ]
        );

        do_action('commerce.fulfillment.shipped', $fulfillment);

        return $fulfillment->fresh();
    }

    /**
     * Mark a fulfillment as delivered.
     */
    public function markAsDelivered(OrderFulfillment $fulfillment): OrderFulfillment
    {
        $fulfillment->markAsDelivered();

        // Add timeline event
        OrderTimelineEvent::createEvent(
            $fulfillment->order,
            'fulfillment_delivered',
            'Fulfillment Delivered',
            "Order items have been delivered",
            ['fulfillment_id' => $fulfillment->id]
        );

        // Check if all order items are fulfilled
        $this->updateOrderFulfillmentStatus($fulfillment->order);

        do_action('commerce.fulfillment.delivered', $fulfillment);

        return $fulfillment->fresh();
    }

    /**
     * Update tracking information for a fulfillment.
     */
    public function updateTracking(
        OrderFulfillment $fulfillment,
        string $trackingNumber,
        ?string $carrier = null,
        ?string $trackingUrl = null
    ): OrderFulfillment {
        $fulfillment->update([
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'tracking_url' => $trackingUrl,
        ]);

        // Add timeline event
        OrderTimelineEvent::createEvent(
            $fulfillment->order,
            'tracking_updated',
            'Tracking Updated',
            "Tracking number updated to: {$trackingNumber}",
            [
                'fulfillment_id' => $fulfillment->id,
                'tracking_number' => $trackingNumber,
                'carrier' => $carrier,
            ]
        );

        do_action('commerce.fulfillment.tracking_updated', $fulfillment);

        return $fulfillment->fresh();
    }

    /**
     * Get fulfillment status with detailed information.
     */
    public function getFulfillmentStatus(OrderFulfillment $fulfillment): array
    {
        return [
            'id' => $fulfillment->id,
            'status' => $fulfillment->status,
            'tracking_number' => $fulfillment->tracking_number,
            'carrier' => $fulfillment->carrier,
            'tracking_url' => $fulfillment->tracking_url,
            'shipped_at' => $fulfillment->shipped_at?->toIso8601String(),
            'delivered_at' => $fulfillment->delivered_at?->toIso8601String(),
            'estimated_delivery' => $fulfillment->estimated_delivery?->toIso8601String(),
            'items_count' => $fulfillment->getItemsCount(),
            'total_quantity' => $fulfillment->getTotalQuantity(),
            'has_tracking' => $fulfillment->hasTracking(),
            'is_shipped' => $fulfillment->isShipped(),
            'is_delivered' => $fulfillment->isDelivered(),
        ];
    }

    /**
     * Update order fulfillment status based on fulfillments.
     */
    protected function updateOrderFulfillmentStatus(Order $order): void
    {
        $deliveredCount = $order->fulfillments()->where('status', 'delivered')->count();
        $totalCount = $order->fulfillments()->count();

        if ($deliveredCount === 0) {
            $status = Order::FULFILLMENT_UNFULFILLED;
        } elseif ($deliveredCount === $totalCount) {
            $status = Order::FULFILLMENT_FULFILLED;
        } else {
            $status = Order::FULFILLMENT_PARTIAL;
        }

        $order->update(['fulfillment_status' => $status]);
    }
}
