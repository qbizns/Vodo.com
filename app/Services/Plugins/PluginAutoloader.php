<?php

declare(strict_types=1);

namespace App\Services\Plugins;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PluginAutoloader - Centralized class autoloader for plugins.
 *
 * Phase 2, Task 2.3: Central Plugin Autoloader
 *
 * This class replaces per-plugin SPL autoload callbacks with a single
 * efficient dispatcher. With 50 plugins each registering 2 autoloaders,
 * you'd have 100 closure checks per class load. This centralizes it to 1.
 *
 * Features:
 * - Single SPL autoload callback for all plugins
 * - Cached namespace mappings for fast lookups
 * - PSR-4 compliant autoloading
 * - Debug mode for troubleshooting
 *
 * Usage:
 *   $autoloader = app(PluginAutoloader::class);
 *   $autoloader->addNamespace('MyPlugin\\', '/path/to/plugin/src');
 *   $autoloader->register(); // Register with SPL
 */
class PluginAutoloader
{
    /**
     * Namespace to directory mappings.
     *
     * @var array<string, string>
     */
    protected array $namespaces = [];

    /**
     * Class to file mappings (direct mapping).
     *
     * @var array<string, string>
     */
    protected array $classMap = [];

    /**
     * Whether the autoloader has been registered.
     */
    protected bool $registered = false;

    /**
     * Failed class lookups (for debugging).
     *
     * @var array<string>
     */
    protected array $failedLookups = [];

    /**
     * Successful class loads count.
     */
    protected int $loadCount = 0;

    /**
     * Cache key for namespace mappings.
     */
    protected const CACHE_KEY = 'plugin_autoloader:namespaces';

    /**
     * Create a new autoloader instance.
     */
    public function __construct()
    {
        $this->loadCachedMappings();
    }

    /**
     * Register the autoloader with SPL.
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        spl_autoload_register([$this, 'loadClass'], true, true);
        $this->registered = true;

        if (config('platform.autoloader.log_loading')) {
            Log::debug('PluginAutoloader: Registered with SPL');
        }
    }

    /**
     * Unregister the autoloader from SPL.
     */
    public function unregister(): void
    {
        if (!$this->registered) {
            return;
        }

        spl_autoload_unregister([$this, 'loadClass']);
        $this->registered = false;
    }

    /**
     * Add a PSR-4 namespace mapping.
     *
     * @param string $namespace The namespace prefix (with trailing \\)
     * @param string $path The directory path (without trailing /)
     */
    public function addNamespace(string $namespace, string $path): void
    {
        // Ensure namespace ends with backslash
        $namespace = rtrim($namespace, '\\') . '\\';

        // Ensure path doesn't end with slash
        $path = rtrim($path, '/\\');

        $this->namespaces[$namespace] = $path;

        // Update cache
        $this->updateCache();
    }

    /**
     * Add multiple namespace mappings.
     *
     * @param array<string, string> $namespaces
     */
    public function addNamespaces(array $namespaces): void
    {
        foreach ($namespaces as $namespace => $path) {
            $this->addNamespace($namespace, $path);
        }
    }

    /**
     * Add a direct class-to-file mapping.
     *
     * @param string $class Fully qualified class name
     * @param string $file Absolute file path
     */
    public function addClassMapping(string $class, string $file): void
    {
        $this->classMap[$class] = $file;
    }

    /**
     * Remove a namespace mapping.
     *
     * @param string $namespace The namespace prefix to remove
     */
    public function removeNamespace(string $namespace): void
    {
        $namespace = rtrim($namespace, '\\') . '\\';

        unset($this->namespaces[$namespace]);

        $this->updateCache();
    }

    /**
     * Remove all namespaces for a plugin.
     *
     * @param string $pluginSlug The plugin slug
     */
    public function removePluginNamespaces(string $pluginSlug): void
    {
        $prefix = "App\\Plugins\\{$pluginSlug}\\";

        foreach (array_keys($this->namespaces) as $namespace) {
            if (str_starts_with($namespace, $prefix)) {
                unset($this->namespaces[$namespace]);
            }
        }

        $this->updateCache();
    }

