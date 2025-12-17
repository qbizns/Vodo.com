<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class FileField extends AbstractFieldType
{
    protected string $name = 'file';
    protected string $label = 'File';
    protected string $category = 'media';
    protected string $description = 'Single file upload';
    protected string $icon = 'file';
    protected string $storageType = 'json';
    protected bool $requiresSerialization = true;

    public function getValidationRules(array $fieldConfig = [], array $context = []): array
    {
        $rules = [];
        $rules[] = ($fieldConfig['required'] ?? false) ? 'required' : 'nullable';
        $rules[] = 'file';
        if ($max = $fieldConfig['max_size'] ?? null) $rules[] = "max:{$max}";
        if ($types = $fieldConfig['allowed_types'] ?? null) $rules[] = 'mimes:' . implode(',', $types);
        return $rules;
    }

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null || !is_array($value)) return '';
        $filename = $value['original_name'] ?? $value['filename'] ?? 'File';
        $size = $this->formatFileSize($value['size'] ?? 0);
        return "{$filename} ({$size})";
    }

    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getFilterOperators(): array
    {
        return ['is_null', 'is_not_null'];
    }
}

class ImageField extends AbstractFieldType
{
    protected string $name = 'image';
    protected string $label = 'Image';
    protected string $category = 'media';
    protected string $description = 'Single image upload';
    protected string $icon = 'image';
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

class GalleryField extends AbstractFieldType
{
    protected string $name = 'gallery';
    protected string $label = 'Gallery';
    protected string $category = 'media';
    protected string $description = 'Multiple image uploads';
    protected string $icon = 'images';
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

class MediaField extends AbstractFieldType
{
    protected string $name = 'media';
    protected string $label = 'Media';
    protected string $category = 'media';
    protected string $description = 'Media library picker';
    protected string $icon = 'folder';
    protected string $storageType = 'json';
    protected bool $requiresSerialization = true;

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        if (($fieldConfig['multiple'] ?? false) && is_array($value)) {
            return count($value) . ' item' . (count($value) !== 1 ? 's' : '');
        }
        return is_array($value) ? ($value['name'] ?? $value['filename'] ?? 'Media') : (string) $value;
    }

    public function getFilterOperators(): array
    {
        return ['is_null', 'is_not_null'];
    }
}
