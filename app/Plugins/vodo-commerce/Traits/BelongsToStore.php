<?php

declare(strict_types=1);

namespace VodoCommerce\Traits;

use App\Services\Tenant\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Models\Store;

/**
 * BelongsToStore - Trait for commerce models that require store-level isolation.
 *
 * Commerce entities belong to a Store, which belongs to a Tenant.
 * This trait provides:
 * - Automatic store scoping on queries (if store context is set)
 * - Auto-assignment of store_id on create
 * - Store relationship
 *
 * Usage:
 *
 * class Product extends Model
 * {
 *     use BelongsToStore;
 * }
 *
 * // In storefront/admin context, store is resolved from route or session
 * Product::all(); // Only returns products for current store
 */
trait BelongsToStore
{
    /**
     * The current store context for queries.
     */
    protected static ?int $currentStoreId = null;

    /**
     * Boot the trait.
     */
    public static function bootBelongsToStore(): void
    {
        // Add global scope for store isolation
        static::addGlobalScope('store', function (Builder $builder) {
            $storeId = static::getCurrentStoreId();

            if ($storeId !== null) {
                $model = new static;
                $builder->where($model->getQualifiedStoreColumn(), $storeId);
            }
        });

        // Auto-set store on create
        static::creating(function ($model) {
            $column = $model->getStoreColumn();

            if (is_null($model->$column)) {
                $storeId = static::getCurrentStoreId();

                if ($storeId !== null) {
                    $model->$column = $storeId;
                }
            }
        });
    }

    /**
     * Set the current store context.
     */
    public static function setCurrentStore(?int $storeId): void
    {
        static::$currentStoreId = $storeId;
    }

    /**
     * Get the current store ID from context.
     */
    public static function getCurrentStoreId(): ?int
    {
        // First check static context
        if (static::$currentStoreId !== null) {
            return static::$currentStoreId;
        }

        // Check request context (from route parameter or session)
        $request = request();

        // From route parameter
        if ($request->route('store')) {
            $store = $request->route('store');
            if ($store instanceof Store) {
                return $store->id;
            }
            if (is_numeric($store)) {
                return (int) $store;
            }
        }

        // From session
        if ($request->session()->has('current_store_id')) {
            return (int) $request->session()->get('current_store_id');
        }

        return null;
    }

    /**
     * Get the store column name.
     */
    public function getStoreColumn(): string
    {
        return $this->storeColumn ?? 'store_id';
    }

    /**
     * Get the qualified store column name (with table prefix).
     */
    public function getQualifiedStoreColumn(): string
    {
        return $this->getTable() . '.' . $this->getStoreColumn();
    }

    /**
     * Get the store ID for this record.
     */
    public function getStoreId(): ?int
    {
        $column = $this->getStoreColumn();
        return $this->$column;
    }

    /**
     * Get the store relationship.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, $this->getStoreColumn());
    }

    /**
     * Check if record belongs to current store.
     */
    public function belongsToCurrentStore(): bool
    {
        return $this->getStoreId() === static::getCurrentStoreId();
    }

    /**
     * Validate that user has access to this record's store.
     */
    public function validateStoreAccess(): bool
    {
        $store = $this->store;

        if (!$store) {
            return false;
        }

        $tenantManager = app(TenantManager::class);
        $currentTenantId = $tenantManager->getCurrentTenantId();

        // Store must belong to current tenant
        return $store->tenant_id === $currentTenantId;
    }

    /**
     * Scope to bypass store filtering.
     */
    public function scopeWithoutStoreScope($query): Builder
    {
        return $query->withoutGlobalScope('store');
    }

    /**
     * Alias for withoutStoreScope.
     */
    public function scopeAllStores($query): Builder
    {
        return $query->withoutGlobalScope('store');
    }

    /**
     * Scope to a specific store.
     */
    public function scopeForStore($query, int $storeId): Builder
    {
        return $query->withoutGlobalScope('store')
                     ->where($this->getStoreColumn(), $storeId);
    }

    /**
     * Execute callback in store context.
     */
    public static function inStoreContext(int $storeId, callable $callback): mixed
    {
        $previousStoreId = static::$currentStoreId;
        static::$currentStoreId = $storeId;

        try {
            return $callback();
        } finally {
            static::$currentStoreId = $previousStoreId;
        }
    }

    /**
     * Execute callback without store scoping.
     */
    public static function withoutStoreContext(callable $callback): mixed
    {
        $previousStoreId = static::$currentStoreId;
        static::$currentStoreId = null;

        try {
            return $callback();
        } finally {
            static::$currentStoreId = $previousStoreId;
        }
    }
}
