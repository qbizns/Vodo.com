<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Kanban View Type - Card-based board view.
 *
 * Features:
 * - Grouping by status/category
 * - Drag and drop between columns
 * - Card customization
 * - Quick create
 * - Column folding
 * - Progress indicators
 */
class KanbanViewType extends AbstractViewType
{
    protected string $name = 'kanban';
    protected string $label = 'Kanban View';
    protected string $description = 'Card-based board with draggable columns for workflow visualization';
    protected string $icon = 'columns';
    protected string $category = 'board';
    protected int $priority = 4;

    protected array $supportedFeatures = [
        'drag_drop',
        'quick_create',
        'column_folding',
        'progress_bar',
        'card_colors',
        'grouping',
        'filtering',
        'searching',
    ];

    protected array $defaultConfig = [
        'draggable' => true,
        'quick_create' => true,
        'collapsible' => true,
        'show_count' => true,
        'card_click_action' => 'view',
        'column_width' => 300,
        'max_cards_per_column' => null,
    ];

    protected array $extensionPoints = [
        'before_board' => 'Content before the kanban board',
        'after_board' => 'Content after the kanban board',
        'column_header' => 'Custom column header content',
        'column_footer' => 'Custom column footer content',
        'card_header' => 'Custom card header content',
        'card_footer' => 'Custom card footer content',
        'empty_column' => 'Content for empty columns',
    ];

    protected array $availableActions = [
        'card' => [
            'view' => ['label' => 'View', 'icon' => 'eye'],
            'edit' => ['label' => 'Edit', 'icon' => 'edit'],
            'delete' => ['label' => 'Delete', 'icon' => 'trash', 'confirm' => true],
        ],
        'column' => [
            'add_card' => ['label' => 'Add Card', 'icon' => 'plus'],
            'fold' => ['label' => 'Fold', 'icon' => 'minimize'],
        ],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'group_by', 'card'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'kanban'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'group_by' => ['type' => 'string'],
                'columns' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'color' => ['type' => 'string'],
                            'fold' => ['type' => 'boolean'],
                            'limit' => ['type' => 'integer'],
                        ],
                    ],
                ],
                'card' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'subtitle' => ['type' => 'string'],
                        'image' => ['type' => 'string'],
                        'fields' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'progress' => ['type' => 'string'],
                        'colors' => [
                            'type' => 'object',
                            'properties' => [
                                'field' => ['type' => 'string'],
                                'map' => ['type' => 'object'],
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
        if (empty($definition['group_by'])) {
            $this->addError('group_by', 'Kanban view requires a group_by field');
        }

        if (empty($definition['card'])) {
            $this->addError('card', 'Kanban view requires card configuration');
        } elseif (empty($definition['card']['title'])) {
            $this->addError('card.title', 'Card must have a title field');
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        // Find status field for grouping
        $statusField = $fields->first(fn($f) =>
            in_array(is_array($f) ? $f['slug'] : $f->slug, ['status', 'state', 'stage'])
        );

        // Find title field
        $titleField = $fields->first(fn($f) =>
            in_array(is_array($f) ? $f['slug'] : $f->slug, ['name', 'title'])
        );

        // Find image field
        $imageField = $fields->first(fn($f) =>
            (is_array($f) ? $f['type'] : $f->type) === 'image'
        );

        // Get display fields
        $displayFields = $fields
            ->filter(fn($f) => (is_array($f) ? ($f['show_in_list'] ?? true) : ($f->show_in_list ?? true)))
            ->take(3)
            ->map(fn($f) => is_array($f) ? $f['slug'] : $f->slug)
            ->values()
            ->toArray();

        return [
            'type' => 'kanban',
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Board',
            'group_by' => $statusField ? (is_array($statusField) ? $statusField['slug'] : $statusField->slug) : 'status',
            'card' => [
                'title' => $titleField ? (is_array($titleField) ? $titleField['slug'] : $titleField->slug) : 'name',
                'image' => $imageField ? (is_array($imageField) ? $imageField['slug'] : $imageField->slug) : null,
                'fields' => $displayFields,
            ],
            'config' => $this->getDefaultConfig(),
        ];
    }
}
