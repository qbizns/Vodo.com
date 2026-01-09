<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\WebhookEvent;
use VodoCommerce\Models\WebhookSubscription;

class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'subscription_id' => WebhookSubscription::factory(),
            'event_type' => $this->faker->randomElement([
                'order.created',
                'order.updated',
                'product.created',
                'customer.created',
            ]),
            'event_id' => 'evt_' . Str::uuid(),
            'payload' => [
                'id' => $this->faker->randomNumber(),
                'data' => $this->faker->words(5),
                'timestamp' => now()->toIso8601String(),
            ],
            'status' => WebhookEvent::STATUS_PENDING,
            'delivered_at' => null,
            'failed_at' => null,
            'next_retry_at' => null,
            'retry_count' => 0,
            'max_retries' => 3,
            'last_error' => null,
            'error_history' => null,
            'processing_at' => null,
            'processing_by' => null,
            'meta' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WebhookEvent::STATUS_PENDING,
            'next_retry_at' => now()->addMinutes(5),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WebhookEvent::STATUS_PROCESSING,
            'processing_at' => now(),
            'processing_by' => 'worker-' . $this->faker->randomNumber(3),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WebhookEvent::STATUS_DELIVERED,
            'delivered_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WebhookEvent::STATUS_FAILED,
            'failed_at' => now(),
            'last_error' => $this->faker->sentence(),
            'retry_count' => 3,
            'error_history' => [
                ['error' => $this->faker->sentence(), 'retry_count' => 0, 'timestamp' => now()->subHours(2)->toDateTimeString()],
                ['error' => $this->faker->sentence(), 'retry_count' => 1, 'timestamp' => now()->subHours(1)->toDateTimeString()],
                ['error' => $this->faker->sentence(), 'retry_count' => 2, 'timestamp' => now()->toDateTimeString()],
            ],
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WebhookEvent::STATUS_CANCELLED,
        ]);
    }
}
