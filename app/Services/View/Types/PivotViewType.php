<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Pivot View Type - Matrix/crosstab display.
 *
 * Features:
 * - Multi-dimensional analysis
 * - Row and column grouping
 * - Aggregation (sum, count, avg, etc.)
 * - Expandable rows
 * - Export to Excel
 */
class PivotViewType extends AbstractViewType
{
    protected string $name = 'pivot';
    protected string $label = 'Pivot View';
    protected string $description = 'Matrix/crosstab display for multi-dimensional analysis';
    protected string $icon = 'table';
    protected string $category = 'analytics';
    protected int $priority = 7;

    protected array $supportedFeatures = [
        'aggregation',
        'grouping',
        'expand_collapse',
        'totals',
        'percentages',
        'export',
        'drill_down',
    ];

    protected array $defaultConfig = [
        'show_totals' => true,
        'show_row_totals' => true,
        'show_column_totals' => true,
        'show_percentages' => false,
        'expandable' => true,
        'default_aggregate' => 'sum',
    ];

    protected array $extensionPoints = [
        'before_pivot' => 'Content before the pivot table',
        'after_pivot' => 'Content after the pivot table',
        'cell_content' => 'Custom cell content',
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'measures'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'pivot'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'measures' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'aggregate' => [
                                'type' => 'string',
                                'enum' => ['sum', 'count', 'avg', 'min', 'max'],
                            ],
                            'format' => ['type' => 'string'],
                        ],
                    ],
                ],
                'rows' => ['type' => 'array', 'items' => ['type' => 'string']],
                'columns' => ['type' => 'array', 'items' => ['type' => 'string']],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['measures'])) {
            $this->addError('measures', 'Pivot view requires at least one measure');
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        // Find numeric fields for measures
        $numericFields = $fields->filter(fn($f) =>
            in_array(is_array($f) ? $f['type'] : $f->type, ['integer', 'decimal', 'money'])
        );

        $measures = [];
        foreach ($numericFields->take(3) as $field) {
            $slug = is_array($field) ? $field['slug'] : $field->slug;
            $name = is_array($field) ? ($field['name'] ?? $slug) : ($field->name ?? $slug);
            $measures[$slug] = [
                'label' => $name,
                'aggregate' => 'sum',
            ];
        }

        // Add count measure
        $measures['_count'] = ['label' => 'Count', 'aggregate' => 'count'];

        return [
            'type' => 'pivot',
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Pivot',
            'measures' => $measures,
            'rows' => [],
            'columns' => [],
            'config' => $this->getDefaultConfig(),
        ];
    }
}
