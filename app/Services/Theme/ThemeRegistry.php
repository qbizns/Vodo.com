<?php

declare(strict_types=1);

namespace App\Services\Theme;

use App\Models\Theme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

/**
 * Theme Registry - Manages theme registration and resolution.
 *
 * Plugins can register themes that provide layouts, templates, and components
 * for their public-facing pages (storefronts, portals, etc.).
 *
 * @example Register a theme
 * ```php
 * $registry->register('modern-store', [
 *     'name' => 'Modern Store Theme',
 *     'description' => 'A clean, modern storefront theme',
 *     'layouts' => ['main', 'checkout', 'minimal'],
 *     'templates' => ['home', 'product', 'category', 'cart', 'checkout'],
 *     'settings' => [
 *         'primary_color' => '#3B82F6',
 *         'font_family' => 'Inter',
 *     ],
 * ], 'vodo-commerce');
 * ```
 */
class ThemeRegistry
{
    protected const CACHE_PREFIX = 'theme_registry:';
    protected const CACHE_TTL = 3600;

    /**
     * Registered themes in memory.
     *
     * @var array<string, array>
     */
    protected array $themes = [];

    /**
     * Plugin ownership mapping.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Registered theme slots.
     *
     * @var array<string, array>
     */
    protected array $slots = [];

    /**
     * Slot implementations by theme.
     *
     * @var array<string, array<string, string>>
     */
    protected array $slotImplementations = [];

    /**
     * Register a new theme.
     *
     * @param string $slug Theme slug (unique identifier)
     * @param array $config Theme configuration
     * @param string|null $pluginSlug Plugin registering this theme
     * @return self
     */
    public function register(string $slug, array $config, ?string $pluginSlug = null): self
    {
        $this->themes[$slug] = array_merge([
            'slug' => $slug,
            'name' => $config['name'] ?? ucfirst($slug),
            'description' => $config['description'] ?? '',
            'version' => $config['version'] ?? '1.0.0',
            'author' => $config['author'] ?? null,
            'preview' => $config['preview'] ?? null,
            'layouts' => $config['layouts'] ?? ['main'],
            'templates' => $config['templates'] ?? [],
            'components' => $config['components'] ?? [],
            'settings_schema' => $config['settings_schema'] ?? [],
            'default_settings' => $config['default_settings'] ?? [],
            'assets' => $config['assets'] ?? [],
            'parent' => $config['parent'] ?? null,
            'supports' => $config['supports'] ?? ['storefront'],
        ], $config);

        if ($pluginSlug) {
            $this->pluginOwnership[$slug] = $pluginSlug;
        }

        // Register slot implementations if provided
        if (!empty($config['slots'])) {
            foreach ($config['slots'] as $slotName => $implementation) {
                $this->implementSlot($slug, $slotName, $implementation);
            }
        }

        // Register view namespace for theme
        $basePath = $config['path'] ?? null;
        if ($basePath && is_dir($basePath)) {
            View::addNamespace("theme-{$slug}", $basePath);
        }

        // Persist to database for admin management
        $this->persistTheme($slug, $config, $pluginSlug);

        $this->clearCache($slug);

        return $this;
    }

    /**
     * Unregister a theme.
     */
    public function unregister(string $slug): bool
    {
        if (!isset($this->themes[$slug])) {
            return false;
        }

        unset($this->themes[$slug]);
        unset($this->pluginOwnership[$slug]);
        unset($this->slotImplementations[$slug]);

        Theme::where('slug', $slug)->delete();
        $this->clearCache($slug);

        return true;
    }

    /**
     * Get a theme by slug.
     */
    public function get(string $slug): ?array
    {
        if (isset($this->themes[$slug])) {
            return $this->themes[$slug];
        }

        // Try cache
        $cached = Cache::get(self::CACHE_PREFIX . $slug);
        if ($cached) {
            $this->themes[$slug] = $cached;
            return $cached;
        }

        // Try database
        $theme = Theme::where('slug', $slug)->first();
        if ($theme) {
            $config = array_merge($theme->config ?? [], [
                'slug' => $theme->slug,
                'name' => $theme->name,
            ]);
            $this->themes[$slug] = $config;
            Cache::put(self::CACHE_PREFIX . $slug, $config, self::CACHE_TTL);
            return $config;
        }

        return null;
    }

    /**
     * Check if a theme exists.
     */
    public function has(string $slug): bool
    {
        return isset($this->themes[$slug]) || Theme::where('slug', $slug)->exists();
    }

    /**
     * Get all registered themes.
     */
    public function all(): Collection
    {
        $dbThemes = Theme::all()->keyBy('slug')->map(fn($theme) => array_merge(
            $theme->config ?? [],
            ['slug' => $theme->slug, 'name' => $theme->name]
        ));

        return collect($this->themes)->merge($dbThemes);
    }

    /**
     * Get themes by plugin.
     */
    public function getByPlugin(string $pluginSlug): Collection
    {
        return $this->all()->filter(
            fn($theme, $slug) => ($this->pluginOwnership[$slug] ?? null) === $pluginSlug
        );
    }

    /**
     * Get themes that support a specific feature.
     */
    public function getBySupport(string $feature): Collection
    {
        return $this->all()->filter(
            fn($theme) => in_array($feature, $theme['supports'] ?? [])
        );
    }

