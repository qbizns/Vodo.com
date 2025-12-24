<?php

namespace App\Services\Field\Types;

use App\Services\Field\AbstractFieldType;

class TimeField extends AbstractFieldType
{
    protected string $name = 'time';
    protected string $label = 'Time';
    protected string $category = 'date';
    protected string $description = 'Time picker';
    protected ?string $icon = 'clock';
    protected bool $filterable = true;
    protected bool $sortable = true;

    public function getFilterOperators(): array
    {
        return ['equals', 'not_equals', 'greater_than', 'less_than', 'between', 'is_null', 'is_not_null'];
    }
}
