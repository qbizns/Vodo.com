<?php

declare(strict_types=1);

namespace VodoCommerce\Policies;

use Illuminate\Foundation\Auth\User;
use VodoCommerce\Models\WebhookSubscription;

/**
 * Authorization policy for WebhookSubscription resources.
 * SECURITY: Prevents users from accessing webhook subscriptions belonging to other stores.
 */
class WebhookSubscriptionPolicy extends StoreAccessPolicy
{
    /**
     * Determine whether the user can view any webhook subscriptions.
     */
    public function viewAny(User $user): bool
    {
        return $this->canViewAny($user);
    }

    /**
     * Determine whether the user can view the webhook subscription.
     */
    public function view(User $user, WebhookSubscription $webhookSubscription): bool
    {
        return $this->canAccessStoreResource($user, $webhookSubscription);
    }

    /**
     * Determine whether the user can create webhook subscriptions.
     */
    public function create(User $user): bool
    {
        return $this->canViewAny($user);
    }

    /**
     * Determine whether the user can update the webhook subscription.
     */
    public function update(User $user, WebhookSubscription $webhookSubscription): bool
    {
        return $this->canAccessStoreResource($user, $webhookSubscription);
    }

    /**
     * Determine whether the user can delete the webhook subscription.
     */
    public function delete(User $user, WebhookSubscription $webhookSubscription): bool
    {
        return $this->canAccessStoreResource($user, $webhookSubscription);
    }

    /**
     * Determine whether the user can test the webhook subscription.
     */
    public function test(User $user, WebhookSubscription $webhookSubscription): bool
    {
        return $this->canAccessStoreResource($user, $webhookSubscription);
    }

    /**
     * Determine whether the user can regenerate the webhook secret.
     */
    public function regenerateSecret(User $user, WebhookSubscription $webhookSubscription): bool
    {
        return $this->canAccessStoreResource($user, $webhookSubscription);
    }
}
