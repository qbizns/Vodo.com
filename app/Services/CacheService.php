<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\TaggableStore;

/**
 * Cache Service - Wrapper for cache operations with proper tag support.
 * 
 * Features:
 * - Graceful fallback when tags not supported
 * - Consistent tag naming
 * - Automatic prefix handling
 * - Statistics and debugging
 * 
 * Example usage:
 * 
 * $cache = app(CacheService::class);
 * 
 * // Store with tags
 * $cache->tags(['views', 'invoices'])->put('invoice_form', $data, 3600);
 * 
 * // Retrieve
 * $data = $cache->tags(['views', 'invoices'])->get('invoice_form');
 * 
 * // Clear by tags
 * $cache->tags(['invoices'])->flush();
 */
class CacheService
{
    /**
     * Cache prefix.
     */
    protected string $prefix = 'app:';

    /**
     * Default TTL in seconds.
     */
    protected int $defaultTtl = 3600;

    /**
     * Whether cache is enabled.
     */
    protected bool $enabled = true;

    /**
     * Current tags context.
     */
    protected array $currentTags = [];

    /**
     * Check if the cache store supports tags.
     */
    public function supportsTags(): bool
    {
        return Cache::store()->getStore() instanceof TaggableStore;
    }

    /**
     * Set tags for the next operation.
     */
    public function tags(array $tags): self
    {
        $clone = clone $this;
        $clone->currentTags = array_map(fn($tag) => $this->prefix . $tag, $tags);
        return $clone;
    }

    /**
     * Get a cached value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->enabled) {
            return $default;
        }

        $fullKey = $this->prefix . $key;

        if (!empty($this->currentTags) && $this->supportsTags()) {
            return Cache::tags($this->currentTags)->get($fullKey, $default);
        }

        return Cache::get($fullKey, $default);
    }

    /**
     * Store a value in cache.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $fullKey = $this->prefix . $key;
        $ttl = $ttl ?? $this->defaultTtl;

        if (!empty($this->currentTags) && $this->supportsTags()) {
            return Cache::tags($this->currentTags)->put($fullKey, $value, $ttl);
        }

        return Cache::put($fullKey, $value, $ttl);
    }

    /**
     * Store forever.
     */
    public function forever(string $key, mixed $value): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $fullKey = $this->prefix . $key;

        if (!empty($this->currentTags) && $this->supportsTags()) {
            return Cache::tags($this->currentTags)->forever($fullKey, $value);
        }

        return Cache::forever($fullKey, $value);
    }

    /**
     * Get or store if not exists.
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        $fullKey = $this->prefix . $key;
        $ttl = $ttl ?? $this->defaultTtl;

        if (!empty($this->currentTags) && $this->supportsTags()) {
            return Cache::tags($this->currentTags)->remember($fullKey, $ttl, $callback);
        }

        return Cache::remember($fullKey, $ttl, $callback);
    }

    /**
     * Get or store forever.
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        $fullKey = $this->prefix . $key;

        if (!empty($this->currentTags) && $this->supportsTags()) {
            return Cache::tags($this->currentTags)->rememberForever($fullKey, $callback);
        }

        return Cache::rememberForever($fullKey, $callback);
    }

    /**
     * Remove a cached value.
     */
    public function forget(string $key): bool
    {
        $fullKey = $this->prefix . $key;

        if (!empty($this->currentTags) && $this->supportsTags()) {
            return Cache::tags($this->currentTags)->forget($fullKey);
        }

        return Cache::forget($fullKey);
    }

    /**
     * Clear cache by tags or pattern.
     */
    public function flush(): bool
    {
        if (!empty($this->currentTags) && $this->supportsTags()) {
            return Cache::tags($this->currentTags)->flush();
        }

        // Fallback: clear all app cache (use with caution)
        // In production, you'd want a more targeted approach
        return false;
    }

    /**
     * Clear cache for a specific entity.
     */
    public function flushEntity(string $entityName): bool
    {
        return $this->tags(['entities', $entityName])->flush();
    }

    /**
     * Clear all view caches.
     */
    public function flushViews(?string $entityName = null): bool
    {
        $tags = ['views'];
        if ($entityName) {
            $tags[] = "views:{$entityName}";
        }
        return $this->tags($tags)->flush();
    }

    /**
     * Clear workflow caches.
     */
    public function flushWorkflows(?string $entityName = null): bool
    {
        $tags = ['workflows'];
        if ($entityName) {
            $tags[] = "workflows:{$entityName}";
        }
        return $this->tags($tags)->flush();
    }

    /**
     * Clear permission caches.
     */
    public function flushPermissions(?int $userId = null): bool
    {
        $tags = ['permissions'];
        if ($userId) {
            $tags[] = "permissions:user:{$userId}";
        }
        return $this->tags($tags)->flush();
    }

    /**
     * Clear record rules cache.
     */
    public function flushRecordRules(?string $entityName = null): bool
    {
        $tags = ['record_rules'];
        if ($entityName) {
            $tags[] = "record_rules:{$entityName}";
        }
        return $this->tags($tags)->flush();
    }

    /**
     * Enable caching.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable caching.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Execute callback without caching.
     */
    public function withoutCache(callable $callback): mixed
    {
        $this->disable();

        try {
            return $callback();
        } finally {
            $this->enable();
        }
    }

    /**
     * Check if caching is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set the cache prefix.
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Set the default TTL.
     */
    public function setDefaultTtl(int $ttl): void
    {
        $this->defaultTtl = $ttl;
    }

    /**
     * Get cache statistics (if available).
     */
    public function statistics(): array
    {
        // This depends on your cache driver
        return [
            'driver' => config('cache.default'),
            'supports_tags' => $this->supportsTags(),
            'enabled' => $this->enabled,
            'prefix' => $this->prefix,
        ];
    }
}
