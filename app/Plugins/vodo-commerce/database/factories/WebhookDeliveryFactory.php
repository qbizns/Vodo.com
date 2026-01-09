<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\WebhookDelivery;
use VodoCommerce\Models\WebhookEvent;
use VodoCommerce\Models\WebhookSubscription;

class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    public function definition(): array
    {
        return [
            'event_id' => WebhookEvent::factory(),
            'subscription_id' => WebhookSubscription::factory(),
            'url' => $this->faker->url(),
            'payload' => [
                'event' => 'order.created',
                'data' => $this->faker->words(5),
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $this->faker->sha256(),
            ],
            'attempt_number' => 1,
            'status' => WebhookDelivery::STATUS_PENDING,
            'response_code' => null,
            'response_body' => null,
            'response_headers' => null,
            'error_message' => null,
            'sent_at' => null,
            'completed_at' => null,
            'duration_ms' => null,
            'meta' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);
    }

    public function success(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WebhookDelivery::STATUS_SUCCESS,
            'response_code' => 200,
            'response_body' => '{"status":"ok"}',
            'response_headers' => 'Content-Type: application/json',
            'sent_at' => $this->faker->dateTimeBetween('-1 hour', '-30 minutes'),
            'completed_at' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
            'duration_ms' => $this->faker->numberBetween(100, 2000),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WebhookDelivery::STATUS_FAILED,
            'response_code' => $this->faker->randomElement([400, 404, 500, 503]),
            'response_body' => '{"error":"Internal server error"}',
            'error_message' => $this->faker->sentence(),
            'sent_at' => $this->faker->dateTimeBetween('-1 hour', '-30 minutes'),
            'completed_at' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
            'duration_ms' => $this->faker->numberBetween(100, 5000),
        ]);
    }

    public function timeout(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WebhookDelivery::STATUS_TIMEOUT,
            'error_message' => 'Request timeout',
            'sent_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
            'duration_ms' => 30000,
        ]);
    }
}
