<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_vendor_review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_review_id')->constrained('commerce_vendor_reviews')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('commerce_customers')->cascadeOnDelete();

            // Vote Type
            $table->enum('vote', ['helpful', 'unhelpful'])->default('helpful');

            // Session tracking for guests
            $table->string('session_id')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            // Constraints - one vote per customer per review
            $table->unique(['vendor_review_id', 'customer_id']);
            $table->index(['vendor_review_id', 'vote']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_vendor_review_votes');
    }
};
