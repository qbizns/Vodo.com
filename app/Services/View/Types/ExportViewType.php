<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Export View Type - Data export configuration.
 *
 * Features:
 * - Multiple export formats
 * - Column selection
 * - Filtering
 * - Scheduling
 * - Templates
 */
class ExportViewType extends AbstractViewType
{
    protected string $name = 'export';
    protected string $label = 'Export View';
    protected string $description = 'Data export configuration for CSV, Excel, and PDF';
    protected string $icon = 'download';
    protected string $category = 'workflow';
    protected int $priority = 12;

    protected array $supportedFeatures = [
        'format_selection',
        'column_selection',
        'filtering',
        'scheduling',
        'templates',
        'email_delivery',
    ];

    protected array $defaultConfig = [
        'formats' => ['csv', 'xlsx', 'pdf'],
        'default_format' => 'xlsx',
        'include_header' => true,
        'date_format' => 'Y-m-d',
        'max_rows' => null,
    ];

    protected array $extensionPoints = [
        'before_export' => 'Content before export options',
        'after_export' => 'Content after export options',
        'format_options' => 'Additional format-specific options',
    ];

    protected array $availableActions = [
        'export' => ['label' => 'Export', 'icon' => 'download', 'primary' => true],
        'preview' => ['label' => 'Preview', 'icon' => 'eye'],
        'save_template' => ['label' => 'Save as Template', 'icon' => 'save'],
        'schedule' => ['label' => 'Schedule Export', 'icon' => 'clock'],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'entity'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'export'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'formats' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => ['csv', 'xlsx', 'xls', 'pdf', 'json']],
                ],
                'columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                'filters' => ['type' => 'object'],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        $columns = $this->filterFields($fields, 'list')
            ->map(fn($f) => is_array($f) ? $f['slug'] : $f->slug)
            ->values()
            ->toArray();

        return [
            'type' => 'export',
            'entity' => $entityName,
            'name' => 'Export ' . Str::title(str_replace('_', ' ', $entityName)),
            'columns' => $columns,
            'config' => $this->getDefaultConfig(),
        ];
    }
}
