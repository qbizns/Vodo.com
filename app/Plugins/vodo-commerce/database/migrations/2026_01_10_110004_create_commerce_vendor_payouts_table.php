<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_vendor_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('commerce_vendors')->cascadeOnDelete();
            $table->string('payout_number')->unique(); // e.g., "PAYOUT-2026-01-001"

            // Payout Period
            $table->date('period_start');
            $table->date('period_end');

            // Financial Details
            $table->decimal('gross_amount', 12, 2); // Total vendor earnings before adjustments
            $table->decimal('platform_fees', 12, 2)->default(0); // Platform fees deducted
            $table->decimal('adjustments', 12, 2)->default(0); // Manual adjustments (can be negative)
            $table->decimal('net_amount', 12, 2); // Final payout amount (gross - fees + adjustments)
            $table->string('currency', 3)->default('USD');

            // Commission Summary
            $table->unsignedInteger('commission_count')->default(0); // Number of commissions included
            $table->unsignedInteger('order_count')->default(0); // Number of orders

            // Payout Method
            $table->enum('payout_method', ['bank_transfer', 'paypal', 'stripe', 'check', 'manual'])->default('bank_transfer');
            $table->json('payout_details')->nullable(); // Account info, transaction IDs, etc.

            // Status & Processing
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Transaction Details
            $table->string('transaction_id')->nullable(); // PayPal transaction ID, bank reference, etc.
            $table->text('failure_reason')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Notes & Documentation
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable(); // Receipts, statements, etc.

            // Processed By
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'period_start', 'period_end']);
            $table->index('payout_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_vendor_payouts');
    }
};
