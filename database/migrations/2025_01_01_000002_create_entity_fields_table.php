<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entity_fields', function (Blueprint $table) {
            $table->id();
            $table->string('entity_name')->comment('Reference to entity_definitions.name');
            $table->string('name')->comment('Field name for display');
            $table->string('slug')->comment('Machine name (column identifier)');
            $table->string('type')->comment('Field type (string, text, integer, float, boolean, date, datetime, json, relation, select, etc.)');
            $table->json('config')->nullable()->comment('Field configuration (validation, options, defaults, etc.)');
            $table->text('description')->nullable()->comment('Help text for the field');
            $table->string('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_searchable')->default(false)->comment('Include in search queries');
            $table->boolean('is_filterable')->default(false)->comment('Allow filtering by this field');
            $table->boolean('is_sortable')->default(true)->comment('Allow sorting by this field');
            $table->boolean('show_in_list')->default(true)->comment('Show in list/table view');
            $table->boolean('show_in_form')->default(true)->comment('Show in create/edit form');
            $table->boolean('show_in_rest')->default(true)->comment('Include in REST API responses');
            $table->integer('list_order')->default(0)->comment('Order in list view');
            $table->integer('form_order')->default(0)->comment('Order in form view');
            $table->string('form_group')->nullable()->comment('Group/section in form');
            $table->string('form_width')->default('full')->comment('Width in form (full, half, third, quarter)');
            $table->string('plugin_slug')->nullable()->comment('Plugin that added this field');
            $table->boolean('is_system')->default(false)->comment('System field (cannot be deleted)');
            $table->timestamps();

            $table->unique(['entity_name', 'slug']);
            $table->index('entity_name');
            $table->index('plugin_slug');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_fields');
    }
};
