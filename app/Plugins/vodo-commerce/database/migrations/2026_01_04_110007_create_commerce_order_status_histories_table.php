<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->string('changed_by_type');
            $table->unsignedBigInteger('changed_by_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('commerce_orders')->cascadeOnDelete();
            $table->index(['order_id', 'created_at']);
            $table->index('new_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_order_status_histories');
    }
};
