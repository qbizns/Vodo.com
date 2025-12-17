<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Trait for User models to have roles and permissions
 * 
 * Add to your User model:
 * use App\Traits\HasPermissions;
 * 
 * class User extends Authenticatable
 * {
 *     use HasPermissions;
 * }
 */
trait HasPermissions
{
    // =========================================================================
    // Relationships
    // =========================================================================

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('scope_type', 'scope_id', 'expires_at')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('granted', 'scope_type', 'scope_id', 'expires_at')
            ->withTimestamps();
    }

    // =========================================================================
    // Role Management
    // =========================================================================

    /**
     * Assign a role to the user
     */
    public function assignRole(string|Role|array $roles, $scope = null): self
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $role = Role::findBySlug($role);
            }

            if (!$role) continue;

            $pivotData = [];
            if ($scope) {
                $pivotData['scope_type'] = get_class($scope);
                $pivotData['scope_id'] = $scope->id;
            }

            $this->roles()->syncWithoutDetaching([$role->id => $pivotData]);
        }

        $this->clearPermissionCache();
        return $this;
    }

    /**
     * Remove a role from the user
     */
    public function removeRole(string|Role|array $roles, $scope = null): self
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $role = Role::findBySlug($role);
            }

            if (!$role) continue;

            $query = $this->roles()->wherePivot('role_id', $role->id);
            
            if ($scope) {
                $query->wherePivot('scope_type', get_class($scope))
                      ->wherePivot('scope_id', $scope->id);
            }

            $query->detach($role->id);
        }

        $this->clearPermissionCache();
        return $this;
    }

    /**
     * Sync roles (replace all)
     */
    public function syncRoles(array $roles): self
    {
        $roleIds = [];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $role = Role::findBySlug($role);
            }
            if ($role) {
                $roleIds[] = $role->id;
            }
        }

        $this->roles()->sync($roleIds);
        $this->clearPermissionCache();
        return $this;
    }

    /**
     * Check if user has a role
     */
    public function hasRole(string|Role $role, $scope = null): bool
    {
        if (is_string($role)) {
            $roleSlug = $role;
        } else {
            $roleSlug = $role->slug;
        }

        $query = $this->roles()->where('slug', $roleSlug);

        if ($scope) {
            $query->wherePivot('scope_type', get_class($scope))
                  ->wherePivot('scope_id', $scope->id);
        }

        // Check expiration
        $query->where(function ($q) {
            $q->whereNull('user_roles.expires_at')
              ->orWhere('user_roles.expires_at', '>', now());
        });

        return $query->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get user's highest role
     */
    public function getHighestRole(): ?Role
    {
        return $this->roles()->orderBy('level', 'desc')->first();
    }

    /**
     * Get all role slugs
     */
    public function getRoleSlugs(): array
    {
        return $this->roles->pluck('slug')->toArray();
    }

    // =========================================================================
    // Direct Permission Management
    // =========================================================================

    /**
     * Grant a permission directly to the user
     */
    public function grantPermission(string|Permission|array $permissions, $scope = null): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = Permission::findOrCreate($permission);
            }

            $pivotData = ['granted' => true];
            if ($scope) {
                $pivotData['scope_type'] = get_class($scope);
                $pivotData['scope_id'] = $scope->id;
            }

            $this->permissions()->syncWithoutDetaching([$permission->id => $pivotData]);
        }

        $this->clearPermissionCache();
        return $this;
    }

    /**
     * Deny a permission directly (overrides role permissions)
     */
    public function denyPermission(string|Permission|array $permissions, $scope = null): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = Permission::findBySlug($permission);
                if (!$permission) continue;
            }

            $pivotData = ['granted' => false];
            if ($scope) {
                $pivotData['scope_type'] = get_class($scope);
                $pivotData['scope_id'] = $scope->id;
            }

            $this->permissions()->syncWithoutDetaching([$permission->id => $pivotData]);
        }

        $this->clearPermissionCache();
        return $this;
    }

    /**
     * Revoke a direct permission
     */
    public function revokePermission(string|Permission|array $permissions): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permission = Permission::findBySlug($permission);
                if (!$permission) continue;
            }

            $this->permissions()->detach($permission->id);
        }

        $this->clearPermissionCache();
        return $this;
    }

    // =========================================================================
    // Permission Checking
    // =========================================================================

    /**
     * Check if user has a permission
     */
    public function hasPermission(string $permissionSlug, $scope = null): bool
    {
        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Check cache
        $cacheKey = $this->getPermissionCacheKey($permissionSlug, $scope);
        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        // Check direct user permission first (highest priority)
        $directPermission = $this->getDirectPermission($permissionSlug, $scope);
        if ($directPermission !== null) {
            $this->permissionCache[$cacheKey] = $directPermission;
            return $directPermission;
        }

        // Check role permissions
        foreach ($this->roles as $role) {
            if ($scope) {
                // Check scoped role
                $pivot = $role->pivot;
                if ($pivot->scope_type !== get_class($scope) || $pivot->scope_id !== $scope->id) {
                    continue;
                }
            }

            if ($role->hasPermission($permissionSlug)) {
                $this->permissionCache[$cacheKey] = true;
                return true;
            }
        }

        // Check wildcard permissions
        if ($this->hasWildcardPermission($permissionSlug)) {
            $this->permissionCache[$cacheKey] = true;
            return true;
        }

        $this->permissionCache[$cacheKey] = false;
        return false;
    }

    /**
     * Alias for hasPermission (Laravel Gate compatibility)
     */
    public function can($ability, $arguments = []): bool
    {
        // First check Laravel's built-in Gate
        if (app('gate')->has($ability)) {
            return app('gate')->forUser($this)->check($ability, $arguments);
        }

        // Then check our permission system
        $scope = is_array($arguments) && count($arguments) > 0 ? $arguments[0] : null;
        return $this->hasPermission($ability, $scope);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get direct permission value
     */
    protected function getDirectPermission(string $slug, $scope = null): ?bool
    {
        $query = $this->permissions()
            ->where('slug', $slug)
            ->where(function ($q) {
                $q->whereNull('user_permissions.expires_at')
                  ->orWhere('user_permissions.expires_at', '>', now());
            });

        if ($scope) {
            $query->wherePivot('scope_type', get_class($scope))
                  ->wherePivot('scope_id', $scope->id);
        }

        $permission = $query->first();

        return $permission ? (bool) $permission->pivot->granted : null;
    }

    /**
     * Check wildcard permissions (e.g., 'posts.*')
     */
    protected function hasWildcardPermission(string $slug): bool
    {
        $parts = explode('.', $slug);
        if (count($parts) < 2) {
            return false;
        }

        // Check for 'group.*' permission
        $wildcardSlug = $parts[0] . '.*';
        
        // Check direct
        $direct = $this->getDirectPermission($wildcardSlug);
        if ($direct !== null) {
            return $direct;
        }

        // Check roles
        foreach ($this->roles as $role) {
            if ($role->hasPermission($wildcardSlug)) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // Special Checks
    // =========================================================================

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::ROLE_SUPER_ADMIN);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ROLE_ADMIN) || $this->isSuperAdmin();
    }

    /**
     * Check if user has higher role than another user
     */
    public function hasHigherRoleThan($user): bool
    {
        $myHighest = $this->getHighestRole();
        $theirHighest = $user->getHighestRole();

        if (!$myHighest || !$theirHighest) {
            return false;
        }

        return $myHighest->isHigherThan($theirHighest);
    }

    // =========================================================================
    // Permission Collection
    // =========================================================================

    /**
     * Get all user's permissions (direct + from roles)
     */
    public function getAllPermissions(): Collection
    {
        $permissions = collect();

        // Get direct granted permissions
        $directPermissions = $this->permissions()
            ->wherePivot('granted', true)
            ->where(function ($q) {
                $q->whereNull('user_permissions.expires_at')
                  ->orWhere('user_permissions.expires_at', '>', now());
            })
            ->get();

        $permissions = $permissions->merge($directPermissions);

        // Get role permissions
        foreach ($this->roles as $role) {
            $permissions = $permissions->merge($role->getAllPermissions());
        }

        // Remove denied permissions
        $deniedIds = $this->permissions()
            ->wherePivot('granted', false)
            ->pluck('permissions.id');

        return $permissions->unique('id')->reject(fn($p) => $deniedIds->contains($p->id));
    }

    /**
     * Get all permission slugs
     */
    public function getAllPermissionSlugs(): array
    {
        return $this->getAllPermissions()->pluck('slug')->toArray();
    }

    // =========================================================================
    // Cache Management
    // =========================================================================

    protected array $permissionCache = [];

    protected function getPermissionCacheKey(string $slug, $scope = null): string
    {
        $key = "perm:{$slug}";
        if ($scope) {
            $key .= ':' . get_class($scope) . ':' . $scope->id;
        }
        return $key;
    }

    public function clearPermissionCache(): void
    {
        $this->permissionCache = [];
    }

    /**
     * Boot trait - clear cache on role/permission changes
     */
    public static function bootHasPermissions(): void
    {
        static::saved(function ($model) {
            $model->clearPermissionCache();
        });
    }
}
