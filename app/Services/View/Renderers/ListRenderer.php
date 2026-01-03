<?php

declare(strict_types=1);

namespace App\Services\View\Renderers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * List Renderer - Renders entity list views from view definitions.
 *
 * Converts list view definitions registered via ViewRegistry into HTML.
 * Supports columns, sorting, filtering, pagination, and row actions.
 *
 * @example Usage
 * ```php
 * $renderer = new ListRenderer();
 * $html = $renderer->render($listDefinition, [
 *     'records' => $records,
 *     'filters' => request()->only(['search', 'status']),
 * ], [
 *     'user' => auth()->user(),
 * ]);
 * ```
 */
class ListRenderer extends AbstractRenderer
{
    /**
     * {@inheritdoc}
     */
    public function getViewType(): string
    {
        return 'list';
    }

    /**
     * {@inheritdoc}
     */
    public function getViewPath(): string
    {
        return 'backend.entity.list';
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $definition, array $data = [], array $context = []): string
    {
        $prepared = $this->prepareData($definition, $data);
        
        return view($this->getViewPath(), $prepared)->render();
    }

    /**
     * {@inheritdoc}
     */
    public function prepareData(array $definition, array $data): array
    {
        $entityName = $definition['entity'] ?? '';
        $entity = $this->getEntity($entityName);
        $records = $data['records'] ?? collect();

        // Prepare columns from definition
        $columns = $this->prepareColumns($definition);

        // Prepare row actions
        $rowActions = $this->prepareRowActions($definition);

        // Prepare bulk actions
        $bulkActions = $this->prepareBulkActions($definition);

        // Prepare filters
        $filters = $this->prepareFilters($definition, $data['filters'] ?? []);

        return [
            'viewType' => 'list',
            'viewDefinition' => $definition,
            'entity' => $entity,
            'entityName' => $entityName,
            'records' => $records,
            'columns' => $columns,
            'rowActions' => $rowActions,
            'bulkActions' => $bulkActions,
            'filters' => $filters,
            'currentFilters' => $data['filters'] ?? [],
            'pagination' => $this->preparePagination($records),
            'createUrl' => $data['createUrl'] ?? route('admin.entities.create', $entityName),
            'apiUrl' => $data['apiUrl'] ?? url("/api/v1/entities/{$entityName}"),
            'pageTitle' => $data['pageTitle'] ?? ($entity ? $entity->getPluralLabel() : 'Records'),
            'config' => array_merge([
                'selectable' => true,
                'searchable' => true,
                'exportable' => false,
                'per_page' => 25,
            ], $definition['config'] ?? []),
        ];
    }

    /**
     * Prepare column configurations from definition.
     */
    protected function prepareColumns(array $definition): array
    {
        $rawColumns = $definition['columns'] ?? [];

        if (empty($rawColumns)) {
            // Generate from entity fields
            $entityName = $definition['entity'] ?? '';
            return $this->generateDefaultColumns($entityName);
        }

        $columns = [];
        foreach ($rawColumns as $columnName => $columnConfig) {
            // Handle array of column names vs associative column configs
            if (is_int($columnName) && is_string($columnConfig)) {
                $columnName = $columnConfig;
                $columnConfig = [];
            }

            $columns[$columnName] = $this->normalizeColumn($columnName, $columnConfig);
        }

        return $columns;
    }

    /**
     * Normalize a column configuration.
     */
    protected function normalizeColumn(string $columnName, array|string $columnConfig): array
    {
        if (is_string($columnConfig)) {
            $columnConfig = ['label' => $columnConfig];
        }

        return array_merge([
            'name' => $columnName,
            'label' => $columnConfig['label'] ?? $this->fieldToLabel($columnName),
            'sortable' => $columnConfig['sortable'] ?? false,
            'searchable' => $columnConfig['searchable'] ?? false,
            'filterable' => $columnConfig['filterable'] ?? false,
            'width' => $columnConfig['width'] ?? 'auto',
            'align' => $columnConfig['align'] ?? 'left',
            'widget' => $columnConfig['widget'] ?? 'text',
            'link' => $columnConfig['link'] ?? ($columnName === 'name' || $columnName === 'title'),
            'truncate' => $columnConfig['truncate'] ?? null,
            'format' => $columnConfig['format'] ?? null,
            'config' => $columnConfig['config'] ?? [],
        ], $columnConfig);
    }

