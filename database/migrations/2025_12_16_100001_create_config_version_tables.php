<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create configuration version control tables.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Main config versions table
        Schema::create('config_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            
            // Config identification
            $table->string('config_type', 50); // entity, workflow, view, record_rule, etc.
            $table->string('config_name', 200); // e.g., "invoice", "approval_workflow"
            $table->string('branch', 100)->default('main');
            $table->unsignedInteger('version');
            
            // Content
            $table->json('content');
            $table->string('content_hash', 64); // SHA-256 hash for quick comparison
            $table->text('description')->nullable();
            
            // Version lineage
            $table->foreignId('parent_version_id')->nullable()->constrained('config_versions')->nullOnDelete();
            $table->foreignId('rollback_version_id')->nullable()->constrained('config_versions')->nullOnDelete();
            
            // Status and environment
            $table->string('status', 20)->default('draft'); // draft, pending_review, approved, rejected, active, archived
            $table->string('environment', 20)->default('development'); // development, staging, production
            
            // Audit
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('promoted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('promoted_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tenant_id');
            $table->index('config_type');
            $table->index(['config_type', 'config_name']);
            $table->index(['config_type', 'config_name', 'branch']);
            $table->index(['config_type', 'config_name', 'environment']);
            $table->index('status');
            $table->index('environment');
            $table->index('content_hash');
            
            // Unique version per config+branch
            $table->unique(['tenant_id', 'config_type', 'config_name', 'branch', 'version'], 'config_versions_unique');
        });

        // Reviews table
        Schema::create('config_version_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_version_id')->constrained('config_versions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, changes_requested
            $table->text('comments')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index('config_version_id');
            $table->index('reviewer_id');
            $table->index('status');
            $table->unique(['config_version_id', 'reviewer_id']);
        });

        // Config snapshots (for point-in-time recovery)
        Schema::create('config_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('environment', 20);
            $table->json('version_ids'); // Array of config_version IDs in this snapshot
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('tenant_id');
            $table->index('environment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_snapshots');
        Schema::dropIfExists('config_version_reviews');
        Schema::dropIfExists('config_versions');
    }
};
