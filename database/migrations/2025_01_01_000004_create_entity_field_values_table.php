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
        Schema::create('entity_field_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('record_id');
            $table->string('field_slug')->comment('Reference to entity_fields.slug');
            $table->longText('value')->nullable()->comment('Serialized field value');
            $table->timestamps();

            $table->foreign('record_id')
                ->references('id')
                ->on('entity_records')
                ->cascadeOnDelete();

            $table->unique(['record_id', 'field_slug']);
            $table->index('field_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_field_values');
    }
};