    /**
     * Generate default columns from entity fields.
     */
    protected function generateDefaultColumns(string $entityName): array
    {
        if (empty($entityName)) {
            return [];
        }

        $fields = $this->getEntityFields($entityName);
        $columns = [];

        foreach ($fields as $field) {
            if (!$field->show_in_list) {
                continue;
            }

            $columns[$field->slug] = [
                'name' => $field->slug,
                'label' => $field->name,
                'sortable' => $field->is_sortable,
                'searchable' => $field->is_searchable,
                'filterable' => $field->is_filterable,
                'width' => 'auto',
                'align' => in_array($field->type, ['integer', 'decimal', 'money']) ? 'right' : 'left',
                'widget' => $this->getListWidgetForFieldType($field->type),
                'link' => $field->slug === 'name' || $field->slug === 'title',
                'truncate' => $field->type === 'text' ? 50 : null,
                'format' => null,
                'config' => $field->config ?? [],
            ];
        }

        return $columns;
    }

    /**
     * Get widget for field type in list context.
     */
    protected function getListWidgetForFieldType(string $type): string
    {
        return match ($type) {
            'boolean' => 'badge',
            'select' => 'badge',
            'date' => 'date',
            'datetime' => 'datetime',
            'money', 'decimal' => 'currency',
            'image' => 'thumbnail',
            'email' => 'email',
            'url' => 'link',
            'relation' => 'relation',
            default => 'text',
        };
    }

    /**
     * Prepare row actions.
     */
    protected function prepareRowActions(array $definition): array
    {
        $actions = $definition['actions'] ?? ['view', 'edit', 'delete'];

        // Handle structured actions config
        if (isset($definition['actions']['row'])) {
            $actions = $definition['actions']['row'];
        }

        $prepared = [];
        foreach ($actions as $action) {
            if (is_string($action)) {
                $prepared[] = $this->prepareRowAction($action);
            } else {
                $prepared[] = array_merge($this->prepareRowAction($action['name'] ?? ''), $action);
            }
        }

        return $prepared;
    }

    /**
     * Prepare a single row action.
     */
    protected function prepareRowAction(string $actionName): array
    {
        return [
            'name' => $actionName,
            'label' => $this->getRowActionLabel($actionName),
            'icon' => $this->getActionIcon($actionName),
            'class' => $this->getRowActionClass($actionName),
            'confirm' => in_array($actionName, ['delete', 'destroy', 'remove', 'archive']),
            'method' => $this->getRowActionMethod($actionName),
        ];
    }

    /**
     * Get row action label.
     */
    protected function getRowActionLabel(string $action): string
    {
        return match ($action) {
            'view' => 'View',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'duplicate' => 'Duplicate',
            'archive' => 'Archive',
            'restore' => 'Restore',
            default => $this->fieldToLabel($action),
        };
    }

    /**
     * Get row action CSS class.
     */
    protected function getRowActionClass(string $action): string
    {
        return match ($action) {
            'delete', 'destroy', 'remove' => 'text-danger',
            'archive' => 'text-warning',
            default => '',
        };
    }

    /**
     * Get HTTP method for row action.
     */
    protected function getRowActionMethod(string $action): string
    {
        return match ($action) {
            'delete', 'destroy' => 'DELETE',
            'archive', 'restore' => 'POST',
            default => 'GET',
        };
    }

    /**
     * Prepare bulk actions.
     */
    protected function prepareBulkActions(array $definition): array
    {
        $actions = [];

        // Handle structured actions config
        if (isset($definition['actions']['bulk'])) {
            $actions = $definition['actions']['bulk'];
        }

        if (empty($actions)) {
            // Default bulk actions
            $actions = ['delete', 'export'];
        }

        $prepared = [];
        foreach ($actions as $action) {
            if (is_string($action)) {
                $prepared[] = $this->prepareBulkAction($action);
            } else {
                $prepared[] = array_merge($this->prepareBulkAction($action['name'] ?? ''), $action);
            }
        }

        return $prepared;
    }

    /**
     * Prepare a single bulk action.
     */
    protected function prepareBulkAction(string $actionName): array
    {
        return [
            'name' => $actionName,
            'label' => $this->getBulkActionLabel($actionName),
            'icon' => $this->getActionIcon($actionName),
            'confirm' => in_array($actionName, ['delete', 'destroy', 'archive']),
            'confirmMessage' => $this->getBulkConfirmMessage($actionName),
        ];
    }

