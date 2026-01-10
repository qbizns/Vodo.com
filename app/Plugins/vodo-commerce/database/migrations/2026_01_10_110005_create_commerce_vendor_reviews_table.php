<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_vendor_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('commerce_vendors')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('commerce_customers')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('commerce_orders')->nullOnDelete();

            // Review Content
            $table->unsignedTinyInteger('rating'); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('comment');

            // Sub-Ratings (optional detailed ratings)
            $table->unsignedTinyInteger('product_quality_rating')->nullable(); // 1-5
            $table->unsignedTinyInteger('shipping_speed_rating')->nullable(); // 1-5
            $table->unsignedTinyInteger('communication_rating')->nullable(); // 1-5
            $table->unsignedTinyInteger('customer_service_rating')->nullable(); // 1-5

            // Verification
            $table->boolean('is_verified_purchase')->default(false);

            // Moderation
            $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])->default('pending');
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_flagged')->default(false);
            $table->text('flag_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('flagged_at')->nullable();

            // Helpfulness Votes
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('unhelpful_count')->default(0);

            // Vendor Response
            $table->text('vendor_response')->nullable();
            $table->timestamp('vendor_response_at')->nullable();

            // Admin Response
            $table->text('admin_response')->nullable();
            $table->timestamp('admin_response_at')->nullable();
            $table->foreignId('admin_responder_id')->nullable()->constrained('users')->nullOnDelete();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['vendor_id', 'is_approved']);
            $table->index(['customer_id']);
            $table->index(['order_id']);
            $table->index(['rating']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_vendor_reviews');
    }
};
