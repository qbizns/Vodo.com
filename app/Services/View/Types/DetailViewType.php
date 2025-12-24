<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Detail View Type - Read-only record display.
 *
 * Features:
 * - Header with title, status, image
 * - Field sections
 * - Related records tabs
 * - Activity timeline
 * - Actions (edit, delete, etc.)
 */
class DetailViewType extends AbstractViewType
{
    protected string $name = 'detail';
    protected string $label = 'Detail View';
    protected string $description = 'Read-only record display with sections and related data';
    protected string $icon = 'eye';
    protected string $category = 'data';
    protected int $priority = 3;

    protected array $supportedFeatures = [
        'header',
        'sections',
        'tabs',
        'related_records',
        'activity_timeline',
        'comments',
        'attachments',
        'breadcrumbs',
    ];

    protected array $defaultConfig = [
        'layout' => 'card', // card, full, compact
        'show_header' => true,
        'show_breadcrumbs' => true,
        'show_updated_at' => true,
        'show_created_by' => true,
    ];

    protected array $extensionPoints = [
        'before_header' => 'Content before the header',
        'after_header' => 'Content after the header',
        'before_sections' => 'Content before sections',
        'after_sections' => 'Content after sections',
        'before_tabs' => 'Content before tabs',
        'after_tabs' => 'Content after tabs',
        'sidebar' => 'Sidebar content',
    ];

    protected array $availableActions = [
        'edit' => ['label' => 'Edit', 'icon' => 'edit', 'primary' => true],
        'delete' => ['label' => 'Delete', 'icon' => 'trash', 'confirm' => true],
        'duplicate' => ['label' => 'Duplicate', 'icon' => 'copy'],
        'archive' => ['label' => 'Archive', 'icon' => 'archive'],
        'print' => ['label' => 'Print', 'icon' => 'printer'],
        'share' => ['label' => 'Share', 'icon' => 'share'],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'detail'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'header' => [
                    'type' => 'object',
                    'properties' => [
                        'title_field' => ['type' => 'string'],
                        'subtitle_field' => ['type' => 'string'],
                        'image_field' => ['type' => 'string'],
                        'status_field' => ['type' => 'string'],
                    ],
                ],
                'sections' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'fields' => ['type' => 'array'],
                            'type' => ['type' => 'string', 'enum' => ['fields', 'stats', 'custom']],
                        ],
                    ],
                ],
                'tabs' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'view' => ['type' => 'string'],
                            'filter' => ['type' => 'string'],
                        ],
                    ],
                ],
                'actions' => ['type' => 'array'],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        // Find title and image fields
        $titleField = $fields->first(fn($f) =>
            in_array(is_array($f) ? $f['slug'] : $f->slug, ['name', 'title'])
        );
        $imageField = $fields->first(fn($f) =>
            (is_array($f) ? $f['type'] : $f->type) === 'image'
        );
        $statusField = $fields->first(fn($f) =>
            in_array(is_array($f) ? $f['slug'] : $f->slug, ['status', 'state'])
        );

        // Build sections from form groups
        $sections = ['overview' => ['label' => 'Overview', 'fields' => []]];

        foreach ($this->filterFields($fields, 'form') as $field) {
            $slug = is_array($field) ? $field['slug'] : $field->slug;
            $group = is_array($field) ? ($field['form_group'] ?? 'overview') : ($field->form_group ?? 'overview');

            if (!isset($sections[$group])) {
                $sections[$group] = [
                    'label' => Str::title(str_replace('_', ' ', $group)),
                    'fields' => [],
                ];
            }

            $sections[$group]['fields'][] = $slug;
        }

        return [
            'type' => 'detail',
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Details',
            'header' => [
                'title_field' => $titleField ? (is_array($titleField) ? $titleField['slug'] : $titleField->slug) : 'name',
                'image_field' => $imageField ? (is_array($imageField) ? $imageField['slug'] : $imageField->slug) : null,
                'status_field' => $statusField ? (is_array($statusField) ? $statusField['slug'] : $statusField->slug) : null,
            ],
            'sections' => $sections,
            'tabs' => [],
            'actions' => ['edit', 'delete'],
            'config' => $this->getDefaultConfig(),
        ];
    }
}
