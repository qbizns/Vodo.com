<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class EntityRecord extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'entity_records';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'entity_name',
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'author_id',
        'parent_id',
        'menu_order',
        'featured_image',
        'published_at',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'meta' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_TRASH = 'trash';

    /**
     * All available statuses.
     */
    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_ARCHIVED => 'Archived',
        self::STATUS_TRASH => 'Trash',
    ];

    /**
     * Loaded custom field values (cached).
     */
    protected array $loadedFields = [];

    /**
     * Fields that have been modified.
     */
    protected array $dirtyFields = [];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from title if not provided
        static::creating(function ($record) {
            if (empty($record->slug) && !empty($record->title)) {
                $record->slug = static::generateUniqueSlug($record->title, $record->entity_name);
            }
        });

        // Save field values after record is saved
        static::saved(function ($record) {
            $record->saveFieldValues();
        });
    }

    /**
     * Generate unique slug.
     */
    public static function generateUniqueSlug(string $title, string $entityName, ?int $excludeId = null): string
    {
        $slug = \Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('entity_name', $entityName)
            ->where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Get the entity definition.
     */
    public function entityDefinition(): BelongsTo
    {
        return $this->belongsTo(EntityDefinition::class, 'entity_name', 'name');
    }

    /**
     * Get the author.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(config('entity.user_model', \App\Models\User::class), 'author_id');
    }

    /**
     * Get parent record (for hierarchical entities).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Get child records.
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('menu_order');
    }

    /**
     * Get field values.
     */
    public function fieldValues(): HasMany
    {
        return $this->hasMany(EntityFieldValue::class, 'record_id');
    }

    /**
     * Get taxonomy terms.
     */
    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(
            TaxonomyTerm::class,
            'entity_record_terms',
            'record_id',
            'term_id'
        )->withPivot('order')->withTimestamps();
    }

    /**
     * Get terms for a specific taxonomy.
     */
    public function getTerms(string $taxonomyName)
    {
        return $this->terms()->where('taxonomy_name', $taxonomyName)->get();
    }

    /**
     * Sync terms for a taxonomy.
     */
    public function syncTerms(string $taxonomyName, array $termIds): void
    {
        // Get current term IDs for this taxonomy
        $currentTermIds = $this->terms()
            ->where('taxonomy_name', $taxonomyName)
            ->pluck('taxonomy_terms.id')
            ->toArray();

        // Detach old terms
        $this->terms()->detach($currentTermIds);

        // Attach new terms
        if (!empty($termIds)) {
            $attachData = [];
            foreach ($termIds as $order => $termId) {
                $attachData[$termId] = ['order' => $order];
            }
            $this->terms()->attach($attachData);
        }

        // Update term counts
        TaxonomyTerm::whereIn('id', array_merge($currentTermIds, $termIds))
            ->each(fn($term) => $term->updateCount());
    }

    /**
     * Load all field values.
     */
    public function loadFieldValues(): self
    {
        if (empty($this->loadedFields)) {
            $values = $this->fieldValues()->get()->keyBy('field_slug');
            $fields = $this->entityDefinition?->fields ?? collect();

            foreach ($fields as $field) {
                $valueModel = $values->get($field->slug);
                $rawValue = $valueModel?->value;
                $this->loadedFields[$field->slug] = $field->castFromStorage($rawValue);
            }
        }

        return $this;
    }

    /**
     * Get a custom field value.
     */
    public function getField(string $slug, $default = null)
    {
        $this->loadFieldValues();
        return $this->loadedFields[$slug] ?? $default;
    }

    /**
     * Set a custom field value.
     */
    public function setField(string $slug, $value): self
    {
        $this->loadedFields[$slug] = $value;
        $this->dirtyFields[$slug] = true;
        return $this;
    }

    /**
     * Set multiple field values.
     */
    public function setFields(array $fields): self
    {
        foreach ($fields as $slug => $value) {
            $this->setField($slug, $value);
        }
        return $this;
    }

    /**
     * Save field values to database.
     */
    public function saveFieldValues(): void
    {
        if (empty($this->dirtyFields)) {
            return;
        }

        $fields = $this->entityDefinition?->fields->keyBy('slug') ?? collect();

        foreach ($this->dirtyFields as $slug => $dirty) {
            if (!$dirty) continue;

            $field = $fields->get($slug);
            if (!$field) continue;

            $value = $this->loadedFields[$slug] ?? null;
            $storedValue = $field->castForStorage($value);

            EntityFieldValue::updateOrCreate(
                ['record_id' => $this->id, 'field_slug' => $slug],
                ['value' => $storedValue]
            );
        }

        $this->dirtyFields = [];
    }

    /**
     * Get all field values as array.
     */
    public function getFieldsArray(): array
    {
        $this->loadFieldValues();
        return $this->loadedFields;
    }

    /**
     * Magic getter for field values.
     */
    public function __get($key)
    {
        // First check native attributes
        $value = parent::__get($key);
        if ($value !== null || array_key_exists($key, $this->attributes)) {
            return $value;
        }

        // Then check custom fields
        $this->loadFieldValues();
        if (array_key_exists($key, $this->loadedFields)) {
            return $this->loadedFields[$key];
        }

        return null;
    }

    /**
     * Magic setter for field values.
     */
    public function __set($key, $value)
    {
        // Check if it's a fillable attribute
        if (in_array($key, $this->fillable) || $key === 'id') {
            parent::__set($key, $value);
            return;
        }

        // Otherwise, treat as custom field
        $this->setField($key, $value);
    }

    /**
     * Check if record is published.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Publish the record.
     */
    public function publish(): self
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->published_at = $this->published_at ?? now();
        $this->save();
        return $this;
    }

    /**
     * Unpublish (draft) the record.
     */
    public function unpublish(): self
    {
        $this->status = self::STATUS_DRAFT;
        $this->save();
        return $this;
    }

    /**
     * Archive the record.
     */
    public function archive(): self
    {
        $this->status = self::STATUS_ARCHIVED;
        $this->save();
        return $this;
    }

    /**
     * Move to trash.
     */
    public function trash(): self
    {
        $this->status = self::STATUS_TRASH;
        $this->save();
        return $this;
    }

    /**
     * Scope: Filter by entity name.
     */
    public function scopeForEntity(Builder $query, string $entityName): Builder
    {
        return $query->where('entity_name', $entityName);
    }

    /**
     * Scope: Only published records.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope: Only draft records.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope: Exclude trashed status.
     */
    public function scopeNotTrashed(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_TRASH);
    }

    /**
     * Scope: Filter by author.
     */
    public function scopeByAuthor(Builder $query, int $authorId): Builder
    {
        return $query->where('author_id', $authorId);
    }

    /**
     * Scope: Search in title and content.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
              ->orWhere('content', 'LIKE', "%{$term}%")
              ->orWhere('excerpt', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Scope: Filter by taxonomy term.
     */
    public function scopeWithTerm(Builder $query, int $termId): Builder
    {
        return $query->whereHas('terms', fn($q) => $q->where('taxonomy_terms.id', $termId));
    }

    /**
     * Scope: Filter by taxonomy and term slug.
     */
    public function scopeWithTermSlug(Builder $query, string $taxonomy, string $termSlug): Builder
    {
        return $query->whereHas('terms', function ($q) use ($taxonomy, $termSlug) {
            $q->where('taxonomy_name', $taxonomy)
              ->where('slug', $termSlug);
        });
    }

    /**
     * Convert to array with fields.
     */
    public function toArrayWithFields(): array
    {
        $this->loadFieldValues();
        
        return array_merge(
            $this->toArray(),
            ['fields' => $this->loadedFields]
        );
    }
}
