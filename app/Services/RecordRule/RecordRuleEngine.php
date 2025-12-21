<?php

declare(strict_types=1);

namespace App\Services\RecordRule;

use App\Models\RecordRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Record Rule Engine - Row-level security for entity records.
 * 
 * Features:
 * - Domain-based filtering (who sees which records)
 * - Permission-based access (read, write, create, delete)
 * - Group-based rules
 * - Dynamic domain evaluation
 * 
 * Domain syntax (similar to Odoo):
 * 
 * ['user_id', '=', '{user.id}']           - Records belonging to current user
 * ['team_id', 'in', '{user.team_ids}']    - Records in user's teams
 * ['company_id', '=', '{user.company_id}'] - Records in user's company
 * ['is_public', '=', true]                 - Public records
 * ['status', 'in', ['draft', 'pending']]   - Records in specific status
 * 
 * Example usage:
 * 
 * // Define a rule: salespeople see only their own invoices
 * $engine->defineRule('invoice', [
 *     'name' => 'Salesperson sees own invoices',
 *     'domain' => [['salesperson_id', '=', '{user.id}']],
 *     'groups' => ['salesperson'],
 *     'perm_read' => true,
 *     'perm_write' => true,
 * ]);
 * 
 * // Define a rule: managers see team invoices
 * $engine->defineRule('invoice', [
 *     'name' => 'Manager sees team invoices',
 *     'domain' => [['team_id', 'in', '{user.team_ids}']],
 *     'groups' => ['sales_manager'],
 *     'perm_read' => true,
 *     'perm_write' => true,
 * ]);
 */
class RecordRuleEngine
{
    /**
     * Domain operator handlers.
     * @var array<string, callable>
     */
    protected array $operators = [];

    /**
     * Custom domain functions.
     * @var array<string, callable>
     */
    protected array $functions = [];

    /**
     * User context resolver.
     */
    protected ?\Closure $userContextResolver = null;

    /**
     * Bypass flag for admin operations.
     */
    protected bool $bypassRules = false;

    public function __construct()
    {
        $this->registerBuiltInOperators();
    }

    /**
     * Define a record rule.
     */
    public function defineRule(string $entityName, array $definition, ?string $pluginSlug = null): RecordRule
    {
        return RecordRule::updateOrCreate(
            [
                'entity_name' => $entityName,
                'name' => $definition['name'],
                'plugin_slug' => $pluginSlug,
            ],
            [
                'domain' => $definition['domain'] ?? [],
                'groups' => $definition['groups'] ?? [],
                'perm_read' => $definition['perm_read'] ?? true,
                'perm_write' => $definition['perm_write'] ?? false,
                'perm_create' => $definition['perm_create'] ?? false,
                'perm_delete' => $definition['perm_delete'] ?? false,
                'is_global' => $definition['is_global'] ?? false,
                'is_active' => true,
            ]
        );
    }

    /**
     * Apply record rules to a query.
     */
    public function applyRules(Builder $query, string $entityName, string $permission = 'read', ?object $user = null): Builder
    {
        if ($this->bypassRules) {
            return $query;
        }

        $user = $user ?? Auth::user();
        if (!$user) {
            // No user, apply strictest rules (no access)
            return $query->whereRaw('1 = 0');
        }

        // Check for superuser/admin bypass
        if ($this->isSuperuser($user)) {
            return $query;
        }

        $rules = $this->getApplicableRules($entityName, $permission, $user);

        if ($rules->isEmpty()) {
            // No rules defined = full access (or restrict based on config)
            if (config('recordrules.default_deny', false)) {
                return $query->whereRaw('1 = 0');
            }
            return $query;
        }

        // Build combined domain from all applicable rules
        $query->where(function ($q) use ($rules, $user) {
            foreach ($rules as $index => $rule) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $q->$method(function ($subQ) use ($rule, $user) {
                    $this->applyDomain($subQ, $rule->domain, $user);
                });
            }
        });

