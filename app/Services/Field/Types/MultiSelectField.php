<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class MultiSelectField extends AbstractFieldType
{
    protected string $name = 'multiselect';
    protected string $label = 'Multi-Select';
    protected string $category = 'choice';
    protected string $description = 'Multiple selection';
    protected ?string $icon = 'check-square';
    protected string $storageType = 'json';
    protected bool $requiresSerialization = true;
    protected bool $filterable = true;
    protected bool $supportsMultiple = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = ['nullable', 'array'];
        $options = array_column($fieldConfig['options'] ?? [], 'value');
        if (!empty($options)) $rules['*'] = 'in:' . implode(',', $options);
        return $rules;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null || empty($value)) return '';
        $values = is_array($value) ? $value : json_decode($value, true);
        $labels = [];
        foreach ($fieldConfig['options'] ?? [] as $option) {
            if (in_array($option['value'], $values)) $labels[] = $option['label'];
        }
        return implode(', ', $labels);
    }

    public function getFilterOperators(): array
    {
        return ['contains', 'not_contains', 'is_null', 'is_not_null'];
    }
}
