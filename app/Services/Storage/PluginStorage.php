<?php

declare(strict_types=1);

namespace App\Services\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Plugin Storage - Scoped file storage for plugins.
 *
 * Provides isolated storage for plugins with optional tenant scoping
 * and quota management.
 *
 * @example Basic usage
 * ```php
 * $storage = app(PluginStorage::class);
 *
 * // Plugin-scoped storage
 * $storage->disk('vodo-commerce')->put('products/image.jpg', $content);
 *
 * // Tenant-scoped storage within plugin
 * $storage->tenant('vodo-commerce', $tenantId)->put('logo.png', $content);
 *
 * // Check quota
 * $usage = $storage->getUsage('vodo-commerce', $tenantId);
 * ```
 */
class PluginStorage
{
    /**
     * Base storage path for plugins.
     */
    protected string $basePath = 'plugins';

    /**
     * Default quota per plugin in bytes (100MB).
     */
    protected int $defaultQuota = 104857600;

    /**
     * Get a storage disk scoped to a plugin.
     */
    public function disk(string $pluginSlug): PluginStorageDisk
    {
        return new PluginStorageDisk(
            Storage::disk($this->getDefaultDisk()),
            $this->getPluginPath($pluginSlug),
            $pluginSlug
        );
    }

    /**
     * Get a storage disk scoped to a plugin and tenant.
     */
    public function tenant(string $pluginSlug, int $tenantId): PluginStorageDisk
    {
        return new PluginStorageDisk(
            Storage::disk($this->getDefaultDisk()),
            $this->getTenantPath($pluginSlug, $tenantId),
            $pluginSlug,
            $tenantId
        );
    }

    /**
     * Get a public storage disk for a plugin (web-accessible).
     */
    public function public(string $pluginSlug): PluginStorageDisk
    {
        return new PluginStorageDisk(
            Storage::disk('public'),
            $this->getPluginPath($pluginSlug),
            $pluginSlug
        );
    }

    /**
     * Get public tenant-scoped storage.
     */
    public function publicTenant(string $pluginSlug, int $tenantId): PluginStorageDisk
    {
        return new PluginStorageDisk(
            Storage::disk('public'),
            $this->getTenantPath($pluginSlug, $tenantId),
            $pluginSlug,
            $tenantId
        );
    }

    /**
     * Get storage usage for a plugin.
     */
    public function getUsage(string $pluginSlug, ?int $tenantId = null): array
    {
        $disk = $tenantId
            ? $this->tenant($pluginSlug, $tenantId)
            : $this->disk($pluginSlug);

        $size = $disk->totalSize();
        $quota = $this->getQuota($pluginSlug, $tenantId);

        return [
            'used' => $size,
            'quota' => $quota,
            'available' => max(0, $quota - $size),
            'percentage' => $quota > 0 ? round(($size / $quota) * 100, 2) : 0,
        ];
    }

    /**
     * Get quota for a plugin/tenant.
     */
    public function getQuota(string $pluginSlug, ?int $tenantId = null): int
    {
        // Could be stored in database for dynamic quotas
        return config("plugins.storage.quotas.{$pluginSlug}", $this->defaultQuota);
    }

    /**
     * Check if storage quota is exceeded.
     */
    public function isQuotaExceeded(string $pluginSlug, ?int $tenantId = null): bool
    {
        $usage = $this->getUsage($pluginSlug, $tenantId);
        return $usage['used'] >= $usage['quota'];
    }

    /**
     * Clean up storage for a plugin (on uninstall).
     */
    public function cleanup(string $pluginSlug, ?int $tenantId = null): bool
    {
        $disk = $tenantId
            ? $this->tenant($pluginSlug, $tenantId)
            : $this->disk($pluginSlug);

        return $disk->deleteDirectory('');
    }

    /**
     * Get the plugin storage path.
     */
    protected function getPluginPath(string $pluginSlug): string
    {
        return $this->basePath . '/' . Str::slug($pluginSlug);
    }

