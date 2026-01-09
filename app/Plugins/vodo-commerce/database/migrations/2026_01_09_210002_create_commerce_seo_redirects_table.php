<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Redirect URLs
            $table->string('from_url', 500); // Source URL (can be pattern with wildcards)
            $table->string('to_url', 500); // Destination URL
            $table->boolean('is_regex')->default(false); // Whether from_url is a regex pattern

            // Redirect Type
            $table->enum('redirect_type', [
                '301', // Permanent redirect (most common for SEO)
                '302', // Temporary redirect
                '307', // Temporary redirect (maintains HTTP method)
                '308', // Permanent redirect (maintains HTTP method)
            ])->default('301');

            // Status
            $table->boolean('is_active')->default(true);

            // Analytics
            $table->integer('hit_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();

            // Metadata
            $table->string('reason')->nullable(); // Why was this redirect created
            $table->string('created_by')->nullable(); // User who created it
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'from_url']);
            $table->index(['store_id', 'is_active']);
            $table->index('hit_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_seo_redirects');
    }
};
