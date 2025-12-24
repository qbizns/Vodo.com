<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class UrlWidget extends AbstractWidget
{
    protected string $name = 'url';
    protected string $label = 'URL';
    protected array $supportedTypes = ['url', 'string'];
    protected string $component = 'widgets.url';
    protected array $defaultOptions = [
        'link' => true,
        'target' => '_blank',
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $link = $options['link'] ?? $this->defaultOptions['link'];
        $target = $options['target'] ?? $this->defaultOptions['target'];

        if ($link) {
            return "<a href=\"{$value}\" target=\"{$target}\">{$value}</a>";
        }

        return $value;
    }

    public function validate(mixed $value, array $options = []): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return ['url' => 'Invalid URL'];
        }

        return [];
    }
}
