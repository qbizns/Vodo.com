<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class UrlField extends AbstractFieldType
{
    protected string $name = 'url';
    protected string $label = 'URL';
    protected string $category = 'text';
    protected string $description = 'Web URL';
    protected ?string $icon = 'link';
    protected bool $searchable = true;
    protected bool $filterable = true;
    protected bool $supportsUnique = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'url';
        $rules[] = 'max:2048';
        return $rules;
    }
}
