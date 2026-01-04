<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\CustomerWallet;
use VodoCommerce\Models\CustomerWalletTransaction;

/**
 * @extends Factory<CustomerWalletTransaction>
 */
class CustomerWalletTransactionFactory extends Factory
{
    protected $model = CustomerWalletTransaction::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10, 500);

        return [
            'wallet_id' => CustomerWallet::factory(),
            'type' => fake()->randomElement(['deposit', 'withdrawal', 'refund', 'purchase']),
            'amount' => $amount,
            'balance_after' => fake()->randomFloat(2, 0, 1000),
            'description' => fake()->sentence(),
            'reference' => fake()->optional()->uuid(),
            'meta' => [],
        ];
    }

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deposit',
            'amount' => abs($attributes['amount']),
        ]);
    }

    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'withdrawal',
            'amount' => -abs($attributes['amount']),
        ]);
    }
}
