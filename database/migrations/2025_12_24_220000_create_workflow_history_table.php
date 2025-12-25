<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create workflow history table for tracking state transitions.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists (created in platform_tables migration)
        if (Schema::hasTable('workflow_history')) {
            return;
        }

        Schema::create('workflow_history', function (Blueprint $table) {
            $table->id();
            $table->string('workflow', 100)->index();
            $table->string('transition', 100);
            $table->string('model_type', 255);
            $table->unsignedBigInteger('model_id');
            $table->string('from_state', 50);
            $table->string('to_state', 50);
            $table->json('context')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['workflow', 'transition']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_history');
    }
};