    /**
     * Get the active theme for a tenant/store.
     */
    public function getActive(?int $tenantId = null, ?string $storeSlug = null): ?array
    {
        $cacheKey = self::CACHE_PREFIX . "active:{$tenantId}:{$storeSlug}";

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $this->get($cached);
        }

        // Query for active theme
        $query = Theme::where('is_active', true);

        if ($tenantId) {
            $query->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id');
            });
        }

        $theme = $query->orderBy('tenant_id', 'desc')->first();

        if ($theme) {
            Cache::put($cacheKey, $theme->slug, self::CACHE_TTL);
            return $this->get($theme->slug);
        }

        // Return default theme
        return $this->getDefault();
    }

    /**
     * Get the default theme.
     */
    public function getDefault(): ?array
    {
        $default = Theme::where('is_default', true)->first();

        if ($default) {
            return $this->get($default->slug);
        }

        // Return first registered theme
        $first = $this->all()->first();
        return $first ?: null;
    }

    /**
     * Set the active theme for a tenant.
     */
    public function setActive(string $slug, ?int $tenantId = null): bool
    {
        if (!$this->has($slug)) {
            return false;
        }

        // Deactivate current active theme for tenant
        Theme::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Activate new theme
        Theme::where('slug', $slug)->update([
            'is_active' => true,
            'tenant_id' => $tenantId,
        ]);

        $this->clearCache("active:{$tenantId}");

        return true;
    }

    /**
     * Register a theme slot.
     *
     * Slots are named injection points where themes can provide content.
     */
    public function registerSlot(string $name, array $config = []): self
    {
        $this->slots[$name] = array_merge([
            'name' => $name,
            'description' => $config['description'] ?? '',
            'accepts' => $config['accepts'] ?? ['blade', 'component'],
            'default' => $config['default'] ?? null,
            'required' => $config['required'] ?? false,
        ], $config);

        return $this;
    }

    /**
     * Implement a slot for a theme.
     */
    public function implementSlot(string $themeSlug, string $slotName, string $implementation): self
    {
        if (!isset($this->slotImplementations[$themeSlug])) {
            $this->slotImplementations[$themeSlug] = [];
        }

        $this->slotImplementations[$themeSlug][$slotName] = $implementation;

        return $this;
    }

    /**
     * Get the implementation for a slot in a theme.
     */
    public function getSlotImplementation(string $themeSlug, string $slotName): ?string
    {
        // Check theme implementation
        if (isset($this->slotImplementations[$themeSlug][$slotName])) {
            return $this->slotImplementations[$themeSlug][$slotName];
        }

        // Check parent theme
        $theme = $this->get($themeSlug);
        if ($theme && !empty($theme['parent'])) {
            return $this->getSlotImplementation($theme['parent'], $slotName);
        }

        // Return slot default
        return $this->slots[$slotName]['default'] ?? null;
    }

    /**
     * Get all registered slots.
     */
    public function getSlots(): array
    {
        return $this->slots;
    }

    /**
     * Render a theme slot.
     */
    public function renderSlot(string $slotName, array $data = [], ?string $themeSlug = null): string
    {
        $themeSlug = $themeSlug ?? $this->getActive()['slug'] ?? null;

        if (!$themeSlug) {
            return '';
        }

        $implementation = $this->getSlotImplementation($themeSlug, $slotName);

        if (!$implementation) {
            return '';
        }

        try {
            return view($implementation, $data)->render();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to render slot {$slotName}", [
                'theme' => $themeSlug,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Get theme settings merged with defaults.
     */
    public function getSettings(string $slug, ?int $tenantId = null): array
    {
        $theme = $this->get($slug);

        if (!$theme) {
            return [];
        }

        $defaults = $theme['default_settings'] ?? [];

        // Get tenant overrides from database
        $dbTheme = Theme::where('slug', $slug)
            ->where('tenant_id', $tenantId)
            ->first();

        $overrides = $dbTheme?->settings ?? [];

        return array_merge($defaults, $overrides);
    }

    /**
     * Update theme settings for a tenant.
     */
    public function updateSettings(string $slug, array $settings, ?int $tenantId = null): bool
    {
        $theme = Theme::where('slug', $slug)->first();

        if (!$theme) {
            return false;
        }

        if ($tenantId) {
            // Create tenant-specific override
            Theme::updateOrCreate(
                ['slug' => $slug, 'tenant_id' => $tenantId],
                ['settings' => $settings]
            );
        } else {
            $theme->update(['settings' => $settings]);
        }

        $this->clearCache($slug);

        return true;
    }

    /**
     * Get the asset URL for a theme.
     */
    public function assetUrl(string $themeSlug, string $path): string
    {
        $theme = $this->get($themeSlug);
        $basePath = $theme['assets']['base_url'] ?? "/themes/{$themeSlug}";

        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Persist theme to database.
     */
    protected function persistTheme(string $slug, array $config, ?string $pluginSlug): void
    {
        Theme::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $config['name'] ?? ucfirst($slug),
                'description' => $config['description'] ?? null,
                'version' => $config['version'] ?? '1.0.0',
                'plugin_slug' => $pluginSlug,
                'config' => $config,
                'is_active' => $config['is_active'] ?? false,
                'is_default' => $config['is_default'] ?? false,
            ]
        );
    }

    /**
     * Clear theme cache.
     */
    protected function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }

    /**
     * Get plugin ownership.
     */
    public function getOwner(string $slug): ?string
    {
        return $this->pluginOwnership[$slug] ?? null;
    }
}
