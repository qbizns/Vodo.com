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
        Schema::dropIfExists('plugin_events');
        Schema::create('plugin_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plugin_slug', 100);
            $table->enum('event', [
                'installed', 'activated', 'deactivated', 'updated', 
                'uninstalled', 'error', 'settings_changed', 
                'license_activated', 'license_expired'
            ]);
            $table->string('version', 20)->nullable();
            $table->string('previous_version', 20)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('plugin_slug');
            $table->index('event');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_events');
    }
};
