<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class ImageField extends AbstractFieldType
{
    protected string $name = 'image';
    protected string $label = 'Image';
    protected string $category = 'media';
    protected string $description = 'Single image upload';
    protected ?string $icon = 'image';
    protected string $storageType = 'json';
    protected bool $requiresSerialization = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = [];
        $rules[] = ($fieldConfig['required'] ?? false) ? 'required' : 'nullable';
        $rules[] = 'image';
        if ($max = $fieldConfig['max_size'] ?? null) $rules[] = "max:{$max}";
        $types = $fieldConfig['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $rules[] = 'mimes:' . implode(',', $types);
        return $rules;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null || !is_array($value)) return '';
        if ($format === 'thumbnail') {
            $url = $value['thumbnails']['thumb'] ?? $value['url'] ?? '';
            return '<img src="' . e($url) . '" alt="" class="thumbnail">';
        }
        return $value['original_name'] ?? 'Image';
    }

    public function getFilterOperators(): array
    {
        return ['is_null', 'is_not_null'];
    }
}
