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
     * This migration converts the user_roles and user_permissions tables
     * to use polymorphic relationships, allowing admins, owners, console_users,
     * and clients to have their own permissions.
     */
    public function up(): void
    {
        // Update user_roles table to be polymorphic
        Schema::table('user_roles', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['user_id']);
            
            // Add user_type column for polymorphic relationship
            $table->string('user_type', 100)->default('App\\Models\\User')->after('id');
            
            // Update index - drop old unique and create new one
            $table->dropUnique('user_role_scope_unique');
            $table->unique(['user_type', 'user_id', 'role_id', 'scope_type', 'scope_id'], 'user_role_scope_unique');
            
            // Add index for polymorphic lookup
            $table->index(['user_type', 'user_id'], 'user_roles_user_index');
        });

        // Update user_permissions table to be polymorphic
        Schema::table('user_permissions', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['user_id']);
            
            // Add user_type column for polymorphic relationship
            $table->string('user_type', 100)->default('App\\Models\\User')->after('id');
            
            // Update index - drop old unique and create new one
            $table->dropUnique('user_perm_scope_unique');
            $table->unique(['user_type', 'user_id', 'permission_id', 'scope_type', 'scope_id'], 'user_perm_scope_unique');
            
            // Add index for polymorphic lookup
            $table->index(['user_type', 'user_id'], 'user_permissions_user_index');
        });

        // Update permission_audit table to be polymorphic if not already
        if (!Schema::hasColumn('permission_audit', 'user_type')) {
            Schema::table('permission_audit', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->string('user_type', 100)->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert permission_audit
        if (Schema::hasColumn('permission_audit', 'user_type')) {
            Schema::table('permission_audit', function (Blueprint $table) {
                $table->dropColumn('user_type');
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Revert user_permissions
        Schema::table('user_permissions', function (Blueprint $table) {
            $table->dropIndex('user_permissions_user_index');
            $table->dropUnique('user_perm_scope_unique');
            $table->dropColumn('user_type');
            $table->unique(['user_id', 'permission_id', 'scope_type', 'scope_id'], 'user_perm_scope_unique');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Revert user_roles
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropIndex('user_roles_user_index');
            $table->dropUnique('user_role_scope_unique');
            $table->dropColumn('user_type');
            $table->unique(['user_id', 'role_id', 'scope_type', 'scope_id'], 'user_role_scope_unique');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

