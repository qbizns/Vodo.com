<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class RichTextField extends AbstractFieldType
{
    protected string $name = 'richtext';
    protected string $label = 'Rich Text';
    protected string $category = 'text';
    protected string $description = 'Rich text with HTML formatting';
    protected ?string $icon = 'file-text';
    protected string $storageType = 'text';
    protected bool $searchable = true;

    public function formatForDisplay($value, array $fieldConfig = [], string $format = 'default'): string
    {
        if ($value === null) return '';
        return $format === 'plain' ? strip_tags($value) : $value;
    }
}
