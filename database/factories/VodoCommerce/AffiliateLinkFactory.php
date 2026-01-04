<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Affiliate;
use VodoCommerce\Models\AffiliateLink;

/**
 * @extends Factory<AffiliateLink>
 */
class AffiliateLinkFactory extends Factory
{
    protected $model = AffiliateLink::class;

    public function definition(): array
    {
        return [
            'affiliate_id' => Affiliate::factory(),
            'url' => fake()->url(),
            'utm_source' => fake()->word(),
            'utm_medium' => fake()->randomElement(['social', 'email', 'cpc']),
            'utm_campaign' => fake()->words(2, true),
            'clicks' => fake()->numberBetween(0, 1000),
            'conversions' => fake()->numberBetween(0, 50),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
