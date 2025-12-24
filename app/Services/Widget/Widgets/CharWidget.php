<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class CharWidget extends AbstractWidget
{
    protected string $name = 'char';
    protected string $label = 'Text Input';
    protected array $supportedTypes = ['string', 'text'];
    protected string $component = 'widgets.char';
    protected array $defaultOptions = [
        'placeholder' => '',
        'maxlength' => null,
        'pattern' => null,
    ];
}