    /**
     * Get the tenant storage path within a plugin.
     */
    protected function getTenantPath(string $pluginSlug, int $tenantId): string
    {
        return $this->getPluginPath($pluginSlug) . '/tenants/' . $tenantId;
    }

    /**
     * Get the default storage disk.
     */
    protected function getDefaultDisk(): string
    {
        return config('plugins.storage.disk', 'local');
    }
}

/**
 * Plugin Storage Disk - Scoped filesystem operations.
 */
class PluginStorageDisk
{
    public function __construct(
        protected Filesystem $filesystem,
        protected string $basePath,
        protected string $pluginSlug,
        protected ?int $tenantId = null
    ) {}

    /**
     * Get the full path for a relative path.
     */
    protected function path(string $path): string
    {
        return rtrim($this->basePath, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Store a file.
     */
    public function put(string $path, mixed $contents, array $options = []): bool
    {
        return $this->filesystem->put($this->path($path), $contents, $options);
    }

    /**
     * Store a file from an uploaded file.
     */
    public function putFile(string $path, mixed $file, array $options = []): string|false
    {
        return $this->filesystem->putFile($this->path($path), $file, $options);
    }

    /**
     * Store a file with a specific name.
     */
    public function putFileAs(string $path, mixed $file, string $name, array $options = []): string|false
    {
        return $this->filesystem->putFileAs($this->path($path), $file, $name, $options);
    }

    /**
     * Get file contents.
     */
    public function get(string $path): ?string
    {
        return $this->filesystem->get($this->path($path));
    }

    /**
     * Check if file exists.
     */
    public function exists(string $path): bool
    {
        return $this->filesystem->exists($this->path($path));
    }

    /**
     * Delete a file.
     */
    public function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : [$paths];
        $fullPaths = array_map(fn($p) => $this->path($p), $paths);

        return $this->filesystem->delete($fullPaths);
    }

    /**
     * Delete a directory.
     */
    public function deleteDirectory(string $directory): bool
    {
        return $this->filesystem->deleteDirectory($this->path($directory));
    }

    /**
     * Get all files in a directory.
     */
    public function files(string $directory = '', bool $recursive = false): array
    {
        $method = $recursive ? 'allFiles' : 'files';
        return $this->filesystem->$method($this->path($directory));
    }

    /**
     * Get all directories.
     */
    public function directories(string $directory = '', bool $recursive = false): array
    {
        $method = $recursive ? 'allDirectories' : 'directories';
        return $this->filesystem->$method($this->path($directory));
    }

    /**
     * Create a directory.
     */
    public function makeDirectory(string $path): bool
    {
        return $this->filesystem->makeDirectory($this->path($path));
    }

    /**
     * Get the URL for a file.
     */
    public function url(string $path): string
    {
        return $this->filesystem->url($this->path($path));
    }

    /**
     * Get the file size.
     */
    public function size(string $path): int
    {
        return $this->filesystem->size($this->path($path));
    }

    /**
     * Get the last modified time.
     */
    public function lastModified(string $path): int
    {
        return $this->filesystem->lastModified($this->path($path));
    }

    /**
     * Copy a file.
     */
    public function copy(string $from, string $to): bool
    {
        return $this->filesystem->copy($this->path($from), $this->path($to));
    }

    /**
     * Move a file.
     */
    public function move(string $from, string $to): bool
    {
        return $this->filesystem->move($this->path($from), $this->path($to));
    }

    /**
     * Get total size of all files.
     */
    public function totalSize(): int
    {
        $files = $this->files('', true);
        $total = 0;

        foreach ($files as $file) {
            try {
                $total += $this->filesystem->size($file);
            } catch (\Throwable $e) {
                // Skip files that can't be accessed
            }
        }

        return $total;
    }

    /**
     * Get the underlying filesystem.
     */
    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Get the base path.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }
}
