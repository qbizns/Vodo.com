<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderNote;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<OrderNote>
 */
class OrderNoteFactory extends Factory
{
    protected $model = OrderNote::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'order_id' => Order::factory(),
            'author_type' => fake()->randomElement(['admin', 'customer', 'system']),
            'author_id' => fn (array $attributes) => $attributes['author_type'] === 'system' ? null : fake()->numberBetween(1, 100),
            'content' => fake()->paragraph(),
            'is_customer_visible' => fake()->boolean(30),
        ];
    }

    public function customerVisible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_customer_visible' => true,
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_customer_visible' => false,
            'author_type' => 'admin',
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'author_type' => 'system',
            'author_id' => null,
            'is_customer_visible' => false,
        ]);
    }

    public function fromCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'author_type' => 'customer',
            'is_customer_visible' => true,
        ]);
    }
}
