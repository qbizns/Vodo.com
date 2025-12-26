<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Tenant\TenantManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * HasTenantCache - Trait for services that need tenant-aware caching.
 *
 * Phase 1, Task 1.4: Cache Key Tenant Isolation
 *
 * This trait provides tenant-aware cache key generation to ensure
 * that cached data for one tenant is not accidentally served to another.
 *
 * The cache key format is: {prefix}:tenant:{tenant_id}:{key}
 * For global data (no tenant): {prefix}:global:{key}
 *
 * Usage:
 *   class EntityRegistry
 *   {
 *       use HasTenantCache;
 *
 *       protected const CACHE_PREFIX = 'entity_registry:';
 *       protected const CACHE_TTL = 3600;
 *
 *       public function get(string $name): ?Entity
 *       {
 *           return $this->tenantCache(
 *               $name,
 *               fn() => Entity::where('name', $name)->first()
 *           );
 *       }
 *   }
 */
trait HasTenantCache
{
    /**
     * Get the cache prefix (override in using class).
     */
    protected function getCachePrefix(): string
    {
        // Use class constant if defined, otherwise use class name
        if (defined('static::CACHE_PREFIX')) {
            return static::CACHE_PREFIX;
        }

        return strtolower(class_basename(static::class)) . ':';
    }

    /**
     * Get the cache TTL in seconds (override in using class).
     */
    protected function getCacheTTL(): int
    {
        if (defined('static::CACHE_TTL')) {
            return static::CACHE_TTL;
        }

        return config('platform.cache.default_ttl', 3600);
    }

    /**
     * Get the current tenant ID for cache key generation.
     */
    protected function getCacheTenantId(): ?int
    {
        try {
            return app(TenantManager::class)->getCurrentTenantId();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate a tenant-aware cache key.
     *
     * Format: {prefix}tenant:{id}:{key} or {prefix}global:{key}
     */
    protected function tenantCacheKey(string $key): string
    {
        $prefix = $this->getCachePrefix();
        $tenantId = $this->getCacheTenantId();

        if ($tenantId !== null) {
            return "{$prefix}tenant:{$tenantId}:{$key}";
        }

        return "{$prefix}global:{$key}";
    }

    /**
     * Generate a global cache key (not tenant-scoped).
     *
     * Use this for data that should be shared across all tenants.
     */
    protected function globalCacheKey(string $key): string
    {
        return $this->getCachePrefix() . "global:{$key}";
    }

    /**
     * Cache a value with tenant isolation.
     *
     * @param string $key Cache key (will be prefixed with tenant)
     * @param callable $callback Function to generate value if not cached
     * @param int|null $ttl TTL in seconds (null = use default)
     * @return mixed
     */
    protected function tenantCache(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->tenantCacheKey($key);
        $ttl = $ttl ?? $this->getCacheTTL();

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Cache a value globally (shared across all tenants).
     *
     * @param string $key Cache key
     * @param callable $callback Function to generate value if not cached
     * @param int|null $ttl TTL in seconds (null = use default)
     * @return mixed
     */
    protected function globalCache(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->globalCacheKey($key);
        $ttl = $ttl ?? $this->getCacheTTL();

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get a cached value for the current tenant.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    protected function getTenantCache(string $key, mixed $default = null): mixed
    {
        return Cache::get($this->tenantCacheKey($key), $default);
    }

    /**
     * Put a value in the tenant cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl TTL in seconds (null = use default)
     */
    protected function putTenantCache(string $key, mixed $value, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->getCacheTTL();
        Cache::put($this->tenantCacheKey($key), $value, $ttl);
    }

    /**
     * Check if a key exists in the tenant cache.
     */
    protected function hasTenantCache(string $key): bool
    {
        return Cache::has($this->tenantCacheKey($key));
    }

    /**
     * Forget a specific tenant cache key.
     */
    protected function forgetTenantCache(string $key): void
    {
        Cache::forget($this->tenantCacheKey($key));
    }

    /**
     * Forget a specific global cache key.
     */
    protected function forgetGlobalCache(string $key): void
    {
        Cache::forget($this->globalCacheKey($key));
    }

    /**
     * Clear all cache for the current tenant.
     *
     * Note: This uses cache tags if available, otherwise
     * you'll need to track keys manually.
     */
    protected function clearTenantCache(): void
    {
        $tenantId = $this->getCacheTenantId();
        $prefix = $this->getCachePrefix();

        if ($tenantId !== null) {
            $tag = "{$prefix}tenant:{$tenantId}";

            // Try to use cache tags if supported
            try {
                Cache::tags([$tag])->flush();
                return;
            } catch (\BadMethodCallException $e) {
                // Tags not supported by this cache driver
            }

            // Fallback: Log warning about manual cleanup needed
            Log::warning("Cache tags not supported. Manual cache cleanup may be needed.", [
                'prefix' => $prefix,
                'tenant_id' => $tenantId,
            ]);
        }
    }

    /**
     * Clear all global cache for this registry.
     */
    protected function clearGlobalCache(): void
    {
        $prefix = $this->getCachePrefix();
        $tag = "{$prefix}global";

        try {
            Cache::tags([$tag])->flush();
            return;
        } catch (\BadMethodCallException $e) {
            // Tags not supported
        }

        Log::warning("Cache tags not supported. Manual cache cleanup may be needed.", [
            'prefix' => $prefix,
            'scope' => 'global',
        ]);
    }

    /**
     * Execute a callback without caching.
     *
     * Useful for admin operations that need fresh data.
     *
     * @param callable $callback
     * @return mixed
     */
    protected function withoutCache(callable $callback): mixed
    {
        // Temporarily disable caching by using a very short TTL
        $originalTtl = $this->getCacheTTL();

        // We can't actually change the TTL dynamically with traits,
        // so instead we'll bypass the cache entirely
        return $callback();
    }

    /**
     * Warm the cache for a specific key.
     *
     * @param string $key Cache key
     * @param callable $callback Function to generate value
     * @param int|null $ttl TTL in seconds
     */
    protected function warmTenantCache(string $key, callable $callback, ?int $ttl = null): void
    {
        $this->forgetTenantCache($key);
        $this->tenantCache($key, $callback, $ttl);
    }

    /**
     * Get cache statistics for debugging.
     */
    protected function getCacheStats(): array
    {
        $tenantId = $this->getCacheTenantId();
        $prefix = $this->getCachePrefix();

        return [
            'prefix' => $prefix,
            'tenant_id' => $tenantId,
            'key_format' => $tenantId
                ? "{$prefix}tenant:{$tenantId}:{key}"
                : "{$prefix}global:{key}",
            'ttl' => $this->getCacheTTL(),
            'driver' => config('cache.default'),
        ];
    }
}
