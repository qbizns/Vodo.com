<?php

namespace App\Services\Field;

use App\Contracts\FieldTypeContract;
use Illuminate\Support\Facades\Validator;

abstract class AbstractFieldType implements FieldTypeContract
{
    protected string $name;
    protected string $label;
    protected string $category = 'custom';
    protected string $description = '';
    protected ?string $icon = null;
    protected string $storageType = 'string';
    protected bool $requiresSerialization = false;
    protected ?string $formComponent = null;
    protected ?string $listComponent = null;
    protected bool $searchable = false;
    protected bool $filterable = false;
    protected bool $sortable = false;
    protected bool $supportsDefault = true;
    protected bool $supportsUnique = false;
    protected bool $supportsMultiple = false;

    public function getName(): string { return $this->name; }
    public function getLabel(): string { return $this->label; }
    public function getCategory(): string { return $this->category; }
    public function getDescription(): string { return $this->description; }
    public function getIcon(): ?string { return $this->icon; }
    public function getStorageType(): string { return $this->storageType; }
    public function requiresSerialization(): bool { return $this->requiresSerialization; }
    public function getFormComponent(): ?string { return $this->formComponent; }
    public function getListComponent(): ?string { return $this->listComponent; }
    public function isSearchable(): bool { return $this->searchable; }
    public function isFilterable(): bool { return $this->filterable; }
    public function isSortable(): bool { return $this->sortable; }
    public function supportsDefault(): bool { return $this->supportsDefault; }
    public function supportsUnique(): bool { return $this->supportsUnique; }
    public function supportsMultiple(): bool { return $this->supportsMultiple; }

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        return [($fieldConfig['required'] ?? false) ? 'required' : 'nullable'];
    }

    public function validate($value, array $fieldConfig = [], array $context = []): bool|array
    {
        $rules = $this->getValidationRules($fieldConfig, $context);
        if (empty($rules)) return true;
        $validator = Validator::make(['value' => $value], ['value' => $rules]);
        return $validator->fails() ? $validator->errors()->get('value') : true;
    }

    public function validateConfig(array $config): bool|array { return true; }
    public function getConfigSchema(): array { return ['type' => 'object', 'properties' => []]; }
    public function getDefaultConfig(): array { return []; }

    public function castForStorage($value, array $fieldConfig = [])
    {
        if ($value === null || $value === '') return null;
        if ($this->requiresSerialization) return json_encode($value);
        return match ($this->storageType) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    public function castFromStorage($value, array $fieldConfig = [])
    {
        if ($value === null) return null;
        if ($this->requiresSerialization) return json_decode($value, true);
        return match ($this->storageType) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => (bool) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        return is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'is_null', 'is_not_null'];
    }

    public function applyFilter($query, string $fieldSlug, string $operator, $value, array $fieldConfig = [])
    {
        return match ($operator) {
            'equals' => $query->whereHas('fieldValues', fn($q) => $q->where('field_slug', $fieldSlug)->where('value', $value)),
            'not_equals' => $query->whereHas('fieldValues', fn($q) => $q->where('field_slug', $fieldSlug)->where('value', '!=', $value)),
            'contains' => $query->whereHas('fieldValues', fn($q) => $q->where('field_slug', $fieldSlug)->where('value', 'like', "%{$value}%")),
            'is_null' => $query->whereDoesntHave('fieldValues', fn($q) => $q->where('field_slug', $fieldSlug)->whereNotNull('value')),
            'is_not_null' => $query->whereHas('fieldValues', fn($q) => $q->where('field_slug', $fieldSlug)->whereNotNull('value')),
            default => $query,
        };
    }

    public function beforeSave($value, array $fieldConfig = [], array $context = []) { return $value; }
    public function afterLoad($value, array $fieldConfig = [], array $context = []) { return $value; }
    public function getFormData(array $fieldConfig = [], array $context = []): array { return []; }
    public function toArray($value, array $fieldConfig = []) { return $value; }
    public function fromArray($data, array $fieldConfig = []) { return $data; }

    public function toDefinition(): array
    {
        return [
            'name' => $this->getName(), 'label' => $this->getLabel(), 'category' => $this->getCategory(),
            'description' => $this->getDescription(), 'handler_class' => static::class,
            'config_schema' => $this->getConfigSchema(), 'default_config' => $this->getDefaultConfig(),
            'form_component' => $this->getFormComponent(), 'list_component' => $this->getListComponent(),
            'icon' => $this->getIcon(), 'is_searchable' => $this->isSearchable(),
            'is_filterable' => $this->isFilterable(), 'is_sortable' => $this->isSortable(),
            'supports_default' => $this->supportsDefault(), 'supports_unique' => $this->supportsUnique(),
            'supports_multiple' => $this->supportsMultiple(), 'storage_type' => $this->getStorageType(),
            'requires_serialization' => $this->requiresSerialization(),
        ];
    }
}
