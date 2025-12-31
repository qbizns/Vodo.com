<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1: Platform Partnership - Plugin Security Infrastructure
 *
 * Creates tables for:
 * - Plugin permissions (scoped access control)
 * - Plugin API keys (authentication & rate limiting)
 * - Plugin audit logs (security tracking)
 * - Plugin resource usage (sandboxing metrics)
 */
return new class extends Migration
{
    public function up(): void
    {
        // =========================================================================
        // Plugin Permissions - Defines what each plugin can access
        // =========================================================================
        Schema::create('plugin_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug', 100)->index();
            $table->string('scope', 100)->index();
            $table->string('resource')->nullable();
            $table->enum('access_level', ['read', 'write', 'delete', 'admin'])->default('read');
            $table->json('constraints')->nullable();
            $table->boolean('is_granted')->default(false);
            $table->timestamp('granted_at')->nullable();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['plugin_slug', 'scope', 'resource'], 'plugin_scope_resource_unique');
            $table->foreign('plugin_slug')
                ->references('slug')
                ->on('plugins')
                ->onDelete('cascade');
        });

        // =========================================================================
        // Plugin Scopes - Master list of available scopes
        // =========================================================================
        Schema::create('plugin_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('category', 50)->index();
            $table->boolean('is_dangerous')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->json('implies')->nullable();
            $table->integer('risk_level')->default(1);
            $table->timestamps();
        });

        // =========================================================================
        // Plugin API Keys - Authentication for plugin API access
        // =========================================================================
        Schema::create('plugin_api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug', 100)->index();
            $table->string('name', 100);
            $table->string('key_id', 32)->unique();
            $table->string('key_hash', 128);
            $table->string('key_prefix', 8);
            $table->json('scopes')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->json('allowed_domains')->nullable();
            $table->integer('rate_limit_per_minute')->default(60);
            $table->integer('rate_limit_per_hour')->default(1000);
            $table->integer('rate_limit_per_day')->default(10000);
            $table->unsignedBigInteger('total_requests')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('plugin_slug')
                ->references('slug')
                ->on('plugins')
                ->onDelete('cascade');
        });

        // =========================================================================
        // Plugin Audit Logs - Security event tracking
        // =========================================================================
        Schema::create('plugin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug', 100)->index();
            $table->string('event_type', 50)->index();
            $table->string('event_category', 30)->index();
            $table->string('severity', 20)->default('info');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type', 50)->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('request_id', 36)->nullable()->index();
            $table->decimal('execution_time_ms', 10, 2)->nullable();
            $table->unsignedBigInteger('memory_usage_bytes')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['plugin_slug', 'event_type', 'created_at']);
            $table->index(['severity', 'created_at']);
        });

        // =========================================================================
        // Plugin Resource Usage - Sandboxing metrics
        // =========================================================================
        Schema::create('plugin_resource_usage', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug', 100)->index();
            $table->date('usage_date')->index();
            $table->unsignedBigInteger('api_requests')->default(0);
            $table->unsignedBigInteger('hook_executions')->default(0);
            $table->unsignedBigInteger('entity_reads')->default(0);
            $table->unsignedBigInteger('entity_writes')->default(0);
            $table->unsignedBigInteger('storage_bytes_used')->default(0);
            $table->unsignedBigInteger('network_bytes_out')->default(0);
            $table->unsignedBigInteger('network_bytes_in')->default(0);
            $table->decimal('total_execution_time_ms', 15, 2)->default(0);
            $table->unsignedBigInteger('peak_memory_bytes')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->unsignedInteger('timeout_count')->default(0);
            $table->unsignedInteger('rate_limit_hits')->default(0);
            $table->timestamps();

            $table->unique(['plugin_slug', 'usage_date']);
            $table->foreign('plugin_slug')
                ->references('slug')
                ->on('plugins')
                ->onDelete('cascade');
        });

        // =========================================================================
        // Plugin Sandbox Violations - Security breach attempts
        // =========================================================================
        Schema::create('plugin_sandbox_violations', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug', 100)->index();
            $table->string('violation_type', 50)->index();
            $table->text('description');
            $table->json('context')->nullable();
            $table->string('severity', 20)->default('warning');
            $table->boolean('auto_disabled')->default(false);
            $table->timestamp('created_at')->index();

            $table->foreign('plugin_slug')
                ->references('slug')
                ->on('plugins')
                ->onDelete('cascade');

            $table->index(['plugin_slug', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_sandbox_violations');
        Schema::dropIfExists('plugin_resource_usage');
        Schema::dropIfExists('plugin_audit_logs');
        Schema::dropIfExists('plugin_api_keys');
        Schema::dropIfExists('plugin_scopes');
        Schema::dropIfExists('plugin_permissions');
    }
};
