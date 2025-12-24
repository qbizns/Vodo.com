<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Import View Type - Bulk data import interface.
 *
 * Features:
 * - File upload (CSV, Excel)
 * - Column mapping
 * - Data preview
 * - Validation
 * - Progress tracking
 */
class ImportViewType extends AbstractViewType
{
    protected string $name = 'import';
    protected string $label = 'Import View';
    protected string $description = 'Bulk data import interface for CSV and Excel files';
    protected string $icon = 'upload';
    protected string $category = 'workflow';
    protected int $priority = 11;

    protected array $supportedFeatures = [
        'file_upload',
        'column_mapping',
        'preview',
        'validation',
        'progress_tracking',
        'error_handling',
        'duplicate_detection',
    ];

    protected array $defaultConfig = [
        'formats' => ['csv', 'xlsx', 'xls'],
        'max_file_size' => 10, // MB
        'max_rows' => 10000,
        'has_header' => true,
        'skip_errors' => false,
        'update_existing' => false,
        'match_field' => null,
        'preview_rows' => 5,
    ];

    protected array $extensionPoints = [
        'before_upload' => 'Content before file upload',
        'after_upload' => 'Content after file upload',
        'before_mapping' => 'Content before column mapping',
        'after_mapping' => 'Content after column mapping',
        'before_preview' => 'Content before preview',
        'after_import' => 'Content after import completes',
    ];

    protected array $availableActions = [
        'upload' => ['label' => 'Upload File', 'icon' => 'upload'],
        'preview' => ['label' => 'Preview', 'icon' => 'eye'],
        'import' => ['label' => 'Start Import', 'icon' => 'play', 'primary' => true],
        'cancel' => ['label' => 'Cancel', 'icon' => 'x'],
        'download_template' => ['label' => 'Download Template', 'icon' => 'download'],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'entity'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'import'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'formats' => [
                    'type' => 'array',
                    'items' => ['type' => 'string', 'enum' => ['csv', 'xlsx', 'xls', 'json']],
                ],
                'fields' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'required' => ['type' => 'boolean'],
                            'default' => [],
                            'transform' => ['type' => 'string'],
                        ],
                    ],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        $importFields = [];

        foreach ($this->filterFields($fields, 'form') as $field) {
            $slug = is_array($field) ? $field['slug'] : $field->slug;
            $name = is_array($field) ? ($field['name'] ?? $slug) : ($field->name ?? $slug);
            $required = is_array($field) ? ($field['is_required'] ?? false) : ($field->is_required ?? false);
            $isSystem = is_array($field) ? ($field['is_system'] ?? false) : ($field->is_system ?? false);

            // Skip system fields
            if ($isSystem) {
                continue;
            }

            $importFields[$slug] = [
                'label' => $name,
                'required' => $required,
            ];
        }

        return [
            'type' => 'import',
            'entity' => $entityName,
            'name' => 'Import ' . Str::title(str_replace('_', ' ', $entityName)),
            'fields' => $importFields,
            'config' => $this->getDefaultConfig(),
        ];
    }
}
