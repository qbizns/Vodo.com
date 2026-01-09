<?php

declare(strict_types=1);

namespace VodoCommerce\Policies;

use Illuminate\Foundation\Auth\User;
use VodoCommerce\Models\Order;

/**
 * Authorization policy for Order resources.
 * SECURITY: Prevents users from accessing orders belonging to other stores.
 */
class OrderPolicy extends StoreAccessPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $this->canViewAny($user);
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        return $this->canAccessStoreResource($user, $order);
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        return $this->canViewAny($user);
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        return $this->canAccessStoreResource($user, $order);
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        return $this->canAccessStoreResource($user, $order);
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        return $this->canAccessStoreResource($user, $order);
    }

    /**
     * Determine whether the user can refund the order.
     */
    public function refund(User $user, Order $order): bool
    {
        return $this->canAccessStoreResource($user, $order);
    }

    /**
     * Determine whether the user can export orders.
     */
    public function export(User $user): bool
    {
        return $this->canViewAny($user);
    }
}
