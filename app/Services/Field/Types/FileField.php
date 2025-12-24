<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class FileField extends AbstractFieldType
{
    protected string $name = 'file';
    protected string $label = 'File';
    protected string $category = 'media';
    protected string $description = 'Single file upload';
    protected ?string $icon = 'file';
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
