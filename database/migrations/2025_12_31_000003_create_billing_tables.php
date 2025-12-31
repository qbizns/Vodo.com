<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payment methods stored per tenant
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('gateway'); // stripe, moyasar
            $table->string('gateway_customer_id')->nullable();
            $table->string('gateway_payment_method_id');
            $table->string('type'); // card, bank_account, apple_pay
            $table->string('brand')->nullable(); // visa, mastercard, mada
            $table->string('last_four', 4)->nullable();
            $table->string('exp_month', 2)->nullable();
            $table->string('exp_year', 4)->nullable();
            $table->string('holder_name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_default']);
        });

        // Developer payment accounts for receiving payouts
        Schema::create('developer_payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('developer_id')->unique();
            $table->string('gateway'); // stripe_connect, moyasar, bank_transfer
            $table->string('gateway_account_id')->nullable();
            $table->string('account_status')->default('pending'); // pending, verified, suspended
            $table->string('country_code', 2)->default('SA');
            $table->string('currency', 3)->default('SAR');
            $table->decimal('commission_rate', 5, 4)->default(0.2000); // 20% platform fee
            $table->json('payout_schedule')->nullable(); // weekly, monthly, threshold
            $table->decimal('minimum_payout', 10, 2)->default(100.00);
            $table->json('bank_details')->nullable(); // encrypted bank info
            $table->json('tax_info')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('account_status');
        });

        // Invoices for plugin purchases/subscriptions
        Schema::create('marketplace_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('subscription_id')->nullable()->index();
            $table->string('status')->default('draft'); // draft, pending, paid, failed, refunded, void
            $table->string('currency', 3)->default('SAR');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0.15); // 15% VAT
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('billing_period')->nullable(); // monthly, yearly
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->json('billing_address')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('due_date');
        });

        // Invoice line items
        Schema::create('marketplace_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('marketplace_invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('listing_id')->nullable()->index();
            $table->string('description');
            $table->string('type'); // subscription, one_time, usage, addon
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Payment transactions
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('invoice_id')->nullable()->constrained('marketplace_invoices');
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods');
            $table->string('gateway'); // stripe, moyasar
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_charge_id')->nullable();
            $table->string('type'); // charge, refund, payout
            $table->string('status'); // pending, processing, succeeded, failed, refunded
            $table->string('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 12, 2)->default(0); // gateway fee
            $table->decimal('net_amount', 12, 2); // amount - fee
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            $table->json('gateway_response')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'gateway_transaction_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('type');
        });

        // Revenue splits for each transaction
        Schema::create('revenue_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('payment_transactions')->cascadeOnDelete();
            $table->unsignedBigInteger('listing_id')->index();
            $table->unsignedBigInteger('developer_id')->index();
            $table->string('currency', 3);
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('platform_fee', 12, 2);
            $table->decimal('platform_fee_rate', 5, 4);
            $table->decimal('gateway_fee', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('developer_amount', 12, 2);
            $table->string('status')->default('pending'); // pending, available, paid
            $table->foreignId('payout_id')->nullable()->constrained('developer_payouts');
            $table->timestamps();

            $table->index(['developer_id', 'status']);
        });

        // Developer payouts
        Schema::create('developer_payouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('payout_number')->unique();
            $table->unsignedBigInteger('developer_id')->index();
            $table->foreignId('payment_account_id')->constrained('developer_payment_accounts');
            $table->string('gateway');
            $table->string('gateway_payout_id')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('currency', 3);
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('fees', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2);
            $table->integer('items_count')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('failure_reason')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['developer_id', 'status']);
            $table->index('payout_number');
        });

        // Usage records for metered billing
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('marketplace_subscriptions')->cascadeOnDelete();
            $table->unsignedBigInteger('listing_id')->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('metric'); // api_calls, storage_gb, users, transactions
            $table->decimal('quantity', 15, 4);
            $table->string('unit')->default('count');
            $table->decimal('unit_price', 12, 6)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->boolean('invoiced')->default(false);
            $table->foreignId('invoice_item_id')->nullable()->constrained('marketplace_invoice_items');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'metric', 'period_start']);
            $table->index(['tenant_id', 'listing_id', 'metric']);
        });

        // Discount codes
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // percentage, fixed_amount
            $table->decimal('value', 12, 2);
            $table->string('currency', 3)->nullable(); // null for percentage
            $table->string('applies_to')->default('all'); // all, specific_listings, categories
            $table->json('applicable_ids')->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('uses_count')->default(0);
            $table->integer('max_uses_per_tenant')->nullable();
            $table->decimal('minimum_amount', 12, 2)->nullable();
            $table->decimal('maximum_discount', 12, 2)->nullable();
            $table->boolean('first_purchase_only')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['code', 'is_active']);
            $table->index('expires_at');
        });

        // Discount code usage tracking
        Schema::create('discount_code_uses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_code_id')->constrained('discount_codes')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('marketplace_invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->decimal('discount_amount', 12, 2);
            $table->timestamps();

            $table->unique(['discount_code_id', 'invoice_id']);
        });

        // Refund requests
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('transaction_id')->constrained('payment_transactions');
            $table->foreignId('invoice_id')->constrained('marketplace_invoices');
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('listing_id')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, processed
            $table->string('reason');
            $table->text('description')->nullable();
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->string('gateway_refund_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Add foreign key for revenue_splits -> developer_payouts after both tables exist
        // Already added inline above
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('discount_code_uses');
        Schema::dropIfExists('discount_codes');
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('developer_payouts');
        Schema::dropIfExists('revenue_splits');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('marketplace_invoice_items');
        Schema::dropIfExists('marketplace_invoices');
        Schema::dropIfExists('developer_payment_accounts');
        Schema::dropIfExists('payment_methods');
    }
};
