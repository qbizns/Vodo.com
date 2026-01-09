<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\ProductReview;
use VodoCommerce\Models\ReviewVote;

class ReviewVoteFactory extends Factory
{
    protected $model = ReviewVote::class;

    public function definition(): array
    {
        return [
            'review_id' => ProductReview::factory(),
            'customer_id' => Customer::factory(),
            'vote_type' => $this->faker->randomElement([ReviewVote::TYPE_HELPFUL, ReviewVote::TYPE_NOT_HELPFUL]),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'meta' => null,
        ];
    }

    public function helpful(): static
    {
        return $this->state(fn(array $attributes) => [
            'vote_type' => ReviewVote::TYPE_HELPFUL,
        ]);
    }

    public function notHelpful(): static
    {
        return $this->state(fn(array $attributes) => [
            'vote_type' => ReviewVote::TYPE_NOT_HELPFUL,
        ]);
    }
}
