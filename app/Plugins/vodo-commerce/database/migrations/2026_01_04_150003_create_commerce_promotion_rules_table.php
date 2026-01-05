<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Allows creating complex multi-condition promotion rules.
     * Example: "Cart must contain 2 items from Brand X AND total > $100"
     */
    public function up(): void
    {
        Schema::create('commerce_promotion_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discount_id');
            $table->string('rule_type');
            // Options: 'cart_subtotal', 'cart_quantity', 'product_quantity', 'category_quantity',
            //          'brand_quantity', 'customer_group', 'customer_tag', 'shipping_country',
            //          'shipping_state', 'day_of_week', 'time_of_day'

            $table->string('operator');
            // Options: 'equals', 'not_equals', 'greater_than', 'less_than', 'greater_or_equal',
            //          'less_or_equal', 'contains', 'not_contains', 'in', 'not_in'

            $table->text('value');
            // The value to compare against (can be JSON for complex values)

            $table->json('metadata')->nullable();
            // Additional configuration for the rule

            $table->integer('position')->default(0);
            // Order of evaluation

            $table->timestamps();

            $table->foreign('discount_id')->references('id')->on('commerce_discounts')->cascadeOnDelete();

            $table->index(['discount_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commerce_promotion_rules');
    }
};
