<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntityDefinition extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'entity_definitions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'table_name',
        'labels',
        'config',
        'supports',
        'icon',
        'menu_position',
        'is_public',
        'has_archive',
        'show_in_menu',
        'show_in_rest',
        'is_hierarchical',
        'is_system',
        'is_active',
        'plugin_slug',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'labels' => 'array',
        'config' => 'array',
        'supports' => 'array',
        'is_public' => 'boolean',
        'has_archive' => 'boolean',
        'show_in_menu' => 'boolean',
        'show_in_rest' => 'boolean',
        'is_hierarchical' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Default values for attributes.
     */
    protected $attributes = [
        'labels' => '{}',
        'config' => '{}',
        'supports' => '["title", "content"]',
        'icon' => 'box',
        'menu_position' => 100,
        'is_public' => true,
        'has_archive' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'is_hierarchical' => false,
        'is_system' => false,
        'is_active' => true,
    ];

    /**
     * Get the fields for this entity.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(EntityField::class, 'entity_name', 'name')
            ->orderBy('form_order');
    }

    /**
     * Get all records for this entity.
     */
    public function records(): HasMany
    {
        return $this->hasMany(EntityRecord::class, 'entity_name', 'name');
    }

    /**
     * Get the taxonomies associated with this entity.
     */
    public function taxonomies()
    {
        return Taxonomy::whereJsonContains('entity_names', $this->name)->get();
    }

    /**
     * Get a label value.
     */
    public function getLabel(string $key, ?string $default = null): ?string
    {
        return $this->labels[$key] ?? $default ?? $this->name;
    }

    /**
     * Get singular label.
     */
    public function getSingularLabel(): string
    {
        return $this->getLabel('singular', $this->name);
    }

    /**
     * Get plural label.
     */
    public function getPluralLabel(): string
    {
        return $this->getLabel('plural', $this->name . 's');
    }

    /**
     * Check if entity supports a feature.
     */
    public function supports(string $feature): bool
    {
        return in_array($feature, $this->supports ?? []);
    }

    /**
     * Get config value.
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Scope to get only active entities.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get entities for a plugin.
     */
    public function scopeForPlugin($query, string $pluginSlug)
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    /**
     * Scope to get public entities.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get entities shown in menu.
     */
    public function scopeInMenu($query)
    {
        return $query->where('show_in_menu', true)->orderBy('menu_position');
    }
}
