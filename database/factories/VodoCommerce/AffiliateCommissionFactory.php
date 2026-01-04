<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Affiliate;
use VodoCommerce\Models\AffiliateCommission;
use VodoCommerce\Models\Order;

/**
 * @extends Factory<AffiliateCommission>
 */
class AffiliateCommissionFactory extends Factory
{
    protected $model = AffiliateCommission::class;

    public function definition(): array
    {
        $orderAmount = fake()->randomFloat(2, 50, 500);
        $commissionRate = fake()->randomFloat(2, 5, 20);
        $commissionAmount = ($orderAmount * $commissionRate) / 100;

        return [
            'affiliate_id' => Affiliate::factory(),
            'order_id' => Order::factory(),
            'order_amount' => $orderAmount,
            'commission_amount' => $commissionAmount,
            'commission_rate' => $commissionRate,
            'status' => 'pending',
            'meta' => [],
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'approved_at' => now()->subDays(7),
            'paid_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }
}
