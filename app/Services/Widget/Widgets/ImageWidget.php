<?php

declare(strict_types=1);

namespace App\Services\Widget\Widgets;

use App\Services\Widget\AbstractWidget;

class ImageWidget extends AbstractWidget
{
    protected string $name = 'image';
    protected string $label = 'Image';
    protected array $supportedTypes = ['image', 'file'];
    protected string $component = 'widgets.image';
    protected array $defaultOptions = [
        'max_size' => 5 * 1024 * 1024,
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'preview_size' => 'thumbnail',
    ];
}
