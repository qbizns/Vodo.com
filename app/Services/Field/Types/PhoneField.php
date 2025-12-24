<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class PhoneField extends AbstractFieldType
{
    protected string $name = 'phone';
    protected string $label = 'Phone';
    protected string $category = 'text';
    protected string $description = 'Phone number';
    protected ?string $icon = 'phone';
    protected bool $searchable = true;
    protected bool $filterable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'string';
        $rules[] = 'max:30';
        return $rules;
    }
}
