<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class JsonWidget extends AbstractWidget
{
    protected string $name = 'json';
    protected string $label = 'JSON Editor';
    protected array $supportedTypes = ['json', 'text'];
    protected string $component = 'widgets.json';
    protected array $defaultOptions = [
        'mode' => 'tree',
        'height' => 300,
    ];
    protected array $jsDependencies = ['jsoneditor'];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return $value;
        }

        return json_encode($value, JSON_PRETTY_PRINT);
    }

    public function parse(mixed $value, array $options = []): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }
}
