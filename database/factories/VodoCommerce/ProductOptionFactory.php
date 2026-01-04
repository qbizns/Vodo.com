<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductOption;

/**
 * @extends Factory<ProductOption>
 */
class ProductOptionFactory extends Factory
{
    protected $model = ProductOption::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->randomElement(['Size', 'Color', 'Material', 'Style']),
            'type' => fake()->randomElement(['select', 'radio', 'checkbox', 'text']),
            'is_required' => fake()->boolean(),
            'position' => 0,
        ];
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }
}
