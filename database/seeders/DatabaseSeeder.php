<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main Database Seeder
 * 
 * Seeds the database with initial data including:
 * - Default roles and permissions
 * - Super admin users for all panels
 * - Test users (optional)
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create default permissions and roles
        $this->call(PermissionsSeeder::class);

        // 2. Create permission groups and bind permissions to groups
        $this->call(PermissionGroupsBindingSeeder::class);

        // 3. Create super admin users for all panels
        $this->call(SuperAdminSeeder::class);

        // 4. Seed plugins table
        $this->call(PluginsSeeder::class);

        // 5. Optionally create test users (uncomment for development)
        // $this->call(TestUsersSeeder::class);
    }
}
