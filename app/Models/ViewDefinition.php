<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ViewDefinition extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'category',
        'description',
        'content',
        'inherit_id',
        'priority',
        'config',
        'slots',
        'plugin_slug',
        'is_system',
        'is_active',
        'is_cacheable',
        'version',
    ];

    protected $casts = [
        'config' => 'array',
        'slots' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'is_cacheable' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * View types
     */
    public const TYPE_BLADE = 'blade';
    public const TYPE_COMPONENT = 'component';
    public const TYPE_HTML = 'html';
    public const TYPE_PARTIAL = 'partial';

    /**
     * Common categories
     */
    public const CATEGORY_ADMIN = 'admin';
    public const CATEGORY_FRONTEND = 'frontend';
    public const CATEGORY_EMAIL = 'email';
    public const CATEGORY_WIDGET = 'widget';
    public const CATEGORY_LAYOUT = 'layout';

    // =========================================================================
    // Boot
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($view) {
            if (empty($view->slug)) {
                $view->slug = Str::slug($view->name, '_');
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get extensions that target this view
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(ViewExtension::class, 'view_name', 'name')
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('sequence');
    }

    /**
     * Get the parent view (if this is an inherited view)
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'inherit_id', 'name');
    }

    /**
     * Get child views that inherit from this view
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'inherit_id', 'name');
    }

    /**
     * Get the compiled view cache
     */
    public function compiledView()
    {
        return $this->hasOne(CompiledView::class, 'view_name', 'name');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Get config value with dot notation support
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Get defined slots
     */
    public function getSlots(): array
    {
        return $this->slots ?? [];
    }

    /**
     * Check if view has a specific slot
     */
    public function hasSlot(string $name): bool
    {
        return isset($this->slots[$name]);
    }

    /**
     * Get slot configuration
     */
    public function getSlot(string $name): ?array
    {
        return $this->slots[$name] ?? null;
    }

    /**
     * Check if this view inherits from another
     */
    public function hasParent(): bool
    {
        return !empty($this->inherit_id);
    }

    /**
     * Get the full inheritance chain
     */
    public function getInheritanceChain(): array
    {
        $chain = [$this];
        $current = $this;

        while ($current->hasParent()) {
            $parent = self::where('name', $current->inherit_id)->first();
            if (!$parent || in_array($parent->id, array_column($chain, 'id'))) {
                break; // Prevent circular inheritance
            }
            $chain[] = $parent;
            $current = $parent;
        }

        return array_reverse($chain); // Root first
    }

    /**
     * Get the base content (resolving inheritance)
     */
    public function getBaseContent(): string
    {
        if (!$this->hasParent()) {
            return $this->content;
        }

        $parent = self::where('name', $this->inherit_id)->first();
        if (!$parent) {
            return $this->content;
        }

        // For inherited views, the content contains extension directives
        // The actual base comes from the parent
        return $parent->getBaseContent();
    }

    /**
     * Check if view needs recompilation
     */
    public function needsRecompilation(): bool
    {
        if (!$this->is_cacheable) {
            return true;
        }

        $compiled = $this->compiledView;
        if (!$compiled) {
            return true;
        }

        return $compiled->content_hash !== $this->computeContentHash();
    }

    /**
     * Compute hash of view content + all extensions
     */
    public function computeContentHash(): string
    {
        $parts = [
            $this->content,
            $this->updated_at?->timestamp ?? 0,
        ];

        // Include all extension content in hash
        $extensions = ViewExtension::where('view_name', $this->name)
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('sequence')
            ->get(['content', 'xpath', 'operation', 'attribute_changes', 'updated_at']);

        foreach ($extensions as $ext) {
            $parts[] = $ext->xpath;
            $parts[] = $ext->operation;
            $parts[] = $ext->content;
            $parts[] = json_encode($ext->attribute_changes);
            $parts[] = $ext->updated_at?->timestamp ?? 0;
        }

        return hash('sha256', implode('|', $parts));
    }

    // =========================================================================
    // Mutators
    // =========================================================================

    /**
     * Set config value
     */
    public function setConfig(string $key, $value): self
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;
        return $this;
    }

    /**
     * Define a slot
     */
    public function defineSlot(string $name, array $config = []): self
    {
        $slots = $this->slots ?? [];
        $slots[$name] = array_merge([
            'required' => false,
            'default' => null,
            'description' => null,
        ], $config);
        $this->slots = $slots;
        return $this;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeCacheable(Builder $query): Builder
    {
        return $query->where('is_cacheable', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('inherit_id');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Find view by name
     */
    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }

    /**
     * Get all available types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_BLADE => 'Blade Template',
            self::TYPE_COMPONENT => 'Component',
            self::TYPE_HTML => 'HTML',
            self::TYPE_PARTIAL => 'Partial',
        ];
    }

    /**
     * Get all available categories
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_ADMIN => 'Admin Panel',
            self::CATEGORY_FRONTEND => 'Frontend',
            self::CATEGORY_EMAIL => 'Email Templates',
            self::CATEGORY_WIDGET => 'Widgets',
            self::CATEGORY_LAYOUT => 'Layouts',
        ];
    }
}
