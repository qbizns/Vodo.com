<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Tenant;
use App\Services\Tenant\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HasTenant - Trait for models that require tenant isolation.
 * 
 * Usage:
 * 
 * class Invoice extends Model
 * {
 *     use HasTenant;
 * 
 *     protected string $tenantColumn = 'company_id'; // Default is 'tenant_id'
 * }
 * 
 * // Tenant scoping is automatic on all queries
 * Invoice::all(); // Only returns invoices for current tenant
 */
trait HasTenant
{
    /**
     * Boot the trait.
     */
    public static function bootHasTenant(): void
    {
        // Add global scope for tenant isolation
        static::addGlobalScope('tenant', function (Builder $builder) {
            $model = new static;
            $manager = app(TenantManager::class);
            
            $entityName = $model->getTable();
            
            if ($manager->hasTenantConfig($entityName)) {
                $manager->applyScope($builder, $entityName);
            } else {
                // Apply default tenant scoping - include global records (null tenant_id)
                $column = $model->getQualifiedTenantColumn();
                $tenantId = $manager->getCurrentTenantId();
                
                if ($tenantId !== null) {
                    $builder->where(function ($query) use ($column, $tenantId) {
                        $query->where($column, $tenantId)
                            ->orWhereNull($column);
                    });
                } else {
                    // Only show global records when no tenant context
                    $builder->whereNull($column);
                }
            }
        });

        // Auto-set tenant on create
        static::creating(function ($model) {
            $column = $model->getTenantColumn();
            
            if (is_null($model->$column)) {
                $manager = app(TenantManager::class);
                $tenantId = $manager->getCurrentTenantId();
                
                if ($tenantId !== null) {
                    $model->$column = $tenantId;
                }
            }
        });
    }

    /**
     * Get the tenant column name.
     */
    public function getTenantColumn(): string
    {
        return $this->tenantColumn ?? 'tenant_id';
    }

    /**
     * Get the qualified tenant column name (with table prefix).
     */
    public function getQualifiedTenantColumn(): string
    {
        return $this->getTable() . '.' . $this->getTenantColumn();
    }

    /**
     * Get the tenant ID for this record.
     */
    public function getTenantId(): ?int
    {
        $column = $this->getTenantColumn();
        return $this->$column;
    }

    /**
     * Get the tenant relationship.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, $this->getTenantColumn());
    }

    /**
     * Check if record belongs to current tenant.
     */
    public function belongsToCurrentTenant(): bool
    {
        $manager = app(TenantManager::class);
        return $this->getTenantId() === $manager->getCurrentTenantId();
    }

    /**
     * Check if record is global (no tenant).
     */
    public function isGlobal(): bool
    {
        return $this->getTenantId() === null;
    }

    /**
     * Make record global (remove tenant).
     */
    public function makeGlobal(): self
    {
        $column = $this->getTenantColumn();
        $this->$column = null;
        $this->save();
        return $this;
    }

    /**
     * Assign record to a specific tenant.
     */
    public function assignToTenant(int $tenantId): self
    {
        $column = $this->getTenantColumn();
        $this->$column = $tenantId;
        $this->save();
        return $this;
    }

    /**
     * Scope to include all tenants (bypass tenant filtering).
     */
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * Alias for withoutTenantScope.
     */
    public function scopeAllTenants($query)
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * Scope to a specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->withoutGlobalScope('tenant')
                     ->where($this->getTenantColumn(), $tenantId);
    }

    /**
     * Scope to global records only.
     */
    public function scopeGlobalOnly($query)
    {
        return $query->withoutGlobalScope('tenant')
                     ->whereNull($this->getTenantColumn());
    }
}
