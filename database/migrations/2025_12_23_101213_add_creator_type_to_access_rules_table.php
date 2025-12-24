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
        Schema::table('access_rules', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['created_by']);
            
            // Add creator_type column for polymorphic relationship
            $table->string('creator_type')->nullable()->after('retention_days');
            
            // Rename created_by to creator_id for clarity
            $table->renameColumn('created_by', 'creator_id');
        });
        
        // Update existing records to have creator_type
        DB::table('access_rules')
            ->whereNotNull('creator_id')
            ->update(['creator_type' => 'App\\Models\\User']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_rules', function (Blueprint $table) {
            // Drop creator_type
            $table->dropColumn('creator_type');
            
            // Rename back to created_by
            $table->renameColumn('creator_id', 'created_by');
            
            // Re-add the foreign key
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }
};
