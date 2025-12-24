<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Tree View Type - Hierarchical/nested list display.
 *
 * Features:
 * - Nested hierarchy display
 * - Expand/collapse nodes
 * - Drag and drop reordering
 * - Lazy loading of children
 * - Node actions
 */
class TreeViewType extends AbstractViewType
{
    protected string $name = 'tree';
    protected string $label = 'Tree View';
    protected string $description = 'Hierarchical nested list for categories and org charts';
    protected string $icon = 'git-branch';
    protected string $category = 'data';
    protected int $priority = 6;

    protected array $supportedFeatures = [
        'expand_collapse',
        'drag_drop',
        'lazy_load',
        'multi_select',
        'search',
        'context_menu',
    ];

    protected array $defaultConfig = [
        'expandable' => true,
        'draggable' => true,
        'lazy_load' => false,
        'max_depth' => null,
        'show_count' => false,
        'show_icons' => true,
        'default_expanded_level' => 1,
    ];

    protected array $extensionPoints = [
        'before_tree' => 'Content before the tree',
        'after_tree' => 'Content after the tree',
        'node_content' => 'Custom node content',
        'empty_state' => 'Content when tree is empty',
    ];

    protected array $availableActions = [
        'node' => [
            'add_child' => ['label' => 'Add Child', 'icon' => 'plus'],
            'edit' => ['label' => 'Edit', 'icon' => 'edit'],
            'delete' => ['label' => 'Delete', 'icon' => 'trash', 'confirm' => true],
            'move' => ['label' => 'Move', 'icon' => 'move'],
        ],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'parent_field'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'tree'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'parent_field' => ['type' => 'string'],
                'order_field' => ['type' => 'string'],
                'display' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'icon' => ['type' => 'string'],
                        'badge' => ['type' => 'string'],
                    ],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['parent_field'])) {
            $this->addError('parent_field', 'Tree view requires a parent_field');
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        $parentField = $fields->first(fn($f) =>
            in_array(is_array($f) ? $f['slug'] : $f->slug, ['parent_id', 'parent'])
        );

        $titleField = $fields->first(fn($f) =>
            in_array(is_array($f) ? $f['slug'] : $f->slug, ['name', 'title'])
        );

        return [
            'type' => 'tree',
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Tree',
            'parent_field' => $parentField ? (is_array($parentField) ? $parentField['slug'] : $parentField->slug) : 'parent_id',
            'display' => [
                'title' => $titleField ? (is_array($titleField) ? $titleField['slug'] : $titleField->slug) : 'name',
            ],
            'config' => $this->getDefaultConfig(),
        ];
    }
}