    /**
     * Load a class file.
     *
     * @param string $class The fully qualified class name
     * @return bool True if class was loaded
     */
    public function loadClass(string $class): bool
    {
        // Check direct class map first
        if (isset($this->classMap[$class])) {
            $file = $this->classMap[$class];
            if (file_exists($file)) {
                require $file;
                $this->loadCount++;
                return true;
            }
        }

        // Try namespace mappings
        foreach ($this->namespaces as $namespace => $path) {
            if (str_starts_with($class, $namespace)) {
                $file = $this->findClassFile($class, $namespace, $path);

                if ($file !== null) {
                    require $file;
                    $this->loadCount++;

                    if (config('platform.autoloader.log_loading')) {
                        Log::debug("PluginAutoloader: Loaded {$class} from {$file}");
                    }

                    return true;
                }
            }
        }

        // Track failed lookups for debugging
        if (str_starts_with($class, 'App\\Plugins\\')) {
            $this->failedLookups[] = $class;
        }

        return false;
    }

    /**
     * Find the file for a class in a namespace.
     */
    protected function findClassFile(string $class, string $namespace, string $basePath): ?string
    {
        // Get the relative class name
        $relativeClass = substr($class, strlen($namespace));

        // Convert namespace separators to directory separators
        $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

        // Try the src directory first (common convention)
        $srcPath = $basePath . '/src/' . $relativePath;
        if (file_exists($srcPath)) {
            return $srcPath;
        }

        // Try the base directory
        $basePPath = $basePath . '/' . $relativePath;
        if (file_exists($basePPath)) {
            return $basePPath;
        }

        return null;
    }

    /**
     * Check if a class can be loaded.
     */
    public function canLoad(string $class): bool
    {
        // Check direct class map
        if (isset($this->classMap[$class])) {
            return file_exists($this->classMap[$class]);
        }

        // Check namespace mappings
        foreach ($this->namespaces as $namespace => $path) {
            if (str_starts_with($class, $namespace)) {
                $file = $this->findClassFile($class, $namespace, $path);
                if ($file !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get all registered namespaces.
     *
     * @return array<string, string>
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Get autoloader statistics.
     */
    public function getStats(): array
    {
        return [
            'registered' => $this->registered,
            'namespace_count' => count($this->namespaces),
            'class_map_count' => count($this->classMap),
            'load_count' => $this->loadCount,
            'failed_lookups' => count($this->failedLookups),
            'failed_classes' => array_slice($this->failedLookups, -10), // Last 10
        ];
    }

    /**
     * Clear failed lookups.
     */
    public function clearFailedLookups(): void
    {
        $this->failedLookups = [];
    }

    /**
     * Load cached namespace mappings.
     */
    protected function loadCachedMappings(): void
    {
        if (!config('platform.autoloader.cache_mappings', true)) {
            return;
        }

        $cached = Cache::get(self::CACHE_KEY);

        if ($cached !== null && is_array($cached)) {
            $this->namespaces = $cached;
        }
    }

    /**
     * Update the namespace cache.
     */
    protected function updateCache(): void
    {
        if (!config('platform.autoloader.cache_mappings', true)) {
            return;
        }

        $ttl = config('platform.autoloader.cache_ttl', 3600);
        Cache::put(self::CACHE_KEY, $this->namespaces, $ttl);
    }

    /**
     * Clear the namespace cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Rebuild the cache from current namespaces.
     */
    public function rebuildCache(): void
    {
        $this->clearCache();
        $this->updateCache();
    }

    /**
     * Register a plugin's autoload configuration.
     *
     * @param string $pluginSlug Plugin slug
     * @param string $pluginPath Plugin base path
     * @param array|null $psr4Config PSR-4 config from plugin.json
     */
    public function registerPlugin(string $pluginSlug, string $pluginPath, ?array $psr4Config = null): void
    {
        if ($psr4Config) {
            foreach ($psr4Config as $namespace => $relativePath) {
                // Handle empty path (root directory)
                $relativePath = trim($relativePath, '/');
                if (empty($relativePath)) {
                    $fullPath = $pluginPath;
                } else {
                    $fullPath = $pluginPath . '/' . $relativePath;
                }
                $this->addNamespace($namespace, $fullPath);
            }
        } else {
            // Default convention: App\Plugins\{PluginSlug}\ → {pluginPath}/src/
            $namespace = "App\\Plugins\\{$pluginSlug}\\";
            $this->addNamespace($namespace, $pluginPath);
        }
    }

    /**
     * Unregister a plugin's autoload configuration.
     */
    public function unregisterPlugin(string $pluginSlug): void
    {
        $this->removePluginNamespaces($pluginSlug);
    }
}
