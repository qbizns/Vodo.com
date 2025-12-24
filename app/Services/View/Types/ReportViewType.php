<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Report View Type - Parameterized reports.
 *
 * Features:
 * - Report parameters
 * - Multiple sections
 * - Charts and tables
 * - Export (PDF, Excel)
 * - Scheduling
 */
class ReportViewType extends AbstractViewType
{
    protected string $name = 'report';
    protected string $label = 'Report View';
    protected string $description = 'Parameterized business reports with charts and tables';
    protected string $icon = 'file-text';
    protected string $category = 'analytics';
    protected int $priority = 15;

    protected array $supportedFeatures = [
        'parameters',
        'sections',
        'charts',
        'tables',
        'export',
        'scheduling',
        'email_delivery',
        'print',
    ];

    protected array $defaultConfig = [
        'auto_run' => false,
        'show_parameters' => true,
        'export_formats' => ['pdf', 'xlsx', 'csv'],
        'print_layout' => 'portrait',
    ];

    protected array $extensionPoints = [
        'before_parameters' => 'Content before parameters',
        'after_parameters' => 'Content after parameters',
        'before_report' => 'Content before report body',
        'after_report' => 'Content after report body',
        'report_header' => 'Custom report header',
        'report_footer' => 'Custom report footer',
    ];

    protected array $availableActions = [
        'run' => ['label' => 'Run Report', 'icon' => 'play', 'primary' => true],
        'export_pdf' => ['label' => 'Export PDF', 'icon' => 'file-text'],
        'export_excel' => ['label' => 'Export Excel', 'icon' => 'table'],
        'print' => ['label' => 'Print', 'icon' => 'printer'],
        'schedule' => ['label' => 'Schedule', 'icon' => 'clock'],
        'save' => ['label' => 'Save Report', 'icon' => 'save'],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'sections'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'report'],
                'name' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'parameters' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'required' => ['type' => 'boolean'],
                            'default' => [],
                            'options' => ['type' => 'array'],
                        ],
                    ],
                ],
                'sections' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string', 'enum' => ['text', 'table', 'chart', 'summary']],
                            'title' => ['type' => 'string'],
                            'data_source' => ['type' => 'string'],
                            'config' => ['type' => 'object'],
                        ],
                    ],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['sections'])) {
            $this->addError('sections', 'Report requires at least one section');
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        return [
            'type' => 'report',
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Report',
            'parameters' => [
                'date_from' => [
                    'label' => 'From Date',
                    'type' => 'date',
                    'required' => false,
                ],
                'date_to' => [
                    'label' => 'To Date',
                    'type' => 'date',
                    'required' => false,
                ],
            ],
            'sections' => [
                [
                    'type' => 'summary',
                    'title' => 'Summary',
                    'data_source' => "{$entityName}.summary",
                ],
                [
                    'type' => 'table',
                    'title' => 'Details',
                    'data_source' => "{$entityName}.list",
                ],
            ],
            'config' => $this->getDefaultConfig(),
        ];
    }
}
