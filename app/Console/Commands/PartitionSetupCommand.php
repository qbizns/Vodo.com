<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Partition Setup Command
 *
 * Phase 1, Task 1.3: Table Partitioning Strategy
 *
 * This command sets up partitioning for high-volume tables.
 * It should be run once during initial setup and after adding new tables.
 *
 * Usage:
 *   php artisan partition:setup              # Setup all configured tables
 *   php artisan partition:setup audit_logs   # Setup specific table
 *   php artisan partition:setup --dry-run    # Preview without executing
 */
class PartitionSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'partition:setup
                            {table? : Specific table to partition}
                            {--dry-run : Preview the SQL without executing}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     */
    protected $description = 'Setup table partitioning for high-volume tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check database driver
        $driver = config('database.default');
        if (!in_array($driver, ['mysql', 'mariadb'])) {
            $this->error("Partitioning is only supported on MySQL/MariaDB. Current driver: {$driver}");
            return Command::FAILURE;
        }

        // Get partition schedules
        $query = DB::table('partition_schedules')->where('is_active', true);

        if ($table = $this->argument('table')) {
            $query->where('table_name', $table);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->warn('No partition schedules found. Run migrations first.');
            return Command::SUCCESS;
        }

        $this->info('');
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║           TABLE PARTITIONING SETUP                            ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->info('');

        foreach ($schedules as $schedule) {
            $this->processTable($schedule);
        }

        return Command::SUCCESS;
    }

    /**
     * Process partitioning for a single table.
     */
    protected function processTable(object $schedule): void
    {
        $table = $schedule->table_name;

        $this->info("Processing: {$table}");
        $this->info(str_repeat('─', 60));

        // Check if table exists
        if (!Schema::hasTable($table)) {
            $this->warn("  ⏭ Table does not exist, skipping");
            return;
        }

        // Check if already partitioned
        if ($this->isPartitioned($table)) {
            $this->info("  ✓ Already partitioned");
            $this->showPartitionInfo($table);
            return;
        }

        // Check for foreign key constraints TO this table
        $fkConstraints = $this->getForeignKeysTo($table);
        if (!empty($fkConstraints)) {
            $this->warn("  ⚠ Table has incoming foreign keys:");
            foreach ($fkConstraints as $fk) {
                $this->warn("    - {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} → {$table}");
            }
            $this->warn("  These must be removed before partitioning.");

            if (!$this->option('force')) {
                return;
            }
        }

        // Generate partition SQL
        $sql = $this->generatePartitionSQL($schedule);

        if ($this->option('dry-run')) {
            $this->info("  📝 DRY RUN - SQL that would be executed:");
            $this->line('');
            $this->line($sql);
            $this->line('');
            return;
        }

        // Confirm before proceeding
        if (!$this->option('force')) {
            $rowCount = DB::table($table)->count();
            $this->warn("  ⚠ This will restructure {$rowCount} rows.");
            $this->warn("  ⚠ Ensure you have a backup before proceeding.");

            if (!$this->confirm('  Continue with partitioning?')) {
                $this->info('  ⏭ Skipped');
                return;
            }
        }

        // Execute partitioning
        try {
            $this->info("  🔄 Applying partitioning...");

            DB::statement($sql);

            // Update last maintenance timestamp
            DB::table('partition_schedules')
                ->where('table_name', $table)
                ->update([
                    'last_maintenance_at' => now(),
                    'next_maintenance_at' => $this->getNextMaintenanceDate($schedule),
                ]);

            // Sync partition metadata
            $this->syncPartitionMetadata($table);

            $this->info("  ✓ Partitioning complete");
            $this->showPartitionInfo($table);

        } catch (\Exception $e) {
            $this->error("  ✗ Failed: " . $e->getMessage());
        }
    }

    /**
     * Check if a table is already partitioned.
     */
    protected function isPartitioned(string $table): bool
    {
        $database = config('database.connections.mysql.database');

        $result = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND PARTITION_NAME IS NOT NULL
        ", [$database, $table]);

        return ($result[0]->count ?? 0) > 0;
    }

    /**
     * Get foreign keys pointing to this table.
     */
    protected function getForeignKeysTo(string $table): array
    {
        $database = config('database.connections.mysql.database');

        return DB::select("
            SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA = ?
              AND REFERENCED_TABLE_NAME = ?
        ", [$database, $table]);
    }

    /**
     * Generate the partition ALTER TABLE statement.
     */
    protected function generatePartitionSQL(object $schedule): string
    {
        $table = $schedule->table_name;
        $column = $schedule->partition_column;
        $interval = $schedule->interval;

        $partitions = $this->generatePartitionDefinitions($interval);

        $sql = "ALTER TABLE `{$table}` PARTITION BY RANGE (";

        // Use TO_DAYS for date columns
        if (in_array($column, ['created_at', 'updated_at', 'published_at', 'transitioned_at'])) {
            $sql .= "TO_DAYS(`{$column}`)";
        } else {
            $sql .= "`{$column}`";
        }

        $sql .= ") (\n";
        $sql .= implode(",\n", $partitions);
        $sql .= "\n)";

        return $sql;
    }

    /**
     * Generate partition definitions based on interval.
     */
    protected function generatePartitionDefinitions(string $interval): array
    {
        $partitions = [];
        $now = now();

        switch ($interval) {
            case 'monthly':
                // Create partitions for past 12 months and next 3 months
                for ($i = -12; $i <= 3; $i++) {
                    $date = $now->copy()->addMonths($i)->startOfMonth();
                    $name = 'p' . $date->format('Ym');
                    $lessThan = $date->copy()->addMonth()->format('Y-m-d');
                    $partitions[] = "    PARTITION {$name} VALUES LESS THAN (TO_DAYS('{$lessThan}'))";
                }
                break;

            case 'yearly':
                // Create partitions for past 5 years and next 2 years
                for ($i = -5; $i <= 2; $i++) {
                    $year = $now->year + $i;
                    $name = 'p' . $year;
                    $lessThan = ($year + 1) . '-01-01';
                    $partitions[] = "    PARTITION {$name} VALUES LESS THAN (TO_DAYS('{$lessThan}'))";
                }
                break;

            case 'daily':
                // Create partitions for past 30 days and next 7 days
                for ($i = -30; $i <= 7; $i++) {
                    $date = $now->copy()->addDays($i)->startOfDay();
                    $name = 'p' . $date->format('Ymd');
                    $lessThan = $date->copy()->addDay()->format('Y-m-d');
                    $partitions[] = "    PARTITION {$name} VALUES LESS THAN (TO_DAYS('{$lessThan}'))";
                }
                break;
        }

        // Always add a MAXVALUE partition for future data
        $partitions[] = "    PARTITION pmax VALUES LESS THAN MAXVALUE";

        return $partitions;
    }

    /**
     * Get the next maintenance date based on interval.
     */
    protected function getNextMaintenanceDate(object $schedule): \DateTime
    {
        return match ($schedule->interval) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };
    }

    /**
     * Sync partition metadata from information_schema.
     */
    protected function syncPartitionMetadata(string $table): void
    {
        $database = config('database.connections.mysql.database');

        $partitions = DB::select("
            SELECT
                PARTITION_NAME,
                PARTITION_METHOD,
                PARTITION_EXPRESSION,
                PARTITION_DESCRIPTION,
                PARTITION_ORDINAL_POSITION,
                TABLE_ROWS,
                DATA_LENGTH,
                INDEX_LENGTH
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND PARTITION_NAME IS NOT NULL
            ORDER BY PARTITION_ORDINAL_POSITION
        ", [$database, $table]);

        foreach ($partitions as $p) {
            DB::table('partition_metadata')->updateOrInsert(
                [
                    'table_name' => $table,
                    'partition_name' => $p->PARTITION_NAME,
                ],
                [
                    'partition_type' => 'RANGE',
                    'partition_column' => 'created_at',
                    'partition_expression' => $p->PARTITION_EXPRESSION,
                    'partition_description' => $p->PARTITION_DESCRIPTION,
                    'partition_ordinal_position' => $p->PARTITION_ORDINAL_POSITION,
                    'partition_method' => $p->PARTITION_METHOD,
                    'table_rows' => $p->TABLE_ROWS,
                    'data_length' => $p->DATA_LENGTH,
                    'index_length' => $p->INDEX_LENGTH,
                    'last_analyzed_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Display partition information for a table.
     */
    protected function showPartitionInfo(string $table): void
    {
        $database = config('database.connections.mysql.database');

        $partitions = DB::select("
            SELECT
                PARTITION_NAME,
                TABLE_ROWS,
                ROUND(DATA_LENGTH / 1024 / 1024, 2) as data_mb
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND PARTITION_NAME IS NOT NULL
            ORDER BY PARTITION_ORDINAL_POSITION
            LIMIT 5
        ", [$database, $table]);

        $this->info("  Partitions (showing first 5):");
        foreach ($partitions as $p) {
            $this->info("    - {$p->PARTITION_NAME}: {$p->TABLE_ROWS} rows, {$p->data_mb} MB");
        }
    }
}
