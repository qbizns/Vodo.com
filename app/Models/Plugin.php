<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Exceptions\Security\SecurityException;

/**
 * Plugin Model - Represents an installed plugin.
 * 
 * Enhanced for Plugin Management System per DATABASE.md specification.
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
        'homepage',
        'status',
        'category',
        'icon',
        'is_core',
        'is_premium',
        'requires_license',
        'min_system_version',
        'min_php_version',
        'path',
        'namespace',
        'entry_class',
        'checksum',
        'error_message',
        'settings',
        'requires',
        'main_class',
        'activated_at',
        'installed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'requires' => 'array',
        'is_core' => 'boolean',
        'is_premium' => 'boolean',
        'requires_license' => 'boolean',
        'activated_at' => 'datetime',
        'installed_at' => 'datetime',
    ];

    /**
     * Plugin status constants.
     */
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ERROR = 'error';
    public const STATUS_UPDATING = 'updating';

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

    // ==================== Relationships ====================

    /**
     * Get the migrations for this plugin.
     */
    public function migrations(): HasMany
    {
        return $this->hasMany(PluginMigration::class);
    }

    /**
     * Get the settings for this plugin.
     */
    public function pluginSettings(): HasMany
    {
        return $this->hasMany(PluginSetting::class);
    }

    /**
     * Get the license for this plugin.
     */
    public function license(): HasOne
    {
        return $this->hasOne(PluginLicense::class);
    }

    /**
     * Get the available update for this plugin.
     */
    public function availableUpdate(): HasOne
    {
        return $this->hasOne(PluginUpdate::class);
    }

    /**
     * Get the dependencies for this plugin.
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(PluginDependency::class);
    }

    /**
     * Get the events/audit log for this plugin.
     */
    public function events(): HasMany
    {
        return $this->hasMany(PluginEvent::class);
    }

    // ==================== Scopes ====================

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
     * Scope to get plugins that have updates available.
     */
    public function scopeHasUpdate($query)
    {
        return $query->whereHas('availableUpdate');
    }

    /**
     * Scope to get core plugins.
     */
    public function scopeCore($query)
    {
        return $query->where('is_core', true);
    }

    /**
     * Scope to get premium plugins.
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to find by slug.
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Scope to search plugins.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%");
        });
    }

    // ==================== Accessors ====================

    /**
     * Check if plugin is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if plugin has an update available.
     */
    public function getHasUpdateAttribute(): bool
    {
        return $this->availableUpdate !== null;
    }

    /**
     * Get the latest available version.
     */
    public function getLatestVersionAttribute(): ?string
    {
        return $this->availableUpdate?->latest_version;
    }

    /**
     * Check if plugin has a valid license.
     */
    public function getHasValidLicenseAttribute(): bool
    {
        if (!$this->requires_license) {
            return true;
        }
        return $this->license?->isValid() ?? false;
    }

    /**
     * Check if plugin has settings configured.
     */
    public function getHasSettingsAttribute(): bool
    {
        // Check if plugin provides a getSettingsFields method
        try {
            $instance = app('plugins.manager')->getLoadedPlugin($this->slug);
            return $instance && method_exists($instance, 'getSettingsFields');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the plugin icon URL.
     * 
     * Converts the relative icon path from plugin.json to a proper URL
     * that can be used in templates.
     */
    public function getIconUrlAttribute(): ?string
    {
        if (empty($this->icon)) {
            return null;
        }

        // If it's already a full URL, return as-is
        if (str_starts_with($this->icon, 'http://') || str_starts_with($this->icon, 'https://')) {
            return $this->icon;
        }

        // If it's a public asset path (starts with /), return as-is
        if (str_starts_with($this->icon, '/')) {
            return $this->icon;
        }

        // Build URL using the plugin asset route
        try {
            return route('admin.plugins.asset', [
                'slug' => $this->slug,
                'path' => $this->icon,
            ]);
        } catch (\Exception $e) {
            // Fallback if route doesn't exist
            return "/admin/plugins/{$this->slug}/assets/{$this->icon}";
        }
    }

    // ==================== Status Methods ====================

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
     * Check if the plugin is updating.
     */
    public function isUpdating(): bool
    {
        return $this->status === self::STATUS_UPDATING;
    }

    // ==================== Capability Methods ====================

    /**
     * Check if plugin can be activated.
     */
    public function canActivate(): bool
    {
        if ($this->status === self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->requires_license && !$this->has_valid_license) {
            return false;
        }

        // Check dependencies
        foreach ($this->dependencies as $dep) {
            if (!$dep->is_optional && !$dep->isSatisfied()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if plugin can be deactivated.
     */
    public function canDeactivate(): bool
    {
        if ($this->is_core) {
            return false;
        }

        // Check if other plugins depend on this one
        $dependents = PluginDependency::where('dependency_slug', $this->slug)
            ->whereHas('plugin', fn($q) => $q->active())
            ->count();

        return $dependents === 0;
    }

    /**
     * Check if plugin can be uninstalled.
     */
    public function canUninstall(): bool
    {
        return !$this->is_core && $this->canDeactivate();
    }

    // ==================== Settings Methods ====================

    /**
     * Get a setting value from the plugin_settings table.
     */
    public function getPluginSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->pluginSettings()->where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return $this->castSettingValue($setting);
    }

    /**
     * Set a setting value in the plugin_settings table.
     */
    public function setPluginSetting(string $key, mixed $value, string $group = 'general'): void
    {
        $this->pluginSettings()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'group' => $group,
                'type' => $this->getSettingType($value),
            ]
        );
    }

    /**
     * Get all settings for a group.
     */
    public function getPluginSettings(?string $group = null): array
    {
        $query = $this->pluginSettings();
        
        if ($group) {
            $query->where('group', $group);
        }

        return $query->get()
            ->mapWithKeys(fn($s) => [$s->key => $this->castSettingValue($s)])
            ->toArray();
    }

    /**
     * Cast a setting value based on its type.
     */
    protected function castSettingValue(PluginSetting $setting): mixed
    {
        $value = $setting->value;

        return match ($setting->type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Determine the type of a setting value.
     */
    protected function getSettingType(mixed $value): string
    {
        return match (true) {
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_bool($value) => 'boolean',
            is_array($value) => 'array',
            default => 'string',
        };
    }

    /**
     * Get a setting value (legacy - from settings JSON column).
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value (legacy - to settings JSON column).
     */
    public function setSetting(string $key, mixed $value): static
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        return $this;
    }

    // ==================== Event Logging ====================

    /**
     * Log a plugin event.
     */
    public function logEvent(string $event, array $payload = [], ?string $previousVersion = null): void
    {
        $this->events()->create([
            'plugin_slug' => $this->slug,
            'event' => $event,
            'version' => $this->version,
            'previous_version' => $previousVersion,
            'user_id' => auth()->id(),
            'payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    // ==================== Path Methods ====================

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

        if ($this->entry_class) {
            return $this->entry_class;
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

    // ==================== Dependency Methods ====================

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
     * Get dependent plugins (plugins that require this plugin).
     */
    public function getDependentPlugins(): \Illuminate\Database\Eloquent\Collection
    {
        $dependentIds = PluginDependency::where('dependency_slug', $this->slug)
            ->pluck('plugin_id');
        
        return Plugin::whereIn('id', $dependentIds)->get();
    }
}
