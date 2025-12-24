<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Search View Type - Global search results display.
 *
 * Features:
 * - Multi-entity search
 * - Filters and facets
 * - Result grouping
 * - Saved searches
 * - Search suggestions
 */
class SearchViewType extends AbstractViewType
{
    protected string $name = 'search';
    protected string $label = 'Search View';
    protected string $description = 'Global search results with filtering and grouping';
    protected string $icon = 'search';
    protected string $category = 'special';
    protected int $priority = 13;
    protected bool $requiresEntity = false;

    protected array $supportedFeatures = [
        'multi_entity',
        'facets',
        'grouping',
        'saved_searches',
        'suggestions',
        'highlighting',
        'fuzzy_search',
    ];

    protected array $defaultConfig = [
        'entities' => [], // Empty = all searchable entities
        'min_query_length' => 2,
        'results_per_entity' => 5,
        'show_facets' => true,
        'show_suggestions' => true,
        'highlight_results' => true,
    ];

    protected array $extensionPoints = [
        'before_results' => 'Content before search results',
        'after_results' => 'Content after search results',
        'result_item' => 'Custom result item template',
        'facets_area' => 'Custom facets area',
        'no_results' => 'Content when no results found',
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'search'],
                'name' => ['type' => 'string'],
                'entities' => ['type' => 'array', 'items' => ['type' => 'string']],
                'filters' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string'],
                            'label' => ['type' => 'string'],
                            'options' => ['type' => 'array'],
                        ],
                    ],
                ],
                'group_by' => ['type' => 'array', 'items' => ['type' => 'string']],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        $searchFields = $fields
            ->filter(fn($f) => (is_array($f) ? ($f['is_searchable'] ?? false) : ($f->is_searchable ?? false)))
            ->map(fn($f) => is_array($f) ? $f['slug'] : $f->slug)
            ->values()
            ->toArray();

        $filters = [];
        foreach ($fields->filter(fn($f) => (is_array($f) ? ($f['is_filterable'] ?? false) : ($f->is_filterable ?? false))) as $field) {
            $slug = is_array($field) ? $field['slug'] : $field->slug;
            $name = is_array($field) ? ($field['name'] ?? $slug) : ($field->name ?? $slug);
            $filters[$slug] = ['label' => $name];
        }

        return [
            'type' => 'search',
            'entity' => $entityName,
            'name' => 'Search ' . Str::title(str_replace('_', ' ', $entityName)),
            'search_fields' => $searchFields,
            'filters' => $filters,
            'config' => $this->getDefaultConfig(),
        ];
    }
}
