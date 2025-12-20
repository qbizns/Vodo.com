<?php

namespace App\Services\Plugins;

use App\Models\Plugin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class PluginInstaller
{
    /**
     * Required fields in plugin.json manifest.
     */
    protected array $requiredManifestFields = [
        'name',
        'slug',
        'version',
        'main',
    ];

    /**
     * Install a plugin from an uploaded ZIP file.
     *
     * @throws \Exception
     */
    public function install(UploadedFile $zipFile): Plugin
    {
        $tempPath = $this->extractToTemp($zipFile);

        try {
            // Find and validate manifest
            $manifestPath = $this->findManifest($tempPath);
            $manifest = $this->validateManifest($manifestPath);

            // Check if plugin already exists
            if (Plugin::where('slug', $manifest['slug'])->exists()) {
                throw new \Exception("Plugin '{$manifest['slug']}' is already installed.");
            }

            // Move to plugins directory (pass manifest path to determine plugin root)
            $pluginPath = $this->moveToPluginsDirectory($tempPath, $manifest['slug'], $manifestPath);

            // Create database record
            $plugin = $this->createPluginRecord($manifest, $pluginPath);

            return $plugin;
        } finally {
            // Cleanup temp directory
            $this->cleanup($tempPath);
        }
    }

    /**
     * Extract ZIP file to a temporary directory.
     *
     * @throws \Exception
     */
    protected function extractToTemp(UploadedFile $zipFile): string
    {
        $zip = new ZipArchive();
        $tempDir = storage_path('app/plugins-temp/' . uniqid('plugin_'));

        if (!File::makeDirectory($tempDir, 0755, true, true)) {
            throw new \Exception('Failed to create temporary directory.');
        }

        $result = $zip->open($zipFile->getRealPath());

        if ($result !== true) {
            throw new \Exception("Failed to open ZIP file. Error code: {$result}");
        }

        $zip->extractTo($tempDir);
        $zip->close();

        return $tempDir;
    }

    /**
     * Find the plugin.json manifest file.
     *
     * @throws \Exception
     */
    protected function findManifest(string $tempPath): string
    {
        // Check root level first
        $manifestPath = $tempPath . '/plugin.json';
        if (file_exists($manifestPath)) {
            return $manifestPath;
        }

        // Search recursively for plugin.json
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'plugin.json') {
                return $file->getPathname();
            }
        }

        // If not found recursively, check one level deep (common case)
        $directories = File::directories($tempPath);
        if (count($directories) === 1) {
            $manifestPath = $directories[0] . '/plugin.json';
            if (file_exists($manifestPath)) {
                return $manifestPath;
            }
        }

        // Provide helpful error message with directory structure
        $files = File::allFiles($tempPath);
        $fileList = array_slice(array_map(function($file) use ($tempPath) {
            return str_replace($tempPath . '/', '', $file->getPathname());
        }, $files), 0, 10); // Show first 10 files
        
        $errorMsg = 'Plugin manifest (plugin.json) not found in ZIP file. ';
        $errorMsg .= 'Make sure plugin.json exists in your plugin directory. ';
        if (!empty($fileList)) {
            $errorMsg .= 'Found files: ' . implode(', ', $fileList) . (count($files) > 10 ? '...' : '');
        }
        
        throw new \Exception($errorMsg);
    }

    /**
     * Validate the plugin manifest.
     *
     * @throws \Exception
     */
    protected function validateManifest(string $manifestPath): array
    {
        $content = file_get_contents($manifestPath);
        $manifest = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid plugin.json: ' . json_last_error_msg());
        }

        // Check required fields
        foreach ($this->requiredManifestFields as $field) {
            if (!isset($manifest[$field]) || empty($manifest[$field])) {
                throw new \Exception("Missing required field in plugin.json: {$field}");
            }
        }

        // Validate slug format
        if (!preg_match('/^[a-z0-9-]+$/', $manifest['slug'])) {
            throw new \Exception('Plugin slug must contain only lowercase letters, numbers, and hyphens.');
        }

        // Validate version format
        if (!preg_match('/^\d+\.\d+\.\d+/', $manifest['version'])) {
            throw new \Exception('Plugin version must follow semantic versioning (e.g., 1.0.0).');
        }

        // Validate main file exists
        $manifestDir = dirname($manifestPath);
        $mainFile = $manifestDir . '/' . $manifest['main'];
        
        if (!file_exists($mainFile)) {
            throw new \Exception("Main plugin file not found: {$manifest['main']}");
        }

        // Check PHP requirements if specified
        if (isset($manifest['requires']['php'])) {
            $this->checkPhpVersion($manifest['requires']['php']);
        }

        return $manifest;
    }

    /**
     * Check PHP version requirement.
     *
     * @throws \Exception
     */
    protected function checkPhpVersion(string $requirement): void
    {
        // Parse version requirement (e.g., ">=8.2", "^8.2", "8.2")
        $requirement = trim($requirement);
        
        if (preg_match('/^([<>=^~]*)(\d+\.\d+\.?\d*)/', $requirement, $matches)) {
            $operator = $matches[1] ?: '>=';
            $version = $matches[2];

            // Convert ^ to >= for simplicity
            if ($operator === '^') {
                $operator = '>=';
            }

            if (!version_compare(PHP_VERSION, $version, $operator)) {
                throw new \Exception("Plugin requires PHP {$requirement}, but you have " . PHP_VERSION);
            }
        }
    }

    /**
     * Move extracted files to the plugins directory.
     *
     * @throws \Exception
     */
    protected function moveToPluginsDirectory(string $tempPath, string $slug, string $manifestPath): string
    {
        $targetPath = app_path("Plugins/{$slug}");

        // Determine the plugin root directory (where plugin.json is located)
        $manifestDir = dirname($manifestPath);
        
        // If manifest is in temp root, use temp root
        // Otherwise, use the directory containing the manifest as the plugin root
        $sourcePath = $manifestDir;
        
        // If the manifest is in a subdirectory, we want to move that subdirectory
        // But if it's at temp root, we check if there's a single subdirectory we should use instead
        if ($manifestDir === $tempPath) {
            // Manifest is at root, check if there's a single subdirectory (common ZIP structure)
            $directories = File::directories($tempPath);
            if (count($directories) === 1) {
                $sourcePath = $directories[0];
            } else {
                $sourcePath = $tempPath;
            }
        }

        // Ensure plugins directory exists
        $pluginsDir = app_path('Plugins');
        if (!File::isDirectory($pluginsDir)) {
            File::makeDirectory($pluginsDir, 0755, true);
        }

        // Move files
        if (!File::copyDirectory($sourcePath, $targetPath)) {
            throw new \Exception('Failed to copy plugin files to plugins directory.');
        }

        return $targetPath;
    }

    /**
     * Create the plugin database record.
     */
    protected function createPluginRecord(array $manifest, string $pluginPath): Plugin
    {
        // Build main class name
        $mainClassName = pathinfo($manifest['main'], PATHINFO_FILENAME);
        // Convert slug hyphens to underscores for valid PHP namespace
        $namespaceSlug = str_replace('-', '_', $manifest['slug']);
        $fullClassName = "App\\Plugins\\{$namespaceSlug}\\{$mainClassName}";

        return Plugin::create([
            'name' => $manifest['name'],
            'slug' => $manifest['slug'],
            'version' => $manifest['version'],
            'description' => $manifest['description'] ?? null,
            'author' => $manifest['author'] ?? null,
            'author_url' => $manifest['author_url'] ?? null,
            'category' => $manifest['category'] ?? null,
            'status' => Plugin::STATUS_INACTIVE,
            'requires' => $manifest['requires'] ?? null,
            'main_class' => $fullClassName,
            'path' => $pluginPath,
            'settings' => [],
        ]);
    }

    /**
     * Delete plugin files.
     *
     * @throws \Exception
     */
    public function deletePluginFiles(Plugin $plugin): bool
    {
        $path = $plugin->getFullPath();

        if (!File::isDirectory($path)) {
            Log::warning("Plugin directory not found during deletion: {$path}");
            return true;
        }

        return File::deleteDirectory($path);
    }

    /**
     * Cleanup temporary files.
     */
    protected function cleanup(string $tempPath): void
    {
        if (File::isDirectory($tempPath)) {
            File::deleteDirectory($tempPath);
        }
    }

    /**
     * Update an existing plugin from a ZIP file.
     *
     * @throws \Exception
     */
    public function update(Plugin $plugin, UploadedFile $zipFile): Plugin
    {
        $tempPath = $this->extractToTemp($zipFile);

        try {
            // Find and validate manifest
            $manifestPath = $this->findManifest($tempPath);
            $manifest = $this->validateManifest($manifestPath);

            // Verify slug matches
            if ($manifest['slug'] !== $plugin->slug) {
                throw new \Exception('Plugin slug in ZIP does not match the installed plugin.');
            }

            // Backup current plugin directory
            $backupPath = storage_path("app/plugins-backup/{$plugin->slug}-" . time());
            File::copyDirectory($plugin->getFullPath(), $backupPath);

            try {
                // Delete current plugin files
                File::deleteDirectory($plugin->getFullPath());

                // Find manifest for update
                $manifestPath = $this->findManifest($tempPath);
                
                // Move new files
                $this->moveToPluginsDirectory($tempPath, $plugin->slug, $manifestPath);

                // Update database record
                $plugin->update([
                    'name' => $manifest['name'],
                    'version' => $manifest['version'],
                    'description' => $manifest['description'] ?? null,
                    'author' => $manifest['author'] ?? null,
                    'author_url' => $manifest['author_url'] ?? null,
                    'category' => $manifest['category'] ?? null,
                    'requires' => $manifest['requires'] ?? null,
                ]);

                // Delete backup
                File::deleteDirectory($backupPath);

                return $plugin->fresh();
            } catch (\Throwable $e) {
                // Restore from backup
                File::deleteDirectory($plugin->getFullPath());
                File::copyDirectory($backupPath, $plugin->getFullPath());
                File::deleteDirectory($backupPath);

                throw $e;
            }
        } finally {
            $this->cleanup($tempPath);
        }
    }

    /**
     * Validate a ZIP file without installing it.
     *
     * @throws \Exception
     */
    public function validateZip(UploadedFile $zipFile): array
    {
        $tempPath = $this->extractToTemp($zipFile);

        try {
            $manifestPath = $this->findManifest($tempPath);
            return $this->validateManifest($manifestPath);
        } finally {
            $this->cleanup($tempPath);
        }
    }
}
