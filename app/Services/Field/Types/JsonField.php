<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class JsonField extends AbstractFieldType
{
    protected string $name = 'json';
    protected string $label = 'JSON';
    protected string $category = 'custom';
    protected string $description = 'Structured JSON data';
    protected ?string $icon = 'code';
    protected string $storageType = 'json';
    protected bool $requiresSerialization = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        return [($fieldConfig['required'] ?? false) ? 'required' : 'nullable', 'array'];
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        return $format === 'pretty' ? json_encode($value, JSON_PRETTY_PRINT) : json_encode($value);
    }

    public function getFilterOperators(): array
    {
        return ['is_null', 'is_not_null'];
    }
}
