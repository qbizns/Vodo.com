<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compiled_views', function (Blueprint $table) {
            $table->id();
            $table->string('view_name', 100)->unique();
            
            // Compiled content
            $table->longText('compiled_content');
            
            // Hash of all inputs (base view + extensions) for cache invalidation
            $table->string('content_hash', 64);
            
            // Extension tracking - which extensions were applied
            $table->json('applied_extensions')->nullable(); // Array of extension IDs
            
            // Compilation metadata
            $table->timestamp('compiled_at');
            $table->unsignedInteger('compilation_time_ms')->nullable(); // Performance tracking
            
            // For debugging
            $table->json('compilation_log')->nullable(); // Detailed log of what was applied
            
            $table->timestamps();
            
            // Index for lookups
            $table->index('content_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compiled_views');
    }
};
