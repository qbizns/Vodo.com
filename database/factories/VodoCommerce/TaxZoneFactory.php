<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\TaxZone;

/**
 * @extends Factory<TaxZone>
 */
class TaxZoneFactory extends Factory
{
    protected $model = TaxZone::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->randomElement([
                'US Sales Tax Zone',
                'Canada GST/PST Zone',
                'EU VAT Zone',
                'UK VAT Zone',
                'Australia GST Zone',
                'Domestic Tax Zone',
                'International Tax Zone',
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
