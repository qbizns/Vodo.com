<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Theme Model
 *
 * Represents a registered theme in the platform.
 * Themes provide layouts, templates, and components for public-facing pages.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $version
 * @property string|null $plugin_slug
 * @property int|null $tenant_id
 * @property array|null $config
 * @property array|null $settings
 * @property bool $is_active
 * @property bool $is_default
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Theme extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'version',
        'plugin_slug',
        'tenant_id',
        'config',
        'settings',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get the tenant that owns this theme configuration.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope: Active themes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Default theme.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Themes by plugin.
     */
    public function scopeForPlugin($query, string $pluginSlug)
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    /**
     * Scope: Themes for a tenant.
     */
    public function scopeForTenant($query, ?int $tenantId)
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->orWhereNull('tenant_id');
        });
    }

    /**
     * Get a specific layout path.
     */
    public function getLayoutPath(string $layout = 'main'): string
    {
        $layouts = $this->config['layouts'] ?? [];

        if (isset($layouts[$layout])) {
            return $layouts[$layout];
        }

        return "theme-{$this->slug}::layouts.{$layout}";
    }

    /**
     * Get a specific template path.
     */
    public function getTemplatePath(string $template): string
    {
        $templates = $this->config['templates'] ?? [];

        if (isset($templates[$template])) {
            return $templates[$template];
        }

        return "theme-{$this->slug}::templates.{$template}";
    }

    /**
     * Get merged settings (defaults + overrides).
     */
    public function getMergedSettings(): array
    {
        $defaults = $this->config['default_settings'] ?? [];
        return array_merge($defaults, $this->settings ?? []);
    }

    /**
     * Check if theme supports a feature.
     */
    public function supports(string $feature): bool
    {
        $supports = $this->config['supports'] ?? ['storefront'];
        return in_array($feature, $supports);
    }
}
