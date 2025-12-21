<?php

namespace App\Providers;

use App\Models\Plugin;
use App\Services\Plugins\HookManager;
use App\Services\Plugins\PluginCacheManager;
use App\Services\Plugins\PluginInstaller;
use App\Services\Plugins\PluginLoader;
use App\Services\Plugins\PluginManager;
use App\Services\Plugins\PluginMigrator;
use App\Services\Plugins\Contracts\PluginInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Loaded plugin instances for this request.
     *
     * @var array<string, PluginInterface>
     */
    protected array $loadedInstances = [];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register HookManager as singleton
        $this->app->singleton(HookManager::class, function ($app) {
            return new HookManager();
        });

        // Register PluginCacheManager as singleton
        $this->app->singleton(PluginCacheManager::class, function ($app) {
            return new PluginCacheManager();
        });

        // Register PluginMigrator
        $this->app->singleton(PluginMigrator::class, function ($app) {
            return new PluginMigrator();
        });

        // Register PluginInstaller
        $this->app->singleton(PluginInstaller::class, function ($app) {
            return new PluginInstaller();
        });

        // Register PluginManager
        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager(
                $app->make(PluginInstaller::class),
                $app->make(PluginMigrator::class),
                $app->make(HookManager::class),
                $app->make(PluginCacheManager::class)
            );
        });

        // Register PluginLoader
        $this->app->singleton(PluginLoader::class, function ($app) {
            return new PluginLoader(
                $app->make(PluginManager::class),
                $app->make(HookManager::class)
            );
        });

        // Register aliases for convenience
        $this->app->alias(HookManager::class, 'plugins.hooks');
        $this->app->alias(PluginManager::class, 'plugins.manager');
        $this->app->alias(PluginLoader::class, 'plugins.loader');
        $this->app->alias(PluginCacheManager::class, 'plugins.cache');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Merge plugin config
        $this->mergeConfigFrom(
            config_path('plugins.php'),
            'plugins'
        );

        // Check safe mode - skip all plugin loading
        if (config('plugins.safe_mode', false)) {
            Log::warning('Plugins running in SAFE MODE - no plugins loaded');
            return;
        }

        // Skip during certain console commands
        if ($this->shouldSkipLoading()) {
            return;
        }

        // Load plugins using cache or fallback to database
        $this->loadPlugins();
    }

    /**
     * Load all active plugins.
     */
    protected function loadPlugins(): void
    {
        $cacheManager = $this->app->make(PluginCacheManager::class);
        $manager = $this->app->make(PluginManager::class);
        $hooks = $this->app->make(HookManager::class);

        $plugins = $this->getActivePlugins($cacheManager);

        foreach ($plugins as $slug => $pluginData) {
            $this->loadPlugin($slug, $pluginData, $manager, $cacheManager);
        }

        // Fire plugins loaded action
        $hooks->doAction('plugins_loaded');
    }

    /**
     * Get active plugins from cache or database.
     */
    protected function getActivePlugins(PluginCacheManager $cacheManager): array
    {
        // Try cache first if enabled
        if (config('plugins.use_cache', true)) {
            $cached = $cacheManager->getActivePlugins();

            if (!empty($cached)) {
                return $cached;
            }

            // Cache miss - rebuild if table exists
            if ($this->pluginsTableExists()) {
                $cacheManager->rebuild(silent: true);
                return $cacheManager->getActivePlugins();
            }
        }

        // Fallback: query database directly
        if (!$this->pluginsTableExists()) {
            return [];
        }

        $plugins = [];

        try {
            $activePlugins = Plugin::active()->get();

            foreach ($activePlugins as $plugin) {
                $plugins[$plugin->slug] = [
                    'main_class' => $plugin->getMainClassName(),
                    'path' => $plugin->getFullPath(),
                    'version' => $plugin->version,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Failed to load plugins from database', [
                'error' => $e->getMessage(),
            ]);
        }

        return $plugins;
    }

    /**
     * Load a single plugin with exception handling.
     */
    protected function loadPlugin(
        string $slug,
        array $pluginData,
        PluginManager $manager,
        PluginCacheManager $cacheManager
    ): void {
        // Skip if already loaded
        if ($manager->isLoaded($slug)) {
            return;
        }

        try {
            // Get plugin model from database for full context
            $plugin = Plugin::where('slug', $slug)->first();

            if (!$plugin) {
                Log::warning("Plugin in cache but not in database: {$slug}");
                $cacheManager->markPluginError($slug);
                return;
            }

            // Register autoloader for this plugin
            $this->registerPluginAutoloader($plugin, $pluginData);

            // Load and instantiate
            $instance = $manager->loadPluginInstance($plugin);

            // Register the plugin
            $instance->register();

            // Boot the plugin
            $instance->boot();

            // Store loaded instance
            $manager->setLoadedPlugin($slug, $instance);
            $this->loadedInstances[$slug] = $instance;

            // Fire plugin loaded action
            $manager->hooks()->doAction('plugin_loaded', $plugin, $instance);

            Log::debug("Plugin loaded: {$slug}");

        } catch (\Throwable $e) {
            Log::error("Failed to load plugin: {$slug}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark plugin as error in database
            $this->markPluginError($slug);

            // Remove from cache to prevent repeated failures
            $cacheManager->markPluginError($slug);
        }
    }

    /**
     * Register autoloader for a plugin.
     */
    protected function registerPluginAutoloader(Plugin $plugin, array $pluginData): void
    {
        $basePath = $pluginData['path'] ?? $plugin->getFullPath();
        $namespaceSlug = str_replace('-', '_', $plugin->slug);
        $namespace = "App\\Plugins\\{$namespaceSlug}\\";

        // Register default App\Plugins\{slug} namespace
        spl_autoload_register(function ($class) use ($basePath, $namespace) {
            if (strpos($class, $namespace) !== 0) {
                return;
            }

            $relativeClass = substr($class, strlen($namespace));
            $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

            // Check src directory first
            $srcPath = $basePath . '/src/' . $relativePath;
            if (file_exists($srcPath)) {
                require_once $srcPath;
                return;
            }

            // Check root directory
            $rootPath = $basePath . '/' . $relativePath;
            if (file_exists($rootPath)) {
                require_once $rootPath;
                return;
            }
        });
        
        // Register custom namespaces from plugin.json autoload config
        $this->registerCustomPluginAutoloader($basePath);
    }
    
    /**
     * Register custom autoloader from plugin.json autoload configuration.
     */
    protected function registerCustomPluginAutoloader(string $basePath): void
    {
        $manifestPath = $basePath . '/plugin.json';
        
        if (!file_exists($manifestPath)) {
            return;
        }
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        if (!isset($manifest['autoload']['psr-4'])) {
            return;
        }
        
        foreach ($manifest['autoload']['psr-4'] as $namespace => $path) {
            // Ensure namespace ends with backslash
            $namespace = rtrim($namespace, '\\') . '\\';
            // Normalize path
            $path = rtrim($path, '/');
            
            spl_autoload_register(function ($class) use ($basePath, $namespace, $path) {
                // Check if the class belongs to this namespace
                if (strpos($class, $namespace) !== 0) {
                    return;
                }

                // Get the relative class name
                $relativeClass = substr($class, strlen($namespace));
                
                // Convert namespace separators to directory separators
                $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

                // Build full path
                $fullPath = $basePath . '/' . $path . '/' . $relativePath;
                
                if (file_exists($fullPath)) {
                    require_once $fullPath;
                    return;
                }
            });
        }
    }

    /**
     * Mark a plugin as errored in the database.
     */
    protected function markPluginError(string $slug): void
    {
        try {
            Plugin::where('slug', $slug)->update([
                'status' => Plugin::STATUS_ERROR,
                'error_message' => 'Failed to load during boot - check logs',
            ]);
        } catch (\Throwable $e) {
            // Ignore database errors during error marking
        }
    }

    /**
     * Check if plugin loading should be skipped.
     */
    protected function shouldSkipLoading(): bool
    {
        if (!$this->app->runningInConsole()) {
            return false;
        }

        $command = $_SERVER['argv'][1] ?? null;

        $skipCommands = [
            'migrate',
            'migrate:fresh',
            'migrate:install',
            'migrate:refresh',
            'migrate:reset',
            'migrate:rollback',
            'migrate:status',
            'db:seed',
            'db:wipe',
            'config:cache',
            'config:clear',
            'route:cache',
            'route:clear',
            'view:cache',
            'view:clear',
            'cache:clear',
            'optimize',
            'optimize:clear',
            'package:discover',
        ];

        return in_array($command, $skipCommands);
    }

    /**
     * Check if the plugins table exists.
     */
    protected function pluginsTableExists(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('plugins');
        } catch (\Throwable $e) {
            return false;
        }
    }
}