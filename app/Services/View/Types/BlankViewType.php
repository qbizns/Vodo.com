<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Blank View Type - Empty canvas (REQUIRES APPROVAL).
 *
 * This view type provides a blank canvas for truly custom layouts.
 * Use should be rare and requires explicit approval.
 *
 * Features:
 * - Custom template
 * - Full control
 * - No constraints
 */
class BlankViewType extends AbstractViewType
{
    protected string $name = 'blank';
    protected string $label = 'Blank View';
    protected string $description = 'Empty canvas for truly custom layouts - REQUIRES APPROVAL';
    protected string $icon = 'layout';
    protected string $category = 'special';
    protected int $priority = 19;
    protected bool $requiresEntity = false;

    protected array $supportedFeatures = [
        'custom_template',
        'full_control',
    ];

    protected array $defaultConfig = [
        'approval' => null, // Must be set to approval ID
    ];

    protected array $extensionPoints = [
        'content' => 'Main content area',
    ];

    protected array $availableActions = [];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'template', 'approval'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'blank'],
                'name' => ['type' => 'string'],
                'template' => ['type' => 'string'],
                'approval' => ['type' => 'string', 'pattern' => '^APPROVED-\\d{4}-\\d+$'],
                'data' => ['type' => 'object'],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['template'])) {
            $this->addError('template', 'Blank view requires a template path');
        }

        if (empty($definition['approval'])) {
            $this->addError('approval', 'Blank view requires an approval ID (format: APPROVED-YYYY-NNN)');
        } elseif (!preg_match('/^APPROVED-\d{4}-\d+$/', $definition['approval'])) {
            $this->addError('approval', 'Invalid approval ID format. Expected: APPROVED-YYYY-NNN');
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        // Blank views should not be auto-generated
        return [
            'type' => 'blank',
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Custom View',
            'template' => null, // Must be explicitly set
            'approval' => null, // Must be explicitly set
            'config' => $this->getDefaultConfig(),
        ];
    }

    /**
     * Override validate to add approval check.
     */
    public function validate(array $definition): array
    {
        $errors = parent::validate($definition);

        // Additional strict check for blank views
        if (empty($errors) && empty($definition['approval'])) {
            $errors['approval'] = 'CRITICAL: Blank views require explicit approval. Use canonical view types instead.';
        }

        return $errors;
    }
}