        return $query;
    }

    /**
     * Check if user can access a specific record.
     */
    public function canAccess(Model $record, string $permission = 'read', ?object $user = null): bool
    {
        if ($this->bypassRules) {
            return true;
        }

        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }

        if ($this->isSuperuser($user)) {
            return true;
        }

        $entityName = $this->getEntityName($record);
        $rules = $this->getApplicableRules($entityName, $permission, $user);

        if ($rules->isEmpty()) {
            return !config('recordrules.default_deny', false);
        }

        // Check if record matches any rule's domain
        foreach ($rules as $rule) {
            if ($this->recordMatchesDomain($record, $rule->domain, $user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can create records.
     */
    public function canCreate(string $entityName, ?object $user = null): bool
    {
        if ($this->bypassRules) {
            return true;
        }

        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }

        if ($this->isSuperuser($user)) {
            return true;
        }

        $rules = $this->getApplicableRules($entityName, 'create', $user);

        if ($rules->isEmpty()) {
            return !config('recordrules.default_deny', false);
        }

        return $rules->isNotEmpty();
    }

    /**
     * Apply read rules to a query (convenience method).
     */
    public function applyReadRules(Builder $query, string $entityName, ?object $user = null): Builder
    {
        return $this->applyRules($query, $entityName, 'read', $user);
    }

    /**
     * Apply write rules to a query (convenience method).
     */
    public function applyWriteRules(Builder $query, string $entityName, ?object $user = null): Builder
    {
        return $this->applyRules($query, $entityName, 'write', $user);
    }

    /**
     * Apply delete rules to a query (convenience method).
     */
    public function applyDeleteRules(Builder $query, string $entityName, ?object $user = null): Builder
    {
        return $this->applyRules($query, $entityName, 'delete', $user);
    }

    /**
     * Get applicable rules for entity and permission.
     */
    protected function getApplicableRules(string $entityName, string $permission, object $user): \Illuminate\Support\Collection
    {
        // Include tenant_id in cache key to prevent cross-tenant cache collision
        $tenantId = $user->tenant_id ?? 'global';
        $cacheKey = "record_rules:{$tenantId}:{$entityName}:{$permission}:{$user->id}";
        $userGroups = $this->getUserGroups($user);
        $permissionField = "perm_{$permission}";

        // Use tags if available for better cache management
        $cacheCallback = function () use ($entityName, $permission, $permissionField, $user, $userGroups) {
            // Optimized query - filter in database instead of PHP
            return RecordRule::forEntity($entityName)
                ->active()
                ->where($permissionField, true)
                ->get()
                ->filter(function ($rule) use ($userGroups) {
                    return $rule->appliesTo($userGroups);
                });
        };

        if (method_exists(Cache::getStore(), 'tags')) {
            return Cache::tags(['record_rules', $entityName])->remember($cacheKey, 300, $cacheCallback);
        }

        return Cache::remember($cacheKey, 300, $cacheCallback);
    }

    /**
     * Apply domain conditions to query.
     */
    protected function applyDomain(Builder $query, array $domain, object $user): void
    {
        foreach ($domain as $condition) {
            if (!is_array($condition) || count($condition) < 3) {
                continue;
            }

            [$field, $operator, $value] = $condition;

            // Resolve dynamic values
            $value = $this->resolveValue($value, $user);

            // Apply condition using operator handler
            $this->applyCondition($query, $field, $operator, $value);
        }
    }

    /**
     * Check if a record matches domain conditions.
     */
    protected function recordMatchesDomain(Model $record, array $domain, object $user): bool
    {
        foreach ($domain as $condition) {
            if (!is_array($condition) || count($condition) < 3) {
                continue;
            }

            [$field, $operator, $value] = $condition;
            $value = $this->resolveValue($value, $user);
            $recordValue = data_get($record, $field);

            if (!$this->evaluateCondition($recordValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply a single condition to query.
     */
    protected function applyCondition(Builder $query, string $field, string $operator, mixed $value): void
    {
        if (isset($this->operators[$operator])) {
            call_user_func($this->operators[$operator], $query, $field, $value);
            return;
        }

        // Default operators
        match ($operator) {
            '=' => $query->where($field, $value),
            '!=' => $query->where($field, '!=', $value),
            '>' => $query->where($field, '>', $value),
            '<' => $query->where($field, '<', $value),
            '>=' => $query->where($field, '>=', $value),
            '<=' => $query->where($field, '<=', $value),
            'in' => $query->whereIn($field, (array)$value),
            'not in' => $query->whereNotIn($field, (array)$value),
            'like' => $query->where($field, 'like', $value),
            'ilike' => $query->where($field, 'ilike', $value),
            'is null' => $query->whereNull($field),
            'is not null' => $query->whereNotNull($field),
            default => $query->where($field, $operator, $value),
        };
    }

    /**
     * Evaluate a condition against a value.
     */
    protected function evaluateCondition(mixed $recordValue, string $operator, mixed $value): bool
    {
        return match ($operator) {
            '=' => $recordValue == $value,
            '!=' => $recordValue != $value,
            '>' => $recordValue > $value,
            '<' => $recordValue < $value,
            '>=' => $recordValue >= $value,
            '<=' => $recordValue <= $value,
            'in' => in_array($recordValue, (array)$value),
            'not in' => !in_array($recordValue, (array)$value),
            'like' => str_contains((string)$recordValue, str_replace('%', '', $value)),
            'is null' => $recordValue === null,
            'is not null' => $recordValue !== null,
            default => false,
        };
    }

    /**
     * Resolve dynamic values in domain.
     */
    protected function resolveValue(mixed $value, object $user): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Check for variable pattern {variable.path}
        if (preg_match('/^\{([^}]+)\}$/', $value, $matches)) {
            $path = $matches[1];

            // User context variables
            if (str_starts_with($path, 'user.')) {
                $userPath = substr($path, 5);
                return data_get($user, $userPath);
            }

            // Custom context resolver
            if ($this->userContextResolver) {
                $result = call_user_func($this->userContextResolver, $path, $user);
                if ($result !== null) {
                    return $result;
                }
            }

            // Built-in functions
            if (str_contains($path, '(')) {
                return $this->evaluateFunction($path, $user);
            }
        }

        return $value;
    }

    /**
     * Evaluate a domain function.
     */
    protected function evaluateFunction(string $expression, object $user): mixed
    {
        if (preg_match('/^(\w+)\(([^)]*)\)$/', $expression, $matches)) {
            $funcName = $matches[1];
            $args = array_map('trim', explode(',', $matches[2]));

            if (isset($this->functions[$funcName])) {
                return call_user_func($this->functions[$funcName], $user, ...$args);
            }
        }

        return null;
    }

    /**
     * Get user's groups.
     */
    protected function getUserGroups(object $user): array
    {
        // This depends on your permission system
        if (method_exists($user, 'getGroups')) {
            return $user->getGroups();
        }

        if (method_exists($user, 'roles')) {
            return $user->roles->pluck('name')->toArray();
        }

        if (isset($user->roles)) {
            return is_array($user->roles) ? $user->roles : [];
        }

        return [];
    }

    /**
     * Check if user is superuser.
     */
    protected function isSuperuser(object $user): bool
    {
        if (method_exists($user, 'isSuperuser')) {
            return $user->isSuperuser();
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('superuser');
        }

        return $user->is_admin ?? false;
    }

    /**
     * Get entity name from model.
     */
    protected function getEntityName(Model $record): string
    {
        return $record->entity_name ?? $record->getTable();
    }

    /**
     * Register a custom operator.
     */
    public function registerOperator(string $operator, callable $handler): void
    {
        $this->operators[$operator] = $handler;
    }

    /**
     * Register a custom domain function.
     */
    public function registerFunction(string $name, callable $handler): void
    {
        $this->functions[$name] = $handler;
    }

    /**
     * Set custom user context resolver.
     */
    public function setUserContextResolver(\Closure $resolver): void
    {
        $this->userContextResolver = $resolver;
    }

    /**
     * Temporarily bypass rules.
     */
    public function withoutRules(callable $callback): mixed
    {
        $this->bypassRules = true;

        try {
            return $callback();
        } finally {
            $this->bypassRules = false;
        }
    }

    /**
     * Clear rules cache.
     */
    public function clearCache(?string $entityName = null): void
    {
        if ($entityName) {
            // Clear cache for specific entity - need to clear for all users and permissions
            // Since we don't know all user IDs, we'll use tags if available
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['record_rules', $entityName])->flush();
            } else {
                // Fallback: Clear specific known keys by iterating active rules
                $permissions = ['read', 'write', 'create', 'delete'];
                $userIds = \App\Models\User::pluck('id')->toArray();
                foreach ($userIds as $userId) {
                    foreach ($permissions as $permission) {
                        Cache::forget("record_rules:{$entityName}:{$permission}:{$userId}");
                    }
                }
            }
        } else {
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['record_rules'])->flush();
            } else {
                // Clear all record rules cache - iterate all entities
                $entities = RecordRule::distinct()->pluck('entity_name')->toArray();
                foreach ($entities as $entity) {
                    $this->clearCache($entity);
                }
            }
        }
    }

    /**
     * Register built-in operators.
     */
    protected function registerBuiltInOperators(): void
    {
        $this->registerOperator('child_of', function ($query, $field, $value) {
            // Hierarchical: field is child of value
            $query->where(function ($q) use ($field, $value) {
                $q->where($field, $value)
                  ->orWhere('parent_path', 'like', "%/{$value}/%");
            });
        });

        $this->registerOperator('parent_of', function ($query, $field, $value) {
            // Hierarchical: field is parent of value
            $quotedField = $query->getGrammar()->wrap($field);
            $query->whereRaw("? LIKE CONCAT('%/', {$quotedField}, '/%')", [$value]);
        });
    }

    /**
     * Get all rules for an entity.
     */
    public function getRulesForEntity(string $entityName): \Illuminate\Support\Collection
    {
        return RecordRule::forEntity($entityName)->active()->get();
    }

    /**
     * Delete rules for a plugin.
     */
    public function deletePluginRules(string $pluginSlug): int
    {
        return RecordRule::where('plugin_slug', $pluginSlug)->delete();
    }
}
