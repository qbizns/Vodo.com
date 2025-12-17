<?php

namespace App\Services\Marketplace;

use App\Models\InstalledPlugin;
use App\Models\MarketplacePlugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class PluginManager
{
    protected MarketplaceClient $client;
    protected LicenseManager $licenseManager;
    protected UpdateManager $updateManager;
    protected string $pluginsPath;
    protected string $tempPath;

    public function __construct(
        MarketplaceClient $client,
        LicenseManager $licenseManager,
        UpdateManager $updateManager
    ) {
        $this->client = $client;
        $this->licenseManager = $licenseManager;
        $this->updateManager = $updateManager;
        $this->pluginsPath = config('marketplace.plugins_path', base_path('plugins'));
        $this->tempPath = storage_path('plugin-temp');
    }

    // =========================================================================
    // Installation
    // =========================================================================

    /**
     * Install a plugin from marketplace
     */
    public function installFromMarketplace(string $marketplaceId, ?string $licenseKey = null): array
    {
        // Get plugin info
        $pluginInfo = $this->client->getPlugin($marketplaceId);
        if (!$pluginInfo) {
            return ['success' => false, 'error' => 'Plugin not found in marketplace'];
        }

        // Check if already installed
        if (InstalledPlugin::findByMarketplaceId($marketplaceId)) {
            return ['success' => false, 'error' => 'Plugin is already installed'];
        }

        // Check if premium and license required
        $isPremium = ($pluginInfo['price'] ?? 0) > 0;
        if ($isPremium && !$licenseKey) {
            return ['success' => false, 'error' => 'License key required for premium plugin'];
        }

        try {
            // Get download URL
            $downloadUrl = $this->client->getDownloadUrl(
                $marketplaceId,
                $pluginInfo['latest_version'],
                $licenseKey
            );

            if (!$downloadUrl) {
                return ['success' => false, 'error' => 'Could not get download URL'];
            }

            // Download package
            $packageFile = $this->downloadPackage($downloadUrl, $pluginInfo['slug']);

            // Install from package
            $result = $this->installFromPackage($packageFile, [
                'marketplace_id' => $marketplaceId,
                'marketplace_url' => $pluginInfo['url'] ?? null,
                'price' => $pluginInfo['price'] ?? 0,
                'is_premium' => $isPremium,
                'is_verified' => $pluginInfo['is_verified'] ?? false,
            ]);

            // Cleanup
            $this->cleanup($packageFile);

            // Activate license if premium
            if ($result['success'] && $isPremium && $licenseKey) {
                $plugin = InstalledPlugin::findBySlug($pluginInfo['slug']);
                $this->licenseManager->activate($plugin, $licenseKey, config('marketplace.email', ''));
            }

            return $result;

        } catch (\Exception $e) {
            Log::error("Failed to install from marketplace: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Install a plugin from uploaded package
     */
    public function installFromPackage(string $packageFile, array $metadata = []): array
    {
        try {
            // Extract to temp location
            $tempDir = $this->tempPath . '/extract-' . uniqid();
            $this->extractPackage($packageFile, $tempDir);

            // Read manifest
            $manifest = $this->readManifest($tempDir);
            if (empty($manifest['slug'])) {
                $this->cleanup($tempDir);
                return ['success' => false, 'error' => 'Invalid plugin package: missing manifest'];
            }

            // Check if already installed
            if (InstalledPlugin::findBySlug($manifest['slug'])) {
                $this->cleanup($tempDir);
                return ['success' => false, 'error' => 'Plugin is already installed'];
            }

            // Check requirements
            $requirements = $this->checkRequirements($manifest);
            if (!empty($requirements)) {
                $this->cleanup($tempDir);
                return ['success' => false, 'error' => 'Requirements not met', 'requirements' => $requirements];
            }

            // Move to plugins directory
            $installPath = $this->pluginsPath . '/' . $manifest['slug'];
            File::moveDirectory($tempDir, $installPath);

            // Register plugin
            $plugin = InstalledPlugin::create([
                'slug' => $manifest['slug'],
                'name' => $manifest['name'] ?? $manifest['slug'],
                'description' => $manifest['description'] ?? null,
                'version' => $manifest['version'] ?? '1.0.0',
                'author' => $manifest['author'] ?? null,
                'author_url' => $manifest['author_url'] ?? null,
                'homepage' => $manifest['homepage'] ?? null,
                'install_path' => $installPath,
                'entry_class' => $manifest['entry_class'] ?? $this->guessEntryClass($manifest['slug']),
                'dependencies' => $manifest['dependencies'] ?? [],
                'requirements' => $manifest['requirements'] ?? [],
                'status' => InstalledPlugin::STATUS_INACTIVE,
                'is_premium' => $metadata['is_premium'] ?? false,
                'is_verified' => $metadata['is_verified'] ?? false,
                'marketplace_id' => $metadata['marketplace_id'] ?? null,
                'marketplace_url' => $metadata['marketplace_url'] ?? null,
                'price' => $metadata['price'] ?? null,
                'installed_at' => now(),
            ]);

            // Run install hook
            $this->runPluginMethod($plugin, 'install');

            if (function_exists('do_action')) {
                do_action('plugin_installed', $plugin);
            }

            return [
                'success' => true,
                'plugin' => $plugin,
                'message' => "Plugin {$plugin->name} installed successfully",
            ];

        } catch (\Exception $e) {
            Log::error("Plugin installation failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // Activation
    // =========================================================================

    /**
     * Activate a plugin
     */
    public function activate(InstalledPlugin $plugin): array
    {
        if ($plugin->isActive()) {
            return ['success' => true, 'message' => 'Plugin is already active'];
        }

        // Check license for premium
        if ($plugin->is_premium && !$plugin->hasValidLicense()) {
            return ['success' => false, 'error' => 'Valid license required to activate premium plugin'];
        }

        // Check dependencies
        $missing = $this->checkDependencies($plugin);
        if (!empty($missing)) {
            return ['success' => false, 'error' => 'Missing dependencies', 'dependencies' => $missing];
        }

        try {
            // Run migrations
            $this->runPluginMigrations($plugin);

            // Run activate hook
            $this->runPluginMethod($plugin, 'activate');

            // Update status
            $plugin->activate();

            if (function_exists('do_action')) {
                do_action('plugin_activated', $plugin);
            }

            return ['success' => true, 'message' => "Plugin {$plugin->name} activated"];

        } catch (\Exception $e) {
            Log::error("Plugin activation failed: " . $e->getMessage());
            $plugin->markError($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Deactivate a plugin
     */
    public function deactivate(InstalledPlugin $plugin): array
    {
        if (!$plugin->isActive()) {
            return ['success' => true, 'message' => 'Plugin is already inactive'];
        }

        // Check dependents
        $dependents = $this->findDependents($plugin);
        if (!empty($dependents)) {
            return ['success' => false, 'error' => 'Other plugins depend on this', 'dependents' => $dependents];
        }

        try {
            // Run deactivate hook
            $this->runPluginMethod($plugin, 'deactivate');

            // Update status
            $plugin->deactivate();

            if (function_exists('do_action')) {
                do_action('plugin_deactivated', $plugin);
            }

            return ['success' => true, 'message' => "Plugin {$plugin->name} deactivated"];

        } catch (\Exception $e) {
            Log::error("Plugin deactivation failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // Uninstallation
    // =========================================================================

    /**
     * Uninstall a plugin
     */
    public function uninstall(InstalledPlugin $plugin, bool $deleteData = false): array
    {
        // Deactivate first
        if ($plugin->isActive()) {
            $result = $this->deactivate($plugin);
            if (!$result['success']) {
                return $result;
            }
        }

        try {
            // Run uninstall hook
            if ($deleteData) {
                $this->runPluginMethod($plugin, 'uninstall');
            }

            // Deactivate license
            if ($plugin->license) {
                $this->licenseManager->deactivate($plugin);
            }

            // Delete files
            if (File::isDirectory($plugin->install_path)) {
                File::deleteDirectory($plugin->install_path);
            }

            // Delete record
            $pluginName = $plugin->name;
            $plugin->delete();

            if (function_exists('do_action')) {
                do_action('plugin_uninstalled', $pluginName);
            }

            return ['success' => true, 'message' => "Plugin {$pluginName} uninstalled"];

        } catch (\Exception $e) {
            Log::error("Plugin uninstallation failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // Queries
    // =========================================================================

    public function getAll(): Collection
    {
        return InstalledPlugin::with('license')->get();
    }

    public function getActive(): Collection
    {
        return InstalledPlugin::active()->get();
    }

    public function getInactive(): Collection
    {
        return InstalledPlugin::inactive()->get();
    }

    public function getWithUpdates(): Collection
    {
        return InstalledPlugin::hasUpdate()->get();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    protected function downloadPackage(string $url, string $slug): string
    {
        $this->ensureDirectory($this->tempPath);
        $packageFile = $this->tempPath . '/' . $slug . '-' . time() . '.zip';

        if (!$this->client->downloadPackage($url, $packageFile)) {
            throw new \RuntimeException("Failed to download package");
        }

        return $packageFile;
    }

    protected function extractPackage(string $file, string $destination): void
    {
        $zip = new ZipArchive();

        if ($zip->open($file) !== true) {
            throw new \RuntimeException("Failed to open package");
        }

        $this->ensureDirectory($destination);
        $zip->extractTo($destination);
        $zip->close();
    }

    protected function readManifest(string $path): array
    {
        $files = ['plugin.json', 'composer.json', 'manifest.json'];

        foreach ($files as $file) {
            $manifestFile = $path . '/' . $file;
            if (File::exists($manifestFile)) {
                return json_decode(File::get($manifestFile), true) ?? [];
            }
        }

        return [];
    }

    protected function checkRequirements(array $manifest): array
    {
        $issues = [];

        if (isset($manifest['require']['php'])) {
            if (!$this->versionSatisfies(PHP_VERSION, $manifest['require']['php'])) {
                $issues[] = "Requires PHP {$manifest['require']['php']}";
            }
        }

        return $issues;
    }

    protected function checkDependencies(InstalledPlugin $plugin): array
    {
        $missing = [];

        foreach ($plugin->dependencies ?? [] as $dep => $version) {
            $depPlugin = InstalledPlugin::findBySlug($dep);
            if (!$depPlugin || !$depPlugin->isActive()) {
                $missing[] = $dep;
            }
        }

        return $missing;
    }

    protected function findDependents(InstalledPlugin $plugin): array
    {
        $dependents = [];

        $activePlugins = InstalledPlugin::active()->get();
        foreach ($activePlugins as $activePlugin) {
            $deps = $activePlugin->dependencies ?? [];
            if (isset($deps[$plugin->slug])) {
                $dependents[] = $activePlugin->slug;
            }
        }

        return $dependents;
    }

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

    protected function guessEntryClass(string $slug): string
    {
        $className = str_replace(['-', '_'], '', ucwords($slug, '-_'));
        return "Plugins\\{$className}\\{$className}Plugin";
    }

    protected function versionSatisfies(string $version, string $constraint): bool
    {
        // Simple version check (could use composer/semver for more complex)
        $constraint = ltrim($constraint, '^~>=<');
        return version_compare($version, $constraint, '>=');
    }

    protected function ensureDirectory(string $path): void
    {
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    protected function cleanup(string $path): void
    {
        if (File::isDirectory($path)) {
            File::deleteDirectory($path);
        } elseif (File::exists($path)) {
            File::delete($path);
        }
    }
}
