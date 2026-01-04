<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderFulfillment;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<OrderFulfillment>
 */
class OrderFulfillmentFactory extends Factory
{
    protected $model = OrderFulfillment::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'order_id' => Order::factory(),
            'tracking_number' => fake()->optional(0.7)->numerify('##########'),
            'carrier' => fake()->optional(0.7)->randomElement(['DHL', 'FedEx', 'UPS', 'USPS', 'Aramex', 'SMSA']),
            'tracking_url' => fn (array $attributes) => $attributes['tracking_number']
                ? fake()->url()
                : null,
            'status' => 'pending',
            'notes' => fake()->optional()->sentence(),
            'shipped_at' => null,
            'delivered_at' => null,
            'estimated_delivery' => fake()->optional(0.5)->dateTimeBetween('+3 days', '+14 days'),
        ];
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_transit',
            'shipped_at' => now()->subDays(fake()->numberBetween(1, 5)),
            'tracking_number' => $attributes['tracking_number'] ?? fake()->numerify('##########'),
            'carrier' => $attributes['carrier'] ?? fake()->randomElement(['DHL', 'FedEx', 'UPS']),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'shipped_at' => now()->subDays(7),
            'delivered_at' => now()->subDays(2),
            'tracking_number' => $attributes['tracking_number'] ?? fake()->numerify('##########'),
            'carrier' => $attributes['carrier'] ?? fake()->randomElement(['DHL', 'FedEx', 'UPS']),
        ]);
    }

    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_transit',
            'shipped_at' => now()->subDays(3),
        ]);
    }

    public function outForDelivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'out_for_delivery',
            'shipped_at' => now()->subDays(5),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'shipped_at' => now()->subDays(3),
            'notes' => fake()->randomElement([
                'Delivery attempted - no one home',
                'Address not found',
                'Customer refused delivery',
                'Damaged in transit',
            ]),
        ]);
    }
}
