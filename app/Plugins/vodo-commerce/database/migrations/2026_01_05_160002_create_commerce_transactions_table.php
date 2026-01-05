<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Relationships
            $table->foreignId('order_id')->nullable()->constrained('commerce_orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('commerce_customers')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('commerce_payment_methods')->cascadeOnDelete();

            // Transaction Identification
            $table->string('transaction_id')->unique(); // Internal transaction ID
            $table->string('external_id')->nullable(); // Gateway transaction ID
            $table->string('reference_number')->nullable(); // Customer-facing reference

            // Transaction Details
            $table->string('type'); // payment, refund, payout, fee, adjustment
            $table->string('status'); // pending, processing, completed, failed, cancelled, refunded
            $table->string('payment_status')->nullable(); // authorized, captured, settled
            $table->string('currency', 3); // USD, SAR, AED
            $table->decimal('amount', 12, 2); // Transaction amount
            $table->decimal('fee_amount', 10, 2)->default(0); // Gateway fees
            $table->decimal('net_amount', 12, 2); // Amount after fees

            // Fee Breakdown
            $table->json('fees')->nullable(); // {gateway: 2.50, processing: 0.30, platform: 0}

            // Payment Method Details
            $table->string('payment_method_type')->nullable(); // card, bank_transfer, wallet, cod
            $table->string('card_brand')->nullable(); // visa, mastercard, amex
            $table->string('card_last4')->nullable(); // Last 4 digits
            $table->string('bank_name')->nullable(); // For bank transfers
            $table->string('wallet_provider')->nullable(); // apple_pay, google_pay

            // Gateway Response
            $table->json('gateway_response')->nullable(); // Full gateway response
            $table->text('failure_reason')->nullable(); // Error message if failed
            $table->string('failure_code')->nullable(); // Error code

            // IP & Security
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('is_test')->default(false);

            // Timing
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            // Refund Information
            $table->foreignId('parent_transaction_id')->nullable()->constrained('commerce_transactions')->nullOnDelete();
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->text('refund_reason')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'type']);
            $table->index(['order_id', 'type']);
            $table->index(['customer_id', 'created_at']);
            $table->index('external_id');
            $table->index('reference_number');
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_transactions');
    }
};
