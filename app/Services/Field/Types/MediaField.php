<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class MediaField extends AbstractFieldType
{
    protected string $name = 'media';
    protected string $label = 'Media';
    protected string $category = 'media';
    protected string $description = 'Media library picker';
    protected ?string $icon = 'folder';
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
