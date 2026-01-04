<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_affiliate_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_id');
            $table->string('url');
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('affiliate_id')->references('id')->on('commerce_affiliates')->cascadeOnDelete();
            $table->index(['affiliate_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_affiliate_links');
    }
};
