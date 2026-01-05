<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\CouponUsage;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<CouponUsage>
 */
class CouponUsageFactory extends Factory
{
    protected $model = CouponUsage::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'discount_id' => Discount::factory(),
            'customer_id' => Customer::factory(),
            'order_id' => Order::factory(),
            'session_id' => fake()->uuid(),
            'discount_code' => strtoupper(fake()->lexify('??????')),
            'discount_amount' => fake()->randomFloat(2, 5, 100),
            'order_subtotal' => fake()->randomFloat(2, 50, 500),
            'applied_to_items' => [],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function withItems(array $items): static
    {
        return $this->state(fn (array $attributes) => [
            'applied_to_items' => $items,
        ]);
    }
}
