<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use App\Services\Permission\PermissionRegistry;
use Illuminate\Support\Collection;

/**
 * Trait for plugins to register and manage permissions
 * 
 * class MyPlugin extends BasePlugin
 * {
 *     use HasPluginPermissions;
 * 
 *     public function activate(): void
 *     {
 *         // Register permissions
 *         $this->registerPermission([
 *             'slug' => 'my-plugin.settings',
 *             'name' => 'Manage Plugin Settings',
 *         ]);
 * 
 *         // Register CRUD permissions
 *         $this->registerCrudPermissions('my-plugin.products');
 * 
 *         // Register a role
 *         $this->registerRole([
 *             'slug' => 'product_manager',
 *             'name' => 'Product Manager',
 *             'permissions' => ['my-plugin.products.*'],
 *         ]);
 *     }
 * 
 *     public function deactivate(): void
 *     {
 *         $this->cleanupPermissions();
 *     }
 * }
 */
trait HasPluginPermissions
{
    protected function permissionRegistry(): PermissionRegistry
    {
        return app(PermissionRegistry::class);
    }

    protected function getPermissionPluginSlug(): string
    {
        return $this->slug ?? $this->pluginSlug ?? strtolower(class_basename($this));
    }

    // =========================================================================
    // Permission Registration
    // =========================================================================

    /**
     * Register a permission
     */
    public function registerPermission(array $config): Permission
    {
        return $this->permissionRegistry()->registerPermission($config, $this->getPermissionPluginSlug());
    }

    /**
     * Register multiple permissions
     */
    public function registerPermissions(array $permissions): array
    {
        return $this->permissionRegistry()->registerPermissions($permissions, $this->getPermissionPluginSlug());
    }

    /**
     * Register CRUD permissions for a resource
     */
    public function registerCrudPermissions(string $resource, array $actions = ['view', 'create', 'update', 'delete']): array
    {
        return $this->permissionRegistry()->registerCrudPermissions($resource, $actions, $this->getPermissionPluginSlug());
    }

    /**
     * Unregister a permission
     */
    public function unregisterPermission(string $slug): bool
    {
        return $this->permissionRegistry()->unregisterPermission($slug, $this->getPermissionPluginSlug());
    }

    // =========================================================================
    // Role Registration
    // =========================================================================

    /**
     * Register a role
     */
    public function registerRole(array $config): Role
    {
        return $this->permissionRegistry()->registerRole($config, $this->getPermissionPluginSlug());
    }

    /**
     * Unregister a role
     */
    public function unregisterRole(string $slug): bool
    {
        return $this->permissionRegistry()->unregisterRole($slug, $this->getPermissionPluginSlug());
    }

    // =========================================================================
    // Role Permission Management
    // =========================================================================

    /**
     * Grant permissions to a role
     */
    public function grantToRole(string $roleSlug, string|array $permissions): void
    {
        $this->permissionRegistry()->grantToRole($roleSlug, $permissions);
    }

    /**
     * Deny permissions from a role
     */
    public function denyFromRole(string $roleSlug, string|array $permissions): void
    {
        $this->permissionRegistry()->denyFromRole($roleSlug, $permissions);
    }

    // =========================================================================
    // Checking
    // =========================================================================

    /**
     * Check if current user has permission
     */
    public function userCan(string $permission, $scope = null): bool
    {
        return $this->permissionRegistry()->userHasPermission(auth()->user(), $permission, $scope);
    }

    /**
     * Check if current user has role
     */
    public function userHasRole(string $role): bool
    {
        return $this->permissionRegistry()->userHasRole(auth()->user(), $role);
    }

    // =========================================================================
    // Retrieval
    // =========================================================================

    /**
     * Get plugin's permissions
     */
    public function getPluginPermissions(): Collection
    {
        return Permission::forPlugin($this->getPermissionPluginSlug())->get();
    }

    /**
     * Get plugin's roles
     */
    public function getPluginRoles(): Collection
    {
        return Role::forPlugin($this->getPermissionPluginSlug())->get();
    }

    // =========================================================================
    // Cleanup
    // =========================================================================

    /**
     * Remove all permissions and roles for this plugin
     */
    public function cleanupPermissions(): int
    {
        $slug = $this->getPermissionPluginSlug();
        
        // Remove roles first (they reference permissions)
        Role::where('plugin_slug', $slug)->where('is_system', false)->delete();
        
        // Remove permissions
        return $this->permissionRegistry()->unregisterPluginPermissions($slug);
    }
}
