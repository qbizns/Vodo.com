<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class TextField extends AbstractFieldType
{
    protected string $name = 'text';
    protected string $label = 'Text';
    protected string $category = 'text';
    protected string $description = 'Single-line text input';
    protected ?string $icon = 'type';
    protected bool $searchable = true;
    protected bool $filterable = true;
    protected bool $sortable = true;
    protected bool $supportsUnique = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'string';
        if ($max = $fieldConfig['max_length'] ?? null) $rules[] = "max:{$max}";
        if ($min = $fieldConfig['min_length'] ?? null) $rules[] = "min:{$min}";
        return $rules;
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'contains', 'starts_with', 'ends_with', 'is_null', 'is_not_null'];
    }
}
