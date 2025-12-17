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
        Schema::create('entity_records', function (Blueprint $table) {
            $table->id();
            $table->string('entity_name')->comment('Reference to entity_definitions.name');
            $table->string('title')->nullable()->comment('Primary title/name');
            $table->string('slug')->nullable()->comment('URL-friendly identifier');
            $table->longText('content')->nullable()->comment('Main content/description');
            $table->text('excerpt')->nullable()->comment('Short summary');
            $table->string('status')->default('draft')->comment('Record status (draft, published, archived, trash)');
            $table->unsignedBigInteger('author_id')->nullable()->comment('User who created the record');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Parent record (for hierarchical entities)');
            $table->integer('menu_order')->default(0)->comment('Custom ordering');
            $table->string('featured_image')->nullable()->comment('Featured image path');
            $table->timestamp('published_at')->nullable()->comment('When record was published');
            $table->json('meta')->nullable()->comment('Quick access metadata');
            $table->timestamps();
            $table->softDeletes();

            $table->index('entity_name');
            $table->index('status');
            $table->index('author_id');
            $table->index('parent_id');
            $table->index('published_at');
            $table->index(['entity_name', 'status']);
            $table->index(['entity_name', 'slug']);
            
            $table->foreign('parent_id')
                ->references('id')
                ->on('entity_records')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_records');
    }
};
