<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_marketplace_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();

            // Setting Key-Value
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'float', 'boolean', 'json', 'array'])->default('string');

            // Categorization
            $table->string('group')->nullable()->index(); // e.g., 'commission', 'approval', 'payout'
            $table->text('description')->nullable();

            // Metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Unique constraint
            $table->unique(['store_id', 'key']);
        });

        // Insert default settings
        DB::table('commerce_marketplace_settings')->insert([
            [
                'store_id' => 1,
                'key' => 'default_commission_rate',
                'value' => '15.00',
                'type' => 'float',
                'group' => 'commission',
                'description' => 'Default commission percentage charged to vendors',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'store_id' => 1,
                'key' => 'vendor_approval_required',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'approval',
                'description' => 'Whether vendor applications require admin approval',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'store_id' => 1,
                'key' => 'product_approval_required',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'approval',
                'description' => 'Whether vendor products require admin approval',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'store_id' => 1,
                'key' => 'minimum_payout_amount',
                'value' => '50.00',
                'type' => 'float',
                'group' => 'payout',
                'description' => 'Minimum amount required for vendor payout',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'store_id' => 1,
                'key' => 'payout_schedule',
                'value' => 'monthly',
                'type' => 'string',
                'group' => 'payout',
                'description' => 'Default payout schedule (daily, weekly, biweekly, monthly)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'store_id' => 1,
                'key' => 'allow_vendor_coupons',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Whether vendors can create their own coupon codes',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'store_id' => 1,
                'key' => 'vendor_can_manage_shipping',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'features',
                'description' => 'Whether vendors can configure their own shipping methods',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_marketplace_settings');
    }
};
