<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class NumberField extends AbstractFieldType
{
    protected string $name = 'number';
    protected string $label = 'Number';
    protected string $category = 'number';
    protected string $description = 'Numeric value';
    protected ?string $icon = 'hash';
    protected bool $filterable = true;
    protected bool $sortable = true;
    protected bool $supportsUnique = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'numeric';
        if (isset($fieldConfig['min'])) $rules[] = "min:{$fieldConfig['min']}";
        if (isset($fieldConfig['max'])) $rules[] = "max:{$fieldConfig['max']}";
        if ($fieldConfig['integer'] ?? false) $rules[] = 'integer';
        return $rules;
    }

    public function castFromStorage($value, array $fieldConfig = [])
    {
        if ($value === null) return null;
        return ($fieldConfig['integer'] ?? false) ? (int) $value : (float) $value;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        $decimals = $fieldConfig['decimal_places'] ?? 2;
        return ($fieldConfig['integer'] ?? false) 
            ? number_format((int) $value) 
            : number_format((float) $value, $decimals);
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'greater_than', 'less_than', 'between', 'is_null', 'is_not_null'];
    }
}
