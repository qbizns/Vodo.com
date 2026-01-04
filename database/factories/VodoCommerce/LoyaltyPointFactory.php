<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\LoyaltyPoint;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<LoyaltyPoint>
 */
class LoyaltyPointFactory extends Factory
{
    protected $model = LoyaltyPoint::class;

    public function definition(): array
    {
        $lifetimeEarned = fake()->numberBetween(0, 10000);
        $lifetimeSpent = fake()->numberBetween(0, $lifetimeEarned);

        return [
            'store_id' => Store::factory(),
            'customer_id' => Customer::factory(),
            'balance' => $lifetimeEarned - $lifetimeSpent,
            'lifetime_earned' => $lifetimeEarned,
            'lifetime_spent' => $lifetimeSpent,
            'expires_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function withBalance(int $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
            'lifetime_earned' => $balance,
        ]);
    }
}
