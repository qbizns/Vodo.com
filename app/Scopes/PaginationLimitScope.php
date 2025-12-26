<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * PaginationLimitScope - Prevents unbounded queries that could exhaust memory.
 *
 * Phase 1, Task 1.2: Query Pagination Enforcement
 *
 * This scope automatically applies a maximum limit to queries that don't have
 * an explicit limit set, preventing accidental "SELECT * FROM large_table"
 * queries that could return millions of rows.
 *
 * The limit is bypassed when:
 * - An explicit limit is already set
 * - Running in console (artisan commands)
 * - The query has been explicitly marked as unlimited
 * - The context is marked as admin/system context
 *
 * Usage:
 *   class EntityRecord extends Model
 *   {
 *       use HasPaginationLimit;
 *   }
 *
 *   // Normal queries are limited
 *   EntityRecord::all(); // Limited to max_query_limit (default 1000)
 *
 *   // Explicit limit is respected
 *   EntityRecord::limit(50)->get(); // Returns 50
 *
 *   // Bypass for admin operations
 *   EntityRecord::withoutPaginationLimit()->get(); // No limit
 */
class PaginationLimitScope implements Scope
{
    /**
     * Default maximum limit when none is set.
     */
    protected int $defaultLimit;

    /**
     * Whether to apply the scope.
     */
    protected bool $enabled;

    /**
     * Create a new scope instance.
     */
    public function __construct()
    {
        $this->defaultLimit = config('platform.query.max_limit', 1000);
        $this->enabled = config('platform.query.enforce_limit', true);
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip if disabled
        if (!$this->enabled) {
            return;
        }

        // Skip if running artisan commands (migrations, seeders, etc.)
        if (app()->runningInConsole() && !$this->isQueueWorker()) {
            return;
        }

        // Skip if already has a limit
        if ($builder->getQuery()->limit !== null) {
            return;
        }

        // Skip if marked as unlimited
        if ($this->isMarkedUnlimited($builder)) {
            return;
        }

        // Skip if in system/admin context
        if ($this->isSystemContext()) {
            return;
        }

        // Apply the default limit
        $builder->limit($this->defaultLimit);
    }

    /**
     * Check if the query has been marked as unlimited.
     */
    protected function isMarkedUnlimited(Builder $builder): bool
    {
        // Check for a custom property on the builder
        return $builder->getModel()->getAttribute('_unlimited_query') === true;
    }

    /**
     * Check if we're in a system/admin context where limits shouldn't apply.
     */
    protected function isSystemContext(): bool
    {
        // Check for system context flag in container
        if (app()->bound('pagination_limit.disabled') && app('pagination_limit.disabled')) {
            return true;
        }

        // Check for specific request contexts
        $request = request();
        if ($request && $request->hasHeader('X-System-Context')) {
            return true;
        }

        return false;
    }

    /**
     * Check if running as a queue worker.
     */
    protected function isQueueWorker(): bool
    {
        return app()->bound('queue.worker') ||
               (isset($_SERVER['argv']) && in_array('queue:work', $_SERVER['argv']));
    }

    /**
     * Get the current default limit.
     */
    public function getDefaultLimit(): int
    {
        return $this->defaultLimit;
    }
}
