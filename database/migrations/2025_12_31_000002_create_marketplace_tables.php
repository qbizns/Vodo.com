<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace Infrastructure Tables
 *
 * This migration creates the complete marketplace infrastructure:
 * - Plugin listings and metadata
 * - Submissions and review workflow
 * - Versions and distribution
 * - Installations and analytics
 * - Ratings and reviews
 * - Pricing and subscriptions
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // PLUGIN LISTINGS - The main marketplace catalog
        // =====================================================================
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug', 100)->unique();
            $table->string('name');
            $table->string('tagline', 255)->nullable();
            $table->text('description');
            $table->text('features')->nullable(); // JSON array of features
            $table->string('category', 50)->index();
            $table->json('tags')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->json('screenshots')->nullable(); // Array of screenshot URLs
            $table->string('video_url')->nullable();

            // Author/Publisher
            $table->unsignedBigInteger('publisher_id')->index();
            $table->string('publisher_name');
            $table->string('publisher_url')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_url')->nullable();
            $table->string('documentation_url')->nullable();

            // Versioning
            $table->string('current_version', 20);
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->string('min_platform_version', 20)->nullable();
            $table->string('max_platform_version', 20)->nullable();

            // Pricing
            $table->enum('pricing_model', ['free', 'one_time', 'subscription'])->default('free');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('price_currency', 3)->default('USD');
            $table->integer('trial_days')->default(0);

            // Status
            $table->enum('status', ['draft', 'pending', 'approved', 'published', 'suspended', 'deprecated'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();

            // Stats (denormalized for performance)
            $table->unsignedInteger('install_count')->default(0);
            $table->unsignedInteger('active_install_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('review_count')->default(0);

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->json('meta_keywords')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category']);
            $table->index(['status', 'pricing_model']);
            $table->index(['status', 'average_rating']);
            $table->index(['status', 'install_count']);
            $table->fullText(['name', 'description', 'tagline']);
        });

        // =====================================================================
        // PLUGIN VERSIONS - All published versions with packages
        // =====================================================================
        Schema::create('marketplace_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->string('version', 20);
            $table->text('changelog')->nullable();
            $table->text('release_notes')->nullable();

            // Package
            $table->string('package_url'); // CDN URL to package
            $table->string('package_hash', 64); // SHA-256 hash
            $table->unsignedBigInteger('package_size'); // Bytes
            $table->string('package_signature')->nullable(); // For signed packages

            // Requirements
            $table->string('min_php_version', 10)->default('8.2');
            $table->string('min_platform_version', 20)->nullable();
            $table->json('dependencies')->nullable(); // Other required plugins

            // Permissions
            $table->json('required_scopes')->nullable();
            $table->json('optional_scopes')->nullable();

            // Status
            $table->enum('status', ['draft', 'pending', 'approved', 'published', 'yanked'])->default('draft');
            $table->enum('channel', ['stable', 'beta', 'alpha', 'rc'])->default('stable');
            $table->boolean('is_current')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('yanked_at')->nullable();
            $table->string('yank_reason')->nullable();

            // Stats
            $table->unsignedInteger('download_count')->default(0);

            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('marketplace_listings')->cascadeOnDelete();
            $table->unique(['listing_id', 'version']);
            $table->index(['listing_id', 'status', 'channel']);
        });

        // =====================================================================
        // SUBMISSIONS - Plugin review workflow
        // =====================================================================
        Schema::create('marketplace_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id')->nullable(); // Null for new plugins
            $table->unsignedBigInteger('version_id')->nullable();
            $table->unsignedBigInteger('submitter_id');
            $table->string('plugin_slug', 100);

            // Submission type
            $table->enum('type', ['new', 'update', 'resubmit'])->default('new');
            $table->string('version', 20);

            // Uploaded package
            $table->string('package_path'); // Temporary storage path
            $table->string('package_hash', 64);
            $table->unsignedBigInteger('package_size');

            // Manifest data (extracted from plugin.json)
            $table->json('manifest');

            // Workflow state
            $table->enum('status', [
                'draft',
                'submitted',
                'automated_review',
                'manual_review',
                'testing',
                'changes_requested',
                'approved',
                'rejected',
                'published',
                'cancelled'
            ])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('review_started_at')->nullable();
            $table->timestamp('review_completed_at')->nullable();

            // Review assignment
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->timestamp('assigned_at')->nullable();

            // Priority (higher = faster review)
            $table->unsignedTinyInteger('priority')->default(5);
            $table->boolean('is_expedited')->default(false);

            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('marketplace_listings')->nullOnDelete();
            $table->index(['status', 'priority', 'submitted_at']);
            $table->index(['submitter_id', 'status']);
        });

        // =====================================================================
        // REVIEW RESULTS - Automated and manual review findings
        // =====================================================================
        Schema::create('marketplace_review_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->enum('review_type', ['automated', 'manual']);
            $table->string('check_name', 100);
            $table->enum('result', ['pass', 'fail', 'warning', 'skip', 'error']);
            $table->string('category', 50)->nullable(); // security, performance, quality, etc.
            $table->text('message')->nullable();
            $table->json('details')->nullable();
            $table->unsignedInteger('score')->nullable(); // 0-100
            $table->unsignedBigInteger('reviewer_id')->nullable(); // For manual reviews
            $table->timestamps();

            $table->foreign('submission_id')->references('id')->on('marketplace_submissions')->cascadeOnDelete();
            $table->index(['submission_id', 'review_type', 'result'], 'idx_review_results_submission_type_result');
        });

        // =====================================================================
        // INSTALLATIONS - Track plugin installations per tenant
        // =====================================================================
        Schema::create('marketplace_installations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('version_id');
            $table->string('installed_version', 20);

            // Status
            $table->enum('status', ['active', 'inactive', 'suspended', 'uninstalled'])->default('active');
            $table->timestamp('installed_at');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();

            // Auto-update preference
            $table->boolean('auto_update')->default(true);
            $table->enum('update_channel', ['stable', 'beta'])->default('stable');

            // License/Subscription
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->timestamp('license_expires_at')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->timestamp('trial_expires_at')->nullable();

            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('marketplace_listings')->cascadeOnDelete();
            $table->foreign('version_id')->references('id')->on('marketplace_versions')->cascadeOnDelete();
            $table->unique(['listing_id', 'tenant_id']);
            $table->index(['tenant_id', 'status']);
        });

        // =====================================================================
        // RATINGS AND REVIEWS
        // =====================================================================
        Schema::create('marketplace_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('installation_id')->nullable();

            // Rating
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->string('title')->nullable();
            $table->text('body')->nullable();

            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'hidden'])->default('pending');
            $table->boolean('is_verified_purchase')->default(false);

            // Publisher response
            $table->text('publisher_response')->nullable();
            $table->timestamp('publisher_responded_at')->nullable();

            // Moderation
            $table->unsignedBigInteger('moderated_by')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->string('moderation_reason')->nullable();

            // Helpfulness
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('listing_id')->references('id')->on('marketplace_listings')->cascadeOnDelete();
            $table->unique(['listing_id', 'user_id']);
            $table->index(['listing_id', 'status', 'rating']);
        });

        // =====================================================================
        // SUBSCRIPTIONS - Paid plugin subscriptions
        // =====================================================================
        Schema::create('marketplace_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('installation_id')->nullable();

            // Subscription details
            $table->enum('status', ['trial', 'active', 'past_due', 'cancelled', 'expired'])->default('trial');
            $table->enum('billing_cycle', ['monthly', 'yearly', 'one_time'])->default('monthly');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            // Dates
            $table->timestamp('started_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Payment provider
            $table->string('payment_provider')->nullable(); // stripe, paddle, etc.
            $table->string('external_subscription_id')->nullable();
            $table->string('external_customer_id')->nullable();

            // Features
            $table->json('features')->nullable(); // Enabled features for this subscription tier

            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('marketplace_listings')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['listing_id', 'status']);
        });

        // =====================================================================
        // CATEGORIES - Plugin categories
        // =====================================================================
        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('listing_count')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('marketplace_categories')->nullOnDelete();
        });

        // =====================================================================
        // ANALYTICS EVENTS - Installation and usage tracking
        // =====================================================================
        Schema::create('marketplace_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('event', 50); // view, install, uninstall, activate, etc.
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('created_at');

            $table->foreign('listing_id')->references('id')->on('marketplace_listings')->cascadeOnDelete();
            $table->index(['listing_id', 'event', 'created_at']);
            $table->index(['created_at']); // For cleanup
        });

        // =====================================================================
        // FEATURED PLUGINS - Curated featured listings
        // =====================================================================
        Schema::create('marketplace_featured', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->string('placement', 50); // homepage, category, search, etc.
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('badge')->nullable(); // "Editor's Choice", "New", etc.
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('marketplace_listings')->cascadeOnDelete();
            $table->index(['placement', 'is_active', 'sort_order']);
        });

        // Seed default categories
        $this->seedCategories();
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_featured');
        Schema::dropIfExists('marketplace_analytics');
        Schema::dropIfExists('marketplace_categories');
        Schema::dropIfExists('marketplace_subscriptions');
        Schema::dropIfExists('marketplace_reviews');
        Schema::dropIfExists('marketplace_installations');
        Schema::dropIfExists('marketplace_review_results');
        Schema::dropIfExists('marketplace_submissions');
        Schema::dropIfExists('marketplace_versions');
        Schema::dropIfExists('marketplace_listings');
    }

    protected function seedCategories(): void
    {
        $categories = [
            ['slug' => 'sales', 'name' => 'Sales & CRM', 'icon' => 'chart-line', 'sort_order' => 1],
            ['slug' => 'inventory', 'name' => 'Inventory & Warehouse', 'icon' => 'boxes', 'sort_order' => 2],
            ['slug' => 'accounting', 'name' => 'Accounting & Finance', 'icon' => 'calculator', 'sort_order' => 3],
            ['slug' => 'hr', 'name' => 'HR & Payroll', 'icon' => 'users', 'sort_order' => 4],
            ['slug' => 'ecommerce', 'name' => 'E-commerce', 'icon' => 'shopping-cart', 'sort_order' => 5],
            ['slug' => 'marketing', 'name' => 'Marketing & Automation', 'icon' => 'bullhorn', 'sort_order' => 6],
            ['slug' => 'integrations', 'name' => 'Integrations', 'icon' => 'plug', 'sort_order' => 7],
            ['slug' => 'analytics', 'name' => 'Analytics & Reporting', 'icon' => 'chart-bar', 'sort_order' => 8],
            ['slug' => 'communications', 'name' => 'Communications', 'icon' => 'comments', 'sort_order' => 9],
            ['slug' => 'productivity', 'name' => 'Productivity', 'icon' => 'tasks', 'sort_order' => 10],
            ['slug' => 'shipping', 'name' => 'Shipping & Logistics', 'icon' => 'truck', 'sort_order' => 11],
            ['slug' => 'payments', 'name' => 'Payments', 'icon' => 'credit-card', 'sort_order' => 12],
            ['slug' => 'utilities', 'name' => 'Utilities & Tools', 'icon' => 'wrench', 'sort_order' => 13],
        ];

        foreach ($categories as $category) {
            \Illuminate\Support\Facades\DB::table('marketplace_categories')->insert(
                array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
};
