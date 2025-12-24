<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeds default roles and permissions for the platform.
 */
class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default permissions
        $this->createPermissions();

        // Create default roles
        $this->createRoles();

        // Assign permissions to roles
        $this->assignPermissions();
    }

    /**
     * Create default permissions.
     */
    protected function createPermissions(): void
    {
        $permissions = [
            // Entity permissions
            ['slug' => 'entities.view', 'name' => 'View Entities', 'group' => 'entities', 'is_system' => true],
            ['slug' => 'entities.create', 'name' => 'Create Entities', 'group' => 'entities', 'is_system' => true],
            ['slug' => 'entities.update', 'name' => 'Update Entities', 'group' => 'entities', 'is_system' => true],
            ['slug' => 'entities.delete', 'name' => 'Delete Entities', 'group' => 'entities', 'is_system' => true],
            ['slug' => 'entities.bulk_delete', 'name' => 'Bulk Delete Entities', 'group' => 'entities', 'is_system' => true],
            ['slug' => 'entities.restore', 'name' => 'Restore Entities', 'group' => 'entities', 'is_system' => true],
            ['slug' => 'entities.force_delete', 'name' => 'Force Delete Entities', 'group' => 'entities', 'is_system' => true],

            // User management
            ['slug' => 'users.view', 'name' => 'View Users', 'group' => 'users', 'is_system' => true],
            ['slug' => 'users.create', 'name' => 'Create Users', 'group' => 'users', 'is_system' => true],
            ['slug' => 'users.update', 'name' => 'Update Users', 'group' => 'users', 'is_system' => true],
            ['slug' => 'users.delete', 'name' => 'Delete Users', 'group' => 'users', 'is_system' => true],
            ['slug' => 'users.manage_roles', 'name' => 'Manage User Roles', 'group' => 'users', 'is_system' => true],

            // Role management
            ['slug' => 'roles.view', 'name' => 'View Roles', 'group' => 'roles', 'is_system' => true],
            ['slug' => 'roles.create', 'name' => 'Create Roles', 'group' => 'roles', 'is_system' => true],
            ['slug' => 'roles.update', 'name' => 'Update Roles', 'group' => 'roles', 'is_system' => true],
            ['slug' => 'roles.delete', 'name' => 'Delete Roles', 'group' => 'roles', 'is_system' => true],

            // Permission management
            ['slug' => 'permissions.view', 'name' => 'View Permissions', 'group' => 'permissions', 'is_system' => true],
            ['slug' => 'permissions.manage', 'name' => 'Manage Permissions', 'group' => 'permissions', 'is_system' => true],

            // Plugin management
            ['slug' => 'plugins.view', 'name' => 'View Plugins', 'group' => 'plugins', 'is_system' => true],
            ['slug' => 'plugins.install', 'name' => 'Install Plugins', 'group' => 'plugins', 'is_system' => true],
            ['slug' => 'plugins.activate', 'name' => 'Activate Plugins', 'group' => 'plugins', 'is_system' => true],
            ['slug' => 'plugins.deactivate', 'name' => 'Deactivate Plugins', 'group' => 'plugins', 'is_system' => true],
            ['slug' => 'plugins.delete', 'name' => 'Delete Plugins', 'group' => 'plugins', 'is_system' => true],
            ['slug' => 'plugins.configure', 'name' => 'Configure Plugins', 'group' => 'plugins', 'is_system' => true],

            // Settings
            ['slug' => 'settings.view', 'name' => 'View Settings', 'group' => 'settings', 'is_system' => true],
            ['slug' => 'settings.update', 'name' => 'Update Settings', 'group' => 'settings', 'is_system' => true],

            // API
            ['slug' => 'api.access', 'name' => 'API Access', 'group' => 'api', 'is_system' => true],
            ['slug' => 'api.manage_keys', 'name' => 'Manage API Keys', 'group' => 'api', 'is_system' => true],

            // Scheduler
            ['slug' => 'scheduler.view', 'name' => 'View Scheduled Tasks', 'group' => 'scheduler', 'is_system' => true],
            ['slug' => 'scheduler.manage', 'name' => 'Manage Scheduled Tasks', 'group' => 'scheduler', 'is_system' => true],

            // Workflows
            ['slug' => 'workflows.view', 'name' => 'View Workflows', 'group' => 'workflows', 'is_system' => true],
            ['slug' => 'workflows.manage', 'name' => 'Manage Workflows', 'group' => 'workflows', 'is_system' => true],

            // Audit
            ['slug' => 'audit.view', 'name' => 'View Audit Logs', 'group' => 'audit', 'is_system' => true],

            // System
            ['slug' => 'system.access_admin', 'name' => 'Access Admin Panel', 'group' => 'system', 'is_system' => true],
            ['slug' => 'system.access_console', 'name' => 'Access Console', 'group' => 'system', 'is_system' => true],
            ['slug' => 'system.impersonate', 'name' => 'Impersonate Users', 'group' => 'system', 'is_system' => true],
        ];

        foreach ($permissions as $data) {
            Permission::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('Created ' . count($permissions) . ' permissions.');
    }

    /**
     * Create default roles.
     */
    protected function createRoles(): void
    {
        $roles = [
            // System roles (highest priority)
            [
                'slug' => Role::ROLE_SUPER_ADMIN,
                'name' => 'Super Admin',
                'description' => 'Full access to all features and all panels',
                'level' => 1000,
                'is_system' => true,
                'color' => '#7C3AED',
                'icon' => 'crown',
            ],
            
            // Panel access roles
            [
                'slug' => 'console_admin',
                'name' => 'Console Admin',
                'description' => 'SaaS platform management - can access Console panel',
                'level' => 900,
                'is_system' => true,
                'color' => '#DC2626',
                'icon' => 'server',
            ],
            [
                'slug' => 'owner',
                'name' => 'Owner',
                'description' => 'Business owner - can access Owner and Admin panels',
                'level' => 800,
                'is_system' => true,
                'color' => '#2563EB',
                'icon' => 'building',
            ],
            [
                'slug' => Role::ROLE_ADMIN,
                'name' => 'Admin',
                'description' => 'Backend administrator - can access Admin panel',
                'level' => 700,
                'is_system' => true,
                'color' => '#059669',
                'icon' => 'shield',
            ],
            [
                'slug' => 'client',
                'name' => 'Client',
                'description' => 'Client area user - can access Client panel',
                'level' => 100,
                'is_default' => true,
                'is_system' => true,
                'color' => '#6B7280',
                'icon' => 'user',
            ],
            
            // Content roles
            [
                'slug' => Role::ROLE_MODERATOR,
                'name' => 'Moderator',
                'description' => 'Content moderation access',
                'level' => 500,
                'is_system' => true,
                'color' => '#F59E0B',
                'icon' => 'eye',
            ],
            [
                'slug' => Role::ROLE_EDITOR,
                'name' => 'Editor',
                'description' => 'Content editing access',
                'level' => 400,
                'is_system' => true,
                'color' => '#8B5CF6',
                'icon' => 'edit',
            ],
            [
                'slug' => Role::ROLE_AUTHOR,
                'name' => 'Author',
                'description' => 'Content creation access',
                'level' => 300,
                'is_system' => true,
                'color' => '#EC4899',
                'icon' => 'fileText',
            ],
            [
                'slug' => Role::ROLE_SUBSCRIBER,
                'name' => 'Subscriber',
                'description' => 'Basic subscriber access',
                'level' => 200,
                'is_system' => true,
                'color' => '#64748B',
                'icon' => 'userCheck',
            ],
        ];

        foreach ($roles as $data) {
            Role::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('Created ' . count($roles) . ' roles.');
    }

    /**
     * Assign permissions to roles.
     */
    protected function assignPermissions(): void
    {
        // Super Admin gets all permissions (handled by isSuperAdmin() check in trait)
        // No need to explicitly assign

        // Admin permissions
        $admin = Role::findBySlug(Role::ROLE_ADMIN);
        if ($admin) {
            $adminPermissions = Permission::whereNotIn('slug', [
                'system.impersonate',
                'plugins.delete',
            ])->pluck('id')->toArray();
            $admin->permissions()->sync($adminPermissions);
        }

        // Moderator permissions
        $moderator = Role::findBySlug(Role::ROLE_MODERATOR);
        if ($moderator) {
            $modPermissions = Permission::whereIn('group', ['entities', 'audit'])
                ->whereNotIn('slug', ['entities.force_delete', 'entities.bulk_delete'])
                ->pluck('id')->toArray();
            $moderator->permissions()->sync($modPermissions);
        }

        // Editor permissions
        $editor = Role::findBySlug(Role::ROLE_EDITOR);
        if ($editor) {
            $editorPermissions = Permission::whereIn('slug', [
                'entities.view',
                'entities.create',
                'entities.update',
                'api.access',
            ])->pluck('id')->toArray();
            $editor->permissions()->sync($editorPermissions);
        }

        // Author permissions
        $author = Role::findBySlug(Role::ROLE_AUTHOR);
        if ($author) {
            $authorPermissions = Permission::whereIn('slug', [
                'entities.view',
                'entities.create',
                'api.access',
            ])->pluck('id')->toArray();
            $author->permissions()->sync($authorPermissions);
        }

        // Subscriber permissions
        $subscriber = Role::findBySlug(Role::ROLE_SUBSCRIBER);
        if ($subscriber) {
            $subscriberPermissions = Permission::whereIn('slug', [
                'entities.view',
                'api.access',
            ])->pluck('id')->toArray();
            $subscriber->permissions()->sync($subscriberPermissions);
        }

        $this->command->info('Assigned permissions to roles.');
    }
}
