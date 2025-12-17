<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('view_extensions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Unique identifier for this extension
            $table->string('view_name', 100); // Target view to extend
            
            // XPath targeting
            $table->string('xpath', 500); // XPath expression to find target element
            
            // Operation type
            $table->enum('operation', [
                'before',      // Insert content before target
                'after',       // Insert content after target
                'replace',     // Replace target entirely
                'remove',      // Remove target element
                'inside_first', // Insert at beginning of target's children
                'inside_last',  // Insert at end of target's children (append)
                'wrap',        // Wrap target with new element
                'attributes',  // Modify target's attributes
            ])->default('after');
            
            // Content for the operation
            $table->longText('content')->nullable(); // New content (not needed for 'remove')
            
            // For 'attributes' operation - JSON of attribute changes
            // {"class": {"add": "new-class", "remove": "old-class"}, "data-id": "123"}
            $table->json('attribute_changes')->nullable();
            
            // Priority - lower numbers applied first
            $table->unsignedInteger('priority')->default(100);
            $table->unsignedInteger('sequence')->default(0); // Order within same priority
            
            // Conditional application
            $table->json('conditions')->nullable(); // Conditions for when to apply
            
            // Ownership
            $table->string('plugin_slug', 100)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('view_name');
            $table->index('plugin_slug');
            $table->index(['view_name', 'is_active', 'priority', 'sequence'], 'view_ext_apply_idx');
            $table->unique(['name', 'plugin_slug'], 'view_ext_unique_name');
            
            // Foreign key (soft - view might not exist yet)
            // $table->foreign('view_name')->references('name')->on('view_definitions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('view_extensions');
    }
};
