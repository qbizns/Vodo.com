<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VodoCommerce\Models\InventoryLocation;
use VodoCommerce\Models\StockTransfer;
use VodoCommerce\Models\Store;

class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'transfer_number' => 'TRF-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'from_location_id' => InventoryLocation::factory(),
            'to_location_id' => InventoryLocation::factory(),
            'status' => StockTransfer::STATUS_PENDING,
            'notes' => $this->faker->optional()->sentence(),
            'requested_at' => now(),
            'approved_at' => null,
            'shipped_at' => null,
            'received_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'tracking_number' => null,
            'carrier' => null,
            'meta' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => StockTransfer::STATUS_PENDING,
            'approved_at' => null,
            'shipped_at' => null,
            'received_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => StockTransfer::STATUS_PENDING,
            'approved_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function inTransit(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => StockTransfer::STATUS_IN_TRANSIT,
            'approved_at' => $this->faker->dateTimeBetween('-14 days', '-7 days'),
            'shipped_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'tracking_number' => strtoupper($this->faker->bothify('??########')),
            'carrier' => $this->faker->randomElement(['UPS', 'FedEx', 'USPS', 'DHL']),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => StockTransfer::STATUS_COMPLETED,
            'approved_at' => $this->faker->dateTimeBetween('-30 days', '-14 days'),
            'shipped_at' => $this->faker->dateTimeBetween('-14 days', '-7 days'),
            'received_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'tracking_number' => strtoupper($this->faker->bothify('??########')),
            'carrier' => $this->faker->randomElement(['UPS', 'FedEx', 'USPS', 'DHL']),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => StockTransfer::STATUS_CANCELLED,
            'cancelled_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'cancellation_reason' => $this->faker->sentence(),
        ]);
    }
}
