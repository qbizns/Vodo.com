<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class PhoneWidget extends AbstractWidget
{
    protected string $name = 'phone';
    protected string $label = 'Phone';
    protected array $supportedTypes = ['phone', 'string'];
    protected string $component = 'widgets.phone';
    protected array $defaultOptions = [
        'link' => true,
        'format' => null,
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $link = $options['link'] ?? $this->defaultOptions['link'];

        if ($link) {
            $tel = preg_replace('/[^0-9+]/', '', $value);

            return "<a href=\"tel:{$tel}\">{$value}</a>";
        }

        return $value;
    }
}
