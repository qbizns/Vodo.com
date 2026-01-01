<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // OAuth Applications (third-party apps)
        Schema::create('commerce_oauth_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('client_id', 64)->unique();
            $table->string('client_secret_hash', 64);
            $table->json('redirect_uris');
            $table->json('scopes')->nullable();
            $table->string('status', 20)->default('active'); // active, suspended, revoked
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index('client_id');
        });

        // OAuth Access Tokens
        Schema::create('commerce_oauth_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')
                ->constrained('commerce_oauth_applications')
                ->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('refresh_token_hash', 64)->nullable()->unique();
            $table->json('scopes')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('refresh_expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['token_hash', 'revoked']);
            $table->index(['refresh_token_hash', 'revoked']);
            $table->index(['application_id', 'revoked']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_oauth_access_tokens');
        Schema::dropIfExists('commerce_oauth_applications');
    }
};
