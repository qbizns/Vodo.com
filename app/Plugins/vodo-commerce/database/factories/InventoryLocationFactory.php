<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\InventoryLocation;
use VodoCommerce\Models\Store;

class InventoryLocationFactory extends Factory
{
    protected $model = InventoryLocation::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => $this->faker->company() . ' ' . $this->faker->randomElement(['Warehouse', 'Store', 'Distribution Center']),
            'code' => strtoupper($this->faker->lexify('???')) . '-' . $this->faker->numerify('###'),
            'type' => $this->faker->randomElement(['warehouse', 'store', 'dropshipper']),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'US',
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'priority' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
            'is_default' => false,
            'settings' => null,
            'meta' => null,
        ];
    }

    public function warehouse(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'warehouse',
            'priority' => $this->faker->numberBetween(0, 50),
        ]);
    }

    public function store(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'store',
            'priority' => $this->faker->numberBetween(50, 75),
        ]);
    }

    public function dropshipper(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'dropshipper',
            'priority' => $this->faker->numberBetween(75, 100),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_default' => true,
            'priority' => 0,
        ]);
    }
}
