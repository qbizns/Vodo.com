<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\CustomerWallet;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<CustomerWallet>
 */
class CustomerWalletFactory extends Factory
{
    protected $model = CustomerWallet::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => Customer::factory(),
            'balance' => fake()->randomFloat(2, 0, 1000),
            'currency' => 'USD',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
        ]);
    }
}
