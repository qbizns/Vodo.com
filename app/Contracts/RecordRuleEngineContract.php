<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\RecordRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Contract for Record Rule Engine implementations.
 */
interface RecordRuleEngineContract
{
    /**
     * Define a record rule.
     */
    public function defineRule(string $entityName, array $definition, ?string $pluginSlug = null): RecordRule;

    /**
     * Apply record rules to a query.
     */
    public function applyRules(Builder $query, string $entityName, string $permission = 'read'): Builder;

    /**
     * Check if user can access a specific record.
     */
    public function canAccess(Model $record, string $permission = 'read', ?object $user = null): bool;

    /**
     * Check if user can create records.
     */
    public function canCreate(string $entityName, ?object $user = null): bool;

    /**
     * Register a custom operator.
     */
    public function registerOperator(string $operator, callable $handler): void;

    /**
     * Register a custom domain function.
     */
    public function registerFunction(string $name, callable $handler): void;

    /**
     * Set custom user context resolver.
     */
    public function setUserContextResolver(\Closure $resolver): void;

    /**
     * Temporarily bypass rules.
     */
    public function withoutRules(callable $callback): mixed;

    /**
     * Clear rules cache.
     */
    public function clearCache(?string $entityName = null): void;

    /**
     * Get all rules for an entity.
     */
    public function getRulesForEntity(string $entityName): Collection;

    /**
     * Delete rules for a plugin.
     */
    public function deletePluginRules(string $pluginSlug): int;
}
