<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds permission groups and binds all permissions to their groups.
 * 
 * This seeder ensures no permission has an empty group_id by:
 * 1. Creating permission groups based on unique 'group' values in permissions table
 * 2. Updating all permissions to link to their respective group
 */
class PermissionGroupsBindingSeeder extends Seeder
{
    /**
     * Permission groups with metadata
     */
    protected array $groupDefinitions = [
        'entities' => [
            'name' => 'Entities',
            'description' => 'Permissions for managing entities and records',
            'icon' => 'database',
            'position' => 10,
        ],
        'users' => [
            'name' => 'Users',
            'description' => 'Permissions for user management',
            'icon' => 'users',
            'position' => 20,
        ],
        'roles' => [
            'name' => 'Roles',
            'description' => 'Permissions for role management',
            'icon' => 'shield',
            'position' => 30,
        ],
        'permissions' => [
            'name' => 'Permissions',
            'description' => 'Permissions for permission management',
            'icon' => 'key',
            'position' => 40,
        ],
        'plugins' => [
            'name' => 'Plugins',
            'description' => 'Permissions for plugin management',
            'icon' => 'puzzlePiece',
            'position' => 50,
        ],
        'settings' => [
            'name' => 'Settings',
            'description' => 'Permissions for system settings',
            'icon' => 'settings',
            'position' => 60,
        ],
        'api' => [
            'name' => 'API',
            'description' => 'Permissions for API access and management',
            'icon' => 'code',
            'position' => 70,
        ],
        'scheduler' => [
            'name' => 'Scheduler',
            'description' => 'Permissions for scheduled tasks',
            'icon' => 'clock',
            'position' => 80,
        ],
        'workflows' => [
            'name' => 'Workflows',
            'description' => 'Permissions for workflow management',
            'icon' => 'gitBranch',
            'position' => 90,
        ],
        'audit' => [
            'name' => 'Audit',
            'description' => 'Permissions for audit logs',
            'icon' => 'fileText',
            'position' => 100,
        ],
        'system' => [
            'name' => 'System',
            'description' => 'System-level permissions',
            'icon' => 'server',
            'position' => 110,
        ],
        'general' => [
            'name' => 'General',
            'description' => 'General permissions',
            'icon' => 'folder',
            'position' => 1000,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating permission groups and binding permissions...');

        // Get all unique groups from permissions table
        $existingGroups = Permission::distinct()->pluck('group')->filter()->toArray();

        // Create permission groups
        $this->createPermissionGroups($existingGroups);

        // Bind all permissions to their groups
        $this->bindPermissionsToGroups();

        // Show summary
        $this->showSummary();
    }

    /**
     * Create permission groups based on existing permission groups and definitions.
     */
    protected function createPermissionGroups(array $existingGroups): void
    {
        $created = 0;
        $updated = 0;

        // Merge existing groups with defined groups
        $allGroups = array_unique(array_merge($existingGroups, array_keys($this->groupDefinitions)));

        foreach ($allGroups as $groupSlug) {
            $definition = $this->groupDefinitions[$groupSlug] ?? [
                'name' => ucwords(str_replace(['_', '-'], ' ', $groupSlug)),
                'description' => "Permissions for {$groupSlug}",
                'icon' => 'folder',
                'position' => 500,
            ];

            $group = PermissionGroup::updateOrCreate(
                ['slug' => $groupSlug],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'icon' => $definition['icon'],
                    'position' => $definition['position'],
                    'is_active' => true,
                ]
            );

            if ($group->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->command->info("Permission groups: {$created} created, {$updated} updated.");
    }

    /**
     * Bind all permissions to their respective groups.
     */
    protected function bindPermissionsToGroups(): void
    {
        // Get all permission groups indexed by slug
        $groups = PermissionGroup::pluck('id', 'slug')->toArray();

        // Get permissions without group_id or with null group_id
        $permissionsWithoutGroup = Permission::whereNull('group_id')->count();

        if ($permissionsWithoutGroup === 0) {
            $this->command->info('All permissions already have group_id assigned.');
            return;
        }

        $updated = 0;

        // Update permissions by group
        foreach ($groups as $groupSlug => $groupId) {
            $count = Permission::where('group', $groupSlug)
                ->whereNull('group_id')
                ->update(['group_id' => $groupId]);

            $updated += $count;

            if ($count > 0) {
                $this->command->line("  - Bound {$count} permissions to group '{$groupSlug}'");
            }
        }

        // Handle any remaining permissions without a valid group
        $remaining = Permission::whereNull('group_id')->get();

        if ($remaining->isNotEmpty()) {
            // Get or create a 'general' group for orphaned permissions
            $generalGroup = PermissionGroup::firstOrCreate(
                ['slug' => 'general'],
                [
                    'name' => 'General',
                    'description' => 'General permissions',
                    'icon' => 'folder',
                    'position' => 1000,
                    'is_active' => true,
                ]
            );

            foreach ($remaining as $permission) {
                $permission->update(['group_id' => $generalGroup->id]);
                $updated++;
                $this->command->line("  - Bound orphan permission '{$permission->slug}' to 'general' group");
            }
        }

        $this->command->info("Updated {$updated} permissions with group_id.");
    }

    /**
     * Show summary of permission bindings.
     */
    protected function showSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== Permission Groups Binding Summary ===');

        $groups = PermissionGroup::active()
            ->withCount('permissions')
            ->orderBy('position')
            ->get();

        foreach ($groups as $group) {
            $this->command->line("  {$group->name} ({$group->slug}): {$group->permissions_count} permissions");
        }

        // Check for any remaining unbound permissions
        $unbound = Permission::whereNull('group_id')->count();
        
        if ($unbound > 0) {
            $this->command->error("WARNING: {$unbound} permissions still have no group_id!");
        } else {
            $this->command->info('✓ All permissions are bound to groups.');
        }

        $this->command->newLine();
    }
}

