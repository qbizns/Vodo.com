<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('view_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->string('type', 50)->default('blade'); // blade, component, html
            $table->string('category', 100)->nullable(); // admin, frontend, email, etc.
            $table->text('description')->nullable();
            
            // View content - the actual template
            $table->longText('content');
            
            // View inheritance
            $table->string('inherit_id', 100)->nullable(); // Parent view name
            $table->unsignedInteger('priority')->default(100);
            
            // Configuration
            $table->json('config')->nullable(); // Additional config (variables, slots, etc.)
            $table->json('slots')->nullable(); // Named slots for component-like behavior
            
            // Ownership and tracking
            $table->string('plugin_slug', 100)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_cacheable')->default(true);
            
            // Metadata
            $table->string('version', 20)->default('1.0.0');
            $table->timestamps();
            
            // Indexes
            $table->index('type');
            $table->index('category');
            $table->index('plugin_slug');
            $table->index('inherit_id');
            $table->index(['is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('view_definitions');
    }
};
