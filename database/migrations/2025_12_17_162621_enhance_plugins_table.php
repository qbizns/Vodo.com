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
        Schema::table('plugins', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('plugins', 'homepage')) {
                $table->string('homepage', 500)->nullable()->after('author_url');
            }
            if (!Schema::hasColumn('plugins', 'category')) {
                $table->string('category', 100)->nullable()->after('status');
            }
            if (!Schema::hasColumn('plugins', 'icon')) {
                $table->string('icon', 500)->nullable()->after('category');
            }
            if (!Schema::hasColumn('plugins', 'is_core')) {
                $table->boolean('is_core')->default(false)->after('icon');
            }
            if (!Schema::hasColumn('plugins', 'is_premium')) {
                $table->boolean('is_premium')->default(false)->after('is_core');
            }
            if (!Schema::hasColumn('plugins', 'requires_license')) {
                $table->boolean('requires_license')->default(false)->after('is_premium');
            }
            if (!Schema::hasColumn('plugins', 'min_system_version')) {
                $table->string('min_system_version', 20)->nullable()->after('requires_license');
            }
            if (!Schema::hasColumn('plugins', 'min_php_version')) {
                $table->string('min_php_version', 20)->nullable()->after('min_system_version');
            }
            if (!Schema::hasColumn('plugins', 'namespace')) {
                $table->string('namespace')->nullable()->after('path');
            }
            if (!Schema::hasColumn('plugins', 'entry_class')) {
                $table->string('entry_class')->nullable()->after('namespace');
            }
            if (!Schema::hasColumn('plugins', 'checksum')) {
                $table->string('checksum', 64)->nullable()->after('entry_class');
            }
            if (!Schema::hasColumn('plugins', 'error_message')) {
                $table->text('error_message')->nullable()->after('checksum');
            }
            if (!Schema::hasColumn('plugins', 'installed_at')) {
                $table->timestamp('installed_at')->nullable()->after('activated_at');
            }
        });

        // Modify status column to include 'updating' if needed
        // Note: This requires raw SQL for enum modification in MySQL
        if (Schema::hasColumn('plugins', 'status')) {
            \DB::statement("ALTER TABLE plugins MODIFY COLUMN status ENUM('active', 'inactive', 'error', 'updating') DEFAULT 'inactive'");
        }

        // Add indexes
        Schema::table('plugins', function (Blueprint $table) {
            if (!$this->hasIndex('plugins', 'idx_plugins_status')) {
                $table->index('status', 'idx_plugins_status');
            }
            if (!$this->hasIndex('plugins', 'idx_plugins_category')) {
                $table->index('category', 'idx_plugins_category');
            }
            if (!$this->hasIndex('plugins', 'idx_plugins_is_core')) {
                $table->index('is_core', 'idx_plugins_is_core');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plugins', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_plugins_status');
            $table->dropIndex('idx_plugins_category');
            $table->dropIndex('idx_plugins_is_core');

            // Drop columns
            $columns = [
                'homepage', 'category', 'icon', 'is_core', 'is_premium',
                'requires_license', 'min_system_version', 'min_php_version',
                'namespace', 'entry_class', 'checksum', 'error_message', 'installed_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('plugins', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Check if an index exists on a table.
     */
    protected function hasIndex(string $table, string $index): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $existingIndex) {
            if ($existingIndex['name'] === $index) {
                return true;
            }
        }
        return false;
    }
};
