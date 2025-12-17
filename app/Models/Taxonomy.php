<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Taxonomy extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'taxonomies';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'labels',
        'entity_names',
        'is_hierarchical',
        'is_public',
        'show_in_menu',
        'show_in_rest',
        'allow_multiple',
        'icon',
        'config',
        'plugin_slug',
        'is_system',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'labels' => 'array',
        'entity_names' => 'array',
        'config' => 'array',
        'is_hierarchical' => 'boolean',
        'is_public' => 'boolean',
        'show_in_menu' => 'boolean',
        'show_in_rest' => 'boolean',
        'allow_multiple' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Default values.
     */
    protected $attributes = [
        'labels' => '{}',
        'entity_names' => '[]',
        'config' => '{}',
        'is_hierarchical' => false,
        'is_public' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'allow_multiple' => true,
        'icon' => 'tag',
        'is_system' => false,
    ];

    /**
     * Get all terms for this taxonomy.
     */
    public function terms(): HasMany
    {
        return $this->hasMany(TaxonomyTerm::class, 'taxonomy_name', 'name');
    }

    /**
     * Get root terms (no parent).
     */
    public function rootTerms(): HasMany
    {
        return $this->terms()->whereNull('parent_id')->orderBy('menu_order');
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
     * Check if taxonomy applies to an entity.
     */
    public function appliesToEntity(string $entityName): bool
    {
        return in_array($entityName, $this->entity_names ?? []);
    }

    /**
     * Get config value.
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Scope: Get taxonomies for an entity.
     */
    public function scopeForEntity($query, string $entityName)
    {
        return $query->whereJsonContains('entity_names', $entityName);
    }

    /**
     * Scope: Get public taxonomies.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: Get taxonomies shown in menu.
     */
    public function scopeInMenu($query)
    {
        return $query->where('show_in_menu', true);
    }

    /**
     * Scope: Get taxonomies for a plugin.
     */
    public function scopeForPlugin($query, string $pluginSlug)
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    /**
     * Create a new term in this taxonomy.
     */
    public function createTerm(array $data): TaxonomyTerm
    {
        $data['taxonomy_name'] = $this->name;
        return TaxonomyTerm::create($data);
    }

    /**
     * Get term by slug.
     */
    public function getTerm(string $slug): ?TaxonomyTerm
    {
        return $this->terms()->where('slug', $slug)->first();
    }

    /**
     * Get terms as tree (for hierarchical taxonomies).
     */
    public function getTree(): array
    {
        if (!$this->is_hierarchical) {
            return $this->terms()->orderBy('menu_order')->get()->toArray();
        }

        $terms = $this->terms()->orderBy('menu_order')->get();
        return $this->buildTree($terms);
    }

    /**
     * Build hierarchical tree from flat list.
     */
    protected function buildTree($terms, $parentId = null): array
    {
        $branch = [];
        
        foreach ($terms as $term) {
            if ($term->parent_id === $parentId) {
                $children = $this->buildTree($terms, $term->id);
                if ($children) {
                    $term->children = $children;
                }
                $branch[] = $term;
            }
        }

        return $branch;
    }
}
