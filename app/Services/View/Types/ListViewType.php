<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * List View Type - Tabular data display.
 *
 * Features:
 * - Sortable columns
 * - Filterable data
 * - Pagination
 * - Bulk actions
 * - Row actions
 * - Export capability
 * - Search
 */
class ListViewType extends AbstractViewType
{
    protected string $name = 'list';
    protected string $label = 'List View';
    protected string $description = 'Tabular data display with sorting, filtering, and pagination';
    protected string $icon = 'list';
    protected string $category = 'data';
    protected int $priority = 1;

    protected array $supportedFeatures = [
        'pagination',
        'sorting',
        'filtering',
        'searching',
        'bulk_actions',
        'row_actions',
        'export',
        'import',
        'column_visibility',
        'column_resize',
        'row_selection',
        'inline_edit',
    ];

    protected array $defaultConfig = [
        'per_page' => 25,
        'per_page_options' => [10, 25, 50, 100],
        'default_sort' => 'created_at',
        'default_sort_direction' => 'desc',
        'selectable' => true,
        'searchable' => true,
        'exportable' => true,
        'show_pagination' => true,
        'show_per_page' => true,
        'show_total' => true,
        'sticky_header' => false,
        'row_click_action' => 'view', // view, edit, none
    ];

    protected array $extensionPoints = [
        'before_header' => 'Content before the table header',
        'after_header' => 'Content after the table header',
        'before_filters' => 'Content before the filter bar',
        'after_filters' => 'Content after the filter bar',
        'before_table' => 'Content before the table',
        'after_table' => 'Content after the table',
        'empty_state' => 'Custom empty state content',
        'bulk_actions' => 'Additional bulk actions',
        'row_actions' => 'Additional row actions',
    ];

    protected array $availableActions = [
        'row' => [
            'view' => ['label' => 'View', 'icon' => 'eye'],
            'edit' => ['label' => 'Edit', 'icon' => 'edit'],
            'delete' => ['label' => 'Delete', 'icon' => 'trash', 'confirm' => true],
            'duplicate' => ['label' => 'Duplicate', 'icon' => 'copy'],
        ],
        'bulk' => [
            'delete' => ['label' => 'Delete Selected', 'icon' => 'trash', 'confirm' => true],
            'export' => ['label' => 'Export Selected', 'icon' => 'download'],
            'archive' => ['label' => 'Archive Selected', 'icon' => 'archive'],
        ],
    ];

    protected array $requiredWidgets = [
        'char', 'text', 'integer', 'float', 'date', 'datetime',
        'checkbox', 'selection', 'badge', 'many2one',
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'columns'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'list'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'columns' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'widget' => ['type' => 'string'],
                            'sortable' => ['type' => 'boolean'],
                            'filterable' => ['type' => 'boolean'],
                            'width' => ['type' => 'string'],
                            'align' => ['type' => 'string', 'enum' => ['left', 'center', 'right']],
                            'visible' => ['type' => 'boolean'],
                            'format' => ['type' => 'string'],
                        ],
                    ],
                ],
                'filters' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string'],
                            'label' => ['type' => 'string'],
                            'options' => ['type' => 'array'],
                        ],
                    ],
                ],
                'actions' => [
                    'type' => 'object',
                    'properties' => [
                        'row' => ['type' => 'array'],
                        'bulk' => ['type' => 'array'],
                    ],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        // Validate columns
        if (empty($definition['columns'])) {
            $this->addError('columns', 'At least one column is required');
            return;
        }

        foreach ($definition['columns'] as $key => $column) {
            if (!is_array($column) && !is_string($column)) {
                $this->addError("columns.{$key}", 'Column must be an array or string');
            }
        }

        // Validate filters reference valid columns or fields
        if (!empty($definition['filters'])) {
            foreach ($definition['filters'] as $key => $filter) {
                if (!is_array($filter)) {
                    $this->addError("filters.{$key}", 'Filter must be an array');
                }
            }
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        $columns = [];
        $filters = [];

        $listFields = $this->filterFields($fields, 'list');

        foreach ($listFields as $field) {
            $slug = is_array($field) ? $field['slug'] : $field->slug;
            $name = is_array($field) ? ($field['name'] ?? $slug) : ($field->name ?? $slug);
            $type = is_array($field) ? ($field['type'] ?? 'string') : ($field->type ?? 'string');
            $sortable = is_array($field) ? ($field['is_sortable'] ?? true) : ($field->is_sortable ?? true);
            $filterable = is_array($field) ? ($field['is_filterable'] ?? false) : ($field->is_filterable ?? false);

            $columns[$slug] = [
                'label' => $name,
                'widget' => $this->getWidgetForField($field),
                'sortable' => $sortable,
                'type' => $type,
            ];

            if ($filterable) {
                $filters[$slug] = [
                    'label' => $name,
                    'type' => $this->getFilterTypeForField($field),
                ];
            }
        }

        return [
            'type' => 'list',
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' List',
            'columns' => $columns,
            'filters' => $filters,
            'actions' => [
                'row' => ['view', 'edit', 'delete'],
                'bulk' => ['delete', 'export'],
            ],
            'config' => $this->getDefaultConfig(),
        ];
    }

    /**
     * Get filter type for a field.
     */
    protected function getFilterTypeForField(array|object $field): string
    {
        $type = is_array($field) ? ($field['type'] ?? 'string') : ($field->type ?? 'string');

        return match ($type) {
            'date', 'datetime' => 'date_range',
            'integer', 'decimal', 'money' => 'number_range',
            'boolean' => 'boolean',
            'select', 'relation' => 'select',
            default => 'text',
        };
    }

    public function prepareData(array $definition, array $data): array
    {
        $prepared = parent::prepareData($definition, $data);

        // Normalize columns
        $columns = [];
        foreach ($definition['columns'] ?? [] as $key => $column) {
            if (is_string($column)) {
                $columns[$column] = ['label' => Str::title(str_replace('_', ' ', $column))];
            } elseif (is_array($column)) {
                $columns[$key] = $column;
            }
        }
        $prepared['columns'] = $columns;

        // Normalize filters
        $filters = [];
        foreach ($definition['filters'] ?? [] as $key => $filter) {
            if (is_string($filter)) {
                $filters[$filter] = ['label' => Str::title(str_replace('_', ' ', $filter))];
            } elseif (is_array($filter)) {
                $filters[$key] = $filter;
            }
        }
        $prepared['filters'] = $filters;

        // Merge actions
        $prepared['actions'] = array_merge(
            ['row' => ['view', 'edit', 'delete'], 'bulk' => ['delete']],
            $definition['actions'] ?? []
        );

        return $prepared;
    }
}
