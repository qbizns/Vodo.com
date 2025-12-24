<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class ColorField extends AbstractFieldType
{
    protected string $name = 'color';
    protected string $label = 'Color';
    protected string $category = 'custom';
    protected string $description = 'Color picker';
    protected ?string $icon = 'palette';
    protected bool $filterable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';
        return $rules;
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'is_null', 'is_not_null'];
    }
}
