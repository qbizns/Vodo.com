<?php

namespace Database\Seeders;

use App\Models\Plugin;
use App\Services\Plugins\PluginCacheManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds the plugins table with installed plugins.
 * 
 * This seeder:
 * 1. Scans the app/Plugins directory for installed plugins
 * 2. Reads plugin.json metadata for each plugin
 * 3. Creates or updates plugin records in the database
 * 4. Clears the plugin cache to ensure fresh state
 */
class PluginsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding plugins table...');

        // Scan and register plugins from the Plugins directory
        $this->scanAndRegisterPlugins();

        // Register any additional built-in plugins
        $this->registerBuiltInPlugins();

        // Clear plugin cache to ensure fresh state
        $this->clearPluginCache();

        $this->showSummary();
    }

    /**
     * Clear the plugin cache to ensure it rebuilds from the database.
     */
    protected function clearPluginCache(): void
    {
        try {
            if (app()->bound(PluginCacheManager::class)) {
                $cacheManager = app(PluginCacheManager::class);
                $cacheManager->clear();
                $this->command->info('Plugin cache cleared.');
            } else {
                // Fallback: delete cache files directly
                $cachePath = base_path('bootstrap/cache/plugins.php');
                if (File::exists($cachePath)) {
                    File::delete($cachePath);
                    $this->command->info('Plugin cache file deleted.');
                }
            }
        } catch (\Exception $e) {
            $this->command->warn('Could not clear plugin cache: ' . $e->getMessage());
        }
    }

    /**
     * Scan the Plugins directory and register found plugins.
     */
    protected function scanAndRegisterPlugins(): void
    {
        $pluginsPath = app_path('Plugins');

        if (!File::isDirectory($pluginsPath)) {
            $this->command->warn('Plugins directory does not exist: ' . $pluginsPath);
            return;
        }

        $directories = File::directories($pluginsPath);

        foreach ($directories as $pluginDir) {
            $manifestPath = $pluginDir . '/plugin.json';

            // Skip if no manifest found
            if (!File::exists($manifestPath)) {
                $this->command->warn('No plugin.json found in: ' . basename($pluginDir));
                continue;
            }

            $this->registerPluginFromManifest($pluginDir, $manifestPath);
        }
    }

    /**
     * Register a plugin from its manifest file.
     */
    protected function registerPluginFromManifest(string $pluginDir, string $manifestPath): void
    {
        try {
            $manifest = json_decode(File::get($manifestPath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->command->error('Invalid JSON in: ' . $manifestPath);
                return;
            }

            // Use slug from manifest if available, otherwise normalize name or use directory name
            $slug = $manifest['slug'] ?? $this->normalizeSlug($manifest['name'] ?? basename($pluginDir));
            
            // Ensure slug is valid (lowercase, alphanumeric with hyphens)
            $slug = $this->normalizeSlug($slug);
            
            // Get the actual main class (reads from PHP file if possible)
            // If main_class is already specified in manifest, use it
            $mainClass = $manifest['main_class'] ?? $this->getMainClass($manifest, $slug, $pluginDir);
            
            // Extract namespace from main class
            $namespace = $this->extractNamespaceFromMainClass($mainClass);
            
            // Check if plugin already exists to preserve status if it was active
            $existingPlugin = Plugin::where('slug', $slug)->first();
            $preserveStatus = $existingPlugin && $existingPlugin->status === Plugin::STATUS_ACTIVE;
            
            // Prepare plugin data from manifest
            $pluginData = [
                'name' => $manifest['title'] ?? ucwords(str_replace('-', ' ', $slug)),
                'version' => $manifest['version'] ?? '1.0.0',
                'description' => $manifest['description'] ?? null,
                'author' => is_array($manifest['author'] ?? null) 
                    ? ($manifest['author']['name'] ?? 'Unknown') 
                    : ($manifest['author'] ?? 'Unknown'),
                'author_url' => is_array($manifest['author'] ?? null) 
                    ? ($manifest['author']['url'] ?? null) 
                    : null,
                'homepage' => $manifest['homepage'] ?? null,
                'category' => $manifest['category'] ?? 'utilities',
                'icon' => $manifest['icon'] ?? null,
                'is_core' => $manifest['is_core'] ?? false,
                'is_premium' => $manifest['is_premium'] ?? false,
                'requires_license' => $manifest['requires_license'] ?? false,
                'min_system_version' => $manifest['requirements']['system'] ?? null,
                'min_php_version' => $manifest['requirements']['php'] ?? null,
                'path' => $pluginDir,
                'namespace' => $namespace,
                'entry_class' => $manifest['entry_class'] ?? null,
                'main_class' => $mainClass,
                'requires' => $manifest['dependencies'] ?? null,
                'settings' => $manifest['settings'] ?? null,
                'status' => $preserveStatus ? Plugin::STATUS_ACTIVE : Plugin::STATUS_INACTIVE,
                'error_message' => null, // Clear any previous errors
                'installed_at' => $existingPlugin?->installed_at ?? now(),
            ];

            // Create or update the plugin
            $plugin = Plugin::updateOrCreate(
                ['slug' => $slug],
                $pluginData
            );

            $status = $plugin->wasRecentlyCreated ? 'Created' : 'Updated';
            $this->command->line("  {$status} plugin: {$slug} (v{$pluginData['version']})");

        } catch (\Exception $e) {
            $this->command->error("Error processing plugin in {$pluginDir}: " . $e->getMessage());
        }
    }

    /**
     * Get namespace from manifest autoload configuration.
     */
    protected function getNamespaceFromManifest(array $manifest, string $slug): string
    {
        if (isset($manifest['autoload']['psr-4'])) {
            $namespaces = array_keys($manifest['autoload']['psr-4']);
            return rtrim($namespaces[0] ?? '', '\\');
        }

        // Convert slug to namespace format (hyphens to underscores for PHP namespace)
        $namespace = str_replace('-', '_', $slug);
        return "App\\Plugins\\{$namespace}";
    }

    /**
     * Get the main class for the plugin by reading the actual PHP file.
     */
    protected function getMainClass(array $manifest, string $slug, string $pluginDir): string
    {
        // Check for entry_class or main field
        $entryClass = $manifest['entry_class'] ?? null;
        $mainFile = $manifest['main'] ?? null;
        
        // If main is a file path, extract class name from it
        if ($mainFile && !$entryClass) {
            $entryClass = pathinfo($mainFile, PATHINFO_FILENAME);
        }
        
        if (!$entryClass) {
            // Fallback: Use the plugin directory namespace convention
            $pluginNamespace = str_replace('-', '_', $slug);
            $className = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug))) . 'Plugin';
            return "App\\Plugins\\{$pluginNamespace}\\{$className}";
        }
        
        // Try to find the entry class file in common locations
        $possiblePaths = [];
        
        if ($mainFile) {
            // If main is specified, try that path first
            $possiblePaths[] = $pluginDir . '/' . $mainFile;
            $possiblePaths[] = $pluginDir . '/' . $entryClass . '.php';
        } else {
            $possiblePaths[] = $pluginDir . '/' . $entryClass . '.php';  // Root of plugin directory
        }
        
        $possiblePaths[] = $pluginDir . '/src/' . $entryClass . '.php';  // src directory
        
        foreach ($possiblePaths as $entryFile) {
            if (file_exists($entryFile)) {
                // Parse the PHP file to get the actual namespace
                $content = file_get_contents($entryFile);
                
                if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                    $namespace = trim($matches[1]);
                    return "{$namespace}\\{$entryClass}";
                }
            }
        }
        
        // If file not found but entry_class is specified, try to construct from autoload config
        if (isset($manifest['autoload']['psr-4'])) {
            $namespaces = array_keys($manifest['autoload']['psr-4']);
            if (!empty($namespaces)) {
                $namespace = rtrim($namespaces[0], '\\');
                return "{$namespace}\\{$entryClass}";
            }
        }
        
        // Check if namespace is specified directly in manifest
        if (isset($manifest['namespace'])) {
            $namespace = rtrim($manifest['namespace'], '\\');
            return "{$namespace}\\{$entryClass}";
        }
        
        // Final fallback: Use the plugin directory namespace convention
        $pluginNamespace = str_replace('-', '_', $slug);
        return "App\\Plugins\\{$pluginNamespace}\\{$entryClass}";
    }

    /**
     * Extract namespace from a fully qualified class name.
     */
    protected function extractNamespaceFromMainClass(string $mainClass): string
    {
        $parts = explode('\\', $mainClass);
        array_pop($parts); // Remove the class name
        return implode('\\', $parts);
    }

    /**
     * Normalize a slug to be lowercase with hyphens.
     */
    protected function normalizeSlug(string $slug): string
    {
        // Convert to lowercase
        $slug = strtolower($slug);
        
        // Replace spaces and underscores with hyphens
        $slug = preg_replace('/[\s_]+/', '-', $slug);
        
        // Remove any characters that aren't alphanumeric or hyphens
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        
        // Ensure it's not empty
        if (empty($slug)) {
            $slug = 'plugin';
        }
        
        return $slug;
    }

    /**
     * Register any built-in or core plugins that should always exist.
     */
    protected function registerBuiltInPlugins(): void
    {
        // You can add core plugins here that should always be present
        // even if they don't have a physical directory yet
        
        $corePlugins = [
            // Example core plugin that might come with the system
            // [
            //     'slug' => 'vodo-core',
            //     'name' => 'Vodo Core',
            //     'description' => 'Core system functionality',
            //     'version' => '1.0.0',
            //     'author' => 'Vodo',
            //     'is_core' => true,
            //     'status' => Plugin::STATUS_ACTIVE,
            // ],
        ];

        foreach ($corePlugins as $pluginData) {
            $slug = $pluginData['slug'];
            unset($pluginData['slug']);

            Plugin::updateOrCreate(
                ['slug' => $slug],
                array_merge($pluginData, ['installed_at' => now()])
            );

            $this->command->line("  Registered core plugin: {$slug}");
        }
    }

    /**
     * Show summary of seeded plugins.
     */
    protected function showSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== Plugins Seeding Summary ===');

        $stats = [
            'Total' => Plugin::count(),
            'Active' => Plugin::active()->count(),
            'Inactive' => Plugin::inactive()->count(),
            'Core' => Plugin::core()->count(),
            'Premium' => Plugin::premium()->count(),
        ];

        foreach ($stats as $label => $count) {
            $this->command->line("  {$label}: {$count}");
        }

        $this->command->newLine();

        // List all plugins
        $plugins = Plugin::orderBy('name')->get(['slug', 'name', 'version', 'status']);

        if ($plugins->isNotEmpty()) {
            $this->command->info('Installed Plugins:');
            foreach ($plugins as $plugin) {
                $statusIcon = $plugin->status === Plugin::STATUS_ACTIVE ? '✓' : '○';
                $this->command->line("  {$statusIcon} {$plugin->name} ({$plugin->slug}) v{$plugin->version} - {$plugin->status}");
            }
        }

        $this->command->newLine();
    }
}

