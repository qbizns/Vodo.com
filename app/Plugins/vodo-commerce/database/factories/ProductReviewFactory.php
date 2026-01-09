<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductReview;
use VodoCommerce\Models\Store;

class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'product_id' => Product::factory(),
            'customer_id' => Customer::factory(),
            'order_id' => null,
            'rating' => $this->faker->numberBetween(1, 5),
            'title' => $this->faker->sentence(6),
            'comment' => $this->faker->paragraphs(2, true),
            'is_verified_purchase' => false,
            'status' => ProductReview::STATUS_PENDING,
            'is_featured' => false,
            'helpful_count' => 0,
            'not_helpful_count' => 0,
            'published_at' => null,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
            'meta' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProductReview::STATUS_APPROVED,
            'approved_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'published_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProductReview::STATUS_PENDING,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProductReview::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    public function flagged(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => ProductReview::STATUS_FLAGGED,
        ]);
    }

    public function verifiedPurchase(): static
    {
        return $this->state(fn(array $attributes) => [
            'order_id' => Order::factory(),
            'is_verified_purchase' => true,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function withRating(int $rating): static
    {
        return $this->state(fn(array $attributes) => [
            'rating' => $rating,
        ]);
    }

    public function withEngagement(int $helpful = 10, int $notHelpful = 2): static
    {
        return $this->state(fn(array $attributes) => [
            'helpful_count' => $helpful,
            'not_helpful_count' => $notHelpful,
        ]);
    }
}
