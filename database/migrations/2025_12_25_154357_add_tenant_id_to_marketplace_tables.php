<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds tenant_id to installed_plugins table to enable multi-tenant
     * plugin installations. Each tenant can have their own installed plugins
     * with separate licenses and updates.
     */
    public function up(): void
    {
        // Add tenant_id to installed_plugins
        Schema::table('installed_plugins', function (Blueprint $table) {
            // Add tenant_id column after id
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            
            // Add index for tenant queries
            $table->index('tenant_id');
        });

        // Update unique constraint: slug should be unique per tenant, not globally
        // First drop the existing unique constraint on slug
        Schema::table('installed_plugins', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        // Add composite unique constraint (tenant_id + slug)
        Schema::table('installed_plugins', function (Blueprint $table) {
            $table->unique(['tenant_id', 'slug'], 'installed_plugins_tenant_slug_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove composite unique constraint
        Schema::table('installed_plugins', function (Blueprint $table) {
            $table->dropUnique('installed_plugins_tenant_slug_unique');
        });

        // Restore original unique constraint on slug
        Schema::table('installed_plugins', function (Blueprint $table) {
            $table->unique('slug');
        });

        // Remove tenant_id column and index
        Schema::table('installed_plugins', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};

