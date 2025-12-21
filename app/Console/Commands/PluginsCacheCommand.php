<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Plugins\PluginCacheManager;
use Illuminate\Console\Command;

/**
 * Rebuild the plugin provider cache.
 * 
 * Usage:
 *   php artisan plugins:cache          # Rebuild cache
 *   php artisan plugins:cache --clear  # Clear cache without rebuilding
 *   php artisan plugins:cache --status # Show cache status
 */
class PluginsCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'plugins:cache 
                            {--clear : Clear the cache without rebuilding}
                            {--status : Show cache status}
                            {--force : Force rebuild even if cache exists}';

    /**
     * The console command description.
     */
    protected $description = 'Rebuild the plugin provider cache for faster loading';

    /**
     * Execute the console command.
     */
    public function handle(PluginCacheManager $cacheManager): int
    {
        // Handle --status flag
        if ($this->option('status')) {
            return $this->showStatus($cacheManager);
        }

        // Handle --clear flag
        if ($this->option('clear')) {
            return $this->clearCache($cacheManager);
        }

        // Rebuild cache
        return $this->rebuildCache($cacheManager);
    }

    /**
     * Show cache status.
     */
    protected function showStatus(PluginCacheManager $cacheManager): int
    {
        $metadata = $cacheManager->getMetadata();

        $this->info('Plugin Cache Status');
        $this->line('');

        if (!$metadata['exists']) {
            $this->warn('Cache file does not exist.');
            $this->line("Path: {$metadata['path']}");
            $this->line('');
            $this->line('Run <comment>php artisan plugins:cache</comment> to create it.');
            return 0;
        }

        $this->table(
            ['Property', 'Value'],
            [
                ['Status', '<info>Active</info>'],
                ['Path', $metadata['path']],
                ['Generated At', $metadata['generated_at']],
                ['File Size', $this->formatBytes($metadata['file_size'])],
                ['Last Modified', $metadata['file_mtime']],
                ['Plugin Count', $metadata['plugin_count']],
            ]
        );

        if (!empty($metadata['plugins'])) {
            $this->line('');
            $this->info('Cached Plugins:');
            foreach ($metadata['plugins'] as $slug) {
                $this->line("  • {$slug}");
            }
        }

        // Check if rebuild is needed
        if ($cacheManager->needsRebuild()) {
            $this->line('');
            $this->warn('Cache may be stale - consider running: php artisan plugins:cache');
        }

        return 0;
    }

    /**
     * Clear the cache.
     */
    protected function clearCache(PluginCacheManager $cacheManager): int
    {
        $this->info('Clearing plugin cache...');

        if ($cacheManager->clear()) {
            $this->info('✓ Plugin cache cleared.');
            return 0;
        }

        $this->error('Failed to clear plugin cache.');
        return 1;
    }

    /**
     * Rebuild the cache.
     */
    protected function rebuildCache(PluginCacheManager $cacheManager): int
    {
        // Check if cache exists and --force not provided
        if (!$this->option('force') && $cacheManager->exists() && !$cacheManager->needsRebuild()) {
            $this->info('Cache already exists and appears current.');
            $this->line('Use <comment>--force</comment> to rebuild anyway, or <comment>--status</comment> to view details.');
            return 0;
        }

        $this->info('Rebuilding plugin cache...');

        $startTime = microtime(true);

        try {
            if ($cacheManager->rebuild()) {
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $metadata = $cacheManager->getMetadata();

                $this->info('✓ Plugin cache rebuilt successfully.');
                $this->line('');
                $this->line("  Plugins cached: <info>{$metadata['plugin_count']}</info>");
                $this->line("  Time: <info>{$duration}ms</info>");
                $this->line("  Path: <comment>{$metadata['path']}</comment>");

                return 0;
            }

            $this->error('Failed to rebuild plugin cache.');
            return 1;

        } catch (\Throwable $e) {
            $this->error("Error rebuilding cache: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Format bytes to human-readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
    }
}
