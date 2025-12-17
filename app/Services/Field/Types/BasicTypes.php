<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class TextField extends AbstractFieldType
{
    protected string $name = 'text';
    protected string $label = 'Text';
    protected string $category = 'text';
    protected string $description = 'Single-line text input';
    protected string $icon = 'type';
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

class TextareaField extends AbstractFieldType
{
    protected string $name = 'textarea';
    protected string $label = 'Textarea';
    protected string $category = 'text';
    protected string $description = 'Multi-line text input';
    protected string $icon = 'align-left';
    protected string $storageType = 'text';
    protected bool $searchable = true;
    protected bool $filterable = true;
}

class RichTextField extends AbstractFieldType
{
    protected string $name = 'richtext';
    protected string $label = 'Rich Text';
    protected string $category = 'text';
    protected string $description = 'Rich text with HTML formatting';
    protected string $icon = 'file-text';
    protected string $storageType = 'text';
    protected bool $searchable = true;

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        return $format === 'plain' ? strip_tags($value) : $value;
    }
}

class NumberField extends AbstractFieldType
{
    protected string $name = 'number';
    protected string $label = 'Number';
    protected string $category = 'number';
    protected string $description = 'Numeric value';
    protected string $icon = 'hash';
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

class EmailField extends AbstractFieldType
{
    protected string $name = 'email';
    protected string $label = 'Email';
    protected string $category = 'text';
    protected string $description = 'Email address';
    protected string $icon = 'mail';
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

class UrlField extends AbstractFieldType
{
    protected string $name = 'url';
    protected string $label = 'URL';
    protected string $category = 'text';
    protected string $description = 'Web URL';
    protected string $icon = 'link';
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

class PhoneField extends AbstractFieldType
{
    protected string $name = 'phone';
    protected string $label = 'Phone';
    protected string $category = 'text';
    protected string $description = 'Phone number';
    protected string $icon = 'phone';
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

class BooleanField extends AbstractFieldType
{
    protected string $name = 'boolean';
    protected string $label = 'Boolean';
    protected string $category = 'boolean';
    protected string $description = 'True/False value';
    protected string $icon = 'toggle-left';
    protected string $storageType = 'boolean';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        return ['nullable', 'boolean'];
    }

    public function castForStorage($value, array $fieldConfig = [])
    {
        if ($value === null) return null;
        return $value ? '1' : '0';
    }

    public function castFromStorage($value, array $fieldConfig = [])
    {
        return $value === null ? null : (bool) $value;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        return $value ? ($fieldConfig['true_label'] ?? 'Yes') : ($fieldConfig['false_label'] ?? 'No');
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'is_null', 'is_not_null'];
    }
}
