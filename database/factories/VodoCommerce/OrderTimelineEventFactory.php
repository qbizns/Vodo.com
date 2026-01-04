<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderTimelineEvent;

/**
 * @extends Factory<OrderTimelineEvent>
 */
class OrderTimelineEventFactory extends Factory
{
    protected $model = OrderTimelineEvent::class;

    public function definition(): array
    {
        $eventType = fake()->randomElement([
            'order_created',
            'status_changed',
            'payment_received',
            'note_added',
            'fulfillment_created',
            'shipped',
            'delivered',
            'refund_requested',
            'refund_approved',
            'order_cancelled',
        ]);

        return [
            'order_id' => Order::factory(),
            'event_type' => $eventType,
            'title' => $this->getTitleForEventType($eventType),
            'description' => fake()->optional()->sentence(),
            'metadata' => fake()->optional()->passthrough([
                'key' => fake()->word(),
                'value' => fake()->word(),
            ]),
            'created_by_type' => fake()->randomElement(['admin', 'customer', 'system']),
            'created_by_id' => fn (array $attributes) => $attributes['created_by_type'] === 'system' ? null : fake()->numberBetween(1, 100),
        ];
    }

    public function orderCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'order_created',
            'title' => 'Order Created',
            'description' => 'Order was successfully created',
            'created_by_type' => 'system',
            'created_by_id' => null,
        ]);
    }

    public function statusChanged(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'status_changed',
            'title' => 'Order Status Changed',
            'description' => fake()->randomElement([
                'Status changed from pending to processing',
                'Status changed from processing to completed',
                'Status changed from completed to delivered',
            ]),
            'created_by_type' => 'admin',
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'shipped',
            'title' => 'Order Shipped',
            'description' => 'Order has been shipped',
            'metadata' => [
                'tracking_number' => fake()->numerify('##########'),
                'carrier' => fake()->randomElement(['DHL', 'FedEx', 'UPS']),
            ],
            'created_by_type' => 'admin',
        ]);
    }

    public function refundRequested(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'refund_requested',
            'title' => 'Refund Requested',
            'description' => 'Customer requested a refund',
            'created_by_type' => 'customer',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'order_cancelled',
            'title' => 'Order Cancelled',
            'description' => fake()->randomElement([
                'Order cancelled by customer',
                'Order cancelled due to payment failure',
                'Order cancelled - out of stock',
            ]),
            'created_by_type' => fake()->randomElement(['admin', 'customer', 'system']),
        ]);
    }

    protected function getTitleForEventType(string $eventType): string
    {
        return match ($eventType) {
            'order_created' => 'Order Created',
            'status_changed' => 'Status Changed',
            'payment_received' => 'Payment Received',
            'note_added' => 'Note Added',
            'fulfillment_created' => 'Fulfillment Created',
            'shipped' => 'Order Shipped',
            'delivered' => 'Order Delivered',
            'refund_requested' => 'Refund Requested',
            'refund_approved' => 'Refund Approved',
            'order_cancelled' => 'Order Cancelled',
            default => fake()->words(2, true),
        };
    }
}
