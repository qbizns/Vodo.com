<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('pass');

        // Console User (SaaS Super Admin)
        DB::table('console_users')->insertOrIgnore([
            'name' => 'Console Admin',
            'email' => 'admin@console.vodo.com',
            'password' => $password,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Owner (Business Owner)
        DB::table('owners')->insertOrIgnore([
            'name' => 'Test Owner',
            'email' => 'admin@owner.vodo.com',
            'password' => $password,
            'company_name' => 'Test Company',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Admin
        DB::table('admins')->insertOrIgnore([
            'name' => 'Test Admin',
            'email' => 'admin@admin.vodo.com',
            'password' => $password,
            'owner_id' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Client
        DB::table('clients')->insertOrIgnore([
            'name' => 'Test Client',
            'email' => 'admin@client.vodo.com',
            'password' => $password,
            'phone' => '+1234567890',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

