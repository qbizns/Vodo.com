<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class ColorWidget extends AbstractWidget
{
    protected string $name = 'color';
    protected string $label = 'Color Picker';
    protected array $supportedTypes = ['color', 'string'];
    protected string $component = 'widgets.color';
    protected array $defaultOptions = [
        'show_preview' => true,
        'show_hex' => true,
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $showPreview = $options['show_preview'] ?? $this->defaultOptions['show_preview'];

        if ($showPreview) {
            return "<span class=\"inline-block w-4 h-4 rounded\" style=\"background-color:{$value}\"></span> {$value}";
        }

        return $value;
    }
}
