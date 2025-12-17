<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use App\Models\PluginMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PluginMigrator
{
    /**
     * Run all pending migrations for a plugin.
     *
     * @throws \Exception
     */
    public function runMigrations(Plugin $plugin): array
    {
        $migrationsPath = $plugin->getFullPath() . '/migrations';
        
        if (!File::isDirectory($migrationsPath)) {
            return [];
        }

        $files = $this->getMigrationFiles($migrationsPath);
        $ran = $this->getRanMigrations($plugin);
        $pending = array_diff(array_keys($files), $ran);

        if (empty($pending)) {
            return [];
        }

        $batch = PluginMigration::getNextBatch($plugin->id);
        $migrated = [];

        foreach ($pending as $migrationName) {
            $this->runMigration($plugin, $files[$migrationName], $migrationName, $batch);
            $migrated[] = $migrationName;
        }

        return $migrated;
    }

    /**
     * Run a single migration.
     *
     * @throws \Exception
     */
    protected function runMigration(Plugin $plugin, string $path, string $name, int $batch): void
    {
        Log::info("Running plugin migration: {$name} for plugin: {$plugin->slug}");

        try {
            $migration = $this->resolveMigration($path);
            
            // Run migration without wrapping in transaction
            // Laravel migrations handle transactions internally
            $migration->up();

            // Record migration after successful run
            PluginMigration::create([
                'plugin_id' => $plugin->id,
                'migration' => $name,
                'batch' => $batch,
            ]);
        } catch (\Throwable $e) {
            Log::error("Migration failed: {$name}", [
                'plugin' => $plugin->slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception("Migration '{$name}' failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Rollback the last batch of migrations for a plugin.
     */
    public function rollbackLastBatch(Plugin $plugin): array
    {
        $batch = PluginMigration::getLastBatch($plugin->id);
        
        if ($batch === 0) {
            return [];
        }

        return $this->rollbackBatch($plugin, $batch);
    }

    /**
     * Rollback a specific batch of migrations.
     */
    public function rollbackBatch(Plugin $plugin, int $batch): array
    {
        $migrations = PluginMigration::where('plugin_id', $plugin->id)
            ->where('batch', $batch)
            ->orderBy('id', 'desc')
            ->get();

        $rolledBack = [];
        $migrationsPath = $plugin->getFullPath() . '/migrations';

        foreach ($migrations as $migration) {
            $path = $migrationsPath . '/' . $migration->migration . '.php';
            
            if (File::exists($path)) {
                $this->rollbackMigration($path, $migration->migration);
            }

            $migration->delete();
            $rolledBack[] = $migration->migration;
        }

        return $rolledBack;
    }

    /**
     * Rollback all migrations for a plugin.
     */
    public function rollbackAllMigrations(Plugin $plugin): array
    {
        $migrations = PluginMigration::where('plugin_id', $plugin->id)
            ->orderBy('batch', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $rolledBack = [];
        $migrationsPath = $plugin->getFullPath() . '/migrations';

        foreach ($migrations as $migration) {
            $path = $migrationsPath . '/' . $migration->migration . '.php';
            
            if (File::exists($path)) {
                try {
                    $this->rollbackMigration($path, $migration->migration);
                } catch (\Throwable $e) {
                    Log::warning("Failed to rollback migration {$migration->migration}: " . $e->getMessage());
                }
            }

            $migration->delete();
            $rolledBack[] = $migration->migration;
        }

        return $rolledBack;
    }

    /**
     * Rollback a single migration.
     */
    protected function rollbackMigration(string $path, string $name): void
    {
        Log::info("Rolling back plugin migration: {$name}");

        $migration = $this->resolveMigration($path);
        
        // Run migration without wrapping in transaction
        // Laravel migrations handle transactions internally
        $migration->down();
    }

    /**
     * Get migration files from a directory.
     */
    protected function getMigrationFiles(string $path): array
    {
        $files = [];

        if (!File::isDirectory($path)) {
            return $files;
        }

        foreach (File::files($path) as $file) {
            if ($file->getExtension() === 'php') {
                $name = $file->getBasename('.php');
                $files[$name] = $file->getPathname();
            }
        }

        // Sort by name (which typically includes timestamp)
        ksort($files);

        return $files;
    }

    /**
     * Get already ran migrations for a plugin.
     */
    protected function getRanMigrations(Plugin $plugin): array
    {
        return PluginMigration::where('plugin_id', $plugin->id)
            ->pluck('migration')
            ->toArray();
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @throws \Exception
     */
    protected function resolveMigration(string $path): Migration
    {
        $migration = require $path;

        if ($migration instanceof Migration) {
            return $migration;
        }

        // For anonymous class migrations (Laravel 8+ style)
        if (is_object($migration)) {
            return $migration;
        }

        throw new \Exception("Migration file must return a Migration instance: {$path}");
    }

    /**
     * Get migration status for a plugin.
     */
    public function getMigrationStatus(Plugin $plugin): array
    {
        $migrationsPath = $plugin->getFullPath() . '/migrations';
        $files = $this->getMigrationFiles($migrationsPath);
        $ran = $this->getRanMigrations($plugin);

        $status = [];

        foreach ($files as $name => $path) {
            $status[] = [
                'name' => $name,
                'ran' => in_array($name, $ran),
                'batch' => $this->getMigrationBatch($plugin, $name),
            ];
        }

        return $status;
    }

    /**
     * Get the batch number for a specific migration.
     */
    protected function getMigrationBatch(Plugin $plugin, string $name): ?int
    {
        $migration = PluginMigration::where('plugin_id', $plugin->id)
            ->where('migration', $name)
            ->first();

        return $migration?->batch;
    }

    /**
     * Check if a plugin has pending migrations.
     */
    public function hasPendingMigrations(Plugin $plugin): bool
    {
        $migrationsPath = $plugin->getFullPath() . '/migrations';
        
        if (!File::isDirectory($migrationsPath)) {
            return false;
        }

        $files = $this->getMigrationFiles($migrationsPath);
        $ran = $this->getRanMigrations($plugin);

        return count(array_diff(array_keys($files), $ran)) > 0;
    }

    /**
     * Count pending migrations for a plugin.
     */
    public function countPendingMigrations(Plugin $plugin): int
    {
        $migrationsPath = $plugin->getFullPath() . '/migrations';
        
        if (!File::isDirectory($migrationsPath)) {
            return 0;
        }

        $files = $this->getMigrationFiles($migrationsPath);
        $ran = $this->getRanMigrations($plugin);

        return count(array_diff(array_keys($files), $ran));
    }
}
