<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('label', 100);
            $table->string('category', 50)->default('custom');
            $table->text('description')->nullable();
            $table->string('handler_class', 255);
            $table->json('config_schema')->nullable();
            $table->json('default_config')->nullable();
            $table->string('form_component', 100)->nullable();
            $table->string('list_component', 100)->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_sortable')->default(false);
            $table->boolean('supports_default')->default(true);
            $table->boolean('supports_unique')->default(false);
            $table->boolean('supports_multiple')->default(false);
            $table->string('storage_type', 20)->default('string');
            $table->boolean('requires_serialization')->default(false);
            $table->string('plugin_slug', 100)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('category');
            $table->index('plugin_slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_types');
    }
};
