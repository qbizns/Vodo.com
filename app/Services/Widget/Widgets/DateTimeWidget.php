<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;
use Carbon\Carbon;

class DateTimeWidget extends AbstractWidget
{
    protected string $name = 'datetime';
    protected string $label = 'Date & Time';
    protected array $supportedTypes = ['datetime'];
    protected string $component = 'widgets.datetime';
    protected array $defaultOptions = [
        'format' => 'Y-m-d H:i:s',
        'display_format' => 'M j, Y g:i A',
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
