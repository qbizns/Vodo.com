<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Main Database Seeder
 * 
 * IMPORTANT: This seeder MUST be run after every fresh migration!
 * Run: ./art.sh db:seed
 * Or:  ./art.sh migrate:fresh --seed
 * 
 * Seeds the database with essential data including:
 * - Default permissions and permission groups (REQUIRED)
 * - Super admin users for all panels (REQUIRED)
 * - Plugins from app/Plugins directory (REQUIRED)
 * - View types documentation (REQUIRED)
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║           VODO DATABASE SEEDER                           ║');
        $this->command->info('║  Seeding essential data for system operation...          ║');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');
        $this->command->info('');

        // 1. Create default permissions and roles (REQUIRED)
        $this->command->info('Step 1/5: Creating permissions...');
        $this->call(PermissionsSeeder::class);

        // 2. Create permission groups and bind ALL permissions to groups (REQUIRED)
        // This ensures no permission has null group_id
        $this->command->info('Step 2/5: Creating permission groups and binding...');
        $this->call(PermissionGroupsBindingSeeder::class);

        // 3. Create super admin users for all panels (REQUIRED)
        $this->command->info('Step 3/5: Creating super admin users...');
        $this->call(SuperAdminSeeder::class);

        // 4. Scan and register plugins from app/Plugins directory (REQUIRED)
        $this->command->info('Step 4/5: Registering plugins...');
        $this->call(PluginsSeeder::class);

        // 5. Register view types and sample views (REQUIRED)
        $this->command->info('Step 5/5: Registering view types...');
        $this->call(ViewTypesSeeder::class);

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════════════╗');
        $this->command->info('║  ✓ Database seeding completed successfully!              ║');
        $this->command->info('╚══════════════════════════════════════════════════════════╝');
        $this->command->info('');
    }
}
