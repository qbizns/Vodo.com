<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\ShippingZone;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<ShippingZone>
 */
class ShippingZoneFactory extends Factory
{
    protected $model = ShippingZone::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->randomElement([
                'North America',
                'Europe',
                'Asia Pacific',
                'Middle East',
                'South America',
                'Africa',
                'Domestic',
                'International',
            ]),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'priority' => fake()->numberBetween(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(8, 10),
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(0, 3),
        ]);
    }
}
