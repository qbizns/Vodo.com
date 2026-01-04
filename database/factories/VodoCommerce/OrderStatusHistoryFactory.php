<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderStatusHistory;

/**
 * @extends Factory<OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    protected $model = OrderStatusHistory::class;

    public function definition(): array
    {
        $statuses = ['pending', 'processing', 'completed', 'cancelled', 'refunded'];
        $fromStatus = fake()->randomElement($statuses);
        $toStatus = fake()->randomElement(array_diff($statuses, [$fromStatus]));

        return [
            'order_id' => Order::factory(),
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => fake()->optional()->sentence(),
            'changed_by_type' => fake()->randomElement(['admin', 'customer', 'system']),
            'changed_by_id' => fn (array $attributes) => $attributes['changed_by_type'] === 'system' ? null : fake()->numberBetween(1, 100),
        ];
    }

    public function pendingToProcessing(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_status' => 'pending',
            'to_status' => 'processing',
            'note' => 'Order moved to processing',
            'changed_by_type' => 'admin',
        ]);
    }

    public function processingToCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_status' => 'processing',
            'to_status' => 'completed',
            'note' => 'Order completed successfully',
            'changed_by_type' => 'admin',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_status' => fake()->randomElement(['pending', 'processing']),
            'to_status' => 'cancelled',
            'note' => fake()->randomElement([
                'Cancelled by customer request',
                'Payment failed',
                'Out of stock',
            ]),
            'changed_by_type' => fake()->randomElement(['admin', 'customer', 'system']),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'from_status' => 'completed',
            'to_status' => 'refunded',
            'note' => 'Order refunded',
            'changed_by_type' => 'admin',
        ]);
    }

    public function systemChange(): static
    {
        return $this->state(fn (array $attributes) => [
            'changed_by_type' => 'system',
            'changed_by_id' => null,
            'note' => 'Automatic status change',
        ]);
    }
}
