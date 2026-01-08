<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\InventoryItem;
use VodoCommerce\Models\InventoryLocation;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;

class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'location_id' => InventoryLocation::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'quantity' => $this->faker->numberBetween(0, 100),
            'reserved_quantity' => 0,
            'reorder_point' => $this->faker->numberBetween(5, 20),
            'reorder_quantity' => $this->faker->numberBetween(20, 50),
            'bin_location' => strtoupper($this->faker->lexify('???-?-##')),
            'unit_cost' => $this->faker->randomFloat(2, 5, 100),
            'last_counted_at' => $this->faker->optional(0.5)->dateTimeBetween('-30 days', 'now'),
            'meta' => null,
        ];
    }

    public function withVariant(?ProductVariant $variant = null): static
    {
        return $this->state(fn(array $attributes) => [
            'variant_id' => $variant?->id ?? ProductVariant::factory(),
        ]);
    }

    public function inStock(int $quantity = null): static
    {
        return $this->state(fn(array $attributes) => [
            'quantity' => $quantity ?? $this->faker->numberBetween(50, 200),
            'reserved_quantity' => 0,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $reorderPoint = $this->faker->numberBetween(10, 20);
            return [
                'quantity' => $this->faker->numberBetween(1, $reorderPoint),
                'reorder_point' => $reorderPoint,
                'reorder_quantity' => $this->faker->numberBetween(30, 50),
            ];
        });
    }

    public function outOfStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'quantity' => 0,
            'reserved_quantity' => 0,
        ]);
    }

    public function withReservations(int $reserved = null): static
    {
        return $this->state(function (array $attributes) use ($reserved) {
            $quantity = $attributes['quantity'] ?? $this->faker->numberBetween(20, 100);
            $reservedQty = $reserved ?? $this->faker->numberBetween(1, (int)($quantity / 2));

            return [
                'quantity' => $quantity,
                'reserved_quantity' => min($reservedQty, $quantity),
            ];
        });
    }
}
