<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class MoneyField extends AbstractFieldType
{
    protected string $name = 'money';
    protected string $label = 'Money';
    protected string $category = 'number';
    protected string $description = 'Currency value';
    protected string $icon = 'dollar-sign';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'numeric';
        if (isset($fieldConfig['min'])) $rules[] = "min:{$fieldConfig['min']}";
        if (isset($fieldConfig['max'])) $rules[] = "max:{$fieldConfig['max']}";
        return $rules;
    }

    public function castForStorage($value, array $fieldConfig = [])
    {
        if ($value === null || $value === '') return null;
        $decimals = $fieldConfig['decimal_places'] ?? 2;
        return (string) round((float) $value * pow(10, $decimals));
    }

    public function castFromStorage($value, array $fieldConfig = [])
    {
        if ($value === null) return null;
        $decimals = $fieldConfig['decimal_places'] ?? 2;
        return (float) $value / pow(10, $decimals);
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        $decimals = $fieldConfig['decimal_places'] ?? 2;
        $symbol = $fieldConfig['currency_symbol'] ?? '$';
        $position = $fieldConfig['symbol_position'] ?? 'before';
        $formatted = number_format((float) $value, $decimals, '.', ',');
        return $position === 'before' ? $symbol . $formatted : $formatted . ' ' . $symbol;
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'greater_than', 'less_than', 'between', 'is_null', 'is_not_null'];
    }
}

class ColorField extends AbstractFieldType
{
    protected string $name = 'color';
    protected string $label = 'Color';
    protected string $category = 'custom';
    protected string $description = 'Color picker';
    protected string $icon = 'palette';
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

class SlugField extends AbstractFieldType
{
    protected string $name = 'slug';
    protected string $label = 'Slug';
    protected string $category = 'text';
    protected string $description = 'URL-friendly identifier';
    protected string $icon = 'link-2';
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

class JsonField extends AbstractFieldType
{
    protected string $name = 'json';
    protected string $label = 'JSON';
    protected string $category = 'custom';
    protected string $description = 'Structured JSON data';
    protected string $icon = 'code';
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

class RatingField extends AbstractFieldType
{
    protected string $name = 'rating';
    protected string $label = 'Rating';
    protected string $category = 'number';
    protected string $description = 'Star rating';
    protected string $icon = 'star';
    protected string $storageType = 'integer';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = parent::getValidationRules($fieldConfig, $context);
        $rules[] = 'integer';
        $rules[] = 'min:0';
        $rules[] = 'max:' . ($fieldConfig['max'] ?? 5);
        return $rules;
    }

    public function castForStorage($value, array $fieldConfig = [])
    {
        return $value === null || $value === '' ? null : (string) (int) $value;
    }

    public function castFromStorage($value, array $fieldConfig = [])
    {
        return $value === null ? null : (int) $value;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        $max = $fieldConfig['max'] ?? 5;
        if ($format === 'text') return "{$value}/{$max}";
        return str_repeat('★', $value) . str_repeat('☆', $max - $value);
    }

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'greater_than', 'less_than', 'between', 'is_null', 'is_not_null'];
    }
}

class AddressField extends AbstractFieldType
{
    protected string $name = 'address';
    protected string $label = 'Address';
    protected string $category = 'custom';
    protected string $description = 'Composite address field';
    protected string $icon = 'map-pin';
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

class LocationField extends AbstractFieldType
{
    protected string $name = 'location';
    protected string $label = 'Location';
    protected string $category = 'custom';
    protected string $description = 'GPS coordinates';
    protected string $icon = 'map';
    protected string $storageType = 'json';
    protected bool $requiresSerialization = true;
    protected bool $filterable = true;

    public function validate($value, array $fieldConfig = [], array $context = []): bool|array
    {
        if ($value === null) return !($fieldConfig['required'] ?? false);
        if (!is_array($value)) return ['Value must be a location object with lat/lng'];
        $errors = [];
        if (!isset($value['lat']) || !isset($value['lng'])) {
            $errors[] = 'Both latitude and longitude are required';
        } else {
            if ($value['lat'] < -90 || $value['lat'] > 90) $errors[] = 'Latitude must be between -90 and 90';
            if ($value['lng'] < -180 || $value['lng'] > 180) $errors[] = 'Longitude must be between -180 and 180';
        }
        return empty($errors) ? true : $errors;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null || !is_array($value)) return '';
        return sprintf('%.6f, %.6f', $value['lat'] ?? 0, $value['lng'] ?? 0);
    }

    public function getFilterOperators(): array
    {
        return ['within_radius', 'is_null', 'is_not_null'];
    }
}
