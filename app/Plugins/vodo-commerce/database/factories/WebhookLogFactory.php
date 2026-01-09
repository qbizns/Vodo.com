<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\WebhookDelivery;
use VodoCommerce\Models\WebhookEvent;
use VodoCommerce\Models\WebhookLog;
use VodoCommerce\Models\WebhookSubscription;

class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'subscription_id' => WebhookSubscription::factory(),
            'event_id' => null,
            'delivery_id' => null,
            'level' => $this->faker->randomElement([
                WebhookLog::LEVEL_DEBUG,
                WebhookLog::LEVEL_INFO,
                WebhookLog::LEVEL_WARNING,
                WebhookLog::LEVEL_ERROR,
            ]),
            'message' => $this->faker->sentence(),
            'context' => [
                'additional_info' => $this->faker->words(3, true),
            ],
            'category' => $this->faker->randomElement(['delivery', 'retry', 'validation', 'configuration']),
            'action' => $this->faker->randomElement(['sent', 'failed', 'retrying', 'validated']),
            'meta' => null,
        ];
    }

    public function debug(): static
    {
        return $this->state(fn(array $attributes) => [
            'level' => WebhookLog::LEVEL_DEBUG,
        ]);
    }

    public function info(): static
    {
        return $this->state(fn(array $attributes) => [
            'level' => WebhookLog::LEVEL_INFO,
        ]);
    }

    public function warning(): static
    {
        return $this->state(fn(array $attributes) => [
            'level' => WebhookLog::LEVEL_WARNING,
        ]);
    }

    public function error(): static
    {
        return $this->state(fn(array $attributes) => [
            'level' => WebhookLog::LEVEL_ERROR,
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn(array $attributes) => [
            'level' => WebhookLog::LEVEL_CRITICAL,
        ]);
    }

    public function forEvent(?WebhookEvent $event = null): static
    {
        return $this->state(fn(array $attributes) => [
            'event_id' => $event?->id ?? WebhookEvent::factory(),
        ]);
    }

    public function forDelivery(?WebhookDelivery $delivery = null): static
    {
        return $this->state(fn(array $attributes) => [
            'delivery_id' => $delivery?->id ?? WebhookDelivery::factory(),
        ]);
    }
}