    /**
     * Get bulk action label.
     */
    protected function getBulkActionLabel(string $action): string
    {
        return match ($action) {
            'delete' => 'Delete Selected',
            'export' => 'Export Selected',
            'archive' => 'Archive Selected',
            'restore' => 'Restore Selected',
            default => $this->fieldToLabel($action) . ' Selected',
        };
    }

    /**
     * Get bulk action confirm message.
     */
    protected function getBulkConfirmMessage(string $action): string
    {
        return match ($action) {
            'delete' => 'Are you sure you want to delete the selected items?',
            'archive' => 'Are you sure you want to archive the selected items?',
            default => 'Are you sure you want to perform this action on the selected items?',
        };
    }

    /**
     * Prepare filters from definition.
     */
    protected function prepareFilters(array $definition, array $currentFilters): array
    {
        $filters = $definition['filters'] ?? [];

        if (empty($filters)) {
            return [];
        }

        $prepared = [];
        foreach ($filters as $filterName => $filterConfig) {
            if (is_int($filterName) && is_string($filterConfig)) {
                $filterName = $filterConfig;
                $filterConfig = [];
            }

            $prepared[$filterName] = array_merge([
                'name' => $filterName,
                'label' => $this->fieldToLabel($filterName),
                'type' => 'text',
                'options' => [],
                'value' => $currentFilters[$filterName] ?? null,
            ], is_array($filterConfig) ? $filterConfig : ['type' => $filterConfig]);
        }

        return $prepared;
    }

    /**
     * Prepare pagination data.
     */
    protected function preparePagination($records): array
    {
        if ($records instanceof LengthAwarePaginator) {
            return [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'from' => $records->firstItem(),
                'to' => $records->lastItem(),
                'has_more' => $records->hasMorePages(),
                'links' => $records->links()->toHtml(),
            ];
        }

        if ($records instanceof Collection) {
            return [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $records->count(),
                'total' => $records->count(),
                'from' => 1,
                'to' => $records->count(),
                'has_more' => false,
                'links' => '',
            ];
        }

        return [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 0,
            'total' => 0,
            'from' => 0,
            'to' => 0,
            'has_more' => false,
            'links' => '',
        ];
    }

    /**
     * Format a cell value based on column widget.
     */
    public function formatCellValue($record, array $column): string
    {
        $value = $this->getCellValue($record, $column['name']);
        $widget = $column['widget'] ?? 'text';

        return match ($widget) {
            'date' => $value ? date('M j, Y', strtotime($value)) : '-',
            'datetime' => $value ? date('M j, Y g:i A', strtotime($value)) : '-',
            'currency' => $value !== null ? number_format((float) $value, 2) : '-',
            'badge' => $value ?? '-',
            'boolean' => $value ? 'Yes' : 'No',
            'thumbnail' => $value ? "<img src=\"{$value}\" class=\"w-8 h-8 rounded object-cover\" />" : '-',
            'email' => $value ? "<a href=\"mailto:{$value}\" class=\"text-primary\">{$value}</a>" : '-',
            'link' => $value ? "<a href=\"{$value}\" target=\"_blank\" class=\"text-primary\">{$value}</a>" : '-',
            default => $this->formatTextValue($value, $column),
        };
    }

    /**
     * Get cell value from record.
     */
    protected function getCellValue($record, string $columnName): mixed
    {
        // Check direct property
        if (isset($record->{$columnName})) {
            return $record->{$columnName};
        }

        // Check meta fields
        if (isset($record->meta[$columnName])) {
            return $record->meta[$columnName];
        }

        // Check fields JSON column
        if (isset($record->fields[$columnName])) {
            return $record->fields[$columnName];
        }

        return null;
    }

    /**
     * Format text value with optional truncation.
     */
    protected function formatTextValue($value, array $column): string
    {
        if ($value === null) {
            return '-';
        }

        $value = (string) $value;
        $truncate = $column['truncate'] ?? null;

        if ($truncate && strlen($value) > $truncate) {
            return htmlspecialchars(substr($value, 0, $truncate)) . '...';
        }

        return htmlspecialchars($value);
    }
}

