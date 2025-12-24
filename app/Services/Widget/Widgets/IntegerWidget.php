<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class IntegerWidget extends AbstractWidget
{
    protected string $name = 'integer';
    protected string $label = 'Integer';
    protected array $supportedTypes = ['integer', 'decimal'];
    protected string $component = 'widgets.integer';
    protected array $defaultOptions = [
        'min' => null,
        'max' => null,
        'step' => 1,
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null) {
            return '';
        }

        return number_format((int) $value, 0, '.', ',');
    }

    public function parse(mixed $value, array $options = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) preg_replace('/[^0-9\-]/', '', (string) $value);
    }
}
