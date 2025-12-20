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
        Schema::dropIfExists('plugin_updates');
        Schema::create('plugin_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained()->cascadeOnDelete();
            $table->string('current_version', 20);
            $table->string('latest_version', 20);
            $table->text('changelog')->nullable();
            $table->string('download_url', 500)->nullable();
            $table->unsignedBigInteger('package_size')->nullable();
            $table->string('requires_system_version', 20)->nullable();
            $table->string('requires_php_version', 20)->nullable();
            $table->boolean('is_security_update')->default(false);
            $table->boolean('is_breaking_change')->default(false);
            $table->date('release_date')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            
            $table->unique('plugin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_updates');
    }
};
