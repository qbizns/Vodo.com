<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enhance the ui_view_definitions table for the View Registry Pattern.
 *
 * Adds support for:
 * - View descriptions and icons
 * - Access control via groups
 * - Extended view type support (20 canonical types)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ui_view_definitions', function (Blueprint $table) {
            // Add description for view documentation
            $table->text('description')->nullable()->after('name');

            // Add icon for UI display
            $table->string('icon', 50)->nullable()->after('view_type');

            // Add access control groups (JSON array of group slugs)
            $table->json('access_groups')->nullable()->after('plugin_slug');

            // Add index for view type queries
            $table->index('view_type');
        });
    }

    public function down(): void
    {
        Schema::table('ui_view_definitions', function (Blueprint $table) {
            $table->dropIndex(['view_type']);
            $table->dropColumn(['description', 'icon', 'access_groups']);
        });
    }
};
