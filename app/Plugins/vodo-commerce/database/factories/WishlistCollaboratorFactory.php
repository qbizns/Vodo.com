<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Wishlist;
use VodoCommerce\Models\WishlistCollaborator;

class WishlistCollaboratorFactory extends Factory
{
    protected $model = WishlistCollaborator::class;

    public function definition(): array
    {
        return [
            'wishlist_id' => Wishlist::factory(),
            'customer_id' => Customer::factory(),
            'permission' => WishlistCollaborator::PERMISSION_VIEW,
            'invited_email' => null,
            'status' => WishlistCollaborator::STATUS_PENDING,
            'invitation_token' => Str::random(32),
            'invited_at' => now(),
            'accepted_at' => null,
            'last_activity_at' => null,
            'meta' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WishlistCollaborator::STATUS_PENDING,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WishlistCollaborator::STATUS_ACCEPTED,
            'accepted_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'last_activity_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => WishlistCollaborator::STATUS_DECLINED,
        ]);
    }

    public function withViewPermission(): static
    {
        return $this->state(fn(array $attributes) => [
            'permission' => WishlistCollaborator::PERMISSION_VIEW,
        ]);
    }

    public function withEditPermission(): static
    {
        return $this->state(fn(array $attributes) => [
            'permission' => WishlistCollaborator::PERMISSION_EDIT,
        ]);
    }

    public function withManagePermission(): static
    {
        return $this->state(fn(array $attributes) => [
            'permission' => WishlistCollaborator::PERMISSION_MANAGE,
        ]);
    }
}
