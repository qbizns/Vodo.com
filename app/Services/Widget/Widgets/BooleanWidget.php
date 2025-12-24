<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class BooleanWidget extends AbstractWidget
{
    protected string $name = 'boolean';
    protected string $label = 'Checkbox';
    protected array $supportedTypes = ['boolean'];
    protected string $component = 'widgets.boolean';
    protected array $defaultOptions = [
        'true_label' => 'Yes',
        'false_label' => 'No',
    ];

    public function format(mixed $value, array $options = []): string
    {
        $trueLabel = $options['true_label'] ?? $this->defaultOptions['true_label'];
        $falseLabel = $options['false_label'] ?? $this->defaultOptions['false_label'];

        return $value ? $trueLabel : $falseLabel;
    }

    public function parse(mixed $value, array $options = []): mixed
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
