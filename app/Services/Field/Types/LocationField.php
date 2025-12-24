<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class LocationField extends AbstractFieldType
{
    protected string $name = 'location';
    protected string $label = 'Location';
    protected string $category = 'custom';
    protected string $description = 'GPS coordinates';
    protected ?string $icon = 'map';
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
