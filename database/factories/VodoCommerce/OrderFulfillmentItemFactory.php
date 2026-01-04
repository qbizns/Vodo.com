<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\OrderFulfillment;
use VodoCommerce\Models\OrderFulfillmentItem;
use VodoCommerce\Models\OrderItem;

/**
 * @extends Factory<OrderFulfillmentItem>
 */
class OrderFulfillmentItemFactory extends Factory
{
    protected $model = OrderFulfillmentItem::class;

    public function definition(): array
    {
        return [
            'fulfillment_id' => OrderFulfillment::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => fake()->numberBetween(1, 5),
        ];
    }

    public function singleItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 1,
        ]);
    }

    public function bulk(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(5, 20),
        ]);
    }
}
