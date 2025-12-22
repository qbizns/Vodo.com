<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds new tables and columns for the Permissions & Access Control module:
     * - permission_groups: UI grouping for permissions
     * - permission_audit: Audit logging for permission changes
     * - access_rules: Conditional access rules (ABAC)
     * - Updates to existing roles and permissions tables
     */
    public function up(): void
    {
        // Permission Groups table for UI organization
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('plugin_slug', 100)->nullable();
            $table->string('icon', 50)->default('folder');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('plugin_slug');
            $table->index('position');
            $table->index('is_active');
        });

        // Access Rules table for conditional access (ABAC)
        Schema::create('access_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->json('permissions'); // Array of permission slugs this rule applies to
            $table->json('conditions'); // Array of condition objects
            $table->enum('action', ['deny', 'log'])->default('deny');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('retention_days')->default(90);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('priority');
            $table->index('is_active');
        });

        // Permission Audit table for logging
        Schema::create('permission_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50); // role_created, role_updated, permissions_synced, etc.
            $table->string('target_type', 50); // role, permission, user, access_rule
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name', 255)->nullable();
            $table->json('changes')->nullable(); // Before/after values
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action');
            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
            $table->index(['user_id', 'action', 'created_at']);
        });

        // Update roles table with new columns
        Schema::table('roles', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('roles', 'color')) {
                $table->string('color', 7)->default('#6B7280')->after('description');
            }
            if (!Schema::hasColumn('roles', 'icon')) {
                $table->string('icon', 50)->default('shield')->after('color');
            }
            if (!Schema::hasColumn('roles', 'position')) {
                $table->unsignedInteger('position')->default(0)->after('icon');
            }
            if (!Schema::hasColumn('roles', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('plugin_slug');
                $table->index('tenant_id');
            }
            if (!Schema::hasColumn('roles', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Update permissions table with new columns
        Schema::table('permissions', function (Blueprint $table) {
            // Add group_id FK if it doesn't exist
            if (!Schema::hasColumn('permissions', 'group_id')) {
                $table->foreignId('group_id')->nullable()->after('id')
                    ->constrained('permission_groups')->nullOnDelete();
            }
            // Add label column if it doesn't exist
            if (!Schema::hasColumn('permissions', 'label')) {
                $table->string('label', 255)->nullable()->after('name');
            }
            // Add is_dangerous flag
            if (!Schema::hasColumn('permissions', 'is_dangerous')) {
                $table->boolean('is_dangerous')->default(false)->after('is_active');
            }
        });

        // Update role_permissions table with audit columns
        Schema::table('role_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('role_permissions', 'granted_at')) {
                $table->timestamp('granted_at')->nullable()->after('granted');
            }
            if (!Schema::hasColumn('role_permissions', 'granted_by')) {
                $table->foreignId('granted_by')->nullable()->after('granted_at')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // Update user_permissions table with audit columns
        Schema::table('user_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('user_permissions', 'granted_by')) {
                $table->foreignId('granted_by')->nullable()->after('granted')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('user_permissions', 'reason')) {
                $table->text('reason')->nullable()->after('granted_by');
            }
        });

        // Update user_roles table with audit columns
        Schema::table('user_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_roles', 'assigned_by')) {
                $table->foreignId('assigned_by')->nullable()->after('role_id')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('user_roles', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove columns from user_roles
        Schema::table('user_roles', function (Blueprint $table) {
            if (Schema::hasColumn('user_roles', 'assigned_by')) {
                $table->dropForeign(['assigned_by']);
                $table->dropColumn('assigned_by');
            }
            if (Schema::hasColumn('user_roles', 'assigned_at')) {
                $table->dropColumn('assigned_at');
            }
        });

        // Remove columns from user_permissions
        Schema::table('user_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('user_permissions', 'granted_by')) {
                $table->dropForeign(['granted_by']);
                $table->dropColumn('granted_by');
            }
            if (Schema::hasColumn('user_permissions', 'reason')) {
                $table->dropColumn('reason');
            }
        });

        // Remove columns from role_permissions
        Schema::table('role_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('role_permissions', 'granted_by')) {
                $table->dropForeign(['granted_by']);
                $table->dropColumn('granted_by');
            }
            if (Schema::hasColumn('role_permissions', 'granted_at')) {
                $table->dropColumn('granted_at');
            }
        });

        // Remove columns from permissions
        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'group_id')) {
                $table->dropForeign(['group_id']);
                $table->dropColumn('group_id');
            }
            if (Schema::hasColumn('permissions', 'label')) {
                $table->dropColumn('label');
            }
            if (Schema::hasColumn('permissions', 'is_dangerous')) {
                $table->dropColumn('is_dangerous');
            }
        });

        // Remove columns from roles
        Schema::table('roles', function (Blueprint $table) {
            $columns = ['color', 'icon', 'position', 'tenant_id', 'deleted_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('roles', $column)) {
                    if ($column === 'tenant_id') {
                        $table->dropIndex(['tenant_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });

        // Drop new tables
        Schema::dropIfExists('permission_audit');
        Schema::dropIfExists('access_rules');
        Schema::dropIfExists('permission_groups');
    }
};
