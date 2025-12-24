<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class GalleryField extends AbstractFieldType
{
    protected string $name = 'gallery';
    protected string $label = 'Gallery';
    protected string $category = 'media';
    protected string $description = 'Multiple image uploads';
    protected ?string $icon = 'images';
    protected string $storageType = 'json';
    protected bool $requiresSerialization = true;
    protected bool $supportsMultiple = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = ['nullable', 'array'];
        if ($min = $fieldConfig['min_images'] ?? null) $rules[] = "min:{$min}";
        if ($max = $fieldConfig['max_images'] ?? null) $rules[] = "max:{$max}";
        $rules['*'] = ['image'];
        return $rules;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null || !is_array($value)) return '';
        $count = count($value);
        return "{$count} image" . ($count !== 1 ? 's' : '');
    }

    public function getFilterOperators(): array
    {
        return ['is_null', 'is_not_null'];
    }
}
