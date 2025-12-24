<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Calendar View Type - Date/time-based display.
 *
 * Features:
 * - Month/week/day/agenda views
 * - Event creation via drag
 * - Event resizing
 * - All-day events
 * - Recurring events
 * - Color coding
 */
class CalendarViewType extends AbstractViewType
{
    protected string $name = 'calendar';
    protected string $label = 'Calendar View';
    protected string $description = 'Date and time-based event display with multiple view modes';
    protected string $icon = 'calendar';
    protected string $category = 'board';
    protected int $priority = 5;

    protected array $supportedFeatures = [
        'month_view',
        'week_view',
        'day_view',
        'agenda_view',
        'drag_create',
        'drag_resize',
        'all_day_events',
        'recurring_events',
        'color_coding',
        'quick_create',
    ];

    protected array $defaultConfig = [
        'default_view' => 'month',
        'views' => ['month', 'week', 'day', 'agenda'],
        'first_day' => 0, // 0 = Sunday, 1 = Monday
        'min_time' => '00:00',
        'max_time' => '24:00',
        'slot_duration' => 30, // minutes
        'quick_create' => true,
        'drag_create' => true,
        'drag_resize' => true,
        'now_indicator' => true,
    ];

    protected array $extensionPoints = [
        'before_calendar' => 'Content before the calendar',
        'after_calendar' => 'Content after the calendar',
        'event_tooltip' => 'Custom event tooltip content',
        'day_header' => 'Custom day header content',
    ];

    protected array $availableActions = [
        'event' => [
            'view' => ['label' => 'View', 'icon' => 'eye'],
            'edit' => ['label' => 'Edit', 'icon' => 'edit'],
            'delete' => ['label' => 'Delete', 'icon' => 'trash', 'confirm' => true],
            'duplicate' => ['label' => 'Duplicate', 'icon' => 'copy'],
        ],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'date_start'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'calendar'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'date_start' => ['type' => 'string'],
                'date_end' => ['type' => 'string'],
                'all_day' => ['type' => 'string'],
                'display' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string'],
                        'color' => ['type' => 'string'],
                    ],
                ],
                'views' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => ['month', 'week', 'day', 'agenda'],
                    ],
                ],
                'default_view' => [
                    'type' => 'string',
                    'enum' => ['month', 'week', 'day', 'agenda'],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['date_start'])) {
            $this->addError('date_start', 'Calendar view requires a date_start field');
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        // Find date fields
        $dateStartField = $fields->first(fn($f) =>
            in_array(is_array($f) ? $f['slug'] : $f->slug, ['start_date', 'date_start', 'start', 'date', 'starts_at'])
        );

        $dateEndField = $fields->first(fn($f) =>
            in_array(is_array($f) ? $f['slug'] : $f->slug, ['end_date', 'date_end', 'end', 'ends_at'])
        );

        $titleField = $fields->first(fn($f) =>
            in_array(is_array($f) ? $f['slug'] : $f->slug, ['name', 'title'])
        );

        return [
            'type' => 'calendar',
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Calendar',
            'date_start' => $dateStartField ? (is_array($dateStartField) ? $dateStartField['slug'] : $dateStartField->slug) : 'start_date',
            'date_end' => $dateEndField ? (is_array($dateEndField) ? $dateEndField['slug'] : $dateEndField->slug) : null,
            'display' => [
                'title' => $titleField ? (is_array($titleField) ? $titleField['slug'] : $titleField->slug) : 'name',
            ],
            'config' => $this->getDefaultConfig(),
        ];
    }
}
