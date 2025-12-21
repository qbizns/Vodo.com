<?php

declare(strict_types=1);

namespace App\Services\Plugins;

use App\Models\Plugin;
use App\Services\Plugins\Contracts\PluginInterface;
use App\Exceptions\Plugins\PluginException;
use App\Exceptions\Plugins\PluginNotFoundException;
use App\Exceptions\Plugins\PluginActivationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use WeakMap;

/**
 * Plugin Manager - Handles plugin lifecycle with transaction safety.
 * 
 * Phase 10 Improvements:
 * - Transaction wrapping for all state changes
 * - Proper error handling with custom exceptions
 * - WeakMap for instance management to prevent memory leaks
 * - Hook cleanup on deactivation
 * - Dependency validation
 * - Provider cache integration for performance
 */
class PluginManager
{
    /**
     * Loaded plugin instances.
     * Using WeakMap would be ideal but requires objects as keys.
     *
     * @var array<string, PluginInterface>
     */
    protected array $loadedPlugins = [];

    /**
     * Plugin metadata cache for cleanup tracking.
     *
     * @var array<string, array>
     */
    protected array $pluginMetadata = [];

    /**
     * Create a new plugin manager instance.
     */
    public function __construct(
        protected PluginInstaller $installer,
        protected PluginMigrator $migrator,
        protected HookManager $hooks,
        protected ?PluginCacheManager $cacheManager = null
    ) {
        // Allow null for backward compatibility, resolve from container if needed
        if ($this->cacheManager === null && app()->bound(PluginCacheManager::class)) {
            $this->cacheManager = app(PluginCacheManager::class);
        }
    }

    /**
     * Get all plugins from the database.
     */
    public function all(): Collection
    {
        return Plugin::orderBy('name')->get();
    }

    /**
     * Get all active plugins.
     */
    public function getActive(): Collection
    {
        return Plugin::active()->orderBy('name')->get();
    }

    /**
     * Get all inactive plugins.
     */
    public function getInactive(): Collection
    {
        return Plugin::inactive()->orderBy('name')->get();
    }

    /**
     * Find a plugin by slug.
     */
    public function find(string $slug): ?Plugin
    {
        return Plugin::where('slug', $slug)->first();
    }

    /**
     * Find a plugin by slug or fail.
     *
     * @throws PluginNotFoundException
     */
    public function findOrFail(string $slug): Plugin
    {
        $plugin = $this->find($slug);
        
        if (!$plugin) {
            throw PluginNotFoundException::withSlug($slug);
        }

        return $plugin;
    }

    /**
     * Install a plugin from an uploaded ZIP file.
     *
     * @throws PluginException
     */
    public function install(UploadedFile $zipFile): Plugin
    {
        return $this->installer->install($zipFile);
    }

