<?php

namespace App\Services\Permission;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Permission Registry Service
 * 
 * Central service for managing permissions and roles.
 */
class PermissionRegistry
{
    protected array $runtimePermissions = [];

    // =========================================================================
    // Permission Registration
    // =========================================================================

    /**
     * Register a permission
     */
    public function registerPermission(array $config, ?string $pluginSlug = null): Permission
    {
        $slug = $config['slug'] ?? null;
        
        if (!$slug) {
            throw new \InvalidArgumentException('Permission slug is required');
        }

        $existing = Permission::findBySlug($slug);
        if ($existing) {
            if ($existing->plugin_slug !== $pluginSlug && !$existing->is_system) {
                throw new \RuntimeException("Permission '{$slug}' is owned by another plugin");
            }
            return $this->updatePermission($slug, $config, $pluginSlug);
        }

        $permission = Permission::create([
            'slug' => $slug,
            'name' => $config['name'] ?? Permission::slugToName($slug),
            'description' => $config['description'] ?? null,
            'group' => $config['group'] ?? Permission::slugToGroup($slug),
            'category' => $config['category'] ?? null,
            'plugin_slug' => $pluginSlug,
            'is_system' => $config['system'] ?? false,
            'is_active' => $config['active'] ?? true,
            'priority' => $config['priority'] ?? 100,
            'meta' => $config['meta'] ?? null,
        ]);

        // Add dependencies
        if (isset($config['requires']) && is_array($config['requires'])) {
            foreach ($config['requires'] as $required) {
                $permission->requires($required);
            }
        }

        $this->clearCache();

        if (function_exists('do_action')) {
            do_action('permission_registered', $permission);
        }

        return $permission;
    }

    /**
     * Register multiple permissions
     */
    public function registerPermissions(array $permissions, ?string $pluginSlug = null): array
    {
        $registered = [];
        foreach ($permissions as $config) {
            $registered[] = $this->registerPermission($config, $pluginSlug);
        }
        return $registered;
    }

    /**
     * Update a permission
     */
    public function updatePermission(string $slug, array $config, ?string $pluginSlug = null): Permission
    {
        $permission = Permission::findBySlug($slug);
        
        if (!$permission) {
            throw new \RuntimeException("Permission '{$slug}' not found");
        }

        $updateData = [];
        $fields = ['name', 'description', 'group', 'category', 'priority', 'meta'];
        
        foreach ($fields as $field) {
            if (array_key_exists($field, $config)) {
                $updateData[$field] = $config[$field];
            }
        }

        if (array_key_exists('active', $config)) {
            $updateData['is_active'] = $config['active'];
        }

        $permission->update($updateData);
        $this->clearCache();

        return $permission->fresh();
    }

    /**
     * Unregister a permission
     */
    public function unregisterPermission(string $slug, ?string $pluginSlug = null): bool
    {
        $permission = Permission::findBySlug($slug);
        
        if (!$permission) {
            return false;
        }

        if ($permission->plugin_slug !== $pluginSlug) {
            throw new \RuntimeException("Cannot unregister permission - owned by another plugin");
        }

        if ($permission->is_system) {
            throw new \RuntimeException("Cannot unregister system permission");
        }

        $permission->delete();
        $this->clearCache();

        if (function_exists('do_action')) {
            do_action('permission_unregistered', $slug);
        }

        return true;
    }

    /**
     * Unregister all permissions for a plugin
     */
    public function unregisterPluginPermissions(string $pluginSlug): int
    {
        $count = Permission::where('plugin_slug', $pluginSlug)
            ->where('is_system', false)
            ->delete();

        $this->clearCache();
        return $count;
    }

    // =========================================================================
    // Role Registration
    // =========================================================================

    /**
     * Register a role
     */
    public function registerRole(array $config, ?string $pluginSlug = null): Role
    {
        $slug = $config['slug'] ?? null;
        
        if (!$slug) {
            throw new \InvalidArgumentException('Role slug is required');
        }

        $existing = Role::findBySlug($slug);
        if ($existing) {
            return $this->updateRole($slug, $config, $pluginSlug);
        }

        // Resolve parent
        $parentId = null;
        if (isset($config['parent'])) {
            $parent = Role::findBySlug($config['parent']);
            $parentId = $parent?->id;
        }

        $role = Role::create([
            'slug' => $slug,
            'name' => $config['name'] ?? ucwords(str_replace(['_', '-'], ' ', $slug)),
            'description' => $config['description'] ?? null,
            'level' => $config['level'] ?? 0,
            'parent_id' => $parentId,
            'is_default' => $config['default'] ?? false,
            'is_system' => $config['system'] ?? false,
            'is_active' => $config['active'] ?? true,
            'plugin_slug' => $pluginSlug,
            'meta' => $config['meta'] ?? null,
        ]);

        // Assign permissions
        if (isset($config['permissions']) && is_array($config['permissions'])) {
            $role->syncPermissions($config['permissions']);
        }

        $this->clearCache();

        if (function_exists('do_action')) {
            do_action('role_registered', $role);
        }

        return $role;
    }

