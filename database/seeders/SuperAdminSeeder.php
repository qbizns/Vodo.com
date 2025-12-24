<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds super admin and demo users for the platform.
 * 
 * Creates users with appropriate roles to access different panels:
 * - Super Admin: Full access to all panels
 * - Console Admin: SaaS platform management (Console panel)
 * - Owner: Business owner (Owner + Admin panels)
 * - Admin: Backend administrator (Admin panel)
 * - Client: Client area user (Client panel)
 * 
 * Default password for all users: password
 * CHANGE PASSWORDS IMMEDIATELY IN PRODUCTION!
 */
class SuperAdminSeeder extends Seeder
{
    /**
     * Default password for all users
     */
    protected string $defaultPassword = 'password';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make($this->defaultPassword);

        // Create Super Admin (can access ALL panels: Console, Owner, Admin, Client)
        $this->createSuperAdmin($password);

        // Create demo users for each panel role
        $this->createConsoleAdmin($password);
        $this->createOwner($password);
        $this->createAdmin($password);
        $this->createClient($password);

        $this->command->newLine();
        $this->command->info('✓ Users created successfully!');
        $this->command->newLine();
        $this->command->warn('Default password for all users: ' . $this->defaultPassword);
        $this->command->warn('Please change passwords immediately in production!');
    }

    /**
     * Create Super Admin - has access to ALL panels
     */
    protected function createSuperAdmin(string $password): void
    {
        $superAdminRole = Role::where('slug', Role::ROLE_SUPER_ADMIN)->first();

        $user = User::updateOrCreate(
            ['email' => 'super@vodo.com'],
            [
                'name' => 'Super Admin',
                'password' => $password,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        if ($superAdminRole) {
            $user->syncRoles([Role::ROLE_SUPER_ADMIN]);
        }

        $this->command->info('Super Admin: super@vodo.com (access: ALL panels)');
    }

    /**
     * Create Console Admin - can access Console, Owner, Admin, Client
     */
    protected function createConsoleAdmin(string $password): void
    {
        $consoleRole = Role::where('slug', 'console_admin')->first();

        $user = User::updateOrCreate(
            ['email' => 'console@vodo.com'],
            [
                'name' => 'Console Admin',
                'password' => $password,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        if ($consoleRole) {
            $user->syncRoles(['console_admin']);
        }

        $this->command->info('Console Admin: console@vodo.com (access: Console, Owner, Admin, Client)');
    }

    /**
     * Create Owner - can access Owner, Admin, Client
     */
    protected function createOwner(string $password): void
    {
        $ownerRole = Role::where('slug', 'owner')->first();

        $user = User::updateOrCreate(
            ['email' => 'owner@vodo.com'],
            [
                'name' => 'Owner',
                'password' => $password,
                'company_name' => 'Demo Company',
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        if ($ownerRole) {
            $user->syncRoles(['owner']);
        }

        $this->command->info('Owner: owner@vodo.com (access: Owner, Admin, Client)');
    }

    /**
     * Create Admin - can access Admin, Client
     */
    protected function createAdmin(string $password): void
    {
        $adminRole = Role::where('slug', Role::ROLE_ADMIN)->first();

        $user = User::updateOrCreate(
            ['email' => 'admin@vodo.com'],
            [
                'name' => 'Admin',
                'password' => $password,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        if ($adminRole) {
            $user->syncRoles([Role::ROLE_ADMIN]);
        }

        $this->command->info('Admin: admin@vodo.com (access: Admin, Client)');
    }

    /**
     * Create Client - can access Client only
     */
    protected function createClient(string $password): void
    {
        $clientRole = Role::where('slug', 'client')->first();

        $user = User::updateOrCreate(
            ['email' => 'client@vodo.com'],
            [
                'name' => 'Client',
                'password' => $password,
                'phone' => '+1234567890',
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]
        );

        if ($clientRole) {
            $user->syncRoles(['client']);
        }

        $this->command->info('Client: client@vodo.com (access: Client only)');
    }
}

