<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class ProgressBarWidget extends AbstractWidget
{
    protected string $name = 'progressbar';
    protected string $label = 'Progress Bar';
    protected array $supportedTypes = ['integer', 'decimal', 'float'];
    protected string $component = 'widgets.progressbar';
    protected array $defaultOptions = [
        'max' => 100,
        'show_value' => true,
        'color' => 'primary',
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null) {
            return '0%';
        }

        $max = $options['max'] ?? $this->defaultOptions['max'];
        $percent = min(100, max(0, ($value / $max) * 100));

        return round($percent) . '%';
    }
}
