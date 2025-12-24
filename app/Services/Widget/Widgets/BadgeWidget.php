<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class BadgeWidget extends AbstractWidget
{
    protected string $name = 'badge';
    protected string $label = 'Badge';
    protected array $supportedTypes = ['string', 'select'];
    protected string $component = 'widgets.badge';
    protected array $defaultOptions = [
        'colors' => [],
        'default_color' => 'gray',
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $colors = $options['colors'] ?? $this->defaultOptions['colors'];
        $defaultColor = $options['default_color'] ?? $this->defaultOptions['default_color'];
        $color = $colors[$value] ?? $defaultColor;

        return "<span class=\"badge badge-{$color}\">{$value}</span>";
    }
}
