<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\ShippingMethod;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    protected $model = ShippingMethod::class;

    public function definition(): array
    {
        $methods = [
            ['name' => 'Standard Shipping', 'code' => 'standard', 'min' => 5, 'max' => 7, 'cost' => 5.99],
            ['name' => 'Express Shipping', 'code' => 'express', 'min' => 2, 'max' => 3, 'cost' => 15.99],
            ['name' => 'Overnight Shipping', 'code' => 'overnight', 'min' => 1, 'max' => 1, 'cost' => 29.99],
            ['name' => 'Free Shipping', 'code' => 'free', 'min' => 7, 'max' => 10, 'cost' => 0],
            ['name' => 'Economy Shipping', 'code' => 'economy', 'min' => 10, 'max' => 14, 'cost' => 3.99],
        ];

        $method = fake()->randomElement($methods);

        return [
            'store_id' => Store::factory(),
            'name' => $method['name'],
            'code' => $method['code'] . '-' . fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->optional()->sentence(),
            'calculation_type' => fake()->randomElement(['flat_rate', 'per_item', 'weight_based', 'price_based']),
            'base_cost' => $method['cost'],
            'min_delivery_days' => $method['min'],
            'max_delivery_days' => $method['max'],
            'is_active' => true,
            'requires_address' => true,
            'min_order_amount' => fake()->optional(0.3)->randomFloat(2, 0, 50),
            'max_order_amount' => fake()->optional(0.2)->randomFloat(2, 500, 5000),
            'settings' => [],
        ];
    }

    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Standard Shipping',
            'code' => 'standard',
            'calculation_type' => 'flat_rate',
            'base_cost' => 5.99,
            'min_delivery_days' => 5,
            'max_delivery_days' => 7,
        ]);
    }

    public function express(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Express Shipping',
            'code' => 'express',
            'calculation_type' => 'flat_rate',
            'base_cost' => 15.99,
            'min_delivery_days' => 2,
            'max_delivery_days' => 3,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Free Shipping',
            'code' => 'free',
            'calculation_type' => 'flat_rate',
            'base_cost' => 0,
            'min_order_amount' => 50.00,
            'min_delivery_days' => 7,
            'max_delivery_days' => 10,
        ]);
    }

    public function weightBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculation_type' => 'weight_based',
        ]);
    }

    public function perItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculation_type' => 'per_item',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
