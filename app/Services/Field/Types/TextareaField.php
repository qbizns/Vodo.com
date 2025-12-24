<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class TextareaField extends AbstractFieldType
{
    protected string $name = 'textarea';
    protected string $label = 'Textarea';
    protected string $category = 'text';
    protected string $description = 'Multi-line text input';
    protected ?string $icon = 'align-left';
    protected string $storageType = 'text';
    protected bool $searchable = true;
    protected bool $filterable = true;
}
