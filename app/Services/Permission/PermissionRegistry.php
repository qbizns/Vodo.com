<?php

namespace App\Services\Permission;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\PermissionAudit;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Permission Registry Service
 *
 * Central service for managing permissions and roles.
 * Handles registration from plugins, validation, and caching.
 */
class PermissionRegistry
{
    protected array $runtimePermissions = [];
    protected array $pendingPermissions = [];
    protected array $pendingGroups = [];
    protected array $pendingRoles = [];
    protected array $pendingAssignments = [];

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

        // Validate permission slug format
        if (!Permission::validateSlug($slug)) {
            throw new \InvalidArgumentException("Invalid permission slug format: '{$slug}'. Must be in format 'module.action' or 'module.submodule.action'");
        }

        $existing = Permission::findBySlug($slug);
        if ($existing) {
            if ($existing->plugin_slug !== $pluginSlug && !$existing->is_system) {
                throw new \RuntimeException("Permission '{$slug}' is owned by another plugin");
            }
            return $this->updatePermission($slug, $config, $pluginSlug);
        }

        // Resolve or create permission group
        $groupId = null;
        $groupSlug = $config['group'] ?? Permission::slugToGroup($slug);
        if ($groupSlug) {
            $group = PermissionGroup::findOrCreate($groupSlug, [
                'plugin_slug' => $pluginSlug,
            ]);
            $groupId = $group->id;
        }

        $permission = Permission::create([
            'slug' => $slug,
            'name' => $config['name'] ?? Permission::slugToName($slug),
            'label' => $config['label'] ?? null,
            'description' => $config['description'] ?? null,
            'group' => $groupSlug,
            'group_id' => $groupId,
            'category' => $config['category'] ?? null,
            'plugin_slug' => $pluginSlug,
            'is_system' => $config['system'] ?? false,
            'is_active' => $config['active'] ?? true,
            'is_dangerous' => $config['dangerous'] ?? false,
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

    // =========================================================================
    // Permission Groups
    // =========================================================================

    /**
     * Register a permission group
     */
    public function registerGroup(array $config, ?string $pluginSlug = null): PermissionGroup
    {
        $slug = $config['slug'] ?? null;

        if (!$slug) {
            throw new \InvalidArgumentException('Group slug is required');
        }

        return PermissionGroup::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $config['name'] ?? ucwords(str_replace(['-', '_'], ' ', $slug)),
                'description' => $config['description'] ?? null,
                'plugin_slug' => $pluginSlug,
                'icon' => $config['icon'] ?? 'folder',
                'position' => $config['position'] ?? 0,
                'is_active' => $config['active'] ?? true,
            ]
        );
    }

    /**
     * Get permission groups
     */
    public function getGroups(): Collection
    {
        return Cache::remember('permission_groups:all', 3600, fn() =>
            PermissionGroup::active()->ordered()->get()
        );
    }

    /**
     * Get groups with permissions for UI
     */
    public function getGroupedForUI(): array
    {
        return Cache::remember('permissions:grouped_ui', 3600, fn() =>
            PermissionGroup::getGroupedPermissions()
        );
    }

    // =========================================================================
    // Plugin Lifecycle
    // =========================================================================

