<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaxonomyTerm extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'taxonomy_terms';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'taxonomy_name',
        'name',
        'slug',
        'description',
        'parent_id',
        'menu_order',
        'count',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'meta' => 'array',
        'count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name if not provided
        static::creating(function ($term) {
            if (empty($term->slug)) {
                $term->slug = static::generateUniqueSlug($term->name, $term->taxonomy_name);
            }
        });
    }

    /**
     * Generate unique slug within taxonomy.
     */
    public static function generateUniqueSlug(string $name, string $taxonomyName, ?int $excludeId = null): string
    {
        $slug = \Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('taxonomy_name', $taxonomyName)
            ->where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Get the taxonomy definition.
     */
    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class, 'taxonomy_name', 'name');
    }

    /**
     * Get parent term.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Get child terms.
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('menu_order');
    }

    /**
     * Get all descendants.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors.
     */
    public function getAncestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors->reverse();
    }

    /**
     * Get records using this term.
     */
    public function records(): BelongsToMany
    {
        return $this->belongsToMany(
            EntityRecord::class,
            'entity_record_terms',
            'term_id',
            'record_id'
        )->withPivot('order')->withTimestamps();
    }

    /**
     * Update the record count.
     */
    public function updateCount(): self
    {
        $this->count = $this->records()->count();
        $this->save();
        return $this;
    }

    /**
     * Get meta value.
     */
    public function getMeta(string $key, $default = null)
    {
        return data_get($this->meta, $key, $default);
    }

    /**
     * Set meta value.
     */
    public function setMeta(string $key, $value): self
    {
        $meta = $this->meta ?? [];
        data_set($meta, $key, $value);
        $this->meta = $meta;
        return $this;
    }

    /**
     * Get full path (breadcrumb).
     */
    public function getPath(): string
    {
        $ancestors = $this->getAncestors();
        $ancestors->push($this);
        
        return $ancestors->pluck('name')->implode(' > ');
    }

    /**
     * Check if term has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if term is ancestor of another term.
     */
    public function isAncestorOf(TaxonomyTerm $term): bool
    {
        return $term->getAncestors()->contains('id', $this->id);
    }

    /**
     * Scope: Get root terms.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Get terms for a taxonomy.
     */
    public function scopeForTaxonomy($query, string $taxonomyName)
    {
        return $query->where('taxonomy_name', $taxonomyName);
    }

    /**
     * Scope: Order by menu order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('menu_order')->orderBy('name');
    }

    /**
     * Scope: Get terms with count > 0.
     */
    public function scopeWithRecords($query)
    {
        return $query->where('count', '>', 0);
    }
}
