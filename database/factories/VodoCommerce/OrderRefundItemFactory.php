<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\OrderItem;
use VodoCommerce\Models\OrderRefund;
use VodoCommerce\Models\OrderRefundItem;

/**
 * @extends Factory<OrderRefundItem>
 */
class OrderRefundItemFactory extends Factory
{
    protected $model = OrderRefundItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $amount = fake()->randomFloat(2, 10, 200);

        return [
            'refund_id' => OrderRefund::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => $quantity,
            'amount' => $amount,
        ];
    }

    public function fullRefund(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => fake()->randomFloat(2, 100, 500),
        ]);
    }

    public function partialRefund(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 1,
            'amount' => fake()->randomFloat(2, 10, 50),
        ]);
    }
}
