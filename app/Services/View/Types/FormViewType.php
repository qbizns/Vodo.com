<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Form View Type - Create/Edit forms.
 *
 * Features:
 * - Field sections/groups
 * - Validation
 * - Multi-column layouts
 * - Conditional fields
 * - Computed fields
 * - Relation fields (many2one, one2many, many2many)
 */
class FormViewType extends AbstractViewType
{
    protected string $name = 'form';
    protected string $label = 'Form View';
    protected string $description = 'Create and edit forms with validation and sections';
    protected string $icon = 'edit';
    protected string $category = 'data';
    protected int $priority = 2;

    protected array $supportedFeatures = [
        'validation',
        'sections',
        'tabs',
        'multi_column',
        'conditional_fields',
        'computed_fields',
        'relations',
        'file_upload',
        'rich_text',
        'auto_save',
        'draft',
    ];

    protected array $defaultConfig = [
        'mode' => 'edit', // create, edit, view
        'columns' => 2,
        'label_position' => 'top', // top, left, floating
        'submit_button' => 'Save',
        'cancel_button' => 'Cancel',
        'show_required_indicator' => true,
        'auto_save' => false,
        'auto_save_delay' => 3000,
        'confirm_discard' => true,
        'redirect_after_save' => 'list',
    ];

    protected array $extensionPoints = [
        'before_form' => 'Content before the form',
        'after_form' => 'Content after the form',
        'before_sections' => 'Content before all sections',
        'after_sections' => 'Content after all sections',
        'before_actions' => 'Content before form actions',
        'after_actions' => 'Content after form actions',
        'form_header' => 'Form header content',
        'form_footer' => 'Form footer content',
    ];

    protected array $availableActions = [
        'save' => ['label' => 'Save', 'icon' => 'save', 'primary' => true],
        'save_and_new' => ['label' => 'Save & New', 'icon' => 'plus'],
        'save_and_close' => ['label' => 'Save & Close', 'icon' => 'check'],
        'cancel' => ['label' => 'Cancel', 'icon' => 'x'],
        'delete' => ['label' => 'Delete', 'icon' => 'trash', 'confirm' => true],
        'duplicate' => ['label' => 'Duplicate', 'icon' => 'copy'],
    ];

    protected array $requiredWidgets = [
        'char', 'text', 'html', 'integer', 'float', 'monetary',
        'date', 'datetime', 'checkbox', 'selection', 'many2one',
        'one2many', 'many2many', 'image', 'binary',
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'sections'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'form'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'mode' => ['type' => 'string', 'enum' => ['create', 'edit', 'view']],
                'sections' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'columns' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 4],
                            'collapsible' => ['type' => 'boolean'],
                            'collapsed' => ['type' => 'boolean'],
                            'condition' => ['type' => 'string'],
                            'fields' => [
                                'oneOf' => [
                                    ['type' => 'array', 'items' => ['type' => 'string']],
                                    [
                                        'type' => 'object',
                                        'additionalProperties' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'widget' => ['type' => 'string'],
                                                'label' => ['type' => 'string'],
                                                'required' => ['type' => 'boolean'],
                                                'readonly' => ['type' => 'boolean'],
                                                'span' => ['type' => 'integer'],
                                                'help' => ['type' => 'string'],
                                                'placeholder' => ['type' => 'string'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'actions' => ['type' => 'array', 'items' => ['type' => 'string']],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        // Validate sections
        if (empty($definition['sections'])) {
            $this->addError('sections', 'At least one section is required');
            return;
        }

        foreach ($definition['sections'] as $key => $section) {
            if (!is_array($section)) {
                $this->addError("sections.{$key}", 'Section must be an array');
                continue;
            }

            // Validate section has fields
            if (empty($section['fields'])) {
                $this->addError("sections.{$key}.fields", 'Section must have at least one field');
            }

            // Validate columns
            if (isset($section['columns']) && ($section['columns'] < 1 || $section['columns'] > 4)) {
                $this->addError("sections.{$key}.columns", 'Columns must be between 1 and 4');
            }
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        $sections = ['main' => ['label' => null, 'columns' => 2, 'fields' => []]];
        $formFields = $this->filterFields($fields, 'form');

        foreach ($formFields as $field) {
            $slug = is_array($field) ? $field['slug'] : $field->slug;
            $name = is_array($field) ? ($field['name'] ?? $slug) : ($field->name ?? $slug);
            $required = is_array($field) ? ($field['is_required'] ?? false) : ($field->is_required ?? false);
            $group = is_array($field) ? ($field['form_group'] ?? 'main') : ($field->form_group ?? 'main');
            $width = is_array($field) ? ($field['form_width'] ?? 'full') : ($field->form_width ?? 'full');
            $help = is_array($field) ? ($field['description'] ?? null) : ($field->description ?? null);
            $isSystem = is_array($field) ? ($field['is_system'] ?? false) : ($field->is_system ?? false);

            // Create group if not exists
            if (!isset($sections[$group])) {
                $sections[$group] = [
                    'label' => Str::title(str_replace('_', ' ', $group)),
                    'columns' => 2,
                    'fields' => [],
                ];
            }

            $sections[$group]['fields'][$slug] = [
                'widget' => $this->getWidgetForField($field),
                'label' => $name,
                'required' => $required,
                'readonly' => $isSystem,
                'span' => $width === 'full' ? 2 : 1,
                'help' => $help,
            ];
        }

        return [
            'type' => 'form',
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Form',
            'sections' => $sections,
            'actions' => ['save', 'cancel'],
            'config' => $this->getDefaultConfig(),
        ];
    }

    public function prepareData(array $definition, array $data): array
    {
        $prepared = parent::prepareData($definition, $data);

        // Normalize sections
        $sections = [];
        foreach ($definition['sections'] ?? [] as $key => $section) {
            $normalizedSection = [
                'name' => $key,
                'label' => $section['label'] ?? Str::title(str_replace('_', ' ', $key)),
                'columns' => $section['columns'] ?? 2,
                'collapsible' => $section['collapsible'] ?? false,
                'collapsed' => $section['collapsed'] ?? false,
                'condition' => $section['condition'] ?? null,
                'fields' => [],
            ];

            // Normalize fields
            $fields = $section['fields'] ?? [];
            if (is_array($fields) && !$this->isAssociativeArray($fields)) {
                // Sequential array of field names
                foreach ($fields as $fieldName) {
                    $normalizedSection['fields'][$fieldName] = [
                        'widget' => 'char',
                        'label' => Str::title(str_replace('_', ' ', $fieldName)),
                    ];
                }
            } else {
                // Associative array with field configs
                foreach ($fields as $fieldName => $fieldConfig) {
                    if (is_string($fieldConfig)) {
                        $normalizedSection['fields'][$fieldConfig] = [
                            'widget' => 'char',
                            'label' => Str::title(str_replace('_', ' ', $fieldConfig)),
                        ];
                    } else {
                        $normalizedSection['fields'][$fieldName] = array_merge([
                            'widget' => 'char',
                            'label' => Str::title(str_replace('_', ' ', $fieldName)),
                        ], $fieldConfig);
                    }
                }
            }

            $sections[$key] = $normalizedSection;
        }
        $prepared['sections'] = $sections;

        // Set mode
        $prepared['mode'] = $definition['mode'] ?? $data['mode'] ?? 'edit';

        // Merge actions
        $prepared['actions'] = $definition['actions'] ?? ['save', 'cancel'];

        return $prepared;
    }

    /**
     * Check if array is associative.
     */
    protected function isAssociativeArray(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
