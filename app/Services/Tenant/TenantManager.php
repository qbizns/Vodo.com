<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Tenant Manager - Multi-tenant data isolation and scoping.
 * 
 * Features:
 * - Row-level tenant isolation
 * - Company/branch hierarchy support
 * - Cross-tenant data sharing rules
 * - Automatic tenant scoping
 * 
 * Example usage:
 * 
 * // Configure tenant scope for an entity
 * $tenantManager->configureTenant('invoice', [
 *     'column' => 'company_id',
 *     'resolve' => fn() => auth()->user()->company_id,
 * ]);
 * 
 * // Allow cross-tenant access for specific records
 * $tenantManager->allowSharedAccess('product', [
 *     'conditions' => ['is_global' => true],
 * ]);
 */
class TenantManager
{
    /**
     * Tenant configuration per entity.
     * @var array<string, array>
     */
    protected array $tenantConfig = [];

    /**
     * Shared access rules.
     * @var array<string, array>
     */
    protected array $sharedAccessRules = [];

    /**
     * Currently active tenant.
     */
    protected ?int $currentTenantId = null;

    /**
     * Tenant context (company, branch, etc.).
     */
    protected array $tenantContext = [];

    /**
     * Bypass flag for system operations.
     */
    protected bool $bypassTenantScope = false;

    /**
     * Configure tenant isolation for an entity.
     */
    public function configureTenant(string $entityName, array $config): void
    {
        $this->tenantConfig[$entityName] = array_merge([
            'column' => 'tenant_id',
            'resolve' => fn() => $this->getCurrentTenantId(),
            'allow_null' => false,
            'inherit_from' => null, // Parent entity to inherit tenant from
            'shared_column' => null, // Column that marks shared records
        ], $config);
    }

    /**
     * Configure multi-company support for an entity.
     */
    public function configureCompany(string $entityName, array $config = []): void
    {
        $this->configureTenant($entityName, array_merge([
            'column' => 'company_id',
            'resolve' => fn() => $this->getCurrentCompanyId(),
        ], $config));
    }

    /**
     * Configure branch-level isolation.
     */
    public function configureBranch(string $entityName, array $config = []): void
    {
        $this->configureTenant($entityName, array_merge([
            'column' => 'branch_id',
            'resolve' => fn() => $this->getCurrentBranchId(),
        ], $config));
    }

    /**
     * Define shared access rules (cross-tenant visibility).
     */
    public function allowSharedAccess(string $entityName, array $rule): void
    {
        if (!isset($this->sharedAccessRules[$entityName])) {
            $this->sharedAccessRules[$entityName] = [];
        }

        $this->sharedAccessRules[$entityName][] = array_merge([
            'conditions' => [],
            'scopes' => [],
            'permission' => null,
        ], $rule);
    }

