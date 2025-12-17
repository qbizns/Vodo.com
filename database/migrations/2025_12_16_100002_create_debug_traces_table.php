<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create debug traces table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('debug_traces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            
            // Trace identification
            $table->uuid('trace_id')->index();
            $table->uuid('parent_trace_id')->nullable()->index();
            $table->uuid('request_id')->nullable()->index();
            
            // Trace info
            $table->string('type', 50); // request, hook, workflow, computed_field, etc.
            $table->string('name', 500);
            
            // Entity context
            $table->string('entity_type', 200)->nullable();
            $table->string('entity_id', 50)->nullable();
            
            // User context
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Data
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->json('context')->nullable();
            
            // Performance
            $table->decimal('duration_ms', 10, 3)->nullable();
            $table->unsignedBigInteger('memory_bytes')->nullable();
            
            // Status
            $table->string('status', 20)->default('running'); // running, success, error
            $table->text('error')->nullable();
            
            // Timestamps
            $table->timestamp('started_at', 6)->nullable();
            $table->timestamp('ended_at', 6)->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('type');
            $table->index('status');
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
            $table->index(['tenant_id', 'created_at']);
        });

        // Add index for cleanup (traces older than X days)
        // Traces should be pruned regularly
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debug_traces');
    }
};
