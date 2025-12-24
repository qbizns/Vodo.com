<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class SelectionWidget extends AbstractWidget
{
    protected string $name = 'selection';
    protected string $label = 'Dropdown';
    protected array $supportedTypes = ['select', 'string'];
    protected string $component = 'widgets.selection';
    protected array $defaultOptions = [
        'options' => [],
        'placeholder' => 'Select...',
        'searchable' => true,
        'clearable' => true,
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null) {
            return '';
        }

        $optionsList = $options['options'] ?? $this->defaultOptions['options'];

        foreach ($optionsList as $option) {
            if (is_array($option) && ($option['value'] ?? null) === $value) {
                return $option['label'] ?? $value;
            }
        }

        return (string) $value;
    }
}
