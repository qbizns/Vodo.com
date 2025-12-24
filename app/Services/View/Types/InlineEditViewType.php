<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Inline Edit View Type - In-place row editing.
 *
 * Features:
 * - Click to edit cells
 * - Row-level editing
 * - Auto-save
 * - Validation feedback
 */
class InlineEditViewType extends AbstractViewType
{
    protected string $name = 'inline-edit';
    protected string $label = 'Inline Edit View';
    protected string $description = 'In-place row editing for quick data updates';
    protected string $icon = 'edit-3';
    protected string $category = 'special';
    protected int $priority = 18;

    protected array $supportedFeatures = [
        'cell_editing',
        'row_editing',
        'auto_save',
        'validation',
        'undo',
        'bulk_edit',
    ];

    protected array $defaultConfig = [
        'edit_mode' => 'cell', // cell, row
        'auto_save' => true,
        'save_delay' => 500, // ms
        'show_save_indicator' => true,
        'confirm_changes' => false,
        'allow_add_row' => true,
        'allow_delete_row' => true,
    ];

    protected array $extensionPoints = [
        'before_table' => 'Content before the table',
        'after_table' => 'Content after the table',
        'cell_editor' => 'Custom cell editor',
        'row_actions' => 'Additional row actions',
    ];

    protected array $availableActions = [
        'row' => [
            'save' => ['label' => 'Save', 'icon' => 'check'],
            'cancel' => ['label' => 'Cancel', 'icon' => 'x'],
            'delete' => ['label' => 'Delete', 'icon' => 'trash', 'confirm' => true],
        ],
        'table' => [
            'add_row' => ['label' => 'Add Row', 'icon' => 'plus'],
            'save_all' => ['label' => 'Save All', 'icon' => 'save'],
        ],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'columns'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'inline-edit'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'columns' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'widget' => ['type' => 'string'],
                            'editable' => ['type' => 'boolean'],
                            'width' => ['type' => 'string'],
                            'required' => ['type' => 'boolean'],
                        ],
                    ],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['columns'])) {
            $this->addError('columns', 'Inline edit view requires at least one column');
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        $columns = [];
        $listFields = $this->filterFields($fields, 'list');

        foreach ($listFields as $field) {
            $slug = is_array($field) ? $field['slug'] : $field->slug;
            $name = is_array($field) ? ($field['name'] ?? $slug) : ($field->name ?? $slug);
            $isSystem = is_array($field) ? ($field['is_system'] ?? false) : ($field->is_system ?? false);

            $columns[$slug] = [
                'label' => $name,
                'widget' => $this->getWidgetForField($field),
                'editable' => !$isSystem,
            ];
        }

        return [
            'type' => 'inline-edit',
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Inline Edit',
            'columns' => $columns,
            'config' => $this->getDefaultConfig(),
        ];
    }
}
