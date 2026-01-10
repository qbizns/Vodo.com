<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commerce_subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('commerce_subscriptions')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('commerce_customers')->cascadeOnDelete();

            // Invoice Details
            $table->string('invoice_number')->unique(); // INV-20260109-ABCD1234
            $table->string('status'); // draft, pending, paid, failed, void, refunded

            // Billing Period
            $table->timestamp('period_start');
            $table->timestamp('period_end');

            // Amounts
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('amount_due', 10, 2);
            $table->decimal('amount_refunded', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            // Proration
            $table->boolean('is_proration')->default(false);
            $table->decimal('proration_amount', 10, 2)->nullable();

            // Usage Charges (for metered billing)
            $table->decimal('usage_charges', 10, 2')->default(0);
            $table->json('usage_details')->nullable(); // Breakdown of usage charges

            // Payment
            $table->foreignId('transaction_id')->nullable()->constrained('commerce_transactions')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_date')->nullable();

            // Retry Logic
            $table->integer('attempt_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->text('last_error')->nullable();

            // PDF
            $table->string('pdf_url')->nullable();

            // Metadata
            $table->json('line_items')->nullable(); // Snapshot of items at time of invoice
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'customer_id']);
            $table->index(['subscription_id']);
            $table->index(['status']);
            $table->index(['invoice_number']);
            $table->index(['due_date']);
            $table->index(['next_retry_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_subscription_invoices');
    }
};
