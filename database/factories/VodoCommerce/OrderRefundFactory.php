<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderRefund;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<OrderRefund>
 */
class OrderRefundFactory extends Factory
{
    protected $model = OrderRefund::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10, 500);

        return [
            'store_id' => Store::factory(),
            'order_id' => Order::factory(),
            'refund_number' => $this->generateRefundNumber(),
            'amount' => $amount,
            'reason' => fake()->randomElement([
                'Customer changed mind',
                'Product defective',
                'Wrong item shipped',
                'Damaged in transit',
                'Not as described',
                'Duplicate order',
            ]),
            'status' => 'pending',
            'refund_method' => fake()->randomElement(['original_payment', 'store_credit', 'manual']),
            'notes' => fake()->optional()->sentence(),
            'processed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'approved_at' => now()->subDays(fake()->numberBetween(0, 3)),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'approved_at' => now()->subDays(5),
            'processed_at' => now()->subDays(2),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_at' => now()->subDays(1),
            'rejection_reason' => fake()->randomElement([
                'Outside return window',
                'Item was used',
                'No proof of purchase',
                'Does not meet refund policy',
            ]),
        ]);
    }

    public function storeCredit(): static
    {
        return $this->state(fn (array $attributes) => [
            'refund_method' => 'store_credit',
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'refund_method' => 'manual',
            'notes' => 'Manual refund - processed offline',
        ]);
    }

    protected function generateRefundNumber(): string
    {
        $prefix = 'RF';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 4));

        return "{$prefix}-{$timestamp}-{$random}";
    }
}
