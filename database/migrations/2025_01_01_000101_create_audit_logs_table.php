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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type')->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            $table->string('event', 50)->index(); // create, update, delete, restore, custom
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('user_type', 50)->nullable(); // web, admin, owner, api
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->string('batch_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();

            // Composite indexes for common queries
            $table->index(['auditable_type', 'auditable_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });

        // Add soft deletes to key tables (run this separately if tables exist)
        $tablesToAddSoftDeletes = [
            'entity_definitions',
            'entity_fields',
            'entity_records',
            'plugins',
            'permissions',
            'roles',
            'workflow_definitions',
            'ui_view_definitions',
            'document_templates',
        ];

        foreach ($tablesToAddSoftDeletes as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        // Remove soft deletes from tables
        $tablesToRemoveSoftDeletes = [
            'entity_definitions',
            'entity_fields',
            'entity_records',
            'plugins',
            'permissions',
            'roles',
            'workflow_definitions',
            'ui_view_definitions',
            'document_templates',
        ];

        foreach ($tablesToRemoveSoftDeletes as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
