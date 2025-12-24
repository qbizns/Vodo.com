<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class TagsWidget extends AbstractWidget
{
    protected string $name = 'tags';
    protected string $label = 'Tags';
    protected array $supportedTypes = ['json', 'relation'];
    protected string $component = 'widgets.tags';
    protected array $defaultOptions = [
        'color_field' => 'color',
        'separator' => ',',
        'max_tags' => null,
        'suggestions' => [],
    ];

    public function format(mixed $value, array $options = []): string
    {
        if ($value === null || empty($value)) {
            return '';
        }

        if (is_string($value)) {
            $value = explode($options['separator'] ?? ',', $value);
        }

        return implode(', ', array_map(fn($tag) => is_array($tag) ? ($tag['name'] ?? $tag) : $tag, $value));
    }
}