    /**
     * Register permissions from a plugin.
     * Called when a plugin is activated to register its permissions in the database.
     * Also handles reactivation of existing permissions when plugin was previously deactivated.
     *
     * @param \App\Models\Plugin $plugin The plugin model instance
     * @param \App\Services\Plugins\Contracts\PluginInterface $instance The plugin instance
     */
    public function registerPluginPermissions($plugin, $instance): void
    {
        // First, reactivate any existing permissions for this plugin
        // This handles the case where plugin was previously deactivated
        $this->onPluginEnabled($plugin->slug);

        // Get permissions from plugin's getPermissions() method
        $permissions = method_exists($instance, 'getPermissions')
            ? $instance->getPermissions()
            : [];

        if (empty($permissions)) {
            return;
        }

        // Create or update permission group with plugin name
        $group = $this->registerGroup([
            'slug' => $plugin->slug,
            'name' => $plugin->name,
            'icon' => 'plug',
            'active' => true,
        ], $plugin->slug);

        // Register each permission (creates new or updates existing)
        foreach ($permissions as $permSlug => $config) {
            $this->registerPermission([
                'slug' => $permSlug,
                'name' => $config['label'] ?? Permission::slugToName($permSlug),
                'label' => $config['label'] ?? null,
                'description' => $config['description'] ?? null,
                'group' => $plugin->slug,
                'dangerous' => $config['dangerous'] ?? false,
                'active' => true, // Ensure permission is active on registration/reactivation
            ], $plugin->slug);
        }
    }

    /**
     * Handle plugin enabled event - reactivate permissions
     */
    public function onPluginEnabled(string $pluginSlug): void
    {
        DB::transaction(function () use ($pluginSlug) {
            Permission::forPlugin($pluginSlug)->update(['is_active' => true]);
            PermissionGroup::forPlugin($pluginSlug)->update(['is_active' => true]);
            Role::forPlugin($pluginSlug)->update(['is_active' => true]);
        });

        PermissionAudit::logPluginEvent($pluginSlug, PermissionAudit::ACTION_PLUGIN_ENABLED);
        $this->clearCache();
    }

    /**
     * Handle plugin disabled event - deactivate permissions
     */
    public function onPluginDisabled(string $pluginSlug): void
    {
        DB::transaction(function () use ($pluginSlug) {
            Permission::forPlugin($pluginSlug)->update(['is_active' => false]);
            PermissionGroup::forPlugin($pluginSlug)->update(['is_active' => false]);
            Role::forPlugin($pluginSlug)->update(['is_active' => false]);
        });

        PermissionAudit::logPluginEvent($pluginSlug, PermissionAudit::ACTION_PLUGIN_DISABLED);
        $this->clearCache();
    }

