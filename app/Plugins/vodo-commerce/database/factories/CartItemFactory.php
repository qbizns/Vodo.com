<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\CartItem;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => fake()->randomFloat(2, 10, 100),
            'options' => null,
            'meta' => null,
        ];
    }

    /**
     * Indicate that the cart item has a variant.
     */
    public function withVariant(?ProductVariant $variant = null): static
    {
        return $this->state(fn (array $attributes) => [
            'variant_id' => $variant?->id ?? ProductVariant::factory(),
        ]);
    }

    /**
     * Set specific quantity.
     */
    public function quantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Set specific unit price.
     */
    public function price(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $price,
        ]);
    }

    /**
     * Add product options to the cart item.
     */
    public function withOptions(array $options = []): static
    {
        return $this->state(fn (array $attributes) => [
            'options' => $options ?: [
                'size' => 'Large',
                'color' => 'Blue',
            ],
        ]);
    }

    /**
     * Add metadata to the cart item.
     */
    public function withMeta(array $meta = []): static
    {
        return $this->state(fn (array $attributes) => [
            'meta' => $meta ?: [
                'gift_wrap' => true,
                'gift_message' => 'Happy Birthday!',
            ],
        ]);
    }

    /**
     * Create an expensive item.
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => fake()->randomFloat(2, 500, 2000),
        ]);
    }

    /**
     * Create a cheap item.
     */
    public function cheap(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => fake()->randomFloat(2, 1, 10),
        ]);
    }

    /**
     * Create multiple quantity item.
     */
    public function bulk(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(10, 50),
        ]);
    }
}
