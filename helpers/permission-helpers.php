<?php

/**
 * Permission Helper Functions
 */

use App\Models\Permission;
use App\Models\Role;
use App\Services\Permission\PermissionRegistry;

// =============================================================================
// Registry Access
// =============================================================================

if (!function_exists('permission_registry')) {
    function permission_registry(): PermissionRegistry
    {
        return app(PermissionRegistry::class);
    }
}

// =============================================================================
// Permission Registration
// =============================================================================

if (!function_exists('register_permission')) {
    function register_permission(array $config, ?string $pluginSlug = null): Permission
    {
        return permission_registry()->registerPermission($config, $pluginSlug);
    }
}

if (!function_exists('register_permissions')) {
    function register_permissions(array $permissions, ?string $pluginSlug = null): array
    {
        return permission_registry()->registerPermissions($permissions, $pluginSlug);
    }
}

if (!function_exists('register_crud_permissions')) {
    function register_crud_permissions(string $resource, array $actions = ['view', 'create', 'update', 'delete'], ?string $pluginSlug = null): array
    {
        return permission_registry()->registerCrudPermissions($resource, $actions, $pluginSlug);
    }
}

if (!function_exists('unregister_permission')) {
    function unregister_permission(string $slug, ?string $pluginSlug = null): bool
    {
        return permission_registry()->unregisterPermission($slug, $pluginSlug);
    }
}

// =============================================================================
// Role Registration
// =============================================================================

if (!function_exists('register_role')) {
    function register_role(array $config, ?string $pluginSlug = null): Role
    {
        return permission_registry()->registerRole($config, $pluginSlug);
    }
}

if (!function_exists('unregister_role')) {
    function unregister_role(string $slug, ?string $pluginSlug = null): bool
    {
        return permission_registry()->unregisterRole($slug, $pluginSlug);
    }
}

// =============================================================================
// Retrieval
// =============================================================================

if (!function_exists('get_permission')) {
    function get_permission(string $slug): ?Permission
    {
        return permission_registry()->getPermission($slug);
    }
}

if (!function_exists('get_all_permissions')) {
    function get_all_permissions(): \Illuminate\Support\Collection
    {
        return permission_registry()->getAllPermissions();
    }
}

if (!function_exists('get_permissions_grouped')) {
    function get_permissions_grouped(): \Illuminate\Support\Collection
    {
        return permission_registry()->getPermissionsGrouped();
    }
}

if (!function_exists('get_role')) {
    function get_role(string $slug): ?Role
    {
        return permission_registry()->getRole($slug);
    }
}

if (!function_exists('get_all_roles')) {
    function get_all_roles(): \Illuminate\Support\Collection
    {
        return permission_registry()->getAllRoles();
    }
}

// =============================================================================
// Permission Checking
// =============================================================================

if (!function_exists('user_can')) {
    /**
     * Check if current user has permission
     */
    function user_can(string $permission, $scope = null): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return method_exists($user, 'hasPermission') ? $user->hasPermission($permission, $scope) : false;
    }
}

if (!function_exists('user_can_any')) {
    /**
     * Check if current user has any of the permissions
     */
    function user_can_any(array $permissions): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return method_exists($user, 'hasAnyPermission') ? $user->hasAnyPermission($permissions) : false;
    }
}

if (!function_exists('user_can_all')) {
    /**
     * Check if current user has all permissions
     */
    function user_can_all(array $permissions): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return method_exists($user, 'hasAllPermissions') ? $user->hasAllPermissions($permissions) : false;
    }
}

if (!function_exists('user_has_role')) {
    /**
     * Check if current user has role
     */
    function user_has_role(string $role): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return method_exists($user, 'hasRole') ? $user->hasRole($role) : false;
    }
}

if (!function_exists('user_has_any_role')) {
    /**
     * Check if current user has any of the roles
     */
    function user_has_any_role(array $roles): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return method_exists($user, 'hasAnyRole') ? $user->hasAnyRole($roles) : false;
    }
}

if (!function_exists('user_is_admin')) {
    /**
     * Check if current user is admin
     */
    function user_is_admin(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
    }
}

if (!function_exists('user_is_super_admin')) {
    /**
     * Check if current user is super admin
     */
    function user_is_super_admin(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : false;
    }
}

// =============================================================================
// Role Management
// =============================================================================

if (!function_exists('grant_permission_to_role')) {
    function grant_permission_to_role(string $roleSlug, string|array $permissions): void
    {
        permission_registry()->grantToRole($roleSlug, $permissions);
    }
}

if (!function_exists('deny_permission_from_role')) {
    function deny_permission_from_role(string $roleSlug, string|array $permissions): void
    {
        permission_registry()->denyFromRole($roleSlug, $permissions);
    }
}

// =============================================================================
// User Management
// =============================================================================

if (!function_exists('assign_role_to_user')) {
    function assign_role_to_user($user, string|Role|array $roles): void
    {
        if (method_exists($user, 'assignRole')) {
            $user->assignRole($roles);
        }
    }
}

if (!function_exists('remove_role_from_user')) {
    function remove_role_from_user($user, string|Role|array $roles): void
    {
        if (method_exists($user, 'removeRole')) {
            $user->removeRole($roles);
        }
    }
}

if (!function_exists('grant_permission_to_user')) {
    function grant_permission_to_user($user, string|Permission|array $permissions): void
    {
        if (method_exists($user, 'grantPermission')) {
            $user->grantPermission($permissions);
        }
    }
}

if (!function_exists('revoke_permission_from_user')) {
    function revoke_permission_from_user($user, string|Permission|array $permissions): void
    {
        if (method_exists($user, 'revokePermission')) {
            $user->revokePermission($permissions);
        }
    }
}

// =============================================================================
// Cache
// =============================================================================

if (!function_exists('clear_permission_cache')) {
    function clear_permission_cache(): void
    {
        permission_registry()->clearCache();
    }
}
