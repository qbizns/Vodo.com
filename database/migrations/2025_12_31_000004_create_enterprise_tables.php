<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit logs for compliance and security
        // Note: audit_logs may already exist from a previous migration
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_type')->nullable(); // user, system, plugin, api
            $table->string('event'); // created, updated, deleted, accessed, exported
            $table->string('auditable_type')->index(); // Model class
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->json('tags')->nullable(); // security, financial, pii, etc.
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('event');
            });
        }

        // API rate limits configuration per tenant/plan
        Schema::create('rate_limit_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('plan')->nullable(); // free, starter, professional, enterprise
            $table->string('key'); // api, webhook, export, etc.
            $table->integer('max_requests');
            $table->integer('window_seconds'); // time window
            $table->integer('burst_limit')->nullable(); // allow burst
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        // Rate limit tracking/buckets
        Schema::create('rate_limit_buckets', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index(); // tenant:1:api, user:5:export
            $table->integer('tokens');
            $table->timestamp('last_refill');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique('key');
        });

        // API quotas (monthly/daily limits)
        Schema::create('api_quotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('resource'); // api_calls, storage_gb, plugins, users
            $table->bigInteger('limit');
            $table->bigInteger('used')->default(0);
            $table->string('period'); // daily, monthly, yearly
            $table->date('period_start');
            $table->date('period_end');
            $table->boolean('overage_allowed')->default(false);
            $table->decimal('overage_rate', 10, 4)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'resource', 'period_start']);
            $table->index(['tenant_id', 'resource']);
        });

        // Webhook endpoints
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('url');
            $table->string('secret', 64); // HMAC secret
            $table->json('events'); // ['order.created', 'payment.completed']
            $table->string('status')->default('active'); // active, paused, disabled
            $table->string('version')->default('v1');
            $table->integer('timeout_seconds')->default(30);
            $table->integer('retry_count')->default(3);
            $table->json('headers')->nullable(); // custom headers
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->integer('consecutive_failures')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        // Webhook delivery attempts
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('endpoint_id')->constrained('webhook_endpoints')->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->string('status'); // pending, delivered, failed, retrying
            $table->integer('attempts')->default(0);
            $table->integer('http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['endpoint_id', 'status']);
            $table->index(['status', 'next_retry_at']);
            $table->index('event');
        });

        // Performance metrics
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('metric'); // response_time, query_count, memory_usage
            $table->string('endpoint')->nullable();
            $table->decimal('value', 15, 4);
            $table->string('unit')->default('ms');
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at')->index();

            $table->index(['metric', 'recorded_at']);
            $table->index(['tenant_id', 'metric', 'recorded_at']);
        });

        // Cache configurations per tenant
        Schema::create('cache_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->unique();
            $table->boolean('enabled')->default(true);
            $table->integer('default_ttl')->default(3600); // seconds
            $table->json('ttl_overrides')->nullable(); // per-resource TTLs
            $table->json('excluded_paths')->nullable();
            $table->string('strategy')->default('lru'); // lru, lfu, ttl
            $table->bigInteger('max_size_bytes')->nullable();
            $table->timestamps();
        });

        // Feature flags for gradual rollouts
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(false);
            $table->integer('rollout_percentage')->default(0); // 0-100
            $table->json('tenant_ids')->nullable(); // specific tenants
            $table->json('user_ids')->nullable(); // specific users
            $table->json('conditions')->nullable(); // advanced rules
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('enabled');
        });

        // Tenant settings and limits
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->string('plan')->default('free');
            $table->string('status')->default('active'); // active, suspended, cancelled
            $table->integer('max_users')->default(5);
            $table->integer('max_plugins')->default(10);
            $table->bigInteger('storage_limit_bytes')->default(1073741824); // 1GB
            $table->bigInteger('storage_used_bytes')->default(0);
            $table->integer('api_rate_limit')->default(1000); // per minute
            $table->boolean('audit_logging_enabled')->default(true);
            $table->integer('audit_retention_days')->default(90);
            $table->boolean('advanced_analytics_enabled')->default(false);
            $table->boolean('custom_domain_enabled')->default(false);
            $table->boolean('sso_enabled')->default(false);
            $table->json('sso_config')->nullable();
            $table->boolean('ip_whitelist_enabled')->default(false);
            $table->json('ip_whitelist')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('plan');
            $table->index('status');
        });

        // System health checks
        Schema::create('health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status'); // healthy, degraded, unhealthy
            $table->text('message')->nullable();
            $table->decimal('response_time_ms', 10, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['name', 'checked_at']);
        });

        // Background job tracking for visibility
        Schema::create('job_batches_extended', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('name');
            $table->string('type'); // payout, export, import, sync
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->json('options')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_batches_extended');
        Schema::dropIfExists('health_checks');
        Schema::dropIfExists('tenant_settings');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('cache_configs');
        Schema::dropIfExists('performance_metrics');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('api_quotas');
        Schema::dropIfExists('rate_limit_buckets');
        Schema::dropIfExists('rate_limit_configs');
        Schema::dropIfExists('audit_logs');
    }
};
