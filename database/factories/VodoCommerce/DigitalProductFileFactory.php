<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\DigitalProductFile;
use VodoCommerce\Models\Product;

/**
 * @extends Factory<DigitalProductFile>
 */
class DigitalProductFileFactory extends Factory
{
    protected $model = DigitalProductFile::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->words(3, true),
            'file_path' => 'digital-products/' . fake()->uuid() . '.pdf',
            'file_size' => fake()->numberBetween(1000, 50000000),
            'mime_type' => 'application/pdf',
            'download_limit' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'download_limit' => null,
        ]);
    }
}
