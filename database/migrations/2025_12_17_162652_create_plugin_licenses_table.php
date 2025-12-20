<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('plugin_licenses');
        Schema::create('plugin_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->string('license_key', 500);
            $table->enum('license_type', ['standard', 'professional', 'enterprise', 'lifetime'])->default('standard');
            $table->enum('status', ['active', 'expired', 'suspended', 'cancelled'])->default('active');
            $table->unsignedInteger('max_activations')->default(1);
            $table->unsignedInteger('current_activations')->default(0);
            $table->json('features')->nullable();
            $table->string('licensee_name')->nullable();
            $table->string('licensee_email')->nullable();
            $table->date('purchase_date')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_licenses');
    }
};
