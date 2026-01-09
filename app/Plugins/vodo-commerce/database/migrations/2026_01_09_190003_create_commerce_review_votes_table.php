<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('commerce_product_reviews')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('commerce_customers')->cascadeOnDelete();

            // Vote Details
            $table->enum('vote_type', ['helpful', 'not_helpful']);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes & Constraints
            $table->index(['review_id', 'vote_type']);
            $table->index('customer_id');
            // Unique constraint: one vote per customer per review OR one vote per IP per review
            $table->unique(['review_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_review_votes');
    }
};
