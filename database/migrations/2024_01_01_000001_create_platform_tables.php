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
        // Workflow Definitions
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('entity_name')->index();
            $table->text('description')->nullable();
            $table->string('initial_state');
            $table->json('states');
            $table->json('transitions');
            $table->json('config')->nullable();
            $table->string('plugin_slug')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['entity_name', 'is_active']);
        });

        // Workflow Instances
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflow_definitions')->onDelete('cascade');
            $table->morphs('workflowable');
            $table->string('current_state');
            $table->string('previous_state')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('transitioned_at')->nullable();
            $table->foreignId('transitioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workflow_id', 'workflowable_type', 'workflowable_id'], 'workflow_instances_unique');
            $table->index(['current_state']);
        });

        // Workflow History
        Schema::create('workflow_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('workflow_instances')->onDelete('cascade');
            $table->string('transition_id');
            $table->string('from_state');
            $table->string('to_state');
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger_type')->default('manual'); // manual, automatic, scheduled, api
            $table->json('condition_results')->nullable();
            $table->json('actions_executed')->nullable();
            $table->json('data_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['instance_id', 'created_at']);
        });

        // UI View Definitions
        Schema::create('ui_view_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('entity_name')->index();
            $table->string('view_type'); // form, list, kanban, search, calendar, graph, pivot
            $table->integer('priority')->default(16);
            $table->json('arch');
            $table->json('config')->nullable();
            $table->foreignId('inherit_id')->nullable()->constrained('ui_view_definitions')->nullOnDelete();
            $table->string('plugin_slug')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['entity_name', 'view_type', 'is_active']);
        });

        // Document Templates
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('entity_name')->nullable()->index();
            $table->string('document_type'); // invoice, quote, order, receipt, report, letter, label
            $table->string('format')->default('pdf'); // pdf, excel, html, email, word
            $table->longText('content');
            $table->text('header')->nullable();
            $table->text('footer')->nullable();
            $table->text('styles')->nullable();
            $table->json('variables')->nullable();
            $table->json('config')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('plugin_slug')->nullable()->index();
            $table->timestamps();

            $table->index(['entity_name', 'document_type', 'is_default']);
        });

        // Activity Types
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->integer('default_days')->default(1);
            $table->text('default_note')->nullable();
            $table->boolean('is_system')->default(false);
            $table->string('plugin_slug')->nullable()->index();
            $table->timestamps();
        });

        // Activities
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_type_id')->constrained()->onDelete('cascade');
            $table->string('subject');
            $table->text('note')->nullable();
            $table->date('due_date');
            $table->morphs('activityable');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('completed_note')->nullable();
            $table->boolean('is_automated')->default(false);
            $table->timestamps();

            $table->index(['assigned_to', 'due_date', 'completed_at']);
            $table->index(['activityable_type', 'activityable_id', 'completed_at']);
        });

        // Messages (Chatter)
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->morphs('messageable');
            $table->string('message_type')->default('comment'); // comment, notification, email, tracking, activity, system
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('tracking_values')->nullable();
            $table->json('attachments')->nullable();
            $table->json('mentions')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_note')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->timestamps();

            $table->index(['messageable_type', 'messageable_id', 'created_at']);
            $table->index(['message_type']);
        });

        // Record Rules
        Schema::create('record_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('entity_name')->index();
            $table->json('domain')->nullable();
            $table->json('groups')->nullable();
            $table->boolean('perm_read')->default(true);
            $table->boolean('perm_write')->default(false);
            $table->boolean('perm_create')->default(false);
            $table->boolean('perm_delete')->default(false);
            $table->boolean('is_global')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('plugin_slug')->nullable()->index();
            $table->timestamps();

            $table->index(['entity_name', 'is_active']);
        });

        // Seed default activity types
        DB::table('activity_types')->insert([
            ['name' => 'Call', 'slug' => 'call', 'icon' => 'phone', 'color' => 'blue', 'default_days' => 1, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Meeting', 'slug' => 'meeting', 'icon' => 'users', 'color' => 'green', 'default_days' => 1, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Email', 'slug' => 'email', 'icon' => 'mail', 'color' => 'purple', 'default_days' => 1, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'To-Do', 'slug' => 'todo', 'icon' => 'check-square', 'color' => 'orange', 'default_days' => 1, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Reminder', 'slug' => 'reminder', 'icon' => 'bell', 'color' => 'yellow', 'default_days' => 1, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Document Upload', 'slug' => 'upload', 'icon' => 'upload', 'color' => 'gray', 'default_days' => 1, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_rules');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('activity_types');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('ui_view_definitions');
        Schema::dropIfExists('workflow_history');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_definitions');
    }
};
