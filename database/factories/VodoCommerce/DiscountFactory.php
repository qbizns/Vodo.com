<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        $type = fake()->randomElement([Discount::TYPE_PERCENTAGE, Discount::TYPE_FIXED]);
        $value = $type === Discount::TYPE_PERCENTAGE
            ? fake()->numberBetween(5, 50)
            : fake()->numberBetween(10, 100);

        return [
            'store_id' => Store::factory(),
            'code' => strtoupper(fake()->unique()->lexify('??????')),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'type' => $type,
            'value' => $value,
            'minimum_order' => fake()->optional()->randomFloat(2, 50, 200),
            'usage_limit' => fake()->optional()->numberBetween(10, 1000),
            'per_customer_limit' => fake()->optional()->numberBetween(1, 5),
            'current_usage' => 0,
            'starts_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'expires_at' => now()->addDays(fake()->numberBetween(30, 90)),
            'is_active' => true,
            'conditions' => [],

            // Phase 4.2 fields (default to null for standard discounts)
            'applies_to' => Discount::APPLIES_TO_ALL,
            'promotion_type' => null,
            'target_config' => null,
            'customer_eligibility' => Discount::ELIGIBILITY_ALL,
            'first_order_only' => false,
            'is_stackable' => false,
            'priority' => 0,
            'is_automatic' => false,
            'stop_further_rules' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    public function automatic(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_automatic' => true,
            'display_message' => fake()->sentence(),
        ]);
    }

    public function buyXGetY(): static
    {
        return $this->state(fn (array $attributes) => [
            'promotion_type' => Discount::PROMOTION_BUY_X_GET_Y,
            'target_config' => [
                'buy_quantity' => 2,
                'get_quantity' => 1,
                'get_discount_percent' => 100,
                'max_applications' => null,
            ],
        ]);
    }

    public function tiered(): static
    {
        return $this->state(fn (array $attributes) => [
            'promotion_type' => Discount::PROMOTION_TIERED,
            'target_config' => [
                'tiers' => [
                    ['threshold' => 100, 'discount_percent' => 10],
                    ['threshold' => 200, 'discount_percent' => 15],
                    ['threshold' => 500, 'discount_percent' => 20],
                ],
            ],
        ]);
    }

    public function bundle(): static
    {
        return $this->state(fn (array $attributes) => [
            'promotion_type' => Discount::PROMOTION_BUNDLE,
            'target_config' => [
                'required_products' => [1, 2, 3],
            ],
            'applies_to' => Discount::APPLIES_TO_SPECIFIC_PRODUCTS,
            'included_product_ids' => [1, 2, 3],
        ]);
    }

    public function freeGift(): static
    {
        return $this->state(fn (array $attributes) => [
            'promotion_type' => Discount::PROMOTION_FREE_GIFT,
            'target_config' => [
                'free_product_ids' => [1],
                'minimum_purchase' => 100,
            ],
        ]);
    }

    public function firstOrderOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_order_only' => true,
            'customer_eligibility' => Discount::ELIGIBILITY_NEW_CUSTOMERS,
        ]);
    }

    public function stackable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_stackable' => true,
            'priority' => fake()->numberBetween(1, 10),
        ]);
    }

    public function specificProducts(array $productIds): static
    {
        return $this->state(fn (array $attributes) => [
            'applies_to' => Discount::APPLIES_TO_SPECIFIC_PRODUCTS,
            'included_product_ids' => $productIds,
        ]);
    }
}
