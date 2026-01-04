<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VodoCommerce\Models\DigitalProductCode;
use VodoCommerce\Models\Product;

/**
 * @extends Factory<DigitalProductCode>
 */
class DigitalProductCodeFactory extends Factory
{
    protected $model = DigitalProductCode::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'code' => Str::upper(Str::random(16)),
            'order_item_id' => null,
            'assigned_at' => null,
            'expires_at' => null,
        ];
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_at' => now(),
        ]);
    }

    public function withExpiration(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays($days),
        ]);
    }
}
