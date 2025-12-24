<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class RadioField extends AbstractFieldType
{
    protected string $name = 'radio';
    protected string $label = 'Radio';
    protected string $category = 'choice';
    protected string $description = 'Radio button selection';
    protected ?string $icon = 'circle';
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

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'in', 'not_in', 'is_null', 'is_not_null'];
    }
}