    /**
     * Handle plugin uninstall - remove all permissions
     */
    public function onPluginUninstalled(string $pluginSlug): array
    {
        $stats = [
            'permissions_removed' => 0,
            'groups_removed' => 0,
            'roles_removed' => 0,
        ];

        DB::transaction(function () use ($pluginSlug, &$stats) {
            // Remove permission dependencies first
            $permissionIds = Permission::forPlugin($pluginSlug)->pluck('id');
            DB::table('permission_dependencies')
                ->whereIn('permission_id', $permissionIds)
                ->orWhereIn('requires_permission_id', $permissionIds)
                ->delete();

            // Remove role permissions
            DB::table('role_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            // Remove user permissions
            DB::table('user_permissions')
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            $stats['permissions_removed'] = Permission::forPlugin($pluginSlug)->delete();
            $stats['groups_removed'] = PermissionGroup::forPlugin($pluginSlug)->delete();

            // Remove plugin roles
            $roleIds = Role::forPlugin($pluginSlug)->pluck('id');
            DB::table('user_roles')->whereIn('role_id', $roleIds)->delete();
            $stats['roles_removed'] = Role::forPlugin($pluginSlug)->forceDelete();
        });

        PermissionAudit::logPluginEvent($pluginSlug, PermissionAudit::ACTION_PLUGIN_UNINSTALLED, $stats);
        $this->clearCache();

        return $stats;
    }

    // =========================================================================
    // Wildcard Support
    // =========================================================================

    /**
     * Check if user has permission (supports wildcards)
     */
    public function checkPermission($user, string $permission, $scope = null): bool
    {
        if (!$user) {
            return false;
        }

        // Check exact permission
        if ($this->userHasPermission($user, $permission, $scope)) {
            return true;
        }

        // Check wildcard permissions
        $parts = explode('.', $permission);
        $wildcards = [];
        $current = '';

        foreach ($parts as $i => $part) {
            if ($i === 0) {
                $current = $part;
            } else {
                $current .= '.' . $part;
            }

            if ($i < count($parts) - 1) {
                $wildcards[] = $current . '.*';
            }
        }

        foreach ($wildcards as $wildcard) {
            if ($this->userHasPermission($user, $wildcard, $scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Expand a wildcard permission to all matching permissions
     */
    public function expandWildcard(string $pattern): array
    {
        if (!str_ends_with($pattern, '.*')) {
            return [$pattern];
        }

        $prefix = rtrim($pattern, '.*');

        return Permission::active()
            ->where('slug', 'like', $prefix . '.%')
            ->where('slug', '!=', $pattern)
            ->pluck('slug')
            ->toArray();
    }

    // =========================================================================
    // Privilege Escalation Prevention
    // =========================================================================

    /**
     * Check if user can grant a permission
     */
    public function canUserGrantPermission($grantingUser, string $permission): bool
    {
        // Super admin can grant anything
        if ($this->userHasRole($grantingUser, Role::ROLE_SUPER_ADMIN)) {
            return true;
        }

        // Users can only grant permissions they have
        return $this->checkPermission($grantingUser, $permission);
    }

    /**
     * Check if user can edit a role
     */
    public function canUserEditRole($user, Role $role): bool
    {
        // Admin users (backend admins) have full role management access
        if ($user instanceof \App\Modules\Admin\Models\Admin) {
            return true;
        }

        // Super admin can edit any role
        if ($this->userHasRole($user, Role::ROLE_SUPER_ADMIN)) {
            return true;
        }

        // Cannot edit super admin role unless you are super admin
        if ($role->slug === Role::ROLE_SUPER_ADMIN) {
            return false;
        }

        // Cannot edit roles with higher level than yours
        $highestUserRole = $this->getHighestUserRole($user);
        if ($highestUserRole && $role->level > $highestUserRole->level) {
            return false;
        }

        return $this->userHasPermission($user, 'roles.edit');
    }

    /**
     * Get user's highest level role
     */
    public function getHighestUserRole($user): ?Role
    {
        if (!$user || !method_exists($user, 'roles')) {
            return null;
        }

        return $user->roles()->orderBy('level', 'desc')->first();
    }

    /**
     * Filter permissions a user can grant
     */
    public function filterGrantablePermissions($user, array $permissions): array
    {
        if ($this->userHasRole($user, Role::ROLE_SUPER_ADMIN)) {
            return $permissions;
        }

        return array_filter($permissions, fn($perm) =>
            is_string($perm) && $this->checkPermission($user, $perm)
        );
    }

    // =========================================================================
    // Statistics
    // =========================================================================

    /**
     * Get permission statistics
     */
    public function getStatistics(): array
    {
        return Cache::remember('permissions:stats', 3600, function () {
            return [
                'total_permissions' => Permission::count(),
                'active_permissions' => Permission::active()->count(),
                'total_roles' => Role::count(),
                'active_roles' => Role::active()->count(),
                'total_groups' => PermissionGroup::count(),
                'active_groups' => PermissionGroup::active()->count(),
                'permissions_by_plugin' => Permission::selectRaw('plugin_slug, COUNT(*) as count')
                    ->groupBy('plugin_slug')
                    ->pluck('count', 'plugin_slug'),
            ];
        });
    }

    // =========================================================================
    // Extended Cache
    // =========================================================================

    /**
     * Clear all permission-related caches
     */
    public function clearCache(): void
    {
        Cache::forget('permissions:all');
        Cache::forget('permissions:grouped_ui');
        Cache::forget('permissions:stats');
        Cache::forget('permission_groups:all');
        Cache::forget('roles:all');
    }

    /**
     * Warm permission caches
     */
    public function warmCache(): void
    {
        $this->getAllPermissions();
        $this->getAllRoles();
        $this->getGroups();
        $this->getGroupedForUI();
        $this->getStatistics();
    }
}
