<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('commerce_stores')->cascadeOnDelete();
            $table->string('transfer_number')->unique();
            $table->foreignId('from_location_id')->constrained('commerce_inventory_locations')->cascadeOnDelete();
            $table->foreignId('to_location_id')->constrained('commerce_inventory_locations')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, in_transit, completed, cancelled
            $table->text('notes')->nullable();
            $table->string('requested_by_type')->nullable();
            $table->unsignedBigInteger('requested_by_id')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->string('approved_by_type')->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['from_location_id', 'status']);
            $table->index(['to_location_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_stock_transfers');
    }
};
