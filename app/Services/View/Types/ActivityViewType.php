<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Activity View Type - Timeline/audit display.
 *
 * Features:
 * - Chronological timeline
 * - Activity grouping by date
 * - Activity types/icons
 * - User attribution
 * - Filtering
 */
class ActivityViewType extends AbstractViewType
{
    protected string $name = 'activity';
    protected string $label = 'Activity View';
    protected string $description = 'Timeline and audit trail display for history and logs';
    protected string $icon = 'activity';
    protected string $category = 'special';
    protected int $priority = 14;

    protected array $supportedFeatures = [
        'timeline',
        'grouping',
        'filtering',
        'infinite_scroll',
        'real_time',
        'comments',
    ];

    protected array $defaultConfig = [
        'group_by' => 'date', // date, type, user
        'order' => 'desc',
        'per_page' => 20,
        'show_user' => true,
        'show_timestamp' => true,
        'show_icons' => true,
        'allow_comments' => false,
    ];

    protected array $extensionPoints = [
        'before_timeline' => 'Content before the timeline',
        'after_timeline' => 'Content after the timeline',
        'activity_item' => 'Custom activity item template',
        'date_separator' => 'Custom date separator',
    ];

    protected array $availableActions = [
        'activity' => [
            'view_details' => ['label' => 'View Details', 'icon' => 'eye'],
            'add_comment' => ['label' => 'Add Comment', 'icon' => 'message-circle'],
        ],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'activity'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'event_types' => ['type' => 'array', 'items' => ['type' => 'string']],
                'group_by' => ['type' => 'string', 'enum' => ['date', 'type', 'user']],
                'filters' => ['type' => 'object'],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        return [
            'type' => 'activity',
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Activity',
            'event_types' => ['created', 'updated', 'deleted', 'viewed'],
            'group_by' => 'date',
            'config' => $this->getDefaultConfig(),
        ];
    }
}
