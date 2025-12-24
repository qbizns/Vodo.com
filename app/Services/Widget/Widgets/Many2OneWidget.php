<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class Many2OneWidget extends AbstractWidget
{
    protected string $name = 'many2one';
    protected string $label = 'Related Record';
    protected array $supportedTypes = ['relation'];
    protected string $component = 'widgets.many2one';
    protected array $defaultOptions = [
        'relation' => null,
        'display_field' => 'name',
        'searchable' => true,
        'can_create' => false,
        'can_write' => false,
    ];
}
