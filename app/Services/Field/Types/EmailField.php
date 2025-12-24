<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class EmailField extends AbstractFieldType
{
    protected string $name = 'email';
    protected string $label = 'Email';
    protected string $category = 'text';
    protected string $description = 'Email address';
    protected ?string $icon = 'mail';
    protected bool $searchable = true;
    protected bool $filterable = true;
    protected bool $sortable = true;
    protected bool $supportsUnique = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'email';
        $rules[] = 'max:255';
        return $rules;
    }
}
