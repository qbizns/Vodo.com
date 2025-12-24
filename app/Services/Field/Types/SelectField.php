<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class SelectField extends AbstractFieldType
{
    protected string $name = 'select';
    protected string $label = 'Select';
    protected string $category = 'choice';
    protected string $description = 'Dropdown selection';
    protected ?string $icon = 'chevron-down';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $options = array_column($fieldConfig['options'] ?? [], 'value');
        if (!empty($options)) $rules[] = 'in:' . implode(',', $options);
        return $rules;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        foreach ($fieldConfig['options'] ?? [] as $option) {
            if ($option['value'] === $value) return $option['label'];
        }
        return $value;
    }

    public function getFormData(array $fieldConfig = [], array $context = []): array
    {
        return ['options' => $fieldConfig['options'] ?? []];
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'in', 'not_in', 'is_null', 'is_not_null'];
    }
}
