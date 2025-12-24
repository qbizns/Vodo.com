<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class BooleanField extends AbstractFieldType
{
    protected string $name = 'boolean';
    protected string $label = 'Boolean';
    protected string $category = 'boolean';
    protected string $description = 'True/False value';
    protected ?string $icon = 'toggle-left';
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
