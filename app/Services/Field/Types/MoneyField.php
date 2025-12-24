<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class MoneyField extends AbstractFieldType
{
    protected string $name = 'money';
    protected string $label = 'Money';
    protected string $category = 'number';
    protected string $description = 'Currency value';
    protected ?string $icon = 'dollar-sign';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'numeric';
        if (isset($fieldConfig['min'])) $rules[] = "min:{$fieldConfig['min']}";
        if (isset($fieldConfig['max'])) $rules[] = "max:{$fieldConfig['max']}";
        return $rules;
    }

    public function castForStorage($value, array $fieldConfig = [])
    {
        if ($value === null || $value === '') return null;
        $decimals = $fieldConfig['decimal_places'] ?? 2;
        return (string) round((float) $value * pow(10, $decimals));
    }

    public function castFromStorage($value, array $fieldConfig = [])
    {
        if ($value === null) return null;
        $decimals = $fieldConfig['decimal_places'] ?? 2;
        return (float) $value / pow(10, $decimals);
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        $decimals = $fieldConfig['decimal_places'] ?? 2;
        $symbol = $fieldConfig['currency_symbol'] ?? '$';
        $position = $fieldConfig['symbol_position'] ?? 'before';
        $formatted = number_format((float) $value, $decimals, '.', ',');
        return $position === 'before' ? $symbol . $formatted : $formatted . ' ' . $symbol;
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'greater_than', 'less_than', 'between', 'is_null', 'is_not_null'];
    }
}
