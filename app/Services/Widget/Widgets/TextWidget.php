<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class TextWidget extends AbstractWidget
{
    protected string $name = 'text';
    protected string $label = 'Textarea';
    protected array $supportedTypes = ['text', 'string'];
    protected string $component = 'widgets.text';
    protected array $defaultOptions = [
        'rows' => 3,
        'placeholder' => '',
    ];
}
