<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\WebhookSubscription;

class WebhookSubscriptionFactory extends Factory
{
    protected $model = WebhookSubscription::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => $this->faker->words(3, true),
            'url' => $this->faker->url(),
            'description' => $this->faker->optional()->sentence(),
            'events' => $this->faker->randomElements([
                'order.created',
                'order.updated',
                'order.completed',
                'order.cancelled',
                'product.created',
                'product.updated',
                'product.deleted',
                'customer.created',
                'customer.updated',
                'inventory.low_stock',
                'payment.completed',
                'payment.failed',
            ], $this->faker->numberBetween(2, 6)),
            'secret' => 'whsec_' . $this->faker->sha256(),
            'is_active' => true,
            'timeout_seconds' => 30,
            'max_retry_attempts' => 3,
            'retry_delay_seconds' => 60,
            'custom_headers' => $this->faker->optional()->randomElements([
                ['X-Custom-Header' => 'value1'],
                ['Authorization' => 'Bearer token123'],
            ]),
            'total_deliveries' => 0,
            'successful_deliveries' => 0,
            'failed_deliveries' => 0,
            'last_delivery_at' => null,
            'last_success_at' => null,
            'last_failure_at' => null,
            'meta' => null,
        ];
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

    public function withDeliveries(int $successful = 10, int $failed = 2): static
    {
        return $this->state(fn(array $attributes) => [
            'total_deliveries' => $successful + $failed,
            'successful_deliveries' => $successful,
            'failed_deliveries' => $failed,
            'last_delivery_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'last_success_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'last_failure_at' => $failed > 0 ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
        ]);
    }
}
