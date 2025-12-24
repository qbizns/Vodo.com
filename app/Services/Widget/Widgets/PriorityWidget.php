<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class PriorityWidget extends AbstractWidget
{
    protected string $name = 'priority';
    protected string $label = 'Priority Stars';
    protected array $supportedTypes = ['integer', 'select'];
    protected string $component = 'widgets.priority';
    protected array $defaultOptions = [
        'max' => 3,
        'icon' => 'star',
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null) {
            return '';
        }

        $max = $options['max'] ?? $this->defaultOptions['max'];
        $value = min($max, max(0, (int) $value));

        return str_repeat('★', $value) . str_repeat('☆', $max - $value);
    }
}
