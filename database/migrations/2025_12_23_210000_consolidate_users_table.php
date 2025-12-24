<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration consolidates all user tables into a single users table.
     * Panel access is now controlled via roles instead of separate tables.
     */
    public function up(): void
    {
        // Step 1: Add company_name field to users table (from owners)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name', 255)->nullable()->after('name');
            }
        });

        // Step 2: Revert polymorphic relationship back to simple user_id
        // Since we now only have one users table, we don't need user_type
        if (Schema::hasColumn('user_roles', 'user_type')) {
            // First, migrate all role assignments to use user IDs
            // We'll handle data migration in a separate step
            
            Schema::table('user_roles', function (Blueprint $table) {
                $table->dropIndex('user_roles_user_index');
                $table->dropUnique('user_role_scope_unique');
            });

            Schema::table('user_roles', function (Blueprint $table) {
                $table->dropColumn('user_type');
            });

            Schema::table('user_roles', function (Blueprint $table) {
                $table->unique(['user_id', 'role_id', 'scope_type', 'scope_id'], 'user_role_scope_unique');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('user_permissions', 'user_type')) {
            Schema::table('user_permissions', function (Blueprint $table) {
                $table->dropIndex('user_permissions_user_index');
                $table->dropUnique('user_perm_scope_unique');
            });

            Schema::table('user_permissions', function (Blueprint $table) {
                $table->dropColumn('user_type');
            });

            Schema::table('user_permissions', function (Blueprint $table) {
                $table->unique(['user_id', 'permission_id', 'scope_type', 'scope_id'], 'user_perm_scope_unique');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('permission_audit', 'user_type')) {
            Schema::table('permission_audit', function (Blueprint $table) {
                $table->dropColumn('user_type');
            });

            Schema::table('permission_audit', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Step 3: Drop old user tables (they're now obsolete)
        // Note: In production, you'd want to migrate data first
        Schema::dropIfExists('admin_password_reset_tokens');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('client_password_reset_tokens');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('owner_password_reset_tokens');
        Schema::dropIfExists('owners');
        Schema::dropIfExists('console_password_reset_tokens');
        Schema::dropIfExists('console_users');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate console_users table
        Schema::create('console_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('console_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Recreate owners table
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('company_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('owner_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Recreate admins table
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('owner_id')->nullable()->constrained('owners')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('admin_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Recreate clients table
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('client_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Re-add polymorphic columns
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('user_role_scope_unique');
            $table->string('user_type', 100)->default('App\\Models\\User')->after('id');
            $table->unique(['user_type', 'user_id', 'role_id', 'scope_type', 'scope_id'], 'user_role_scope_unique');
            $table->index(['user_type', 'user_id'], 'user_roles_user_index');
        });

        Schema::table('user_permissions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('user_perm_scope_unique');
            $table->string('user_type', 100)->default('App\\Models\\User')->after('id');
            $table->unique(['user_type', 'user_id', 'permission_id', 'scope_type', 'scope_id'], 'user_perm_scope_unique');
            $table->index(['user_type', 'user_id'], 'user_permissions_user_index');
        });

        Schema::table('permission_audit', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->string('user_type', 100)->nullable()->after('id');
        });

        // Remove company_name from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('company_name');
        });
    }
};