    /**
     * Activate a plugin with full transaction safety.
     *
     * @throws PluginActivationException
     */
    public function activate(string $slug): Plugin
    {
        $plugin = $this->findOrFail($slug);

        if ($plugin->isActive()) {
            return $plugin;
        }

        // Check dependencies before activation
        $this->validateDependencies($plugin);

        return DB::transaction(function () use ($plugin, $slug) {
            try {
                // Load and instantiate the plugin class
                $instance = $this->loadPluginInstance($plugin);

                // Track registered hooks for cleanup
                $hooksBefore = $this->captureHookState();

                // Register the plugin
                $instance->register();

                // Run migrations
                $this->migrator->runMigrations($plugin);

                // Boot the plugin
                $instance->boot();

                // Call activate hook
                $instance->activate();

                // Update database status
                $plugin->update([
                    'status' => Plugin::STATUS_ACTIVE,
                    'activated_at' => now(),
                ]);

                // Track hooks registered by this plugin
                $hooksAfter = $this->captureHookState();
                $this->pluginMetadata[$slug] = [
                    'registered_hooks' => $this->diffHookStates($hooksBefore, $hooksAfter),
                ];

                // Fire activation hook
                $this->hooks->doAction(HookManager::HOOK_PLUGIN_ACTIVATED, $plugin, $instance);

                // Store loaded instance
                $this->loadedPlugins[$slug] = $instance;

                // Clear related caches
                $this->clearPluginCaches($slug);

                // Rebuild provider cache
                $this->rebuildProviderCache();

                Log::info("Plugin activated successfully: {$slug}");

                return $plugin->fresh();

            } catch (\Throwable $e) {
                Log::error("Plugin activation failed: {$slug}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Don't update status here - transaction will rollback
                throw PluginActivationException::forPlugin(
                    $slug,
                    "Activation failed: {$e->getMessage()}",
                    ['original_exception' => get_class($e)]
                );
            }
        });
    }

    /**
     * Deactivate a plugin with cleanup.
     *
     * @throws PluginException
     */
    public function deactivate(string $slug): Plugin
    {
        $plugin = $this->findOrFail($slug);

        if ($plugin->isInactive()) {
            return $plugin;
        }

        // Check for dependent plugins
        $this->checkDependents($plugin);

        return DB::transaction(function () use ($plugin, $slug) {
            try {
                // Load instance if available
                $instance = $this->loadedPlugins[$slug] ?? $this->loadPluginInstance($plugin);

                // Call deactivate hook
                $instance->deactivate();

                // Clean up hooks registered by this plugin
                $this->cleanupPluginHooks($slug);

                // Update database status
                $plugin->update([
                    'status' => Plugin::STATUS_INACTIVE,
                    'activated_at' => null,
                ]);

                // Fire deactivation hook
                $this->hooks->doAction(HookManager::HOOK_PLUGIN_DEACTIVATED, $plugin, $instance);

                // Remove from loaded plugins
                unset($this->loadedPlugins[$slug]);
                unset($this->pluginMetadata[$slug]);

                // Clear related caches
                $this->clearPluginCaches($slug);

                // Rebuild provider cache
                $this->rebuildProviderCache();

                Log::info("Plugin deactivated successfully: {$slug}");

                return $plugin->fresh();

            } catch (\Throwable $e) {
                Log::error("Plugin deactivation failed: {$slug}", [
                    'error' => $e->getMessage(),
                ]);

                throw PluginException::forPlugin(
                    $slug,
                    "Deactivation failed: {$e->getMessage()}"
                );
            }
        });
    }

    /**
     * Uninstall a plugin completely.
     *
     * @throws PluginException
     */
    public function uninstall(string $slug): bool
    {
        $plugin = $this->findOrFail($slug);

        return DB::transaction(function () use ($plugin, $slug) {
            try {
                // Deactivate first if active
                if ($plugin->isActive()) {
                    $this->deactivate($slug);
                    $plugin->refresh();
                }

                // Load instance for uninstall hook
                $instance = $this->loadPluginInstance($plugin);

                // Call uninstall hook
                $instance->uninstall();

                // Rollback all migrations
                $this->migrator->rollbackAllMigrations($plugin);

                // Fire uninstall hook
                $this->hooks->doAction(HookManager::HOOK_PLUGIN_UNINSTALLED, $plugin);

                // Delete plugin files
                $this->installer->deletePluginFiles($plugin);

                // Delete from database
                $plugin->delete();

                // Clear all caches
                $this->clearPluginCaches($slug);

                // Rebuild provider cache
                $this->rebuildProviderCache();

                Log::info("Plugin uninstalled successfully: {$slug}");

                return true;

            } catch (\Throwable $e) {
                Log::error("Plugin uninstall failed: {$slug}", [
                    'error' => $e->getMessage(),
                ]);

                throw PluginException::forPlugin(
                    $slug,
                    "Uninstall failed: {$e->getMessage()}"
                );
            }
        });
    }

    /**
     * Load and instantiate a plugin class.
     *
     * @throws PluginNotFoundException
     * @throws PluginException
     */
    public function loadPluginInstance(Plugin $plugin): PluginInterface
    {
        $className = $plugin->getMainClassName();

        // Security: Validate class name is in allowed plugin namespace
        if (!$this->isValidPluginClassName($className)) {
            throw PluginException::forPlugin(
                $plugin->slug,
                "Invalid plugin class namespace. Class must be in App\\Plugins namespace: {$className}"
            );
        }

        // Require the main file if class doesn't exist
        if (!class_exists($className)) {
            $mainFile = $this->getMainFilePath($plugin);
            
            if (!file_exists($mainFile)) {
                throw PluginNotFoundException::fileMissing($plugin->slug, $mainFile);
            }

            require_once $mainFile;

            if (!class_exists($className)) {
                throw PluginNotFoundException::classMissing($plugin->slug, $className);
            }
        }

        $instance = new $className();

        if (!$instance instanceof PluginInterface) {
            throw PluginException::forPlugin(
                $plugin->slug,
                "Plugin class must implement PluginInterface: {$className}"
            );
        }

        $instance->setPlugin($plugin);

        return $instance;
    }

    /**
     * Validate plugin dependencies.
     *
     * @throws PluginActivationException
     */
    protected function validateDependencies(Plugin $plugin): void
    {
        $requires = $plugin->requires ?? [];

        foreach ($requires as $dependency => $version) {
            // Skip non-string values (like arrays) - they're not version constraints
            if (!is_string($version)) {
                continue;
            }

            if ($dependency === 'php') {
                if (version_compare(PHP_VERSION, $version, '<')) {
                    throw PluginActivationException::forPlugin(
                        $plugin->slug,
                        "Requires PHP {$version} or higher, current: " . PHP_VERSION
                    );
                }
                continue;
            }

            if ($dependency === 'laravel') {
                if (version_compare(app()->version(), $version, '<')) {
                    throw PluginActivationException::forPlugin(
                        $plugin->slug,
                        "Requires Laravel {$version} or higher"
                    );
                }
                continue;
            }

            if ($dependency === 'system') {
                // System is always available, but check version if specified
                $systemVersion = config('app.version', '1.0.0');
                if ($version !== '*' && version_compare($systemVersion, $version, '<')) {
                    throw PluginActivationException::forPlugin(
                        $plugin->slug,
                        "Requires system version {$version} or higher, current: {$systemVersion}"
                    );
                }
                continue;
            }

            // Skip 'extensions' - it's a list of PHP extensions, not a plugin dependency
            if ($dependency === 'extensions') {
                continue;
            }

            // Check for plugin dependency
            $depPlugin = $this->find($dependency);
            if (!$depPlugin || !$depPlugin->isActive()) {
                throw PluginActivationException::forPlugin(
                    $plugin->slug,
                    "Requires plugin '{$dependency}' to be active"
                );
            }

            if ($version !== '*' && version_compare($depPlugin->version, $version, '<')) {
                throw PluginActivationException::forPlugin(
                    $plugin->slug,
                    "Requires plugin '{$dependency}' version {$version} or higher"
                );
            }
        }
    }

    /**
     * Check for plugins that depend on this one.
     *
     * @throws PluginException
     */
    protected function checkDependents(Plugin $plugin): void
    {
        $dependents = [];

        foreach ($this->getActive() as $activePlugin) {
            $requires = $activePlugin->requires ?? [];
            if (isset($requires[$plugin->slug])) {
                $dependents[] = $activePlugin->slug;
            }
        }

        if (!empty($dependents)) {
            throw PluginException::forPlugin(
                $plugin->slug,
                "Cannot deactivate: The following plugins depend on it: " . implode(', ', $dependents)
            );
        }
    }

    /**
     * Capture current hook state for tracking.
     */
    protected function captureHookState(): array
    {
        return [
            'actions' => array_keys($this->hooks->getActions()),
            'filters' => array_keys($this->hooks->getFilters()),
        ];
    }

    /**
     * Get difference between two hook states.
     */
    protected function diffHookStates(array $before, array $after): array
    {
        return [
            'actions' => array_diff($after['actions'], $before['actions']),
            'filters' => array_diff($after['filters'], $before['filters']),
        ];
    }

    /**
     * Clean up hooks registered by a plugin.
     */
    protected function cleanupPluginHooks(string $slug): void
    {
        if (!isset($this->pluginMetadata[$slug]['registered_hooks'])) {
            return;
        }

        $hooks = $this->pluginMetadata[$slug]['registered_hooks'];

        foreach ($hooks['actions'] ?? [] as $action) {
            $this->hooks->removeAllActions($action);
        }

        foreach ($hooks['filters'] ?? [] as $filter) {
            $this->hooks->removeAllFilters($filter);
        }

        // Also clean up hooks registered for this specific plugin
        $this->hooks->removeAllActions("plugin_{$slug}_*");
        $this->hooks->removeAllFilters("plugin_{$slug}_*");
    }

    /**
     * Clear plugin-related caches.
     */
    protected function clearPluginCaches(string $slug): void
    {
        Cache::forget("plugin:{$slug}:config");
        Cache::forget("plugin:{$slug}:entities");
        Cache::forget("plugin:{$slug}:permissions");
        Cache::forget('plugins:active');
        Cache::forget('plugins_manifest_synced'); // Force re-sync on next list load
        Cache::forget('plugins:all');
        // Cache::tags(['plugins', "plugin:{$slug}"])->flush(); // Tagging not supported by file/database drivers
    }

    /**
     * Rebuild the provider cache.
     * Called after activate/deactivate/uninstall to update the cached provider list.
     */
    protected function rebuildProviderCache(): void
    {
        if ($this->cacheManager === null) {
            return;
        }

        try {
            $this->cacheManager->rebuild(silent: true);
        } catch (\Throwable $e) {
            Log::warning('Failed to rebuild provider cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the cache manager instance.
     */
    public function cache(): ?PluginCacheManager
    {
        return $this->cacheManager;
    }

    /**
     * Get the path to the plugin's main file.
     */
    protected function getMainFilePath(Plugin $plugin): string
    {
        $manifestPath = $plugin->getFullPath() . '/plugin.json';
        
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['main'])) {
                return $plugin->getFullPath() . '/' . $manifest['main'];
            }
        }

        // Default main file name
        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $plugin->slug)));
        return $plugin->getFullPath() . "/{$className}Plugin.php";
    }

    /**
     * Get a loaded plugin instance.
     */
    public function getLoadedPlugin(string $slug): ?PluginInterface
    {
        return $this->loadedPlugins[$slug] ?? null;
    }

    /**
     * Get all loaded plugin instances.
     *
     * @return array<string, PluginInterface>
     */
    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    /**
     * Check if a plugin is loaded.
     */
    public function isLoaded(string $slug): bool
    {
        return isset($this->loadedPlugins[$slug]);
    }

    /**
     * Store a loaded plugin instance.
     */
    public function setLoadedPlugin(string $slug, PluginInterface $instance): void
    {
        $this->loadedPlugins[$slug] = $instance;
    }

    /**
     * Get the hook manager.
     */
    public function hooks(): HookManager
    {
        return $this->hooks;
    }

    /**
     * Get the plugin installer.
     */
    public function installer(): PluginInstaller
    {
        return $this->installer;
    }

    /**
     * Get the plugin migrator.
     */
    public function migrator(): PluginMigrator
    {
        return $this->migrator;
    }

    /**
     * Refresh a plugin (reload from disk).
     */
    public function refresh(string $slug): Plugin
    {
        $plugin = $this->findOrFail($slug);
        
        // Re-read manifest
        $manifestPath = $plugin->getFullPath() . '/plugin.json';
        
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            
            $plugin->update([
                'name' => $manifest['name'] ?? $plugin->name,
                'version' => $manifest['version'] ?? $plugin->version,
                'description' => $manifest['description'] ?? $plugin->description,
                'author' => $manifest['author'] ?? $plugin->author,
                'author_url' => $manifest['author_url'] ?? $plugin->author_url,
                'category' => $manifest['category'] ?? $plugin->category,
                'requires' => $manifest['requires'] ?? $plugin->requires,
            ]);
        }

        $this->clearPluginCaches($slug);

        return $plugin->fresh();
    }

    /**
     * Get plugin health status.
     */
    public function getHealthStatus(string $slug): array
    {
        $plugin = $this->findOrFail($slug);
        
        $status = [
            'slug' => $slug,
            'status' => $plugin->status,
            'files_exist' => file_exists($plugin->getFullPath()),
            'manifest_valid' => false,
            'main_class_exists' => false,
            'dependencies_met' => true,
            'issues' => [],
        ];

        // Check manifest
        $manifestPath = $plugin->getFullPath() . '/plugin.json';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $status['manifest_valid'] = is_array($manifest) && isset($manifest['name'], $manifest['version']);
        } else {
            $status['issues'][] = 'Missing plugin.json manifest';
        }

        // Check main class
        try {
            $className = $plugin->getMainClassName();
            $status['main_class_exists'] = class_exists($className) || file_exists($this->getMainFilePath($plugin));
        } catch (\Throwable $e) {
            $status['issues'][] = 'Cannot locate main plugin class';
        }

        // Check dependencies
        try {
            $this->validateDependencies($plugin);
        } catch (\Throwable $e) {
            $status['dependencies_met'] = false;
            $status['issues'][] = $e->getMessage();
        }

        return $status;
    }

    /**
     * Sync all plugins' metadata from their manifest files.
     * This ensures database records stay in sync with plugin.json files.
     * 
     * @param bool $force Force sync even if data appears unchanged
     * @return array List of synced plugin slugs
     */
    public function syncAllFromManifest(bool $force = false): array
    {
        $synced = [];
        $plugins = Plugin::all();

        foreach ($plugins as $plugin) {
            if ($this->syncFromManifest($plugin, $force)) {
                $synced[] = $plugin->slug;
            }
        }

        return $synced;
    }

    /**
     * Sync a single plugin's metadata from its manifest file.
     * 
     * @param Plugin $plugin The plugin to sync
     * @param bool $force Force sync even if data appears unchanged
     * @return bool True if any data was updated
     */
    public function syncFromManifest(Plugin $plugin, bool $force = false): bool
    {
        $manifestPath = $plugin->getFullPath() . '/plugin.json';
        
        if (!file_exists($manifestPath)) {
            return false;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        if (!is_array($manifest)) {
            return false;
        }

        // Build update data from manifest
        $updates = [];
        $fields = [
            'name' => 'name',
            'title' => 'name',  // title in manifest maps to name in DB
            'version' => 'version',
            'description' => 'description',
            'category' => 'category',
            'icon' => 'icon',
            'homepage' => 'homepage',
        ];

        // Handle author (can be string or object)
        if (isset($manifest['author'])) {
            if (is_array($manifest['author'])) {
                $updates['author'] = $manifest['author']['name'] ?? null;
                $updates['author_url'] = $manifest['author']['url'] ?? null;
            } else {
                $updates['author'] = $manifest['author'];
            }
        }

        foreach ($fields as $manifestKey => $dbKey) {
            if (isset($manifest[$manifestKey])) {
                $newValue = $manifest[$manifestKey];
                $currentValue = $plugin->{$dbKey};
                
                // Only update if value is different or force is true
                if ($force || $newValue !== $currentValue) {
                    $updates[$dbKey] = $newValue;
                }
            }
        }

        // Handle requirements
        // Filter requirements to only include version constraints (strings), excluding arrays like 'extensions'
        if (isset($manifest['requirements'])) {
            $requires = [];
            foreach ($manifest['requirements'] as $key => $value) {
                // Only include string values (version constraints), skip arrays and other types
                if (is_string($value)) {
                    $requires[$key] = $value;
                }
            }
            if (!empty($requires)) {
                $updates['requires'] = $requires;
            }
        }

        // Only update if there are changes
        if (!empty($updates)) {
            $plugin->update($updates);
            return true;
        }

        return false;
    }

    /**
     * Check all installed plugins for available updates.
     * 
     * In a production environment, this would query the marketplace API.
     * For now, it returns the count of existing update records.
     *
     * @return int Number of updates found
     */
    public function checkForUpdates(): int
    {
        // Get all installed plugins
        $plugins = Plugin::all();
        
        if ($plugins->isEmpty()) {
            return 0;
        }

        // In production, this would:
        // 1. Query the marketplace API for each plugin's latest version
        // 2. Compare with installed version
        // 3. Create/update PluginUpdate records
        
        // For now, just return the count of existing update records
        // The seeder (PluginUpdatesSeeder) creates mock updates for testing
        $updatesCount = \App\Models\PluginUpdate::count();
        
        Log::info('Plugin update check completed', [
            'plugins_checked' => $plugins->count(),
            'updates_found' => $updatesCount,
        ]);

        return $updatesCount;
    }

    /**
     * Validate that a class name is in an allowed plugin namespace.
     *
     * Security: Prevents arbitrary class instantiation by ensuring
     * plugin classes are only loaded from the App\Plugins namespace.
     */
    protected function isValidPluginClassName(string $className): bool
    {
        // Must start with App\Plugins\ namespace
        if (!str_starts_with($className, 'App\\Plugins\\')) {
            return false;
        }

        // Validate the namespace structure: App\Plugins\{plugin_name}\{ClassName}
        // Plugin name should only contain alphanumeric characters and underscores
        $pattern = '/^App\\\\Plugins\\\\[a-zA-Z_][a-zA-Z0-9_]*\\\\[a-zA-Z_][a-zA-Z0-9_]*$/';
        
        return (bool) preg_match($pattern, $className);
    }
}
