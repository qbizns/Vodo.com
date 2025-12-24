<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class One2ManyWidget extends AbstractWidget
{
    protected string $name = 'one2many';
    protected string $label = 'Related Records';
    protected array $supportedTypes = ['relation'];
    protected string $component = 'widgets.one2many';
    protected array $defaultOptions = [
        'relation' => null,
        'view_type' => 'list',
        'can_create' => true,
        'can_delete' => true,
    ];
}
