<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ViewExtension extends Model
{
    protected $fillable = [
        'name',
        'view_name',
        'xpath',
        'operation',
        'content',
        'attribute_changes',
        'priority',
        'sequence',
        'conditions',
        'plugin_slug',
        'is_system',
        'is_active',
        'description',
    ];

    protected $casts = [
        'attribute_changes' => 'array',
        'conditions' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'sequence' => 'integer',
    ];

    /**
     * Operation types
     */
    public const OP_BEFORE = 'before';
    public const OP_AFTER = 'after';
    public const OP_REPLACE = 'replace';
    public const OP_REMOVE = 'remove';
    public const OP_INSIDE_FIRST = 'inside_first';
    public const OP_INSIDE_LAST = 'inside_last';
    public const OP_WRAP = 'wrap';
    public const OP_ATTRIBUTES = 'attributes';

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the view this extension targets
     */
    public function view(): BelongsTo
    {
        return $this->belongsTo(ViewDefinition::class, 'view_name', 'name');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Check if this operation requires content
     */
    public function requiresContent(): bool
    {
        return !in_array($this->operation, [self::OP_REMOVE, self::OP_ATTRIBUTES]);
    }

    /**
     * Check if this is an attribute operation
     */
    public function isAttributeOperation(): bool
    {
        return $this->operation === self::OP_ATTRIBUTES;
    }

    /**
     * Get human-readable operation name
     */
    public function getOperationLabel(): string
    {
        return match($this->operation) {
            self::OP_BEFORE => 'Insert Before',
            self::OP_AFTER => 'Insert After',
            self::OP_REPLACE => 'Replace',
            self::OP_REMOVE => 'Remove',
            self::OP_INSIDE_FIRST => 'Prepend Inside',
            self::OP_INSIDE_LAST => 'Append Inside',
            self::OP_WRAP => 'Wrap With',
            self::OP_ATTRIBUTES => 'Modify Attributes',
            default => ucfirst($this->operation),
        };
    }

    /**
     * Check if conditions are met for this context
     */
    public function conditionsMet(array $context = []): bool
    {
        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $type = $condition['type'] ?? 'equals';
            $field = $condition['field'] ?? null;
            $value = $condition['value'] ?? null;

            if (!$field) {
                continue;
            }

            $contextValue = data_get($context, $field);

            $met = match($type) {
                'equals' => $contextValue == $value,
                'not_equals' => $contextValue != $value,
                'contains' => is_string($contextValue) && str_contains($contextValue, $value),
                'in' => is_array($value) && in_array($contextValue, $value),
                'not_in' => is_array($value) && !in_array($contextValue, $value),
                'exists' => $contextValue !== null,
                'not_exists' => $contextValue === null,
                'true' => (bool)$contextValue === true,
                'false' => (bool)$contextValue === false,
                default => true,
            };

            if (!$met) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get attribute changes for a specific attribute
     */
    public function getAttributeChange(string $attribute): ?array
    {
        return $this->attribute_changes[$attribute] ?? null;
    }

    /**
     * Get classes to add
     */
    public function getClassesToAdd(): array
    {
        $classChange = $this->getAttributeChange('class');
        if (!$classChange) {
            return [];
        }

        $add = $classChange['add'] ?? '';
        return array_filter(explode(' ', $add));
    }

    /**
     * Get classes to remove
     */
    public function getClassesToRemove(): array
    {
        $classChange = $this->getAttributeChange('class');
        if (!$classChange) {
            return [];
        }

        $remove = $classChange['remove'] ?? '';
        return array_filter(explode(' ', $remove));
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /**
     * Validate the XPath expression
     */
    public function validateXpath(): bool
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadHTML('<html><body></body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $xpath = new \DOMXPath($dom);
            $xpath->query($this->xpath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get validation rules for creating/updating
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'view_name' => ['required', 'string', 'max:100'],
            'xpath' => ['required', 'string', 'max:500'],
            'operation' => ['required', 'in:before,after,replace,remove,inside_first,inside_last,wrap,attributes'],
            'content' => ['nullable', 'string'],
            'attribute_changes' => ['nullable', 'array'],
            'priority' => ['integer', 'min:0', 'max:9999'],
            'sequence' => ['integer', 'min:0'],
            'conditions' => ['nullable', 'array'],
            'description' => ['nullable', 'string'],
        ];
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForView(Builder $query, string $viewName): Builder
    {
        return $query->where('view_name', $viewName);
    }

    public function scopeForPlugin(Builder $query, string $pluginSlug): Builder
    {
        return $query->where('plugin_slug', $pluginSlug);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('sequence');
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeOfOperation(Builder $query, string $operation): Builder
    {
        return $query->where('operation', $operation);
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * Get all operation types
     */
    public static function getOperations(): array
    {
        return [
            self::OP_BEFORE => 'Insert Before',
            self::OP_AFTER => 'Insert After',
            self::OP_REPLACE => 'Replace',
            self::OP_REMOVE => 'Remove',
            self::OP_INSIDE_FIRST => 'Prepend Inside',
            self::OP_INSIDE_LAST => 'Append Inside',
            self::OP_WRAP => 'Wrap With',
            self::OP_ATTRIBUTES => 'Modify Attributes',
        ];
    }

    /**
     * Get extensions for a view, ready to apply
     */
    public static function getForView(string $viewName, array $context = []): \Illuminate\Support\Collection
    {
        $extensions = static::forView($viewName)
            ->active()
            ->ordered()
            ->get();

        // Filter by conditions
        if (!empty($context)) {
            $extensions = $extensions->filter(fn($ext) => $ext->conditionsMet($context));
        }

        return $extensions;
    }

    /**
     * Common XPath patterns for convenience
     */
    public static function xpathPatterns(): array
    {
        return [
            'by_id' => '//*[@id="{id}"]',
            'by_class' => '//*[contains(@class, "{class}")]',
            'by_data' => '//*[@data-{attr}="{value}"]',
            'by_tag' => '//{tag}',
            'by_name' => '//*[@name="{name}"]',
            'first_of_class' => '(//*[contains(@class, "{class}")])[1]',
            'last_of_class' => '(//*[contains(@class, "{class}")])[last()]',
            'child_of_id' => '//*[@id="{id}"]/{child}',
            'descendant_of_id' => '//*[@id="{id}"]//{descendant}',
        ];
    }

    /**
     * Build XPath from pattern
     */
    public static function buildXpath(string $pattern, array $replacements): string
    {
        $patterns = self::xpathPatterns();
        $xpath = $patterns[$pattern] ?? $pattern;

        foreach ($replacements as $key => $value) {
            $xpath = str_replace('{' . $key . '}', $value, $xpath);
        }

        return $xpath;
    }
}
