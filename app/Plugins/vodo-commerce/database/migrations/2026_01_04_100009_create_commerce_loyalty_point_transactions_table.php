<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_loyalty_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loyalty_point_id');
            $table->enum('type', ['earned', 'spent', 'expired', 'adjusted']);
            $table->integer('points');
            $table->integer('balance_after');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('loyalty_point_id')->references('id')->on('commerce_loyalty_points')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('commerce_orders')->nullOnDelete();
            $table->index(['loyalty_point_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_loyalty_point_transactions');
    }
};
