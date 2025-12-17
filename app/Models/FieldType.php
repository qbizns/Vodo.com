<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Contracts\FieldTypeContract;

class FieldType extends Model
{
    protected $fillable = [
        'name', 'label', 'category', 'description', 'handler_class',
        'config_schema', 'default_config', 'form_component', 'list_component', 'icon',
        'is_searchable', 'is_filterable', 'is_sortable',
        'supports_default', 'supports_unique', 'supports_multiple',
        'storage_type', 'requires_serialization', 'plugin_slug', 'is_system', 'is_active',
    ];

    protected $casts = [
        'config_schema' => 'array', 'default_config' => 'array',
        'is_searchable' => 'boolean', 'is_filterable' => 'boolean', 'is_sortable' => 'boolean',
        'supports_default' => 'boolean', 'supports_unique' => 'boolean', 'supports_multiple' => 'boolean',
        'requires_serialization' => 'boolean', 'is_system' => 'boolean', 'is_active' => 'boolean',
    ];

    protected ?FieldTypeContract $handlerInstance = null;

    public function getHandler(): ?FieldTypeContract
    {
        if ($this->handlerInstance) return $this->handlerInstance;
        if (!$this->handler_class || !class_exists($this->handler_class)) return null;
        $this->handlerInstance = app($this->handler_class);
        return $this->handlerInstance;
    }

    public function hasValidHandler(): bool
    {
        return $this->handler_class && class_exists($this->handler_class) 
            && is_subclass_of($this->handler_class, FieldTypeContract::class);
    }

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $handler = $this->getHandler();
        return $handler ? $handler->getValidationRules($fieldConfig, $context) : [];
    }

    public function validateValue($value, array $fieldConfig = [], array $context = []): bool|array
    {
        $handler = $this->getHandler();
        return $handler ? $handler->validate($value, $fieldConfig, $context) : true;
    }

    public function castForStorage($value, array $fieldConfig = [])
    {
        $handler = $this->getHandler();
        return $handler ? $handler->castForStorage($value, $fieldConfig) : $value;
    }

    public function castFromStorage($value, array $fieldConfig = [])
    {
        $handler = $this->getHandler();
        return $handler ? $handler->castFromStorage($value, $fieldConfig) : $value;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        $handler = $this->getHandler();
        return $handler ? $handler->formatForDisplay($value, $fieldConfig, $format) : (string) $value;
    }

    public function getFilterOperators(): array
    {
        $handler = $this->getHandler();
        return $handler ? $handler->getFilterOperators() : ['equals'];
    }

    public function getFormData(array $fieldConfig = [], array $context = []): array
    {
        $handler = $this->getHandler();
        return $handler ? $handler->getFormData($fieldConfig, $context) : [];
    }

    // Scopes
    public function scopeActive(Builder $query): Builder { return $query->where('is_active', true); }
    public function scopeForPlugin(Builder $query, string $slug): Builder { return $query->where('plugin_slug', $slug); }
    public function scopeInCategory(Builder $query, string $cat): Builder { return $query->where('category', $cat); }
    public function scopeSystem(Builder $query): Builder { return $query->where('is_system', true); }
    public function scopeCustom(Builder $query): Builder { return $query->where('is_system', false); }
    public function scopeSearchable(Builder $query): Builder { return $query->where('is_searchable', true); }
    public function scopeFilterable(Builder $query): Builder { return $query->where('is_filterable', true); }

    public static function findByName(string $name): ?self { return static::where('name', $name)->first(); }

    public static function getCategories(): array
    {
        return [
            'text' => 'Text', 'number' => 'Number', 'date' => 'Date & Time', 'choice' => 'Choice',
            'boolean' => 'Boolean', 'media' => 'Media', 'relation' => 'Relation', 'custom' => 'Custom',
        ];
    }

    public function toDefinitionArray(): array
    {
        return [
            'name' => $this->name, 'label' => $this->label, 'category' => $this->category,
            'description' => $this->description, 'icon' => $this->icon,
            'config_schema' => $this->config_schema, 'default_config' => $this->default_config,
            'form_component' => $this->form_component, 'list_component' => $this->list_component,
            'capabilities' => [
                'searchable' => $this->is_searchable, 'filterable' => $this->is_filterable,
                'sortable' => $this->is_sortable, 'supports_default' => $this->supports_default,
                'supports_unique' => $this->supports_unique, 'supports_multiple' => $this->supports_multiple,
            ],
            'storage' => ['type' => $this->storage_type, 'requires_serialization' => $this->requires_serialization],
            'filter_operators' => $this->getFilterOperators(),
            'is_system' => $this->is_system, 'plugin_slug' => $this->plugin_slug,
        ];
    }
}
