<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Wishlist;
use VodoCommerce\Models\WishlistItem;

class WishlistItemFactory extends Factory
{
    protected $model = WishlistItem::class;

    public function definition(): array
    {
        return [
            'wishlist_id' => Wishlist::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity' => 1,
            'quantity_purchased' => 0,
            'notes' => $this->faker->optional()->sentence(),
            'priority' => WishlistItem::PRIORITY_MEDIUM,
            'price_when_added' => $this->faker->randomFloat(2, 10, 500),
            'notify_on_price_drop' => $this->faker->boolean(40),
            'notify_on_back_in_stock' => $this->faker->boolean(30),
            'is_purchased' => false,
            'purchased_at' => null,
            'purchased_by' => null,
            'display_order' => 0,
            'meta' => null,
        ];
    }

    public function purchased(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_purchased' => true,
            'quantity_purchased' => $attributes['quantity'],
            'purchased_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function partiallyPurchased(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_purchased' => false,
            'quantity_purchased' => max(1, floor($attributes['quantity'] / 2)),
            'purchased_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => WishlistItem::PRIORITY_HIGH,
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn(array $attributes) => [
            'priority' => WishlistItem::PRIORITY_LOW,
        ]);
    }

    public function withPriceTracking(): static
    {
        return $this->state(fn(array $attributes) => [
            'notify_on_price_drop' => true,
            'notify_on_back_in_stock' => true,
        ]);
    }
}
