<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Partition Maintenance Command
 *
 * Phase 1, Task 1.3: Table Partitioning Strategy
 *
 * This command performs ongoing partition maintenance:
 * - Creates future partitions before they're needed
 * - Archives/drops old partitions based on retention policy
 * - Updates partition metadata
 *
 * Should be scheduled to run daily:
 *   $schedule->command('partition:maintain')->daily();
 *
 * Usage:
 *   php artisan partition:maintain              # Maintain all tables
 *   php artisan partition:maintain audit_logs   # Maintain specific table
 *   php artisan partition:maintain --dry-run    # Preview without executing
 */
class PartitionMaintainCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'partition:maintain
                            {table? : Specific table to maintain}
                            {--dry-run : Preview actions without executing}
                            {--archive : Archive old partitions instead of dropping}';

    /**
     * The console command description.
     */
    protected $description = 'Maintain table partitions (create future, archive old)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $driver = config('database.default');
        if (!in_array($driver, ['mysql', 'mariadb'])) {
            $this->error("Partitioning is only supported on MySQL/MariaDB.");
            return Command::FAILURE;
        }

        $query = DB::table('partition_schedules')->where('is_active', true);

        if ($table = $this->argument('table')) {
            $query->where('table_name', $table);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->info('No partition schedules found.');
            return Command::SUCCESS;
        }

        $this->info('');
        $this->info('Partition Maintenance - ' . now()->format('Y-m-d H:i:s'));
        $this->info(str_repeat('═', 60));

        foreach ($schedules as $schedule) {
            $this->maintainTable($schedule);
        }

        return Command::SUCCESS;
    }

    /**
     * Maintain partitions for a single table.
     */
    protected function maintainTable(object $schedule): void
    {
        $table = $schedule->table_name;
        $this->info("\n{$table}:");

        // Check if table is partitioned
        if (!$this->isPartitioned($table)) {
            $this->warn("  ⏭ Not partitioned, skipping");
            return;
        }

        // Create future partitions
        $created = $this->createFuturePartitions($schedule);
        $this->info("  ✓ Created {$created} future partition(s)");

        // Handle old partitions
        if ($schedule->retention_periods) {
            $handled = $this->handleOldPartitions($schedule);
            $action = $this->option('archive') || $schedule->auto_archive ? 'archived' : 'dropped';
            $this->info("  ✓ {$handled} old partition(s) {$action}");
        }

        // Update metadata
        $this->syncPartitionMetadata($table);

        // Update schedule
        DB::table('partition_schedules')
            ->where('table_name', $table)
            ->update([
                'last_maintenance_at' => now(),
                'next_maintenance_at' => $this->getNextMaintenanceDate($schedule),
            ]);

        $this->info("  ✓ Maintenance complete");
    }

    /**
     * Create future partitions.
     */
    protected function createFuturePartitions(object $schedule): int
    {
        $table = $schedule->table_name;
        $interval = $schedule->interval;
        $preCreate = $schedule->pre_create_periods;

        $existingPartitions = $this->getExistingPartitions($table);
        $created = 0;

        // Generate partition names we should have
        $neededPartitions = $this->getNeededFuturePartitions($interval, $preCreate);

        foreach ($neededPartitions as $partition) {
            if (in_array($partition['name'], $existingPartitions)) {
                continue;
            }

            if ($partition['name'] === 'pmax') {
                continue; // MAXVALUE partition already exists
            }

            $sql = $this->getAddPartitionSQL($table, $partition);

            if ($this->option('dry-run')) {
                $this->line("    Would add: {$partition['name']}");
            } else {
                try {
                    // Need to reorganize the MAXVALUE partition
                    $reorganizeSql = $this->getReorganizePartitionSQL($table, $partition);
                    DB::statement($reorganizeSql);
                    $created++;

                    Log::info("Partition created", [
                        'table' => $table,
                        'partition' => $partition['name'],
                    ]);
                } catch (\Exception $e) {
                    $this->error("    Failed to add {$partition['name']}: " . $e->getMessage());
                }
            }
        }

        return $created;
    }

    /**
     * Handle old partitions (archive or drop).
     */
    protected function handleOldPartitions(object $schedule): int
    {
        $table = $schedule->table_name;
        $interval = $schedule->interval;
        $retention = $schedule->retention_periods;
        $archive = $this->option('archive') || $schedule->auto_archive;

        $cutoffDate = $this->getCutoffDate($interval, $retention);
        $partitionsToHandle = $this->getPartitionsOlderThan($table, $cutoffDate);
        $handled = 0;

        foreach ($partitionsToHandle as $partitionName) {
            if ($partitionName === 'pmax') {
                continue;
            }

            if ($this->option('dry-run')) {
                $action = $archive ? 'archive' : 'drop';
                $this->line("    Would {$action}: {$partitionName}");
                continue;
            }

            try {
                if ($archive) {
                    $this->archivePartition($table, $partitionName, $schedule->archive_table_suffix);
                } else {
                    DB::statement("ALTER TABLE `{$table}` DROP PARTITION `{$partitionName}`");
                }

                // Update metadata
                DB::table('partition_metadata')
                    ->where('table_name', $table)
                    ->where('partition_name', $partitionName)
                    ->update(['is_archived' => true]);

                $handled++;

                Log::info("Partition handled", [
                    'table' => $table,
                    'partition' => $partitionName,
                    'action' => $archive ? 'archived' : 'dropped',
                ]);

            } catch (\Exception $e) {
                $this->error("    Failed to handle {$partitionName}: " . $e->getMessage());
            }
        }

        return $handled;
    }

    /**
     * Archive a partition by moving data to archive table.
     */
    protected function archivePartition(string $table, string $partitionName, string $suffix): void
    {
        $archiveTable = $table . $suffix;

        // Create archive table if not exists
        DB::statement("CREATE TABLE IF NOT EXISTS `{$archiveTable}` LIKE `{$table}`");

        // Remove partitioning from archive table
        try {
            DB::statement("ALTER TABLE `{$archiveTable}` REMOVE PARTITIONING");
        } catch (\Exception $e) {
            // Already not partitioned
        }

        // Exchange partition with archive table
        // This is atomic and instant
        DB::statement("ALTER TABLE `{$table}` EXCHANGE PARTITION `{$partitionName}` WITH TABLE `{$archiveTable}`");

        // Now drop the empty partition
        DB::statement("ALTER TABLE `{$table}` DROP PARTITION `{$partitionName}`");
    }

    /**
     * Get needed future partitions.
     */
    protected function getNeededFuturePartitions(string $interval, int $preCreate): array
    {
        $partitions = [];
        $now = now();

        for ($i = 0; $i <= $preCreate; $i++) {
            switch ($interval) {
                case 'monthly':
                    $date = $now->copy()->addMonths($i)->startOfMonth();
                    $partitions[] = [
                        'name' => 'p' . $date->format('Ym'),
                        'less_than' => $date->copy()->addMonth()->format('Y-m-d'),
                    ];
                    break;

                case 'yearly':
                    $year = $now->year + $i;
                    $partitions[] = [
                        'name' => 'p' . $year,
                        'less_than' => ($year + 1) . '-01-01',
                    ];
                    break;

                case 'daily':
                    $date = $now->copy()->addDays($i)->startOfDay();
                    $partitions[] = [
                        'name' => 'p' . $date->format('Ymd'),
                        'less_than' => $date->copy()->addDay()->format('Y-m-d'),
                    ];
                    break;
            }
        }

        return $partitions;
    }

    /**
     * Get existing partition names.
     */
    protected function getExistingPartitions(string $table): array
    {
        $database = config('database.connections.mysql.database');

        $result = DB::select("
            SELECT PARTITION_NAME
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND PARTITION_NAME IS NOT NULL
        ", [$database, $table]);

        return array_column($result, 'PARTITION_NAME');
    }

    /**
     * Get partitions older than cutoff date.
     */
    protected function getPartitionsOlderThan(string $table, \DateTime $cutoff): array
    {
        // Parse partition names to dates
        $partitions = $this->getExistingPartitions($table);
        $old = [];

        foreach ($partitions as $name) {
            if ($name === 'pmax') {
                continue;
            }

            // Extract date from partition name (p202501, p2025, p20250101)
            $dateStr = substr($name, 1); // Remove 'p' prefix

            try {
                if (strlen($dateStr) === 6) {
                    // Monthly: p202501
                    $date = \DateTime::createFromFormat('Ym', $dateStr);
                } elseif (strlen($dateStr) === 4) {
                    // Yearly: p2025
                    $date = \DateTime::createFromFormat('Y', $dateStr);
                } elseif (strlen($dateStr) === 8) {
                    // Daily: p20250101
                    $date = \DateTime::createFromFormat('Ymd', $dateStr);
                } else {
                    continue;
                }

                if ($date && $date < $cutoff) {
                    $old[] = $name;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $old;
    }

    /**
     * Get cutoff date based on retention policy.
     */
    protected function getCutoffDate(string $interval, int $retention): \DateTime
    {
        $now = now();

        return match ($interval) {
            'daily' => $now->subDays($retention),
            'monthly' => $now->subMonths($retention),
            'yearly' => $now->subYears($retention),
            default => $now->subMonths($retention),
        };
    }

    /**
     * Generate SQL to add a partition.
     */
    protected function getAddPartitionSQL(string $table, array $partition): string
    {
        return "ALTER TABLE `{$table}` ADD PARTITION (
            PARTITION `{$partition['name']}` VALUES LESS THAN (TO_DAYS('{$partition['less_than']}'))
        )";
    }

    /**
     * Generate SQL to reorganize MAXVALUE partition.
     */
    protected function getReorganizePartitionSQL(string $table, array $partition): string
    {
        return "ALTER TABLE `{$table}` REORGANIZE PARTITION pmax INTO (
            PARTITION `{$partition['name']}` VALUES LESS THAN (TO_DAYS('{$partition['less_than']}')),
            PARTITION pmax VALUES LESS THAN MAXVALUE
        )";
    }

    /**
     * Check if a table is partitioned.
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
     * Sync partition metadata.
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
     * Get next maintenance date.
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
}
