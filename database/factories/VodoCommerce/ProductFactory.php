<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'sku' => fake()->unique()->bothify('SKU-####-???'),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'compare_at_price' => null,
            'cost_price' => null,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'stock_status' => 'in_stock',
            'is_virtual' => false,
            'is_downloadable' => false,
            'status' => 'active',
            'featured' => false,
            'meta' => [],
        ];
    }

    public function downloadable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_downloadable' => true,
            'is_virtual' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
            'stock_status' => 'out_of_stock',
        ]);
    }
}
