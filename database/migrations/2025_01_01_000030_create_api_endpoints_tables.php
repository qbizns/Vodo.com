<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Unique identifier
            $table->string('slug', 100); // URL-friendly slug
            $table->string('method', 10); // GET, POST, PUT, PATCH, DELETE
            $table->string('path', 255); // Route path (e.g., /products/{id})
            
            // Handler configuration
            $table->string('handler_type', 20)->default('controller'); // controller, closure, action
            $table->string('handler_class', 255)->nullable(); // Controller class
            $table->string('handler_method', 100)->nullable(); // Controller method
            $table->text('handler_closure')->nullable(); // Serialized closure (for simple handlers)
            
            // Route configuration
            $table->string('prefix', 100)->nullable(); // Route prefix
            $table->string('version', 10)->default('v1'); // API version
            $table->json('middleware')->nullable(); // Array of middleware
            $table->json('where_constraints')->nullable(); // Route parameter constraints
            
            // Request validation
            $table->json('request_rules')->nullable(); // Validation rules
            $table->json('request_messages')->nullable(); // Custom validation messages
            
            // Response configuration
            $table->json('response_schema')->nullable(); // Expected response schema
            $table->string('response_type', 20)->default('json'); // json, xml, html
            
            // Rate limiting
            $table->integer('rate_limit')->nullable(); // Requests per minute
            $table->string('rate_limit_by', 20)->default('ip'); // ip, user, api_key
            
            // Authentication
            $table->string('auth_type', 20)->default('none'); // none, sanctum, api_key, basic
            $table->json('auth_config')->nullable(); // Additional auth config
            $table->json('permissions')->nullable(); // Required permissions
            
            // Documentation
            $table->string('summary', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable(); // OpenAPI tags
            $table->json('parameters')->nullable(); // Parameter documentation
            $table->json('request_body')->nullable(); // Request body documentation
            $table->json('responses')->nullable(); // Response documentation
            
            // Ownership & Status
            $table->string('plugin_slug', 100)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false); // Public documentation
            
            // Metadata
            $table->integer('priority')->default(100); // Route registration priority
            $table->json('meta')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->unique(['method', 'path', 'version']);
            $table->unique(['name', 'plugin_slug']);
            $table->index('plugin_slug');
            $table->index('is_active');
            $table->index(['version', 'is_active']);
        });

        // API Keys table for api_key authentication
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('key', 64)->unique();
            $table->string('secret_hash', 255)->nullable(); // For signed requests
            
            // Ownership
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plugin_slug', 100)->nullable();
            
            // Permissions & Scopes
            $table->json('scopes')->nullable(); // API scopes
            $table->json('allowed_endpoints')->nullable(); // Specific endpoints
            $table->json('allowed_ips')->nullable(); // IP whitelist
            
            // Rate limiting
            $table->integer('rate_limit')->nullable(); // Override default
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedBigInteger('request_count')->default(0);
            
            $table->timestamps();
            
            $table->index('key');
            $table->index('user_id');
            $table->index('is_active');
        });

        // API Request logs
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endpoint_id')->nullable()->constrained('api_endpoints')->nullOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('method', 10);
            $table->string('path', 255);
            $table->string('ip_address', 45);
            $table->string('user_agent', 255)->nullable();
            
            $table->integer('status_code');
            $table->integer('response_time_ms');
            $table->integer('request_size')->nullable();
            $table->integer('response_size')->nullable();
            
            $table->json('request_headers')->nullable();
            $table->json('request_params')->nullable();
            $table->text('error_message')->nullable();
            
            $table->timestamp('created_at');
            
            $table->index(['endpoint_id', 'created_at']);
            $table->index(['api_key_id', 'created_at']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('api_endpoints');
    }
};
