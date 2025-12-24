<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class SlugField extends AbstractFieldType
{
    protected string $name = 'slug';
    protected string $label = 'Slug';
    protected string $category = 'text';
    protected string $description = 'URL-friendly identifier';
    protected ?string $icon = 'link-2';
    protected bool $searchable = true;
    protected bool $filterable = true;
    protected bool $sortable = true;
    protected bool $supportsUnique = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'string';
        $rules[] = 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
        $rules[] = 'max:' . ($fieldConfig['max_length'] ?? 255);
        return $rules;
    }

    public function beforeSave($value, array $fieldConfig = [], array $context = [])
    {
        if ($value === null && isset($fieldConfig['source_field'], $context[$fieldConfig['source_field']])) {
            $value = \Illuminate\Support\Str::slug($context[$fieldConfig['source_field']]);
        }
        return $value;
    }
}
