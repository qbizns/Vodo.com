<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Installed plugins registry
        Schema::create('installed_plugins', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('version', 20);
            $table->string('author', 100)->nullable();
            $table->string('author_url', 255)->nullable();
            $table->string('homepage', 255)->nullable();
            
            // Marketplace info
            $table->string('marketplace_id', 50)->nullable();
            $table->string('marketplace_url', 255)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            // Installation
            $table->string('install_path', 255);
            $table->string('entry_class', 255);
            $table->json('dependencies')->nullable();
            $table->json('requirements')->nullable();
            
            // Status
            $table->string('status', 20)->default('inactive'); // inactive, active, error
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_verified')->default(false);
            
            // Timestamps
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_update_check')->nullable();
            
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('marketplace_id');
        });

        // Plugin licenses
        Schema::create('plugin_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('installed_plugins')->cascadeOnDelete();
            
            // License details
            $table->string('license_key', 255);
            $table->string('license_type', 50)->default('standard'); // standard, extended, lifetime, subscription
            $table->string('status', 20)->default('active'); // active, expired, suspended, invalid
            
            // Activation
            $table->string('activation_id', 100)->nullable();
            $table->string('activation_email', 255)->nullable();
            $table->string('instance_id', 100)->nullable(); // Unique per installation
            $table->integer('activations_used')->default(1);
            $table->integer('activations_limit')->nullable();
            
            // Validity
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            
            // Support & Updates
            $table->boolean('support_active')->default(true);
            $table->timestamp('support_expires_at')->nullable();
            $table->boolean('updates_active')->default(true);
            $table->timestamp('updates_expire_at')->nullable();
            
            $table->json('features')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->unique('license_key');
            $table->index('status');
        });

        // Available updates
        Schema::create('plugin_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('installed_plugins')->cascadeOnDelete();
            
            $table->string('current_version', 20);
            $table->string('new_version', 20);
            $table->text('changelog')->nullable();
            $table->string('download_url', 500)->nullable();
            $table->string('package_hash', 64)->nullable();
            $table->bigInteger('package_size')->nullable();
            
            // Requirements
            $table->string('requires_php', 20)->nullable();
            $table->string('requires_laravel', 20)->nullable();
            $table->json('requires_plugins')->nullable();
            
            // Flags
            $table->boolean('is_security_update')->default(false);
            $table->boolean('is_critical')->default(false);
            $table->boolean('requires_license')->default(false);
            
            $table->timestamp('released_at')->nullable();
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->unique(['plugin_id', 'new_version']);
        });

        // Update history
        Schema::create('plugin_update_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('installed_plugins')->cascadeOnDelete();
            
            $table->string('from_version', 20);
            $table->string('to_version', 20);
            $table->string('status', 20); // success, failed, rolled_back
            $table->text('log')->nullable();
            $table->text('error')->nullable();
            
            $table->string('backup_path', 255)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['plugin_id', 'created_at']);
        });

        // Marketplace cache (for browsing)
        Schema::create('marketplace_plugins', function (Blueprint $table) {
            $table->id();
            $table->string('marketplace_id', 50)->unique();
            $table->string('slug', 100);
            $table->string('name', 150);
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            
            // Author
            $table->string('author', 100)->nullable();
            $table->string('author_url', 255)->nullable();
            $table->boolean('is_verified_author')->default(false);
            
            // Versioning
            $table->string('latest_version', 20);
            $table->string('requires_php', 20)->nullable();
            $table->string('requires_laravel', 20)->nullable();
            
            // Pricing
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_free')->default(true);
            $table->json('pricing_tiers')->nullable();
            
            // Stats
            $table->integer('downloads')->default(0);
            $table->integer('active_installs')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            
            // Categories
            $table->json('categories')->nullable();
            $table->json('tags')->nullable();
            
            // Media
            $table->string('icon_url', 500)->nullable();
            $table->json('screenshots')->nullable();
            
            // Status
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);
            
            $table->timestamp('last_updated')->nullable();
            $table->timestamp('synced_at')->nullable();
            
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index('slug');
            $table->index('is_featured');
            $table->index('rating');
            $table->index('downloads');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_plugins');
        Schema::dropIfExists('plugin_update_history');
        Schema::dropIfExists('plugin_updates');
        Schema::dropIfExists('plugin_licenses');
        Schema::dropIfExists('installed_plugins');
    }
};
