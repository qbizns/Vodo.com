<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class AddressField extends AbstractFieldType
{
    protected string $name = 'address';
    protected string $label = 'Address';
    protected string $category = 'custom';
    protected string $description = 'Composite address field';
    protected ?string $icon = 'map-pin';
    protected string $storageType = 'json';
    protected bool $requiresSerialization = true;
    protected bool $searchable = true;
    protected bool $filterable = true;

    public function validate($value, array $fieldConfig = [], array $context = []): bool|array
    {
        if ($value === null) return !($fieldConfig['required'] ?? false);
        if (!is_array($value)) return ['Value must be an address object'];
        $errors = [];
        foreach ($fieldConfig['required_fields'] ?? [] as $field) {
            if (empty($value[$field])) $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
        return empty($errors) ? true : $errors;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null || !is_array($value)) return '';
        $parts = [];
        if (!empty($value['street'])) $parts[] = $value['street'];
        if (!empty($value['street2'])) $parts[] = $value['street2'];
        $cityLine = array_filter([
            $value['city'] ?? null, $value['state'] ?? null, $value['postal_code'] ?? null
        ]);
        if ($cityLine) $parts[] = implode(', ', $cityLine);
        if (!empty($value['country'])) $parts[] = $value['country'];
        return implode($format === 'single_line' ? ', ' : "\n", $parts);
    }

    public function getFilterOperators(): array
    {
        return ['contains', 'is_null', 'is_not_null'];
    }
}
