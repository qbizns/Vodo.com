<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortcodes', function (Blueprint $table) {
            $table->id();
            $table->string('tag', 50)->unique(); // Shortcode tag [tag]
            $table->string('name', 100); // Human-readable name
            $table->text('description')->nullable();
            
            // Handler configuration
            $table->string('handler_type', 20)->default('class'); // class, closure, view, callback
            $table->string('handler_class', 255)->nullable(); // Handler class
            $table->string('handler_method', 100)->nullable(); // Method name
            $table->string('handler_view', 255)->nullable(); // Blade view path
            
            // Attribute configuration
            $table->json('attributes')->nullable(); // Attribute definitions with defaults
            $table->json('required_attributes')->nullable(); // Required attribute names
            
            // Content handling
            $table->boolean('has_content')->default(false); // [tag]content[/tag] vs [tag /]
            $table->boolean('parse_nested')->default(true); // Parse shortcodes in content
            $table->string('content_type', 20)->default('text'); // text, html, markdown
            
            // Caching
            $table->boolean('is_cacheable')->default(true);
            $table->integer('cache_ttl')->nullable(); // Seconds, null = use default
            $table->json('cache_vary_by')->nullable(); // What to vary cache by
            
            // UI/Documentation
            $table->string('icon', 50)->nullable();
            $table->string('category', 50)->default('general');
            $table->json('example_usage')->nullable(); // Example shortcode strings
            $table->json('preview_data')->nullable(); // Data for visual preview
            
            // Ownership & Status
            $table->string('plugin_slug', 100)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Priority for same-tag resolution
            $table->integer('priority')->default(100);
            
            $table->json('meta')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('plugin_slug');
            $table->index('category');
            $table->index('is_active');
        });

        // Shortcode usage tracking (optional)
        Schema::create('shortcode_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shortcode_id')->constrained('shortcodes')->cascadeOnDelete();
            $table->string('content_type', 50); // post, page, widget, etc.
            $table->unsignedBigInteger('content_id');
            $table->string('field_name', 100)->nullable(); // Which field contains the shortcode
            $table->json('attributes_used')->nullable(); // What attributes were used
            $table->timestamp('created_at');
            
            $table->index(['content_type', 'content_id']);
            $table->index('shortcode_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortcode_usage');
        Schema::dropIfExists('shortcodes');
    }
};
