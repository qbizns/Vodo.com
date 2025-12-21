<?php

declare(strict_types=1);

namespace App\Services\Cache;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Query Cache Service - Intelligent caching for database queries.
 *
 * Features:
 * - Automatic cache key generation from query
 * - Tag-based cache invalidation
 * - TTL management
 * - Cache warming
 * - Statistics tracking
 */
class QueryCache
{
    /**
     * Default cache TTL in seconds.
     */
    protected int $defaultTtl;

    /**
     * Cache prefix.
     */
    protected string $prefix;

    /**
     * Track cache statistics.
     */
    protected bool $trackStats;

    public function __construct()
    {
        $this->defaultTtl = config('cache.query_ttl', 3600);
        $this->prefix = config('cache.prefix', 'vodo') . ':query:';
        $this->trackStats = config('cache.track_stats', false);
    }

    /**
     * Get or execute a query with caching.
     */
    public function remember(
        Builder $query,
        ?int $ttl = null,
        ?string $key = null,
        array $tags = []
    ): Collection {
        $cacheKey = $key ?? $this->generateKey($query);
        $ttl = $ttl ?? $this->defaultTtl;

        // Track cache access
        if ($this->trackStats) {
            $this->trackAccess($cacheKey);
        }

        // Try to get from cache
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            if ($this->trackStats) {
                $this->trackHit($cacheKey);
            }
            return $cached;
        }

        // Execute query and cache result
        $result = $query->get();

        Cache::put($cacheKey, $result, $ttl);

        // Store cache key for tag-based invalidation
        if (!empty($tags)) {
            $this->addToTags($cacheKey, $tags);
        }

        if ($this->trackStats) {
            $this->trackMiss($cacheKey);
        }

        return $result;
    }

    /**
     * Get or execute a query for a single model.
     */
    public function rememberOne(
        Builder $query,
        ?int $ttl = null,
        ?string $key = null,
        array $tags = []
    ): ?Model {
        $cacheKey = $key ?? $this->generateKey($query) . ':first';
        $ttl = $ttl ?? $this->defaultTtl;

        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached === 'null' ? null : $cached;
        }

        $result = $query->first();

        // Cache null as string to distinguish from cache miss
        Cache::put($cacheKey, $result ?? 'null', $ttl);

        if (!empty($tags)) {
            $this->addToTags($cacheKey, $tags);
        }

        return $result;
    }

    /**
     * Cache a model by ID.
     */
    public function rememberById(
        string $modelClass,
        int|string $id,
        ?int $ttl = null
    ): ?Model {
        $cacheKey = $this->prefix . $this->getModelTag($modelClass) . ':' . $id;
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl, function () use ($modelClass, $id) {
            return $modelClass::find($id);
        });
    }

    /**
     * Invalidate cache for a model.
     */
    public function invalidate(Model|string $model): void
    {
        $tag = is_string($model) ? $this->getModelTag($model) : $this->getModelTag(get_class($model));

        $this->invalidateByTag($tag);

        // If it's a model instance, also invalidate by ID
        if ($model instanceof Model && $model->getKey()) {
            $cacheKey = $this->prefix . $tag . ':' . $model->getKey();
            Cache::forget($cacheKey);
        }
    }

    /**
     * Invalidate cache by tag.
     */
    public function invalidateByTag(string $tag): void
    {
        $tagKey = $this->prefix . 'tags:' . $tag;
        $keys = Cache::get($tagKey, []);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget($tagKey);
    }

    /**
     * Invalidate cache by pattern.
     */
    public function invalidateByPattern(string $pattern): void
    {
        // Note: Pattern-based invalidation is driver-dependent
        // This works best with Redis
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $prefix = config('cache.prefix') . ':' . $this->prefix;
            $keys = $redis->keys($prefix . $pattern);

            foreach ($keys as $key) {
                $redis->del($key);
            }
        }
    }

    /**
     * Flush all query cache.
     */
    public function flush(): void
    {
        $this->invalidateByPattern('*');
    }

    /**
     * Pre-warm cache for common queries.
     */
    public function warm(array $queries): array
    {
        $warmed = [];

        foreach ($queries as $name => $query) {
            if ($query instanceof Builder) {
                $this->remember($query);
                $warmed[] = $name;
            } elseif (is_callable($query)) {
                $this->remember($query());
                $warmed[] = $name;
            }
        }

        return $warmed;
    }

    /**
     * Generate cache key from query.
     */
    protected function generateKey(Builder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        $model = get_class($query->getModel());

        $hash = md5($sql . serialize($bindings));

        return $this->prefix . $this->getModelTag($model) . ':' . $hash;
    }

    /**
     * Get tag name for a model class.
     */
    protected function getModelTag(string $modelClass): string
    {
        return strtolower(class_basename($modelClass));
    }

    /**
     * Add cache key to tags for invalidation.
     */
    protected function addToTags(string $cacheKey, array $tags): void
    {
        foreach ($tags as $tag) {
            $tagKey = $this->prefix . 'tags:' . $tag;
            $keys = Cache::get($tagKey, []);
            $keys[] = $cacheKey;
            Cache::put($tagKey, array_unique($keys), $this->defaultTtl * 2);
        }
    }

    /**
     * Track cache access.
     */
    protected function trackAccess(string $key): void
    {
        Cache::increment('cache_accesses');
    }

    /**
     * Track cache hit.
     */
    protected function trackHit(string $key): void
    {
        Cache::increment('cache_hits');
    }

    /**
     * Track cache miss.
     */
    protected function trackMiss(string $key): void
    {
        Cache::increment('cache_misses');
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        return [
            'accesses' => (int) Cache::get('cache_accesses', 0),
            'hits' => (int) Cache::get('cache_hits', 0),
            'misses' => (int) Cache::get('cache_misses', 0),
            'hit_rate' => $this->calculateHitRate(),
        ];
    }

    /**
     * Calculate cache hit rate.
     */
    protected function calculateHitRate(): float
    {
        $hits = (int) Cache::get('cache_hits', 0);
        $misses = (int) Cache::get('cache_misses', 0);
        $total = $hits + $misses;

        if ($total === 0) {
            return 0.0;
        }

        return round(($hits / $total) * 100, 2);
    }

    /**
     * Reset statistics.
     */
    public function resetStats(): void
    {
        Cache::forget('cache_accesses');
        Cache::forget('cache_hits');
        Cache::forget('cache_misses');
    }
}
