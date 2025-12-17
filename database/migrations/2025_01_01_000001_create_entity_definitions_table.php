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
        Schema::create('entity_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Machine name (e.g., product, event)');
            $table->string('slug')->unique()->comment('URL-friendly identifier');
            $table->string('table_name')->comment('Database table for this entity');
            $table->json('labels')->comment('UI labels (singular, plural, add_new, etc.)');
            $table->json('config')->nullable()->comment('Additional configuration');
            $table->json('supports')->nullable()->comment('Supported features (title, content, thumbnail, etc.)');
            $table->string('icon')->default('box')->comment('Icon identifier');
            $table->integer('menu_position')->default(100);
            $table->boolean('is_public')->default(true)->comment('Whether entity is publicly accessible');
            $table->boolean('has_archive')->default(true)->comment('Whether entity has archive/listing page');
            $table->boolean('show_in_menu')->default(true)->comment('Show in admin menu');
            $table->boolean('show_in_rest')->default(true)->comment('Expose via REST API');
            $table->boolean('is_hierarchical')->default(false)->comment('Support parent-child relationships');
            $table->boolean('is_system')->default(false)->comment('System entity (cannot be deleted)');
            $table->boolean('is_active')->default(true);
            $table->string('plugin_slug')->nullable()->comment('Plugin that registered this entity');
            $table->timestamps();

            $table->index('plugin_slug');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_definitions');
    }
};