    /**
     * Set the current tenant ID.
     */
    public function setCurrentTenant(?int $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    /**
     * Get the current tenant ID.
     */
    public function getCurrentTenantId(): ?int
    {
        if ($this->currentTenantId !== null) {
            return $this->currentTenantId;
        }

        // Try to resolve from authenticated user
        $user = Auth::user();
        if ($user && isset($user->tenant_id)) {
            return $user->tenant_id;
        }

        return null;
    }

    /**
     * Set the current company context.
     */
    public function setCurrentCompany(?int $companyId): void
    {
        $this->tenantContext['company_id'] = $companyId;
    }

    /**
     * Get the current company ID.
     */
    public function getCurrentCompanyId(): ?int
    {
        if (isset($this->tenantContext['company_id'])) {
            return $this->tenantContext['company_id'];
        }

        $user = Auth::user();
        if ($user && isset($user->company_id)) {
            return $user->company_id;
        }

        return null;
    }

    /**
     * Set the current branch context.
     */
    public function setCurrentBranch(?int $branchId): void
    {
        $this->tenantContext['branch_id'] = $branchId;
    }

    /**
     * Get the current branch ID.
     */
    public function getCurrentBranchId(): ?int
    {
        if (isset($this->tenantContext['branch_id'])) {
            return $this->tenantContext['branch_id'];
        }

        $user = Auth::user();
        if ($user && isset($user->branch_id)) {
            return $user->branch_id;
        }

        return null;
    }

    /**
     * Apply tenant scope to a query.
     */
    public function applyScope(Builder $query, string $entityName): Builder
    {
        if ($this->bypassTenantScope) {
            return $query;
        }

        $config = $this->tenantConfig[$entityName] ?? null;
        if (!$config) {
            return $query;
        }

        $column = $config['column'];
        $tenantId = call_user_func($config['resolve']);

        // Build main tenant condition
        $query->where(function ($q) use ($column, $tenantId, $config, $entityName) {
            // Primary tenant condition
            if ($tenantId !== null) {
                $q->where($column, $tenantId);
            } elseif (!$config['allow_null']) {
                // No tenant ID and nulls not allowed - return nothing
                $q->whereRaw('1 = 0');
                return;
            }

            // Add shared access conditions
            $this->applySharedAccessRules($q, $entityName);
        });

        return $query;
    }

    /**
     * Apply shared access rules to query.
     */
    protected function applySharedAccessRules(Builder $query, string $entityName): void
    {
        $rules = $this->sharedAccessRules[$entityName] ?? [];

        foreach ($rules as $rule) {
            // Check permission if required
            if ($rule['permission'] && !Auth::user()?->can($rule['permission'])) {
                continue;
            }

            $query->orWhere(function ($q) use ($rule) {
                foreach ($rule['conditions'] as $field => $value) {
                    if (is_callable($value)) {
                        $value = call_user_func($value);
                    }
                    $q->where($field, $value);
                }

                foreach ($rule['scopes'] as $scope => $params) {
                    $q->$scope(...(array)$params);
                }
            });
        }
    }

    /**
     * Get tenant value to set on new records.
     */
    public function getTenantValueForCreate(string $entityName): array
    {
        $config = $this->tenantConfig[$entityName] ?? null;
        if (!$config) {
            return [];
        }

        $column = $config['column'];
        $tenantId = call_user_func($config['resolve']);

        if ($tenantId === null && !$config['allow_null']) {
            throw new \RuntimeException("Cannot create record without tenant context for entity: {$entityName}");
        }

        return [$column => $tenantId];
    }

    /**
     * Validate that a record belongs to current tenant.
     */
    public function validateAccess(Model $record, string $entityName): bool
    {
        if ($this->bypassTenantScope) {
            return true;
        }

        $config = $this->tenantConfig[$entityName] ?? null;
        if (!$config) {
            return true;
        }

        $column = $config['column'];
        $tenantId = call_user_func($config['resolve']);

        // Check direct ownership
        if ($record->$column === $tenantId) {
            return true;
        }

        // Check shared access rules
        foreach ($this->sharedAccessRules[$entityName] ?? [] as $rule) {
            if ($rule['permission'] && !Auth::user()?->can($rule['permission'])) {
                continue;
            }

            $matches = true;
            foreach ($rule['conditions'] as $field => $value) {
                if (is_callable($value)) {
                    $value = call_user_func($value);
                }
                if ($record->$field !== $value) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * Temporarily bypass tenant scope (for system operations).
     */
    public function withoutTenantScope(callable $callback): mixed
    {
        $this->bypassTenantScope = true;
        
        try {
            return $callback();
        } finally {
            $this->bypassTenantScope = false;
        }
    }

    /**
     * Execute callback in a different tenant context.
     */
    public function inTenantContext(int $tenantId, callable $callback): mixed
    {
        $previousTenant = $this->currentTenantId;
        $this->currentTenantId = $tenantId;

        try {
            return $callback();
        } finally {
            $this->currentTenantId = $previousTenant;
        }
    }

    /**
     * Check if entity has tenant configuration.
     */
    public function hasTenantConfig(string $entityName): bool
    {
        return isset($this->tenantConfig[$entityName]);
    }

    /**
     * Get tenant configuration for entity.
     */
    public function getTenantConfig(string $entityName): ?array
    {
        return $this->tenantConfig[$entityName] ?? null;
    }

    /**
     * Get all configured entities.
     */
    public function getConfiguredEntities(): array
    {
        return array_keys($this->tenantConfig);
    }

    /**
     * Get accessible tenant IDs for current user.
     */
    public function getAccessibleTenants(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        // This could be extended to support multi-tenant users
        $tenants = [];

        if (isset($user->tenant_id)) {
            $tenants[] = $user->tenant_id;
        }

        if (isset($user->company_id)) {
            $tenants[] = $user->company_id;
        }

        // Check for shared/admin access
        if (method_exists($user, 'getAccessibleTenants')) {
            $tenants = array_merge($tenants, $user->getAccessibleTenants());
        }

        return array_unique($tenants);
    }

    /**
     * Get current tenant context.
     */
    public function getTenantContext(): array
    {
        return array_merge([
            'tenant_id' => $this->getCurrentTenantId(),
            'company_id' => $this->getCurrentCompanyId(),
            'branch_id' => $this->getCurrentBranchId(),
        ], $this->tenantContext);
    }

    /**
     * Clear tenant context.
     */
    public function clearContext(): void
    {
        $this->currentTenantId = null;
        $this->tenantContext = [];
    }
}
