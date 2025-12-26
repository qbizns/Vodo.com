<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add Partition Support for High-Volume Tables
 *
 * Phase 1, Task 1.3: Table Partitioning Strategy
 *
 * This migration prepares high-volume tables for partitioning by:
 * 1. Adding partition key columns where needed
 * 2. Creating helper tables for partition management
 * 3. Setting up the partition metadata tracking
 *
 * IMPORTANT: Actual partitioning must be done manually via the
 * `php artisan partition:setup` command, as Laravel migrations
 * don't natively support partition DDL.
 *
 * Tables targeted for partitioning:
 * - audit_logs: Partition by month (RANGE on created_at)
 * - entity_records: Partition by year (RANGE on created_at)
 * - workflow_history: Partition by month (RANGE on created_at)
 * - messages: Partition by month (RANGE on created_at)
 *
 * Prerequisites:
 * - MySQL 8.0+ or MariaDB 10.2+
 * - InnoDB storage engine
 * - No foreign keys TO partitioned tables (FROM is OK)
 */
return new class extends Migration
{
    /**
     * Tables to partition and their strategies.
     */
    protected array $partitionConfig = [
        'audit_logs' => [
            'type' => 'RANGE',
            'column' => 'created_at',
            'interval' => 'monthly',
            'retention_months' => 12,
            'description' => 'Audit logs partitioned by month for easy archival',
        ],
        'entity_records' => [
            'type' => 'RANGE',
            'column' => 'created_at',
            'interval' => 'yearly',
            'retention_months' => null, // Keep forever
            'description' => 'Entity records partitioned by year for query optimization',
        ],
        'workflow_history' => [
            'type' => 'RANGE',
            'column' => 'created_at',
            'interval' => 'monthly',
            'retention_months' => 24,
            'description' => 'Workflow history partitioned by month',
        ],
        'messages' => [
            'type' => 'RANGE',
            'column' => 'created_at',
            'interval' => 'monthly',
            'retention_months' => 36,
            'description' => 'Messages partitioned by month',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create partition management table
        if (!Schema::hasTable('partition_metadata')) {
            Schema::create('partition_metadata', function ($table) {
                $table->id();
                $table->string('table_name', 64)->index();
                $table->string('partition_name', 64);
                $table->string('partition_type', 20); // RANGE, LIST, HASH
                $table->string('partition_column', 64);
                $table->string('partition_expression')->nullable();
                $table->string('partition_description')->nullable();
                $table->bigInteger('partition_ordinal_position')->nullable();
                $table->string('partition_method', 20)->nullable();
                $table->bigInteger('table_rows')->nullable();
                $table->bigInteger('data_length')->nullable();
                $table->bigInteger('index_length')->nullable();
                $table->timestamp('partition_created_at')->nullable();
                $table->timestamp('last_analyzed_at')->nullable();
                $table->boolean('is_archived')->default(false);
                $table->timestamps();

                $table->unique(['table_name', 'partition_name']);
            });
        }

        // Create partition schedule table (for automated partition management)
        if (!Schema::hasTable('partition_schedules')) {
            Schema::create('partition_schedules', function ($table) {
                $table->id();
                $table->string('table_name', 64)->unique();
                $table->string('partition_type', 20);
                $table->string('partition_column', 64);
                $table->string('interval', 20); // daily, weekly, monthly, yearly
                $table->integer('retention_periods')->nullable(); // Number of periods to keep
                $table->integer('pre_create_periods')->default(3); // Periods to create in advance
                $table->boolean('auto_archive')->default(false);
                $table->string('archive_table_suffix')->default('_archived');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_maintenance_at')->nullable();
                $table->timestamp('next_maintenance_at')->nullable();
                $table->json('config')->nullable();
                $table->timestamps();
            });
        }

        // Insert partition schedules for configured tables
        foreach ($this->partitionConfig as $table => $config) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            // Check if schedule already exists
            $exists = DB::table('partition_schedules')
                ->where('table_name', $table)
                ->exists();

            if (!$exists) {
                DB::table('partition_schedules')->insert([
                    'table_name' => $table,
                    'partition_type' => $config['type'],
                    'partition_column' => $config['column'],
                    'interval' => $config['interval'],
                    'retention_periods' => $config['retention_months'],
                    'pre_create_periods' => 3,
                    'auto_archive' => $config['retention_months'] !== null,
                    'is_active' => true,
                    'config' => json_encode([
                        'description' => $config['description'],
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Log instructions for manual partitioning
        if (app()->runningInConsole()) {
            echo "\n";
            echo "═══════════════════════════════════════════════════════════════\n";
            echo "  PARTITION SETUP INSTRUCTIONS\n";
            echo "═══════════════════════════════════════════════════════════════\n";
            echo "\n";
            echo "  Partition metadata tables have been created.\n";
            echo "  To enable partitioning on your tables, run:\n";
            echo "\n";
            echo "    php artisan partition:setup\n";
            echo "\n";
            echo "  This command will:\n";
            echo "  1. Backup affected tables\n";
            echo "  2. Create partitioned versions\n";
            echo "  3. Migrate data\n";
            echo "  4. Swap tables atomically\n";
            echo "\n";
            echo "  For ongoing partition maintenance, add to your scheduler:\n";
            echo "\n";
            echo "    \$schedule->command('partition:maintain')->daily();\n";
            echo "\n";
            echo "═══════════════════════════════════════════════════════════════\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partition_schedules');
        Schema::dropIfExists('partition_metadata');
    }
};
