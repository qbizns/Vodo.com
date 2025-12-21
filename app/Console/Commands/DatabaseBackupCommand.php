<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Database Backup Command
 *
 * Creates encrypted database backups and uploads to configured storage.
 * Supports MySQL/MariaDB with optional compression and encryption.
 *
 * Usage:
 *   php artisan backup:database              # Create backup
 *   php artisan backup:database --no-encrypt # Without encryption
 *   php artisan backup:database --upload     # Upload to cloud storage
 */
class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:database
                            {--no-compress : Skip compression}
                            {--no-encrypt : Skip encryption}
                            {--upload : Upload to configured storage disk}
                            {--cleanup : Remove old local backups}
                            {--retention=30 : Days to retain backups}';

    /**
     * The console command description.
     */
    protected $description = 'Create a database backup with optional compression and encryption';

    /**
     * Backup directory path.
     */
    protected string $backupPath;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->backupPath = storage_path('backups/database');

        // Ensure backup directory exists
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        $this->info('Starting database backup...');

        try {
            // Generate backup filename
            $timestamp = now()->format('Y-m-d_H-i-s');
            $database = config('database.connections.' . config('database.default') . '.database');
            $filename = "backup_{$database}_{$timestamp}";

            // Step 1: Create SQL dump
            $sqlFile = $this->createDump($filename);
            if (!$sqlFile) {
                return Command::FAILURE;
            }

            // Step 2: Compress if enabled
            $finalFile = $sqlFile;
            if (!$this->option('no-compress')) {
                $finalFile = $this->compress($sqlFile);
                if ($finalFile !== $sqlFile) {
                    unlink($sqlFile); // Remove uncompressed file
                }
            }

            // Step 3: Encrypt if enabled
            if (!$this->option('no-encrypt')) {
                $encryptedFile = $this->encrypt($finalFile);
                if ($encryptedFile) {
                    unlink($finalFile); // Remove unencrypted file
                    $finalFile = $encryptedFile;
                }
            }

            // Step 4: Upload if requested
            if ($this->option('upload')) {
                $this->uploadToStorage($finalFile);
            }

            // Step 5: Cleanup old backups if requested
            if ($this->option('cleanup')) {
                $this->cleanupOldBackups();
            }

            // Log success
            $size = $this->formatBytes(filesize($finalFile));
            $this->info("Backup completed successfully!");
            $this->info("File: {$finalFile}");
            $this->info("Size: {$size}");

            Log::info('Database backup completed', [
                'file' => basename($finalFile),
                'size' => filesize($finalFile),
                'database' => $database,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Create database dump using mysqldump.
     */
    protected function createDump(string $filename): ?string
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($connection !== 'mysql') {
            $this->error("Only MySQL/MariaDB is supported for backups. Current: {$connection}");
            return null;
        }

        $outputFile = "{$this->backupPath}/{$filename}.sql";

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1',
            escapeshellarg($config['host']),
            escapeshellarg((string) $config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($outputFile)
        );

        $this->info('Creating database dump...');
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('mysqldump failed: ' . implode("\n", $output));
            return null;
        }

        if (!file_exists($outputFile) || filesize($outputFile) === 0) {
            $this->error('Dump file is empty or missing');
            return null;
        }

        $this->info('Database dump created: ' . $this->formatBytes(filesize($outputFile)));

        return $outputFile;
    }

    /**
     * Compress file using gzip.
     */
    protected function compress(string $file): string
    {
        $this->info('Compressing backup...');

        $compressedFile = $file . '.gz';

        // Use gzip command for better performance on large files
        $command = sprintf('gzip -9 -c %s > %s', escapeshellarg($file), escapeshellarg($compressedFile));
        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($compressedFile)) {
            $this->warn('Compression failed, using uncompressed file');
            return $file;
        }

        $originalSize = filesize($file);
        $compressedSize = filesize($compressedFile);
        $ratio = round((1 - $compressedSize / $originalSize) * 100, 1);

        $this->info("Compressed: {$this->formatBytes($originalSize)} -> {$this->formatBytes($compressedSize)} ({$ratio}% reduction)");

        return $compressedFile;
    }

    /**
     * Encrypt file using OpenSSL.
     */
    protected function encrypt(string $file): ?string
    {
        $key = config('app.key');
        if (!$key) {
            $this->warn('No APP_KEY set, skipping encryption');
            return null;
        }

        $this->info('Encrypting backup...');

        $encryptedFile = $file . '.enc';

        // Use OpenSSL for encryption (AES-256-CBC)
        $command = sprintf(
            'openssl enc -aes-256-cbc -salt -pbkdf2 -in %s -out %s -pass pass:%s 2>&1',
            escapeshellarg($file),
            escapeshellarg($encryptedFile),
            escapeshellarg(base64_decode(str_replace('base64:', '', $key)))
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($encryptedFile)) {
            $this->warn('Encryption failed: ' . implode("\n", $output));
            return null;
        }

        $this->info('Backup encrypted successfully');

        return $encryptedFile;
    }

    /**
     * Upload backup to configured storage disk.
     */
    protected function uploadToStorage(string $file): void
    {
        $disk = config('backup.disk', 's3');
        $path = config('backup.path', 'backups/database');

        $this->info("Uploading to {$disk} storage...");

        try {
            $filename = basename($file);
            $destination = "{$path}/{$filename}";

            Storage::disk($disk)->put($destination, fopen($file, 'r'));

            $this->info("Uploaded to: {$destination}");

            Log::info('Backup uploaded to storage', [
                'disk' => $disk,
                'path' => $destination,
            ]);
        } catch (\Exception $e) {
            $this->error("Upload failed: {$e->getMessage()}");
        }
    }

    /**
     * Clean up old backup files.
     */
    protected function cleanupOldBackups(): void
    {
        $retention = (int) $this->option('retention');
        $cutoff = now()->subDays($retention)->timestamp;

        $this->info("Cleaning up backups older than {$retention} days...");

        $deleted = 0;
        foreach (glob("{$this->backupPath}/backup_*") as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} old backup(s)");
    }

    /**
     * Format bytes to human-readable size.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
