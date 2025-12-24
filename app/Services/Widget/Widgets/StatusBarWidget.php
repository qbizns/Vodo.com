<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class StatusBarWidget extends AbstractWidget
{
    protected string $name = 'statusbar';
    protected string $label = 'Status Bar';
    protected array $supportedTypes = ['select', 'string'];
    protected string $component = 'widgets.statusbar';
    protected array $defaultOptions = [
        'states' => [],
        'clickable' => false,
    ];
}
