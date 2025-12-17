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
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');              // owner, admin, console
            $table->unsignedBigInteger('user_id');
            $table->string('dashboard');              // 'main' or plugin slug
            $table->string('widget_id');              // unique widget identifier
            $table->string('plugin_slug')->nullable(); // source plugin
            $table->integer('position')->default(0);
            $table->integer('col')->default(0);       // column position (0-3)
            $table->integer('row')->default(0);       // row position
            $table->integer('width')->default(1);     // 1-4 columns
            $table->integer('height')->default(1);    // 1-3 rows
            $table->json('settings')->nullable();     // widget-specific settings
            $table->boolean('visible')->default(true);
            $table->timestamps();
            
            $table->unique(['user_type', 'user_id', 'dashboard', 'widget_id'], 'dashboard_widget_unique');
            $table->index(['user_type', 'user_id', 'dashboard'], 'dashboard_user_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
