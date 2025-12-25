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
        // Credentials table (encrypted storage)
        Schema::create('integration_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('connector_name');
            $table->text('encrypted_data'); // AES-256-GCM encrypted
            $table->text('encrypted_key'); // Encrypted DEK
            $table->json('metadata')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'connector_name']);
        });

        // Connections table (credential references)
        Schema::create('integration_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('connector_name');
            $table->string('label');
            $table->uuid('credential_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'connector_name']);
        });

        // Flows table
        Schema::create('integration_flows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('tenant_id')->nullable()->index();
            $table->json('trigger_config')->nullable();
            $table->json('settings')->nullable();
            $table->string('status')->default('draft'); // draft, active, inactive
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        // Flow nodes table
        Schema::create('integration_flow_nodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('flow_id');
            $table->string('node_id'); // Internal node identifier
            $table->string('type'); // trigger, action, condition, loop, etc.
            $table->string('name');
            $table->json('config')->nullable();
            $table->json('position')->nullable(); // {x, y} for UI
            $table->timestamps();

            $table->foreign('flow_id')->references('id')->on('integration_flows')->onDelete('cascade');
            $table->unique(['flow_id', 'node_id']);
        });

        // Flow edges table (connections between nodes)
        Schema::create('integration_flow_edges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('flow_id');
            $table->string('source_node');
            $table->string('source_handle')->default('output');
            $table->string('target_node');
            $table->string('target_handle')->default('input');
            $table->json('condition')->nullable();
            $table->timestamps();

            $table->foreign('flow_id')->references('id')->on('integration_flows')->onDelete('cascade');
            $table->index(['flow_id', 'source_node']);
            $table->index(['flow_id', 'target_node']);
        });

        // Trigger subscriptions table
        Schema::create('integration_trigger_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('flow_id');
            $table->string('connector_name');
            $table->string('trigger_name');
            $table->uuid('connection_id')->nullable();
            $table->json('config')->nullable();
            $table->string('status')->default('active');
            $table->string('webhook_id')->nullable();
            $table->string('webhook_secret', 64)->nullable();
            $table->timestamp('webhook_registered_at')->nullable();
            $table->json('polling_state')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamps();

            $table->foreign('flow_id')->references('id')->on('integration_flows')->onDelete('cascade');
            $table->index(['connector_name', 'status']);
        });

        // Trigger events table
        Schema::create('integration_trigger_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_id');
            $table->uuid('flow_id');
            $table->json('data');
            $table->string('deduplication_key')->nullable();
            $table->string('status')->default('pending'); // pending, processed, failed
            $table->timestamp('processed_at')->nullable();
            $table->json('error')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('integration_trigger_subscriptions')->onDelete('cascade');
            $table->index(['subscription_id', 'deduplication_key']);
            $table->index(['flow_id', 'status']);
        });

        // Flow executions table
        Schema::create('integration_flow_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('flow_id');
            $table->unsignedInteger('flow_version');
            $table->string('tenant_id')->nullable()->index();
            $table->json('trigger_data')->nullable();
            $table->json('context')->nullable();
            $table->json('output')->nullable();
            $table->string('status')->default('pending'); // pending, running, waiting, completed, failed, cancelled
            $table->json('error')->nullable();
            $table->unsignedInteger('nodes_executed')->default(0);
            $table->float('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('resume_at')->nullable();
            $table->timestamps();

            $table->foreign('flow_id')->references('id')->on('integration_flows')->onDelete('cascade');
            $table->index(['flow_id', 'status']);
            $table->index(['tenant_id', 'status', 'created_at']);
        });

        // Flow step executions table
        Schema::create('integration_flow_step_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('execution_id');
            $table->string('node_id');
            $table->string('node_type');
            $table->string('node_name');
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->string('status')->default('pending'); // pending, running, success, failed, skipped
            $table->json('error')->nullable();
            $table->float('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('execution_id')->references('id')->on('integration_flow_executions')->onDelete('cascade');
            $table->index(['execution_id', 'node_id']);
        });

        // Action executions table (standalone action tracking)
        Schema::create('integration_action_executions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('connector_name');
            $table->string('action_name');
            $table->uuid('connection_id')->nullable();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->json('context')->nullable();
            $table->string('status')->default('pending');
            $table->json('error')->nullable();
            $table->float('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['connector_name', 'action_name', 'status']);
            $table->index(['connection_id', 'created_at']);
        });

        // Credential access log (audit trail)
        Schema::create('integration_credential_access_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('credential_id');
            $table->string('action'); // retrieved, updated, deleted
            $table->string('actor_type')->nullable(); // user, system, job
            $table->string('actor_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['credential_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_credential_access_logs');
        Schema::dropIfExists('integration_action_executions');
        Schema::dropIfExists('integration_flow_step_executions');
        Schema::dropIfExists('integration_flow_executions');
        Schema::dropIfExists('integration_trigger_events');
        Schema::dropIfExists('integration_trigger_subscriptions');
        Schema::dropIfExists('integration_flow_edges');
        Schema::dropIfExists('integration_flow_nodes');
        Schema::dropIfExists('integration_flows');
        Schema::dropIfExists('integration_connections');
        Schema::dropIfExists('integration_credentials');
    }
};
