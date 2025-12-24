<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class RatingField extends AbstractFieldType
{
    protected string $name = 'rating';
    protected string $label = 'Rating';
    protected string $category = 'number';
    protected string $description = 'Star rating';
    protected ?string $icon = 'star';
    protected string $storageType = 'integer';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'integer';
        $rules[] = 'min:0';
        $rules[] = 'max:' . ($fieldConfig['max'] ?? 5);
        return $rules;
    }

    public function castForStorage($value, array $fieldConfig = [])
    {
        return $value === null || $value === '' ? null : (string) (int) $value;
    }

    public function castFromStorage($value, array $fieldConfig = [])
    {
        return $value === null ? null : (int) $value;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        $max = $fieldConfig['max'] ?? 5;
        if ($format === 'text') return "{$value}/{$max}";
        return str_repeat('★', $value) . str_repeat('☆', $max - $value);
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'greater_than', 'less_than', 'between', 'is_null', 'is_not_null'];
    }
}
