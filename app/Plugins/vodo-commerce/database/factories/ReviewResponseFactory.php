<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\ProductReview;
use VodoCommerce\Models\ReviewResponse;
use VodoCommerce\Models\Store;

class ReviewResponseFactory extends Factory
{
    protected $model = ReviewResponse::class;

    public function definition(): array
    {
        return [
            'review_id' => ProductReview::factory(),
            'store_id' => Store::factory(),
            'responder_id' => null,
            'response_text' => $this->faker->paragraphs(2, true),
            'is_public' => true,
            'published_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'meta' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_public' => true,
            'published_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_public' => false,
            'published_at' => null,
        ]);
    }
}
