<?php

declare(strict_types=1);

namespace App\Traits;

use App\Scopes\PaginationLimitScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * HasPaginationLimit - Trait for models that should have automatic query limits.
 *
 * Phase 1, Task 1.2: Query Pagination Enforcement
 *
 * This trait applies the PaginationLimitScope to prevent unbounded queries
 * that could return millions of rows and exhaust server memory.
 *
 * Usage:
 *   class EntityRecord extends Model
 *   {
 *       use HasPaginationLimit;
 *   }
 *
 * Query Examples:
 *   // Limited automatically
 *   EntityRecord::all(); // Max 1000 records
 *
 *   // Explicit limit honored
 *   EntityRecord::limit(50)->get(); // 50 records
 *
 *   // Bypass for exports/reports
 *   EntityRecord::withoutPaginationLimit()->get(); // All records
 *
 *   // Unlimited in closure
 *   EntityRecord::unlimited(fn($query) => $query->where('status', 'active')->get());
 */
trait HasPaginationLimit
{
    /**
     * Boot the trait.
     */
    public static function bootHasPaginationLimit(): void
    {
        static::addGlobalScope(new PaginationLimitScope());
    }

    /**
     * Remove the pagination limit scope from the query.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutPaginationLimit(Builder $query): Builder
    {
        return $query->withoutGlobalScope(PaginationLimitScope::class);
    }

    /**
     * Alias for withoutPaginationLimit.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnlimited(Builder $query): Builder
    {
        return $query->withoutGlobalScope(PaginationLimitScope::class);
    }

    /**
     * Apply a custom limit, overriding the default.
     *
     * @param Builder $query
     * @param int $limit
     * @return Builder
     */
    public function scopeWithLimit(Builder $query, int $limit): Builder
    {
        return $query->limit($limit);
    }

    /**
     * Execute a callback without the pagination limit.
     *
     * This is useful for exports, reports, or admin operations that need
     * to process all records.
     *
     * @param callable $callback
     * @return mixed
     */
    public static function withoutLimit(callable $callback): mixed
    {
        // Temporarily disable the limit
        $previousValue = app()->bound('pagination_limit.disabled')
            ? app('pagination_limit.disabled')
            : false;

        app()->instance('pagination_limit.disabled', true);

        try {
            return $callback(static::query()->withoutGlobalScope(PaginationLimitScope::class));
        } finally {
            app()->instance('pagination_limit.disabled', $previousValue);
        }
    }

    /**
     * Get a paginated result with sensible defaults.
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function scopePaginateSafe(
        Builder $query,
        ?int $perPage = null,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null
    ) {
        $perPage = $perPage ?? config('platform.query.default_per_page', 15);
        $maxPerPage = config('platform.query.max_per_page', 100);

        // Enforce maximum per page
        $perPage = min($perPage, $maxPerPage);

        return $query->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Get a cursor paginated result (more efficient for large datasets).
     *
     * @param int|null $perPage
     * @param array $columns
     * @param string $cursorName
     * @param \Illuminate\Pagination\Cursor|string|null $cursor
     * @return \Illuminate\Contracts\Pagination\CursorPaginator
     */
    public function scopeCursorPaginateSafe(
        Builder $query,
        ?int $perPage = null,
        array $columns = ['*'],
        string $cursorName = 'cursor',
        $cursor = null
    ) {
        $perPage = $perPage ?? config('platform.query.default_per_page', 15);
        $maxPerPage = config('platform.query.max_per_page', 100);

        // Enforce maximum per page
        $perPage = min($perPage, $maxPerPage);

        return $query->cursorPaginate($perPage, $columns, $cursorName, $cursor);
    }

    /**
     * Stream results in chunks to avoid memory issues.
     *
     * @param int $chunkSize
     * @param callable $callback
     * @return bool
     */
    public function scopeChunkSafe(Builder $query, int $chunkSize, callable $callback): bool
    {
        $maxChunkSize = config('platform.query.max_chunk_size', 1000);
        $chunkSize = min($chunkSize, $maxChunkSize);

        return $query->withoutGlobalScope(PaginationLimitScope::class)
                     ->chunk($chunkSize, $callback);
    }

    /**
     * Lazy load results to minimize memory usage.
     *
     * @param int $chunkSize
     * @return \Illuminate\Support\LazyCollection
     */
    public function scopeLazySafe(Builder $query, int $chunkSize = 1000)
    {
        $maxChunkSize = config('platform.query.max_chunk_size', 1000);
        $chunkSize = min($chunkSize, $maxChunkSize);

        return $query->withoutGlobalScope(PaginationLimitScope::class)
                     ->lazy($chunkSize);
    }
}
