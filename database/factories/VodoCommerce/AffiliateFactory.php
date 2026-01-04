<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VodoCommerce\Models\Affiliate;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<Affiliate>
 */
class AffiliateFactory extends Factory
{
    protected $model = Affiliate::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => Customer::factory(),
            'code' => Str::upper(Str::random(8)),
            'commission_rate' => fake()->randomFloat(2, 5, 20),
            'commission_type' => 'percentage',
            'total_earnings' => fake()->randomFloat(2, 0, 5000),
            'pending_balance' => fake()->randomFloat(2, 0, 1000),
            'paid_balance' => fake()->randomFloat(2, 0, 4000),
            'total_clicks' => fake()->numberBetween(0, 10000),
            'total_conversions' => fake()->numberBetween(0, 500),
            'is_active' => true,
            'approved_at' => now(),
            'meta' => [],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function fixedCommission(): static
    {
        return $this->state(fn (array $attributes) => [
            'commission_type' => 'fixed',
            'commission_rate' => fake()->randomFloat(2, 1, 50),
        ]);
    }
}
