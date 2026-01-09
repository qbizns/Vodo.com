<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('commerce_customers')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('commerce_orders')->nullOnDelete();

            // Review Content
            $table->integer('rating'); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment');

            // Verification & Status
            $table->boolean('is_verified_purchase')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])->default('pending');
            $table->boolean('is_featured')->default(false);

            // Engagement Metrics
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);

            // Publishing
            $table->timestamp('published_at')->nullable();

            // Moderation
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'product_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['rating', 'status']);
            $table->index(['is_verified_purchase', 'status']);
            $table->index('published_at');
            $table->unique(['order_id', 'product_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_product_reviews');
    }
};
