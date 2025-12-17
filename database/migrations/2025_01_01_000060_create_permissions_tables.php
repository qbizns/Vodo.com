<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique(); // e.g., 'posts.create', 'users.delete'
            $table->string('name', 100); // Human-readable name
            $table->text('description')->nullable();
            
            // Grouping
            $table->string('group', 50)->default('general'); // Group for UI organization
            $table->string('category', 50)->nullable(); // Sub-category
            
            // Ownership
            $table->string('plugin_slug', 100)->nullable();
            $table->boolean('is_system')->default(false);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100);
            
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index('group');
            $table->index('plugin_slug');
        });

        // Roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique(); // e.g., 'admin', 'editor', 'subscriber'
            $table->string('name', 100);
            $table->text('description')->nullable();
            
            // Hierarchy
            $table->integer('level')->default(0); // Higher = more powerful
            $table->foreignId('parent_id')->nullable()->constrained('roles')->nullOnDelete();
            
            // Settings
            $table->boolean('is_default')->default(false); // Assigned to new users
            $table->boolean('is_system')->default(false); // Cannot be deleted
            $table->boolean('is_active')->default(true);
            
            // Ownership
            $table->string('plugin_slug', 100)->nullable();
            
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index('level');
            $table->index('plugin_slug');
        });

        // Role has permissions (many-to-many)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            
            // Grant or deny
            $table->boolean('granted')->default(true);
            
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id']);
        });

        // User has roles (many-to-many)
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            
            // Optional: scope roles to specific resources
            $table->string('scope_type', 100)->nullable(); // e.g., 'App\Models\Team'
            $table->unsignedBigInteger('scope_id')->nullable();
            
            // Temporary roles
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'role_id', 'scope_type', 'scope_id'], 'user_role_scope_unique');
            $table->index(['scope_type', 'scope_id']);
        });

        // Direct user permissions (override role permissions)
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            
            // Grant or deny (can override role)
            $table->boolean('granted')->default(true);
            
            // Optional scope
            $table->string('scope_type', 100)->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();
            
            // Temporary permissions
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'permission_id', 'scope_type', 'scope_id'], 'user_perm_scope_unique');
        });

        // Permission dependencies (requires other permissions)
        Schema::create('permission_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('requires_permission_id')->constrained('permissions')->cascadeOnDelete();
            
            $table->unique(['permission_id', 'requires_permission_id'], 'perm_dep_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_dependencies');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
