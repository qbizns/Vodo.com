<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Exceptions\Security\SecurityException;

/**
 * Plugin Model - Represents an installed plugin.
 * 
 * Phase 10 Improvements:
 * - Path traversal protection in getFullPath()
 * - Strict type declarations
 * - Additional helper methods
 */
class Plugin extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'version',
        'description',
        'author',
        'author_url',
        'status',
        'settings',
        'requires',
        'main_class',
        'path',
        'activated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'requires' => 'array',
        'activated_at' => 'datetime',
    ];

    /**
     * Plugin status constants.
     */
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ERROR = 'error';

    /**
     * Valid slug pattern.
     */
    public const SLUG_PATTERN = '/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/';

    /**
     * Minimum slug length.
     */
    public const SLUG_MIN_LENGTH = 2;

    /**
     * Maximum slug length.
     */
    public const SLUG_MAX_LENGTH = 64;

    /**
     * Get the migrations for this plugin.
     */
    public function migrations(): HasMany
    {
        return $this->hasMany(PluginMigration::class);
    }

    /**
     * Check if the plugin is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the plugin is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Check if the plugin has an error.
     */
    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    /**
     * Get the full path to the plugin directory.
     * 
     * Includes path traversal protection to prevent directory escape attacks.
     *
     * @throws SecurityException If path traversal is detected
     */
    public function getFullPath(): string
    {
        $basePath = app_path('Plugins');
        $realBase = realpath($basePath);

        // Ensure base path exists
        if ($realBase === false) {
            // Create the plugins directory if it doesn't exist
            if (!is_dir($basePath)) {
                mkdir($basePath, 0755, true);
            }
            $realBase = realpath($basePath);
        }

        // Validate slug format to prevent path injection
        if (!$this->isValidSlug($this->slug)) {
            throw SecurityException::pathTraversal(
                "Invalid slug: {$this->slug}",
                $realBase
            );
        }

        // Construct the full path
        $fullPath = $this->path ?? "{$basePath}/{$this->slug}";

        // Check for path traversal attempts in the stored path
        if (str_contains($fullPath, '..') || str_contains($fullPath, './')) {
            throw SecurityException::pathTraversal($fullPath, $realBase);
        }

        // If the path exists, validate it's within the allowed base
        if (file_exists($fullPath)) {
            $realPath = realpath($fullPath);
            
            if ($realPath === false || !str_starts_with($realPath, $realBase)) {
                throw SecurityException::pathTraversal($fullPath, $realBase);
            }

            return $realPath;
        }

        // For non-existent paths, ensure they would be within the base directory
        // by checking the normalized path
        $normalizedPath = $this->normalizePath($fullPath);
        
        if (!str_starts_with($normalizedPath, $realBase)) {
            throw SecurityException::pathTraversal($fullPath, $realBase);
        }

        return $normalizedPath;
    }

    /**
     * Validate a plugin slug format.
     */
    public function isValidSlug(string $slug): bool
    {
        // Check length
        if (strlen($slug) < self::SLUG_MIN_LENGTH || strlen($slug) > self::SLUG_MAX_LENGTH) {
            return false;
        }

        // Check for path traversal characters
        if (str_contains($slug, '..') || str_contains($slug, '/') || str_contains($slug, '\\')) {
            return false;
        }

        // Check for null bytes
        if (str_contains($slug, "\0")) {
            return false;
        }

        // Validate format: lowercase alphanumeric with hyphens, not starting/ending with hyphen
        return (bool) preg_match(self::SLUG_PATTERN, $slug);
    }

    /**
     * Normalize a path without requiring it to exist.
     */
    protected function normalizePath(string $path): string
    {
        $parts = [];
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        foreach (explode(DIRECTORY_SEPARATOR, $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }

        $prefix = str_starts_with($path, DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '';
        return $prefix . implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * Get the main plugin class name with namespace.
     */
    public function getMainClassName(): string
    {
        if ($this->main_class) {
            return $this->main_class;
        }
        
        // Convert slug to valid PHP namespace (hyphens to underscores)
        $namespaceSlug = str_replace('-', '_', $this->slug);
        return "App\\Plugins\\{$namespaceSlug}\\{$this->getMainClassBaseName()}";
    }

    /**
     * Get the base name of the main class from the manifest.
     */
    protected function getMainClassBaseName(): string
    {
        $manifestPath = $this->getFullPath() . '/plugin.json';
        
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['main'])) {
                return pathinfo($manifest['main'], PATHINFO_FILENAME);
            }
        }

        // Default to StudlyCase version of slug
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $this->slug))) . 'Plugin';
    }

    /**
     * Get the manifest data.
     */
    public function getManifest(): ?array
    {
        $manifestPath = $this->getFullPath() . '/plugin.json';
        
        if (!file_exists($manifestPath)) {
            return null;
        }

        $content = file_get_contents($manifestPath);
        return json_decode($content, true);
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value.
     */
    public function setSetting(string $key, mixed $value): static
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        return $this;
    }

    /**
     * Check if a dependency is required.
     */
    public function requiresPlugin(string $slug): bool
    {
        $requires = $this->requires ?? [];
        return isset($requires[$slug]);
    }

    /**
     * Get the required version for a dependency.
     */
    public function getRequiredVersion(string $dependency): ?string
    {
        $requires = $this->requires ?? [];
        return $requires[$dependency] ?? null;
    }

    /**
     * Scope to get only active plugins.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get only inactive plugins.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope to get plugins with errors.
     */
    public function scopeWithErrors($query)
    {
        return $query->where('status', self::STATUS_ERROR);
    }

    /**
     * Scope to find by slug.
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
}
