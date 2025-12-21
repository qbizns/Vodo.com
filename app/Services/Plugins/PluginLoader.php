<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use App\Services\Plugins\Contracts\PluginInterface;
use Illuminate\Support\Facades\Log;

/**
 * Plugin Loader - Loads active plugins at boot time.
 * 
 * @deprecated This class is maintained for backward compatibility.
 *             Plugin loading is now handled directly by PluginServiceProvider
 *             using PluginCacheManager for better performance.
 * 
 * @see \App\Providers\PluginServiceProvider
 * @see \App\Services\Plugins\PluginCacheManager
 */

class PluginLoader
{
    /**
     * Whether plugins have been loaded.
     */
    protected bool $loaded = false;

    /**
     * Create a new plugin loader instance.
     */
    public function __construct(
        protected PluginManager $manager,
        protected HookManager $hooks
    ) {}

    /**
     * Load all active plugins.
     */
    public function loadActivePlugins(): void
    {
        if ($this->loaded) {
            return;
        }

        // Skip during migrations or console commands that shouldn't load plugins
        if ($this->shouldSkipLoading()) {
            return;
        }

        $activePlugins = Plugin::active()->get();

        foreach ($activePlugins as $plugin) {
            $this->loadPlugin($plugin);
        }

        $this->loaded = true;

        // Fire plugins loaded action
        $this->hooks->doAction('plugins_loaded');
    }

    /**
     * Load a single plugin.
     */
    protected function loadPlugin(Plugin $plugin): void
    {
        try {
            // Skip if already loaded
            if ($this->manager->isLoaded($plugin->slug)) {
                return;
            }

            // Autoload plugin classes
            $this->registerAutoloader($plugin);

            // Load and instantiate the plugin
            $instance = $this->manager->loadPluginInstance($plugin);

            // Register the plugin
            $instance->register();

            // Boot the plugin
            $instance->boot();

            // Store loaded instance
            $this->manager->setLoadedPlugin($plugin->slug, $instance);

            // Fire plugin loaded action
            $this->hooks->doAction('plugin_loaded', $plugin, $instance);

            Log::debug("Plugin loaded: {$plugin->slug}");
        } catch (\Throwable $e) {
            Log::error("Failed to load plugin: {$plugin->slug}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark plugin as error state
            $plugin->update(['status' => Plugin::STATUS_ERROR]);
        }
    }

    /**
     * Register autoloader for plugin classes.
     */
    protected function registerAutoloader(Plugin $plugin): void
    {
        $basePath = $plugin->getFullPath();
        
        // Register default App\Plugins\{slug} namespace
        $namespaceSlug = str_replace('-', '_', $plugin->slug);
        $namespace = "App\\Plugins\\{$namespaceSlug}\\";

        spl_autoload_register(function ($class) use ($basePath, $namespace) {
            // Check if the class belongs to this plugin's namespace
            if (strpos($class, $namespace) !== 0) {
                return;
            }

            // Get the relative class name
            $relativeClass = substr($class, strlen($namespace));
            
            // Convert namespace separators to directory separators
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
        $this->registerCustomAutoloader($plugin);
    }
    
    /**
     * Register custom autoloader from plugin.json autoload configuration.
     */
    protected function registerCustomAutoloader(Plugin $plugin): void
    {
        $manifestPath = $plugin->getFullPath() . '/plugin.json';
        
        if (!file_exists($manifestPath)) {
            return;
        }
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        if (!isset($manifest['autoload']['psr-4'])) {
            return;
        }
        
        $basePath = $plugin->getFullPath();
        
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
     * Check if plugin loading should be skipped.
     */
    protected function shouldSkipLoading(): bool
    {
        // Skip during migrations
        if (app()->runningInConsole()) {
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
            ];

            if (in_array($command, $skipCommands)) {
                return true;
            }
        }

        // Skip if plugins table doesn't exist yet
        if (!$this->pluginsTableExists()) {
            return true;
        }

        return false;
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

    /**
     * Reload all plugins.
     */
    public function reload(): void
    {
        $this->loaded = false;
        $this->loadActivePlugins();
    }

    /**
     * Check if plugins have been loaded.
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Get the hook manager instance.
     */
    public function hooks(): HookManager
    {
        return $this->hooks;
    }

    /**
     * Get the plugin manager instance.
     */
    public function manager(): PluginManager
    {
        return $this->manager;
    }
}
