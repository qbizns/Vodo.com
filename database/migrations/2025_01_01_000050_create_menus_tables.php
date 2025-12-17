<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('location', 50)->default('sidebar');
            $table->boolean('show_icons')->default(true);
            $table->boolean('show_badges')->default(true);
            $table->boolean('collapsible')->default(true);
            $table->json('roles')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('slug', 100);
            $table->string('label', 100);
            $table->string('title', 255)->nullable();
            $table->string('type', 20)->default('route');
            $table->string('route', 255)->nullable();
            $table->json('route_params')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('action', 255)->nullable();
            $table->string('target', 20)->default('_self');
            $table->string('icon', 100)->nullable();
            $table->string('icon_type', 20)->default('class');
            $table->string('badge_text', 50)->nullable();
            $table->string('badge_type', 20)->nullable();
            $table->string('badge_callback', 255)->nullable();
            $table->json('roles')->nullable();
            $table->json('permissions')->nullable();
            $table->string('visibility_callback', 255)->nullable();
            $table->json('active_patterns')->nullable();
            $table->string('active_callback', 255)->nullable();
            $table->integer('order')->default(0);
            $table->integer('depth')->default(0);
            $table->string('plugin_slug', 100)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['menu_id', 'slug']);
            $table->index('parent_id');
            $table->index('plugin_slug');
            $table->index(['menu_id', 'is_active', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