    /**
     * Update a role
     */
    public function updateRole(string $slug, array $config, ?string $pluginSlug = null): Role
    {
        $role = Role::findBySlug($slug);
        
        if (!$role) {
            throw new \RuntimeException("Role '{$slug}' not found");
        }

        $updateData = [];
        $fields = ['name', 'description', 'level', 'meta'];
        
        foreach ($fields as $field) {
            if (array_key_exists($field, $config)) {
                $updateData[$field] = $config[$field];
            }
        }

        if (array_key_exists('active', $config)) {
            $updateData['is_active'] = $config['active'];
        }

        if (array_key_exists('default', $config)) {
            $updateData['is_default'] = $config['default'];
        }

        $role->update($updateData);

        // Update permissions if provided
        if (isset($config['permissions'])) {
            $role->syncPermissions($config['permissions']);
        }

        $this->clearCache();
        return $role->fresh();
    }

    /**
     * Unregister a role
     */
    public function unregisterRole(string $slug, ?string $pluginSlug = null): bool
    {
        $role = Role::findBySlug($slug);
        
        if (!$role) {
            return false;
        }

        if ($role->plugin_slug !== $pluginSlug) {
            throw new \RuntimeException("Cannot unregister role - owned by another plugin");
        }

        if ($role->is_system) {
            throw new \RuntimeException("Cannot unregister system role");
        }

        $role->delete();
        $this->clearCache();

        return true;
    }

    // =========================================================================
    // Retrieval
    // =========================================================================

    /**
     * Get permission by slug
     */
    public function getPermission(string $slug): ?Permission
    {
        return Permission::findBySlug($slug);
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions(): Collection
    {
        return Cache::remember('permissions:all', 3600, fn() => 
            Permission::active()->ordered()->get()
        );
    }

    /**
     * Get permissions by group
     */
    public function getPermissionsByGroup(string $group): Collection
    {
        return Permission::active()->inGroup($group)->ordered()->get();
    }

    /**
     * Get permissions grouped
     */
    public function getPermissionsGrouped(): Collection
    {
        return $this->getAllPermissions()->groupBy('group');
    }

    /**
     * Get role by slug
     */
    public function getRole(string $slug): ?Role
    {
        return Role::findBySlug($slug);
    }

    /**
     * Get all roles
     */
    public function getAllRoles(): Collection
    {
        return Cache::remember('roles:all', 3600, fn() => 
            Role::active()->ordered()->get()
        );
    }

    /**
     * Get roles for plugin
     */
    public function getRolesForPlugin(string $pluginSlug): Collection
    {
        return Role::forPlugin($pluginSlug)->get();
    }

    // =========================================================================
    // Quick Registration Helpers
    // =========================================================================

    /**
     * Register CRUD permissions for a resource
     */
    public function registerCrudPermissions(
        string $resource,
        array $actions = ['view', 'create', 'update', 'delete'],
        ?string $pluginSlug = null
    ): array {
        $permissions = [];

        foreach ($actions as $action) {
            $permissions[] = $this->registerPermission([
                'slug' => "{$resource}.{$action}",
                'group' => $resource,
            ], $pluginSlug);
        }

        // Add wildcard permission
        $permissions[] = $this->registerPermission([
            'slug' => "{$resource}.*",
            'name' => "Full {$resource} Access",
            'group' => $resource,
        ], $pluginSlug);

        return $permissions;
    }

    /**
     * Grant permission to role
     */
    public function grantToRole(string $roleSlug, string|array $permissions): void
    {
        $role = Role::findBySlug($roleSlug);
        if ($role) {
            $role->grantPermission($permissions);
        }
    }

    /**
     * Deny permission from role
     */
    public function denyFromRole(string $roleSlug, string|array $permissions): void
    {
        $role = Role::findBySlug($roleSlug);
        if ($role) {
            $role->denyPermission($permissions);
        }
    }

    // =========================================================================
    // Checking
    // =========================================================================

    /**
     * Check if a permission exists
     */
    public function permissionExists(string $slug): bool
    {
        return Permission::where('slug', $slug)->exists();
    }

    /**
     * Check if a role exists
     */
    public function roleExists(string $slug): bool
    {
        return Role::where('slug', $slug)->exists();
    }

    /**
     * Check if user has permission
     */
    public function userHasPermission($user, string $permission, $scope = null): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission, $scope);
        }

        return false;
    }

    /**
     * Check if user has role
     */
    public function userHasRole($user, string $role): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($role);
        }

        return false;
    }

    // =========================================================================
    // Cache
    // =========================================================================

    public function clearCache(): void
    {
        Cache::forget('permissions:all');
        Cache::forget('roles:all');
    }

    // =========================================================================
    // Documentation
    // =========================================================================

    /**
     * Get all permissions as documentation
     */
    public function getDocumentation(): array
    {
        $grouped = $this->getPermissionsGrouped();
        $docs = [];

        foreach ($grouped as $group => $permissions) {
            $docs[$group] = [
                'name' => Permission::getGroups()[$group] ?? ucfirst($group),
                'permissions' => $permissions->map->toDocumentation()->toArray(),
            ];
        }

        return $docs;
    }
}
