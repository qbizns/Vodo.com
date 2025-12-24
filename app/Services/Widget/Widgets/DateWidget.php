<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;
use Carbon\Carbon;

class DateWidget extends AbstractWidget
{
    protected string $name = 'date';
    protected string $label = 'Date';
    protected array $supportedTypes = ['date'];
    protected string $component = 'widgets.date';
    protected array $defaultOptions = [
        'format' => 'Y-m-d',
        'display_format' => 'M j, Y',
        'min_date' => null,
        'max_date' => null,
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null) {
            return '';
        }

        $format = $options['display_format'] ?? $this->defaultOptions['display_format'];

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Exception) {
            return (string) $value;
        }
    }

    public function parse(mixed $value, array $options = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $format = $options['format'] ?? $this->defaultOptions['format'];

            return Carbon::parse($value)->format($format);
        } catch (\Exception) {
            return null;
        }
    }
}
