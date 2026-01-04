<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_customer_group_memberships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('group_id');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('commerce_customers')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('commerce_customer_groups')->cascadeOnDelete();
            $table->unique(['customer_id', 'group_id']);
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_customer_group_memberships');
    }
};
