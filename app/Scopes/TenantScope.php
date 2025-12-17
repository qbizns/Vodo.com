<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant Scope - Automatically filters queries by tenant.
 * 
 * When applied to a model, all queries will be scoped to the current tenant.
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId) {
            // Include records belonging to tenant OR global records (null tenant_id)
            $builder->where(function ($query) use ($tenantId, $model) {
                $query->where($model->getTable() . '.tenant_id', $tenantId)
                    ->orWhereNull($model->getTable() . '.tenant_id');
            });
        } else {
            // Only show global records when no tenant context
            $builder->whereNull($model->getTable() . '.tenant_id');
        }
    }

    /**
     * Get current tenant ID.
     */
    protected function getCurrentTenantId(): ?int
    {
        // Try to get from authenticated user
        $user = Auth::user();
        if ($user && isset($user->tenant_id)) {
            return $user->tenant_id;
        }

        // Try to get from app context
        if (app()->bound('current_tenant_id')) {
            return app('current_tenant_id');
        }

        return null;
    }
}
