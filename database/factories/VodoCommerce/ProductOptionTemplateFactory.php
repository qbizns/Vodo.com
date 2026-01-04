<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\ProductOptionTemplate;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<ProductOptionTemplate>
 */
class ProductOptionTemplateFactory extends Factory
{
    protected $model = ProductOptionTemplate::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(['select', 'radio', 'checkbox', 'text']),
            'values' => [
                ['label' => 'Option 1', 'price_adjustment' => 0],
                ['label' => 'Option 2', 'price_adjustment' => 10],
            ],
            'is_required' => fake()->boolean(),
            'position' => 0,
        ];
    }
}
