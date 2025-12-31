<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255)->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->string('request_path');
            $table->string('request_hash', 64);
            $table->string('status', 20)->default('processing'); // processing, completed, failed
            $table->integer('response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->string('resource_type', 50)->nullable(); // order, customer, etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Unique key per store
            $table->unique(['key', 'store_id']);

            // Index for cleanup job
            $table->index('expires_at');

            // Index for finding resources
            $table->index(['resource_type', 'resource_id']);

            // Foreign key
            $table->foreign('store_id')
                ->references('id')
                ->on('commerce_stores')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_idempotency_keys');
    }
};
