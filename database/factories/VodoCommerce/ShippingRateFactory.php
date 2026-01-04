<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\ShippingMethod;
use VodoCommerce\Models\ShippingRate;
use VodoCommerce\Models\ShippingZone;

/**
 * @extends Factory<ShippingRate>
 */
class ShippingRateFactory extends Factory
{
    protected $model = ShippingRate::class;

    public function definition(): array
    {
        return [
            'shipping_method_id' => ShippingMethod::factory(),
            'shipping_zone_id' => ShippingZone::factory(),
            'rate' => fake()->randomFloat(2, 5, 50),
            'per_item_rate' => fake()->randomFloat(2, 0, 5),
            'weight_rate' => fake()->randomFloat(2, 0, 2),
            'min_weight' => fake()->optional(0.3)->randomFloat(2, 0, 5),
            'max_weight' => fake()->optional(0.3)->randomFloat(2, 10, 50),
            'min_price' => fake()->optional(0.3)->randomFloat(2, 0, 50),
            'max_price' => fake()->optional(0.3)->randomFloat(2, 100, 1000),
            'is_free_shipping' => false,
            'free_shipping_threshold' => fake()->optional(0.3)->randomFloat(2, 50, 200),
        ];
    }

    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => 0,
            'is_free_shipping' => true,
            'free_shipping_threshold' => null,
        ]);
    }

    public function withFreeThreshold(): static
    {
        return $this->state(fn (array $attributes) => [
            'free_shipping_threshold' => fake()->randomFloat(2, 50, 150),
        ]);
    }

    public function weightBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'weight_rate' => fake()->randomFloat(2, 1, 5),
            'min_weight' => fake()->randomFloat(2, 0, 5),
            'max_weight' => fake()->randomFloat(2, 10, 100),
        ]);
    }

    public function priceBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_price' => fake()->randomFloat(2, 0, 50),
            'max_price' => fake()->randomFloat(2, 100, 500),
        ]);
    }

    public function flatRate(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate' => fake()->randomFloat(2, 5, 20),
            'per_item_rate' => 0,
            'weight_rate' => 0,
            'min_weight' => null,
            'max_weight' => null,
            'min_price' => null,
            'max_price' => null,
        ]);
    }
}
