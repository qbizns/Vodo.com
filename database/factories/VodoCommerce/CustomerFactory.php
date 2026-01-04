<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->optional()->company(),
            'accepts_marketing' => fake()->boolean(),
            'total_orders' => 0,
            'total_spent' => 0,
            'tags' => [],
            'notes' => fake()->optional()->sentence(),
            'meta' => [],
            'is_banned' => false,
        ];
    }

    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_banned' => true,
            'banned_at' => now(),
            'ban_reason' => fake()->sentence(),
        ]);
    }

    public function withOrders(int $count = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'total_orders' => $count,
            'total_spent' => fake()->randomFloat(2, 100, 5000),
        ]);
    }
}
