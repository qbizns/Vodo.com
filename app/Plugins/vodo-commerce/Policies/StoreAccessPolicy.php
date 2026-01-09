<?php

declare(strict_types=1);

namespace VodoCommerce\Policies;

use Illuminate\Foundation\Auth\User;
use VodoCommerce\Models\Store;

/**
 * Base policy for store-scoped authorization.
 * Ensures users can only access resources belonging to their stores.
 */
class StoreAccessPolicy
{
    /**
     * Check if user has access to a specific store.
     */
    public function accessStore(User $user, Store $store): bool
    {
        // Check if user owns or has access to the store
        // This should be customized based on your user-store relationship
        return $user->stores()->where('stores.id', $store->id)->exists();
    }

    /**
     * Check if user has access to a model belonging to a store.
     */
    protected function canAccessStoreResource(User $user, mixed $model): bool
    {
        if (!isset($model->store_id)) {
            return false;
        }

        // Check if user has access to the store this resource belongs to
        return $user->stores()->where('stores.id', $model->store_id)->exists();
    }

    /**
     * Determine if user can view any resources.
     * Only allow if user has at least one store.
     */
    protected function canViewAny(User $user): bool
    {
        return $user->stores()->exists();
    }
}
