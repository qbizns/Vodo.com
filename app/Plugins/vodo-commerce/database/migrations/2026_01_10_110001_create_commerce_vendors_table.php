<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Business Information
            $table->string('business_name');
            $table->string('legal_name')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();

            // Contact Information
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();

            // Tax & Business Registration
            $table->string('tax_id')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->json('verification_documents')->nullable();

            // Commission Settings
            $table->enum('commission_type', ['flat', 'percentage', 'tiered'])->default('percentage');
            $table->decimal('commission_value', 10, 2)->default(15.00);
            $table->json('commission_tiers')->nullable(); // For tiered commissions

            // Payout Settings
            $table->enum('payout_method', ['bank_transfer', 'paypal', 'stripe', 'manual'])->default('bank_transfer');
            $table->enum('payout_schedule', ['daily', 'weekly', 'biweekly', 'monthly'])->default('monthly');
            $table->decimal('minimum_payout_amount', 10, 2)->default(50.00);
            $table->json('payout_details')->nullable(); // Bank account, PayPal email, etc.

            // Status & Verification
            $table->enum('status', ['pending', 'approved', 'active', 'suspended', 'rejected', 'inactive'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Ratings & Reviews
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);

            // Policies (JSON)
            $table->json('shipping_policy')->nullable();
            $table->json('return_policy')->nullable();
            $table->json('terms_and_conditions')->nullable();

            // Metrics
            $table->unsignedInteger('total_products')->default(0);
            $table->unsignedInteger('total_sales')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'status']);
            $table->index('user_id');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_vendors');
    }
};
