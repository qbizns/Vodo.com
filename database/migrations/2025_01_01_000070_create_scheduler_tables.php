<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Scheduled tasks definition
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            
            // Task type and handler
            $table->string('type', 30)->default('callback'); // callback, job, command, closure
            $table->string('handler', 255); // Class@method, job class, or artisan command
            $table->json('parameters')->nullable(); // Parameters to pass
            
            // Schedule configuration
            $table->string('expression', 100); // Cron expression: "* * * * *"
            $table->string('timezone', 50)->default('UTC');
            
            // Execution control
            $table->boolean('without_overlapping')->default(false);
            $table->integer('expires_after')->nullable(); // Minutes before lock expires
            $table->boolean('run_in_background')->default(false);
            $table->boolean('run_on_one_server')->default(false);
            $table->boolean('even_in_maintenance')->default(false);
            
            // Retry configuration
            $table->integer('max_attempts')->default(1);
            $table->integer('retry_delay')->default(60); // Seconds between retries
            
            // Output handling
            $table->string('output_file', 255)->nullable();
            $table->boolean('append_output')->default(false);
            $table->string('email_output', 255)->nullable();
            $table->boolean('email_on_failure')->default(false);
            
            // Hooks
            $table->string('before_callback', 255)->nullable();
            $table->string('after_callback', 255)->nullable();
            $table->string('success_callback', 255)->nullable();
            $table->string('failure_callback', 255)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100);
            
            // Ownership
            $table->string('plugin_slug', 100)->nullable();
            $table->boolean('is_system')->default(false);
            
            // Timestamps
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index('plugin_slug');
            $table->index(['is_active', 'next_run_at']);
        });

        // Task execution logs
        Schema::create('task_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_task_id')->constrained('scheduled_tasks')->cascadeOnDelete();
            
            // Execution details
            $table->string('status', 20)->default('pending'); // pending, running, completed, failed, skipped
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_ms')->nullable(); // Execution time in milliseconds
            
            // Output
            $table->longText('output')->nullable();
            $table->longText('error')->nullable();
            $table->integer('exit_code')->nullable();
            
            // Retry info
            $table->integer('attempt')->default(1);
            
            // Memory/performance
            $table->integer('memory_usage')->nullable(); // Peak memory in bytes
            
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['scheduled_task_id', 'status']);
            $table->index('started_at');
        });

        // Event subscriptions (pub/sub pattern)
        Schema::create('event_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('event', 150); // Event name/class
            $table->string('listener', 255); // Class@method or callback
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('run_async')->default(false);
            $table->string('queue', 50)->nullable(); // Queue name for async
            $table->string('plugin_slug', 100)->nullable();
            $table->json('conditions')->nullable(); // Conditional execution
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index('event');
            $table->index('plugin_slug');
        });

        // Recurring jobs (simpler than cron - interval based)
        Schema::create('recurring_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 150);
            $table->string('handler', 255);
            $table->json('parameters')->nullable();
            
            // Interval configuration
            $table->string('interval_type', 20); // seconds, minutes, hours, days, weeks
            $table->integer('interval_value')->default(1);
            
            // Execution window
            $table->time('run_after')->nullable(); // Only run after this time
            $table->time('run_before')->nullable(); // Only run before this time
            $table->json('run_on_days')->nullable(); // [1,2,3,4,5] for weekdays
            
            $table->boolean('is_active')->default(true);
            $table->string('plugin_slug', 100)->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index('plugin_slug');
            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_jobs');
        Schema::dropIfExists('event_subscriptions');
        Schema::dropIfExists('task_logs');
        Schema::dropIfExists('scheduled_tasks');
    }
};
