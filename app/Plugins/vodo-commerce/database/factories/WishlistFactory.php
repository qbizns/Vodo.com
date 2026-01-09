<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\Wishlist;

class WishlistFactory extends Factory
{
    protected $model = Wishlist::class;

    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'My Wishlist',
            'Birthday Wishlist',
            'Holiday Wishlist',
            'Wedding Registry',
            'Baby Shower Registry',
            'Favorites',
        ]);

        return [
            'store_id' => Store::factory(),
            'customer_id' => Customer::factory(),
            'name' => $name,
            'description' => $this->faker->optional()->paragraph(),
            'slug' => Str::slug($name) . '-' . Str::random(8),
            'visibility' => Wishlist::VISIBILITY_PRIVATE,
            'share_token' => Str::random(32),
            'is_default' => false,
            'allow_comments' => $this->faker->boolean(30),
            'show_purchased_items' => true,
            'event_type' => null,
            'event_date' => null,
            'items_count' => 0,
            'views_count' => 0,
            'last_viewed_at' => null,
            'meta' => null,
        ];
    }

    public function defaultWishlist(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn(array $attributes) => [
            'visibility' => Wishlist::VISIBILITY_PRIVATE,
        ]);
    }

    public function shared(): static
    {
        return $this->state(fn(array $attributes) => [
            'visibility' => Wishlist::VISIBILITY_SHARED,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn(array $attributes) => [
            'visibility' => Wishlist::VISIBILITY_PUBLIC,
        ]);
    }

    public function weddingRegistry(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Wedding Registry',
            'visibility' => Wishlist::VISIBILITY_PUBLIC,
            'event_type' => 'wedding',
            'event_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'allow_comments' => true,
        ]);
    }

    public function babyShower(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Baby Shower Registry',
            'visibility' => Wishlist::VISIBILITY_PUBLIC,
            'event_type' => 'baby_shower',
            'event_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'allow_comments' => true,
        ]);
    }

    public function withViews(int $count = 50): static
    {
        return $this->state(fn(array $attributes) => [
            'views_count' => $count,
            'last_viewed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
