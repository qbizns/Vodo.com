<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Brand;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'logo' => fake()->imageUrl(200, 200),
            'description' => fake()->paragraph(),
            'website' => fake()->url(),
            'is_active' => true,
            'meta' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
