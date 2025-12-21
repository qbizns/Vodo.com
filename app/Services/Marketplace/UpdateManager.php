<?php

namespace App\Services\Marketplace;

use App\Models\InstalledPlugin;
use App\Models\PluginUpdate;
use App\Models\PluginUpdateHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class UpdateManager
{
    protected MarketplaceClient $client;
    protected LicenseManager $licenseManager;
    protected string $backupPath;
    protected string $tempPath;

    public function __construct(MarketplaceClient $client, LicenseManager $licenseManager)
    {
        $this->client = $client;
        $this->licenseManager = $licenseManager;
        $this->backupPath = storage_path('plugin-backups');
        $this->tempPath = storage_path('plugin-temp');
    }

    // =========================================================================
    // Update Checking
    // =========================================================================

    /**
     * Check for updates for all plugins
     */
    public function checkAll(): array
    {
        $plugins = InstalledPlugin::fromMarketplace()->get();
        
        if ($plugins->isEmpty()) {
            return [];
        }

        $pluginData = $plugins->map(fn($p) => [
            'marketplace_id' => $p->marketplace_id,
            'slug' => $p->slug,
            'version' => $p->version,
        ])->toArray();

        try {
            $response = $this->client->checkUpdates($pluginData);
            $updates = $response['updates'] ?? [];

            foreach ($updates as $update) {
                $this->recordUpdate($update);
            }

            // Update last check time
            InstalledPlugin::whereIn('marketplace_id', array_column($pluginData, 'marketplace_id'))
                ->update(['last_update_check' => now()]);

            return $updates;

        } catch (\Exception $e) {
            Log::error("Failed to check for updates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check for updates for a specific plugin
     */
    public function check(InstalledPlugin $plugin): ?PluginUpdate
    {
        if (!$plugin->marketplace_id) {
            return null;
        }

        try {
            $response = $this->client->checkUpdates([[
                'marketplace_id' => $plugin->marketplace_id,
                'slug' => $plugin->slug,
                'version' => $plugin->version,
            ]]);

            $updates = $response['updates'] ?? [];

            if (empty($updates)) {
                $plugin->markUpdateChecked();
                return null;
            }

            $update = $this->recordUpdate($updates[0]);
            $plugin->markUpdateChecked();

            return $update;

        } catch (\Exception $e) {
            Log::error("Failed to check update for {$plugin->slug}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Record an available update
     */
    protected function recordUpdate(array $data): PluginUpdate
    {
        $plugin = InstalledPlugin::findByMarketplaceId($data['marketplace_id']);

        if (!$plugin) {
            throw new \RuntimeException("Plugin not found: {$data['marketplace_id']}");
        }

        return PluginUpdate::updateOrCreate(
            [
                'plugin_id' => $plugin->id,
            ],
            [
                'current_version' => $plugin->version,
                'latest_version' => $data['version'],
                'changelog' => $data['changelog'] ?? null,
                'download_url' => $data['download_url'] ?? null,
                'package_size' => $data['package_size'] ?? null,
                'requires_php_version' => $data['requires_php'] ?? null,
                'requires_system_version' => $data['requires_system'] ?? null,
                'is_security_update' => $data['is_security_update'] ?? false,
                'is_breaking_change' => $data['is_breaking_change'] ?? false,
                'release_date' => isset($data['released_at']) ? \Carbon\Carbon::parse($data['released_at'])->toDateString() : null,
                'checked_at' => now(),
            ]
        );
    }

    // =========================================================================
    // Update Installation
    // =========================================================================

    /**
     * Install an update
     */
    public function install(InstalledPlugin $plugin, ?PluginUpdate $update = null): array
    {
        $update = $update ?? $plugin->pendingUpdate;

        if (!$update) {
            return ['success' => false, 'error' => 'No update available'];
        }

        // Check requirements
        $issues = $update->meetsRequirements();
        if (!empty($issues)) {
            return ['success' => false, 'error' => 'Requirements not met', 'issues' => $issues];
        }

        // Check license for premium plugins
        if ($update->requires_license && !$this->licenseManager->canUpdate($plugin)) {
            return ['success' => false, 'error' => 'Valid license required for updates'];
        }

        // Start update history
        $history = PluginUpdateHistory::recordStart($plugin, $plugin->version, $update->latest_version);

        try {
            // 1. Create backup
            $backupFile = $this->createBackup($plugin);
            $history->backup_path = $backupFile;
            $history->save();

            // 2. Download package
            $packageFile = $this->downloadPackage($plugin, $update);

            // 3. Verify package
            if ($update->package_hash && !$this->verifyPackage($packageFile, $update->package_hash)) {
                throw new \RuntimeException('Package verification failed');
            }

            // 4. Deactivate plugin
            $wasActive = $plugin->isActive();
            if ($wasActive) {
                $plugin->deactivate();
                $this->runPluginMethod($plugin, 'deactivate');
            }

            // 5. Extract and install
            $this->extractPackage($packageFile, $plugin->install_path);

            // 6. Run migrations
            $this->runPluginMigrations($plugin);

            // 7. Run update hook
            $this->runPluginMethod($plugin, 'update', [$plugin->version, $update->latest_version]);

            // 8. Update version
            $plugin->version = $update->latest_version;
            $plugin->save();

            // 9. Reactivate if was active
            if ($wasActive) {
                $plugin->activate();
                $this->runPluginMethod($plugin, 'activate');
            }

            // 10. Mark update as installed
            $update->markInstalled();

            // 11. Record success
            $history->recordSuccess('Update installed successfully');

            // Cleanup
            $this->cleanup($packageFile);

            if (function_exists('do_action')) {
                do_action('plugin_updated', $plugin, $update);
            }

            return [
                'success' => true,
                'message' => "Updated to version {$update->latest_version}",
                'from_version' => $history->from_version,
                'to_version' => $history->to_version,
            ];

        } catch (\Exception $e) {
            Log::error("Update failed for {$plugin->slug}: " . $e->getMessage());

            // Attempt rollback
            if (isset($backupFile) && File::exists($backupFile)) {
                $this->rollback($plugin, $backupFile);
                $history->recordRollback("Update failed, rolled back: " . $e->getMessage());
            } else {
                $history->recordFailure($e->getMessage());
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'rolled_back' => isset($backupFile),
            ];
        }
    }

    /**
     * Rollback to backup
     */
    public function rollback(InstalledPlugin $plugin, string $backupFile): bool
    {
        try {
            // Remove current version
            File::deleteDirectory($plugin->install_path);

            // Extract backup
            $this->extractPackage($backupFile, $plugin->install_path);

            // Restore version from backup manifest
            $manifest = $this->readManifest($plugin->install_path);
            if (isset($manifest['version'])) {
                $plugin->version = $manifest['version'];
                $plugin->save();
            }

            Log::info("Rolled back {$plugin->slug} to previous version");

            return true;

        } catch (\Exception $e) {
            Log::error("Rollback failed for {$plugin->slug}: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // Bulk Operations
    // =========================================================================

    /**
     * Update all plugins with available updates
     */
    public function updateAll(): array
    {
        $results = [];
        $plugins = InstalledPlugin::hasUpdate()->get();

        foreach ($plugins as $plugin) {
            $results[$plugin->slug] = $this->install($plugin);
        }

        return $results;
    }

    /**
     * Update security-critical plugins only
     */
    public function updateSecurity(): array
    {
        $results = [];
        $updates = PluginUpdate::pending()->security()->with('plugin')->get();

        foreach ($updates as $update) {
            $results[$update->plugin->slug] = $this->install($update->plugin, $update);
        }

        return $results;
    }

    // =========================================================================
    // File Operations
    // =========================================================================

    protected function createBackup(InstalledPlugin $plugin): string
    {
        $this->ensureDirectory($this->backupPath);

        $backupFile = $this->backupPath . '/' . $plugin->slug . '-' . $plugin->version . '-' . time() . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException("Failed to create backup archive");
        }

        $this->addDirectoryToZip($zip, $plugin->install_path, '');
        $zip->close();

        return $backupFile;
    }

    protected function downloadPackage(InstalledPlugin $plugin, PluginUpdate $update): string
    {
        $this->ensureDirectory($this->tempPath);

        $packageFile = $this->tempPath . '/' . $plugin->slug . '-' . $update->latest_version . '.zip';

        // Get download URL (may require license)
        $downloadUrl = $update->download_url;

        if (!$downloadUrl) {
            $license = $plugin->license;
            $downloadUrl = $this->client->getDownloadUrl(
                $plugin->marketplace_id,
                $update->latest_version,
                $license?->license_key
            );
        }

        if (!$downloadUrl) {
            throw new \RuntimeException("Could not get download URL");
        }

        if (!$this->client->downloadPackage($downloadUrl, $packageFile)) {
            throw new \RuntimeException("Failed to download package");
        }

        return $packageFile;
    }

    protected function verifyPackage(string $file, string $expectedHash): bool
    {
        $actualHash = hash_file('sha256', $file);
        return hash_equals($expectedHash, $actualHash);
    }

    protected function extractPackage(string $file, string $destination): void
    {
        $zip = new ZipArchive();

        if ($zip->open($file) !== true) {
            throw new \RuntimeException("Failed to open package");
        }

        // Remove existing directory
        if (File::isDirectory($destination)) {
            File::deleteDirectory($destination);
        }

        File::makeDirectory($destination, 0755, true);
        $zip->extractTo($destination);
        $zip->close();
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $path, string $relativePath): void
    {
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $zipPath = $relativePath . '/' . $file->getRelativePathname();
            $zip->addFile($filePath, ltrim($zipPath, '/'));
        }
    }

    protected function cleanup(string $file): void
    {
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    protected function ensureDirectory(string $path): void
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    // =========================================================================
    // Plugin Methods
    // =========================================================================

    protected function runPluginMethod(InstalledPlugin $plugin, string $method, array $args = []): void
    {
        $instance = $plugin->getInstance();

        if ($instance && method_exists($instance, $method)) {
            $instance->{$method}(...$args);
        }
    }

    protected function runPluginMigrations(InstalledPlugin $plugin): void
    {
        $migrationsPath = $plugin->install_path . '/database/migrations';

        if (File::isDirectory($migrationsPath)) {
            \Artisan::call('migrate', [
                '--path' => str_replace(base_path(), '', $migrationsPath),
                '--force' => true,
            ]);
        }
    }

    protected function readManifest(string $path): array
    {
        $manifestFile = $path . '/plugin.json';

        if (!File::exists($manifestFile)) {
            return [];
        }

        return json_decode(File::get($manifestFile), true) ?? [];
    }

    // =========================================================================
    // Statistics
    // =========================================================================

    public function getUpdateSummary(): array
    {
        return [
            'pending' => PluginUpdate::pending()->count(),
            'security' => PluginUpdate::pending()->security()->count(),
            'critical' => PluginUpdate::pending()->critical()->count(),
            'installed_today' => PluginUpdateHistory::whereDate('created_at', today())
                ->where('status', PluginUpdateHistory::STATUS_SUCCESS)->count(),
        ];
    }

    public function getPendingUpdates(): Collection
    {
        return PluginUpdate::pending()->with('plugin')->get();
    }

    public function getUpdateHistory(int $limit = 20): Collection
    {
        return PluginUpdateHistory::with('plugin')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
