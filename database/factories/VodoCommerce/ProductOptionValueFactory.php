<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\ProductOption;
use VodoCommerce\Models\ProductOptionValue;

/**
 * @extends Factory<ProductOptionValue>
 */
class ProductOptionValueFactory extends Factory
{
    protected $model = ProductOptionValue::class;

    public function definition(): array
    {
        return [
            'option_id' => ProductOption::factory(),
            'label' => fake()->word(),
            'price_adjustment' => fake()->randomFloat(2, 0, 50),
            'price_type' => 'fixed',
            'position' => 0,
            'is_default' => false,
        ];
    }

    public function isDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'price_type' => 'percentage',
            'price_adjustment' => fake()->randomFloat(2, 0, 100),
        ]);
    }
}
