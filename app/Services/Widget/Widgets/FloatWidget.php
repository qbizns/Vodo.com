<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class FloatWidget extends AbstractWidget
{
    protected string $name = 'float';
    protected string $label = 'Decimal';
    protected array $supportedTypes = ['decimal', 'float', 'integer'];
    protected string $component = 'widgets.float';
    protected array $defaultOptions = [
        'decimals' => 2,
        'min' => null,
        'max' => null,
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null) {
            return '';
        }

        $decimals = $options['decimals'] ?? $this->defaultOptions['decimals'];

        return number_format((float) $value, $decimals, '.', ',');
    }

    public function parse(mixed $value, array $options = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    }
}
