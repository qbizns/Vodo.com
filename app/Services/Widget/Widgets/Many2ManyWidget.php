<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class Many2ManyWidget extends AbstractWidget
{
    protected string $name = 'many2many';
    protected string $label = 'Multiple Records';
    protected array $supportedTypes = ['relation'];
    protected string $component = 'widgets.many2many';
    protected array $defaultOptions = [
        'relation' => null,
        'display_field' => 'name',
        'display_type' => 'tags',
        'can_create' => false,
    ];
}
