<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Settings View Type - Key-value configuration interface.
 *
 * Features:
 * - Grouped settings
 * - Various field types
 * - Auto-save option
 * - Default values
 * - Validation
 */
class SettingsViewType extends AbstractViewType
{
    protected string $name = 'settings';
    protected string $label = 'Settings View';
    protected string $description = 'Key-value configuration interface for plugin and system settings';
    protected string $icon = 'settings';
    protected string $category = 'workflow';
    protected int $priority = 10;
    protected bool $requiresEntity = false;

    protected array $supportedFeatures = [
        'grouping',
        'validation',
        'auto_save',
        'defaults',
        'import_export',
        'search',
    ];

    protected array $defaultConfig = [
        'auto_save' => false,
        'sections_collapsible' => true,
        'show_defaults' => true,
        'show_descriptions' => true,
        'submit_button' => 'Save Settings',
    ];

    protected array $extensionPoints = [
        'before_settings' => 'Content before settings form',
        'after_settings' => 'Content after settings form',
        'before_group' => 'Content before each group',
        'after_group' => 'Content after each group',
    ];

    protected array $availableActions = [
        'save' => ['label' => 'Save Settings', 'icon' => 'save', 'primary' => true],
        'reset' => ['label' => 'Reset to Defaults', 'icon' => 'refresh-cw', 'confirm' => true],
        'export' => ['label' => 'Export Settings', 'icon' => 'download'],
        'import' => ['label' => 'Import Settings', 'icon' => 'upload'],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'groups'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'settings'],
                'name' => ['type' => 'string'],
                'groups' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'required' => ['label', 'fields'],
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'icon' => ['type' => 'string'],
                            'collapsible' => ['type' => 'boolean'],
                            'fields' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'widget' => ['type' => 'string'],
                                        'label' => ['type' => 'string'],
                                        'description' => ['type' => 'string'],
                                        'default' => [],
                                        'options' => ['type' => 'array'],
                                        'validation' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['groups'])) {
            $this->addError('groups', 'Settings view requires at least one group');
            return;
        }

        foreach ($definition['groups'] as $key => $group) {
            if (empty($group['label'])) {
                $this->addError("groups.{$key}.label", 'Group label is required');
            }
            if (empty($group['fields'])) {
                $this->addError("groups.{$key}.fields", 'Group must have at least one field');
            }
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        return [
            'type' => 'settings',
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Settings',
            'groups' => [
                'general' => [
                    'label' => 'General Settings',
                    'icon' => 'settings',
                    'fields' => [
                        'enabled' => [
                            'widget' => 'checkbox',
                            'label' => 'Enabled',
                            'default' => true,
                        ],
                    ],
                ],
            ],
            'config' => $this->getDefaultConfig(),
        ];
    }
}
