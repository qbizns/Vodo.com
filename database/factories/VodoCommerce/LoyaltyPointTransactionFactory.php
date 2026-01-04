<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\LoyaltyPoint;
use VodoCommerce\Models\LoyaltyPointTransaction;

/**
 * @extends Factory<LoyaltyPointTransaction>
 */
class LoyaltyPointTransactionFactory extends Factory
{
    protected $model = LoyaltyPointTransaction::class;

    public function definition(): array
    {
        $points = fake()->numberBetween(10, 500);

        return [
            'loyalty_point_id' => LoyaltyPoint::factory(),
            'type' => fake()->randomElement(['earned', 'spent', 'adjusted']),
            'points' => $points,
            'balance_after' => fake()->numberBetween(0, 1000),
            'description' => fake()->sentence(),
            'meta' => [],
        ];
    }

    public function earned(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'points' => abs($attributes['points']),
        ]);
    }

    public function spent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'spent',
            'points' => -abs($attributes['points']),
        ]);
    }
}
