<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shortcode extends Model
{
    protected $fillable = [
        'tag',
        'name',
        'description',
        'handler_type',
        'handler_class',
        'handler_method',
        'handler_view',
        'attributes',
        'required_attributes',
        'has_content',
        'parse_nested',
        'content_type',
        'is_cacheable',
        'cache_ttl',
        'cache_vary_by',
        'icon',
        'category',
        'example_usage',
        'preview_data',
        'plugin_slug',
        'is_system',
        'is_active',
        'priority',
        'meta',
    ];

    protected $casts = [
        'attributes' => 'array',
        'required_attributes' => 'array',
        'has_content' => 'boolean',
        'parse_nested' => 'boolean',
        'is_cacheable' => 'boolean',
        'cache_vary_by' => 'array',
        'example_usage' => 'array',
        'preview_data' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * Handler types
     */
    public const HANDLER_CLASS = 'class';
    public const HANDLER_CLOSURE = 'closure';
    public const HANDLER_VIEW = 'view';
    public const HANDLER_CALLBACK = 'callback';

    /**
     * Content types
     */
    public const CONTENT_TEXT = 'text';
    public const CONTENT_HTML = 'html';
    public const CONTENT_MARKDOWN = 'markdown';

    /**
     * Categories
     */
    public const CATEGORY_GENERAL = 'general';
    public const CATEGORY_MEDIA = 'media';
    public const CATEGORY_LAYOUT = 'layout';
    public const CATEGORY_FORM = 'form';
    public const CATEGORY_SOCIAL = 'social';
    public const CATEGORY_EMBED = 'embed';
    public const CATEGORY_UTILITY = 'utility';

    // =========================================================================
    // Relationships
    // =========================================================================

    public function usages(): HasMany
    {
        return $this->hasMany(ShortcodeUsage::class);
    }

    // =========================================================================
    // Attribute Handling
    // =========================================================================

    /**
     * Get attribute definitions
     */
    public function getAttributeDefinitions(): array
    {
        return $this->getAttribute('attributes') ?? [];
    }

    /**
     * Get default values for all attributes
     */
    public function getDefaultAttributes(): array
    {
        $defaults = [];
        $definitions = $this->getAttributeDefinitions();

        foreach ($definitions as $name => $def) {
            if (isset($def['default'])) {
                $defaults[$name] = $def['default'];
            }
        }

        return $defaults;
    }

    /**
     * Merge provided attributes with defaults
     */
    public function mergeAttributes(array $provided): array
    {
        return array_merge($this->getDefaultAttributes(), $provided);
    }

    /**
     * Validate attributes
     */
    public function validateAttributes(array $attrs): array
    {
        $errors = [];
        $definitions = $this->getAttributeDefinitions();
        $required = $this->required_attributes ?? [];

        // Check required attributes
        foreach ($required as $name) {
            if (!isset($attrs[$name]) || $attrs[$name] === '') {
                $errors[$name] = "Attribute '{$name}' is required";
            }
        }

        // Validate types
        foreach ($attrs as $name => $value) {
            if (!isset($definitions[$name])) {
                continue; // Unknown attributes are allowed
            }

            $def = $definitions[$name];
            $type = $def['type'] ?? 'string';

            $valid = match ($type) {
                'string' => is_string($value) || is_numeric($value),
                'integer', 'int' => is_numeric($value) && (int)$value == $value,
                'float', 'number' => is_numeric($value),
                'boolean', 'bool' => in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no'], true) || is_bool($value),
                'array' => is_array($value),
                'enum' => isset($def['options']) && in_array($value, $def['options']),
                default => true,
            };

            if (!$valid) {
                $errors[$name] = "Attribute '{$name}' must be of type {$type}";
            }

            // Check min/max for numbers
            if (in_array($type, ['integer', 'int', 'float', 'number']) && $valid) {
                if (isset($def['min']) && $value < $def['min']) {
                    $errors[$name] = "Attribute '{$name}' must be at least {$def['min']}";
                }
                if (isset($def['max']) && $value > $def['max']) {
                    $errors[$name] = "Attribute '{$name}' must be at most {$def['max']}";
                }
            }

            // Check pattern for strings
            if ($type === 'string' && isset($def['pattern'])) {
                if (!preg_match($def['pattern'], $value)) {
                    $errors[$name] = "Attribute '{$name}' format is invalid";
                }
            }
        }

        return $errors;
    }

    /**
     * Cast attribute to proper type
     */
    public function castAttribute($name, $value)
    {
        $definitions = $this->getAttributeDefinitions();
        
        if (!isset($definitions[$name])) {
            return $value;
        }

        $type = $definitions[$name]['type'] ?? 'string';

        return match ($type) {
            'integer', 'int' => (int) $value,
            'float', 'number' => (float) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => is_array($value) ? $value : explode(',', $value),
            default => (string) $value,
        };
    }

    /**
     * Cast all attributes to proper types
     */
    public function castAttributes(array $attrs): array
    {
        $casted = [];
        
        foreach ($attrs as $name => $value) {
            $casted[$name] = $this->castAttribute($name, $value);
        }

        return $casted;
    }

    // =========================================================================
    // Handler
    // =========================================================================

    /**
     * Check if handler is valid
     */
    public function hasValidHandler(): bool
    {
        return match ($this->handler_type) {
            self::HANDLER_CLASS => $this->handler_class 
                && class_exists($this->handler_class)
                && method_exists($this->handler_class, $this->handler_method ?? 'render'),
            self::HANDLER_VIEW => $this->handler_view && view()->exists($this->handler_view),
            self::HANDLER_CLOSURE, self::HANDLER_CALLBACK => true,
            default => false,
        };
    }

    // =========================================================================
    // Cache Key
    // =========================================================================

    /**
     * Generate cache key for rendered output
     */
    public function getCacheKey(array $attrs, ?string $content = null): string
    {
        $parts = [
            'shortcode',
            $this->tag,
            md5(serialize($attrs)),
        ];

        if ($this->has_content && $content !== null) {
            $parts[] = md5($content);
        }

        // Add vary-by values
        if ($this->cache_vary_by) {
            foreach ($this->cache_vary_by as $vary) {
                $parts[] = match ($vary) {
                    'user' => auth()->id() ?? 'guest',
                    'locale' => app()->getLocale(),
                    'url' => request()->url(),
                    default => $vary,
                };
            }
        }

        return implode(':', $parts);
    }

    /**
     * Get cache TTL in seconds
     */
    public function getCacheTtl(): int
    {
        return $this->cache_ttl ?? config('shortcodes.cache.default_ttl', 3600);
    }

    // =========================================================================
    // Documentation
    // =========================================================================

    /**
     * Generate usage documentation
     */
    public function toDocumentation(): array
    {
        $attrs = $this->getAttributeDefinitions();
        $attrDocs = [];

        foreach ($attrs as $name => $def) {
            $attrDocs[$name] = [
                'type' => $def['type'] ?? 'string',
                'description' => $def['description'] ?? '',
                'default' => $def['default'] ?? null,
                'required' => in_array($name, $this->required_attributes ?? []),
                'options' => $def['options'] ?? null,
            ];
        }

        return [
            'tag' => $this->tag,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'has_content' => $this->has_content,
            'attributes' => $attrDocs,
            'examples' => $this->example_usage ?? [],
            'syntax' => $this->has_content 
                ? "[{$this->tag}]content[/{$this->tag}]"
                : "[{$this->tag} /]",
        ];
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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('tag');
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Find by tag
     */
    public static function findByTag(string $tag): ?self
    {
        return static::where('tag', strtolower($tag))->first();
    }

    /**
     * Get all categories
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GENERAL => 'General',
            self::CATEGORY_MEDIA => 'Media',
            self::CATEGORY_LAYOUT => 'Layout',
            self::CATEGORY_FORM => 'Forms',
            self::CATEGORY_SOCIAL => 'Social',
            self::CATEGORY_EMBED => 'Embeds',
            self::CATEGORY_UTILITY => 'Utility',
        ];
    }
}
