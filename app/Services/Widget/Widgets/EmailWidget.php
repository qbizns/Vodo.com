<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class EmailWidget extends AbstractWidget
{
    protected string $name = 'email';
    protected string $label = 'Email';
    protected array $supportedTypes = ['email', 'string'];
    protected string $component = 'widgets.email';
    protected array $defaultOptions = [
        'link' => true,
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $link = $options['link'] ?? $this->defaultOptions['link'];

        if ($link) {
            return "<a href=\"mailto:{$value}\">{$value}</a>";
        }

        return $value;
    }

    public function validate(mixed $value, array $options = []): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return ['email' => 'Invalid email address'];
        }

        return [];
    }
}
