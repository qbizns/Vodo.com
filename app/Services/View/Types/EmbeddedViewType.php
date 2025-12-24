<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Embedded View Type - External content container.
 *
 * Features:
 * - Iframe embedding
 * - External URL loading
 * - Secure sandboxing
 * - Height/width control
 */
class EmbeddedViewType extends AbstractViewType
{
    protected string $name = 'embedded';
    protected string $label = 'Embedded View';
    protected string $description = 'External content container for integrations and iframes';
    protected string $icon = 'external-link';
    protected string $category = 'special';
    protected int $priority = 20;
    protected bool $requiresEntity = false;

    protected array $supportedFeatures = [
        'iframe',
        'external_url',
        'sandbox',
        'responsive',
        'loading_indicator',
    ];

    protected array $defaultConfig = [
        'height' => '500px',
        'width' => '100%',
        'sandbox' => 'allow-scripts allow-same-origin',
        'loading' => 'lazy',
        'show_loading_indicator' => true,
        'allow_fullscreen' => true,
        'border' => false,
    ];

    protected array $extensionPoints = [
        'before_embed' => 'Content before the embed',
        'after_embed' => 'Content after the embed',
        'loading_state' => 'Custom loading state',
        'error_state' => 'Custom error state',
    ];

    protected array $availableActions = [
        'refresh' => ['label' => 'Refresh', 'icon' => 'refresh'],
        'fullscreen' => ['label' => 'Fullscreen', 'icon' => 'maximize'],
        'open_external' => ['label' => 'Open in New Tab', 'icon' => 'external-link'],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'src'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'embedded'],
                'name' => ['type' => 'string'],
                'src' => ['type' => 'string', 'format' => 'uri'],
                'height' => ['type' => 'string'],
                'width' => ['type' => 'string'],
                'sandbox' => ['type' => 'string'],
                'allow' => ['type' => 'string'],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['src'])) {
            $this->addError('src', 'Embedded view requires a source URL');
            return;
        }

        // Validate URL format
        if (!filter_var($definition['src'], FILTER_VALIDATE_URL) &&
            !str_starts_with($definition['src'], '/') &&
            !str_starts_with($definition['src'], '{{')) {
            $this->addError('src', 'Invalid source URL format');
        }

        // Security: Check for dangerous protocols
        $dangerousProtocols = ['javascript:', 'data:', 'vbscript:'];
        foreach ($dangerousProtocols as $protocol) {
            if (str_starts_with(strtolower($definition['src']), $protocol)) {
                $this->addError('src', 'Dangerous protocol not allowed: ' . $protocol);
            }
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        return [
            'type' => 'embedded',
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Embed',
            'src' => null, // Must be explicitly set
            'config' => $this->getDefaultConfig(),
        ];
    }
}
