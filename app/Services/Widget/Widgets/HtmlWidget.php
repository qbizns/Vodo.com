<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class HtmlWidget extends AbstractWidget
{
    protected string $name = 'html';
    protected string $label = 'Rich Text Editor';
    protected array $supportedTypes = ['html', 'text'];
    protected string $component = 'widgets.html';
    protected array $defaultOptions = [
        'toolbar' => 'basic',
        'height' => 200,
    ];
    protected array $jsDependencies = ['tinymce'];
}
