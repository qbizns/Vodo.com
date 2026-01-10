<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_vendor_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('commerce_vendors')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('commerce_orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('commerce_order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('commerce_products')->cascadeOnDelete();
            $table->foreignId('payout_id')->nullable()->constrained('commerce_vendor_payouts')->nullOnDelete();

            // Financial Details
            $table->decimal('item_subtotal', 10, 2); // Item price × quantity
            $table->decimal('item_discount', 10, 2)->default(0); // Discount applied
            $table->decimal('item_tax', 10, 2)->default(0); // Tax amount
            $table->decimal('item_total', 10, 2); // Final item total after discount and tax

            // Commission Calculation
            $table->enum('commission_type', ['flat', 'percentage', 'tiered'])->default('percentage');
            $table->decimal('commission_rate', 10, 2); // Percentage or flat amount
            $table->decimal('commission_amount', 10, 2); // Calculated commission
            $table->decimal('platform_fee', 10, 2)->default(0); // Platform takes this
            $table->decimal('vendor_earnings', 10, 2); // What vendor gets (item_total - commission - platform_fee)

            // Status
            $table->enum('status', ['pending', 'approved', 'paid', 'disputed', 'refunded', 'cancelled'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('disputed_at')->nullable();

            // Dispute Information
            $table->text('dispute_reason')->nullable();
            $table->text('dispute_resolution')->nullable();
            $table->timestamp('dispute_resolved_at')->nullable();

            // Refund Tracking
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();

            // Notes
            $table->text('notes')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['vendor_id', 'status']);
            $table->index(['order_id']);
            $table->index(['payout_id']);
            $table->index(['status', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_vendor_commissions');
    }
};
