<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class FileWidget extends AbstractWidget
{
    protected string $name = 'file';
    protected string $label = 'File Upload';
    protected array $supportedTypes = ['file', 'binary'];
    protected string $component = 'widgets.file';
    protected array $defaultOptions = [
        'max_size' => 10 * 1024 * 1024,
        'allowed_types' => [],
        'multiple' => false,
    ];
}
