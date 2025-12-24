<?php

namespace App\Services\Field\Types;

class CheckboxField extends MultiSelectField
{
    protected string $name = 'checkbox';
    protected string $label = 'Checkbox';
    protected string $description = 'Checkbox selection';
    protected ?string $icon = 'check-square';
}
