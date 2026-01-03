<?php

declare(strict_types=1);

namespace App\Services\View;

use App\Contracts\ViewRegistryContract;
use App\Contracts\ViewTypeRegistryContract;
use App\Models\UIViewDefinition;
use App\Models\EntityDefinition;
use App\Models\EntityField;
use Illuminate\Support\Facades\Cache;

/**
 * View Registry - Manages declarative UI views (Odoo/Salesforce-inspired).
 *
 * This is the central registry for all view definitions in the system.
 * It integrates with ViewTypeRegistry for type validation and default generation.
 *
 * Features:
 * - 20 canonical view types (list, form, kanban, calendar, etc.)
 * - View inheritance and XPath-style extensions
 * - Plugin-aware view registration
 * - Automatic view generation from entity definitions
 * - Widget system for field rendering
 * - Caching for performance
 *
 * @example Register a form view
 * ```php
 * $viewRegistry->registerFormView('invoice', [
 *     'groups' => [
 *         'header' => [
 *             'columns' => 2,
 *             'fields' => [
 *                 'partner_id' => ['widget' => 'many2one', 'required' => true],
 *                 'date' => ['widget' => 'date'],
 *             ],
 *         ],
 *     ],
 * ]);
 * ```
 *
 * @example Extend an existing view
 * ```php
 * $viewRegistry->extendView('invoice_form', [
 *     ['xpath' => "//field[@name='amount']", 'position' => 'after', 'content' => [
 *         'discount' => ['widget' => 'percentage'],
 *     ]],
 * ], 'my-plugin');
 * ```
 */
class ViewRegistry implements ViewRegistryContract
{
    /**
     * Cache prefix.
     */
    protected const CACHE_PREFIX = 'view_registry:';

    /**
     * Cache TTL in seconds.
     */
    protected const CACHE_TTL = 3600;

    /**
     * View type registry.
     */
    protected ViewTypeRegistry $typeRegistry;

    /**
     * Widget registry.
     */
    protected array $widgets = [];

    /**
     * Default widgets per field type.
     */
    protected array $defaultWidgets = [
        'string' => 'char',
        'text' => 'text',
        'integer' => 'integer',
        'decimal' => 'float',
        'boolean' => 'checkbox',
        'date' => 'date',
        'datetime' => 'datetime',
        'time' => 'time',
        'email' => 'email',
        'url' => 'url',
        'phone' => 'phone',
        'select' => 'selection',
        'relation' => 'many2one',
        'file' => 'binary',
        'image' => 'image',
        'json' => 'json',
        'html' => 'html',
        'color' => 'color',
        'money' => 'monetary',
    ];

    public function __construct(?ViewTypeRegistry $typeRegistry = null)
    {
        $this->typeRegistry = $typeRegistry ?? new ViewTypeRegistry();
        $this->registerBuiltInWidgets();
    }

    /**
     * Get the view type registry.
     */
    public function getTypeRegistry(): ViewTypeRegistryContract
    {
        return $this->typeRegistry;
    }

    /**
     * Register a form view.
     */
    public function registerFormView(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): UIViewDefinition {
        return $this->registerView(
            $entityName,
            UIViewDefinition::TYPE_FORM,
            $definition,
            $pluginSlug,
            $inheritFrom
        );
    }

    /**
     * Register a list view.
     */
    public function registerListView(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): UIViewDefinition {
        return $this->registerView(
            $entityName,
            UIViewDefinition::TYPE_LIST,
            $definition,
            $pluginSlug,
            $inheritFrom
        );
    }

    /**
     * Register a kanban view.
     */
    public function registerKanbanView(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): UIViewDefinition {
        return $this->registerView(
            $entityName,
            UIViewDefinition::TYPE_KANBAN,
            $definition,
            $pluginSlug,
            $inheritFrom
        );
    }

    /**
     * Register a search view.
     */
    public function registerSearchView(
        string $entityName,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): UIViewDefinition {
        return $this->registerView(
            $entityName,
            UIViewDefinition::TYPE_SEARCH,
            $definition,
            $pluginSlug,
            $inheritFrom
        );
    }

    /**
     * Register a view of any type.
     *
     * @throws \InvalidArgumentException If the view type is invalid
     */
    public function registerView(
        string $entityName,
        string $viewType,
        array $definition,
        ?string $pluginSlug = null,
        ?string $inheritFrom = null
    ): UIViewDefinition {
        // Validate view type exists
        $type = $this->typeRegistry->get($viewType);
        if (!$type) {
            throw new \InvalidArgumentException("Unknown view type: {$viewType}");
        }

        // Validate entity requirement
        if ($type->requiresEntity() && empty($entityName)) {
            throw new \InvalidArgumentException("View type '{$viewType}' requires an entity");
        }

        // Validate the definition against the view type schema
        $definition['type'] = $viewType;
        $definition['entity'] = $entityName;
        $errors = $type->validate($definition);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(
                "Invalid view definition: " . implode(', ', $errors)
            );
        }

        $slug = $definition['slug'] ?? "{$entityName}_{$viewType}";

        if ($pluginSlug) {
            $slug = "{$pluginSlug}_{$slug}";
        }

        $inheritId = null;
        if ($inheritFrom) {
            $parent = UIViewDefinition::where('slug', $inheritFrom)->first();
            $inheritId = $parent?->id;
        }

        $view = UIViewDefinition::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $definition['name'] ?? ucfirst($entityName) . ' ' . ucfirst(str_replace('-', ' ', $viewType)),
                'entity_name' => $entityName,
                'view_type' => $viewType,
                'priority' => $definition['priority'] ?? 16,
                'arch' => $definition,
                'config' => array_merge($type->getDefaultConfig(), $definition['config'] ?? []),
                'inherit_id' => $inheritId,
                'plugin_slug' => $pluginSlug,
                'is_active' => true,
                'icon' => $definition['icon'] ?? $type->getIcon(),
                'description' => $definition['description'] ?? $type->getDescription(),
            ]
        );

        $this->clearCache($entityName, $viewType);

        return $view;
    }

    /**
     * Get a view definition.
     *
     * If a view is registered with sections but no fields, the fields will be
     * auto-populated from entity_fields. This allows plugins to define section
     * metadata (labels, columns, collapsible) while leveraging auto-generation.
     */
    public function getView(string $entityName, string $viewType, ?string $slug = null): ?array
    {
        $cacheKey = self::CACHE_PREFIX . "{$entityName}:{$viewType}" . ($slug ? ":{$slug}" : '');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($entityName, $viewType, $slug) {
            $query = UIViewDefinition::forEntity($entityName)
                ->ofType($viewType)
                ->active()
                ->orderBy('priority');

            if ($slug) {
                $query->where('slug', $slug);
            }

            $view = $query->first();

            if (!$view) {
                // Generate default view from entity fields
                return $this->generateDefaultView($entityName, $viewType);
            }

            $registered = $view->getCompiledArch();

            // For form views, merge with auto-generated fields if sections lack field definitions
            if ($viewType === UIViewDefinition::TYPE_FORM && $this->needsFieldMerge($registered)) {
                $generated = $this->generateDefaultView($entityName, $viewType);
                return $this->mergeViewDefinitions($registered, $generated);
            }

            return $registered;
        });
    }

    /**
     * Check if a view definition needs fields merged from entity_fields.
     *
     * Returns true if the view has sections but any section lacks fields or has empty fields.
     */
    protected function needsFieldMerge(array $definition): bool
    {
        // Check 'sections' key (used in registered views)
        $sections = $definition['sections'] ?? $definition['groups'] ?? [];

        if (empty($sections)) {
            return false;
        }

        foreach ($sections as $section) {
            $fields = $section['fields'] ?? [];
            // If any section has no fields, we need to merge
            if (empty($fields)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merge registered view definition with auto-generated view.
     *
     * The registered view provides section metadata (label, columns, collapsible).
     * The generated view provides field definitions from entity_fields.
     *
     * Rules:
     * - If a section in registered has fields, keep them (explicit override)
     * - If a section in registered has no fields, use fields from generated view
     * - Section metadata (label, columns, collapsible) always comes from registered view
     * - Sections in generated but not in registered are added at the end
     */
    public function mergeViewDefinitions(array $registered, array $generated): array
    {
        // Normalize keys - support both 'sections' and 'groups'
        $registeredSections = $registered['sections'] ?? $registered['groups'] ?? [];
        $generatedSections = $generated['sections'] ?? $generated['groups'] ?? [];

        $mergedSections = [];

        // First, process registered sections (preserving order)
        foreach ($registeredSections as $sectionKey => $registeredSection) {
            $mergedSection = $registeredSection;

            // If section has no fields, populate from generated
            if (empty($registeredSection['fields'])) {
                // Find matching section in generated
                $generatedFields = $generatedSections[$sectionKey]['fields'] ?? [];

                if (empty($generatedFields)) {
                    // No exact match - collect all fields for this group from generated
                    $generatedFields = $this->collectFieldsForGroup($generatedSections, $sectionKey);
                }

                $mergedSection['fields'] = $generatedFields;
            }

            $mergedSections[$sectionKey] = $mergedSection;
        }

        // Add any sections from generated that aren't in registered
        foreach ($generatedSections as $sectionKey => $generatedSection) {
            if (!isset($mergedSections[$sectionKey])) {
                // Only add if section has fields
                if (!empty($generatedSection['fields'])) {
                    $mergedSections[$sectionKey] = $generatedSection;
                }
            }
        }

        // Build merged result - use 'sections' as canonical key
        $result = $registered;
        $result['sections'] = $mergedSections;
        unset($result['groups']); // Remove 'groups' if present to avoid duplication

        return $result;
    }

    /**
     * Collect fields from all sections that belong to a specific group.
     */
    protected function collectFieldsForGroup(array $sections, string $targetGroup): array
    {
        $fields = [];

        foreach ($sections as $sectionKey => $section) {
            if ($sectionKey === $targetGroup) {
                return $section['fields'] ?? [];
            }
        }

        return $fields;
    }

    /**
     * Get form view for an entity.
     */
    public function getFormView(string $entityName): array
    {
        return $this->getView($entityName, UIViewDefinition::TYPE_FORM) ?? [];
    }

    /**
     * Get list view for an entity.
     */
    public function getListView(string $entityName): array
    {
        return $this->getView($entityName, UIViewDefinition::TYPE_LIST) ?? [];
    }

    /**
     * Get kanban view for an entity.
     */
    public function getKanbanView(string $entityName): array
    {
        return $this->getView($entityName, UIViewDefinition::TYPE_KANBAN) ?? [];
    }

    /**
     * Get search view for an entity.
     */
    public function getSearchView(string $entityName): array
    {
        return $this->getView($entityName, UIViewDefinition::TYPE_SEARCH) ?? [];
    }

    /**
     * Generate default view from entity fields.
     *
     * Uses the ViewTypeRegistry to generate type-specific default views.
     * Falls back to legacy generation for basic types if needed.
     */
    public function generateDefaultView(string $entityName, string $viewType): array
    {
        $entity = EntityDefinition::where('name', $entityName)->first();
        if (!$entity) {
            return [];
        }

        $fields = EntityField::where('entity_name', $entityName)
            ->orderBy('form_order')
            ->get();

        // Try to generate using the view type registry first
        $type = $this->typeRegistry->get($viewType);
        if ($type) {
            return $type->generateDefault($entityName, $fields);
        }

        // Fallback for legacy view types
        return match ($viewType) {
            UIViewDefinition::TYPE_FORM => $this->generateDefaultFormView($entity, $fields),
            UIViewDefinition::TYPE_LIST => $this->generateDefaultListView($entity, $fields),
            UIViewDefinition::TYPE_KANBAN => $this->generateDefaultKanbanView($entity, $fields),
            UIViewDefinition::TYPE_SEARCH => $this->generateDefaultSearchView($entity, $fields),
            default => [],
        };
    }

    /**
     * Generate default form view.
     */
    protected function generateDefaultFormView(EntityDefinition $entity, $fields): array
    {
        $groups = ['main' => ['label' => null, 'columns' => 2, 'fields' => []]];
        
        foreach ($fields as $field) {
            if (!$field->show_in_form) {
                continue;
            }

            $group = $field->form_group ?? 'main';
            if (!isset($groups[$group])) {
                $groups[$group] = ['label' => ucfirst($group), 'fields' => []];
            }

            $groups[$group]['fields'][$field->slug] = [
                'widget' => $this->getWidgetForField($field),
                'label' => $field->name,
                'required' => $field->is_required,
                'readonly' => $field->is_system,
                'width' => $field->form_width,
                'help' => $field->description,
            ];
        }

        return [
            'type' => 'form',
            'groups' => $groups,
        ];
    }

    /**
     * Generate default list view.
     */
    protected function generateDefaultListView(EntityDefinition $entity, $fields): array
    {
        $columns = [];

        foreach ($fields as $field) {
            if (!$field->show_in_list) {
                continue;
            }

            $columns[$field->slug] = [
                'label' => $field->name,
                'sortable' => $field->is_sortable,
                'type' => $field->type,
                'width' => 'auto',
            ];
        }

        return [
            'type' => 'list',
            'columns' => $columns,
            'default_order' => 'created_at desc',
            'editable' => false,
            'selectable' => true,
        ];
    }

    /**
     * Generate default kanban view.
     */
    protected function generateDefaultKanbanView(EntityDefinition $entity, $fields): array
    {
        $titleField = $fields->firstWhere('slug', 'title') ?? $fields->firstWhere('slug', 'name');
        $statusField = $fields->firstWhere('slug', 'status') ?? $fields->firstWhere('slug', 'state');

        return [
            'type' => 'kanban',
            'group_by' => $statusField?->slug ?? 'status',
            'card' => [
                'title' => $titleField?->slug ?? 'title',
                'subtitle' => null,
                'image' => $fields->firstWhere('type', 'image')?->slug,
                'fields' => $fields->where('show_in_list', true)->take(3)->pluck('slug')->toArray(),
                'colors' => [],
            ],
            'quick_create' => true,
        ];
    }

    /**
     * Generate default search view.
     */
    protected function generateDefaultSearchView(EntityDefinition $entity, $fields): array
    {
        $searchFields = [];
        $filters = [];
        $groupBy = [];

        foreach ($fields as $field) {
            if ($field->is_searchable) {
                $searchFields[] = $field->slug;
            }

            if ($field->is_filterable) {
                $filters[$field->slug] = [
                    'label' => $field->name,
                    'type' => $this->getFilterTypeForField($field),
                ];
            }

            if (in_array($field->type, ['select', 'relation', 'boolean'])) {
                $groupBy[] = $field->slug;
            }
        }

        return [
            'type' => 'search',
            'search_fields' => $searchFields,
            'filters' => $filters,
            'group_by' => $groupBy,
            'favorites' => true,
        ];
    }

    /**
     * Get widget for a field.
     */
    protected function getWidgetForField(EntityField $field): string
    {
        // Check field config for explicit widget
        $config = $field->config ?? [];
        if (isset($config['widget'])) {
            return $config['widget'];
        }

        return $this->defaultWidgets[$field->type] ?? 'char';
    }

    /**
     * Get filter type for a field.
     */
    protected function getFilterTypeForField(EntityField $field): string
    {
        return match ($field->type) {
            'date', 'datetime' => 'date_range',
            'integer', 'decimal', 'money' => 'number_range',
            'boolean' => 'boolean',
            'select', 'relation' => 'selection',
            default => 'text',
        };
    }

    /**
     * Register a custom widget.
     */
    public function registerWidget(string $name, array $config): void
    {
        $this->widgets[$name] = array_merge([
            'component' => null,
            'formatter' => null,
            'parser' => null,
            'supports' => [],
        ], $config);
    }

    /**
     * Get widget configuration.
     */
    public function getWidget(string $name): ?array
    {
        return $this->widgets[$name] ?? null;
    }

    /**
     * Get all widgets.
     */
    public function getWidgets(): array
    {
        return $this->widgets;
    }

    /**
     * Register built-in widgets.
     */
    protected function registerBuiltInWidgets(): void
    {
        $this->registerWidget('char', [
            'component' => 'InputText',
            'supports' => ['string'],
        ]);

        $this->registerWidget('text', [
            'component' => 'InputTextarea',
            'supports' => ['text'],
        ]);

        $this->registerWidget('html', [
            'component' => 'RichTextEditor',
            'supports' => ['text', 'html'],
        ]);

        $this->registerWidget('integer', [
            'component' => 'InputNumber',
            'supports' => ['integer'],
        ]);

        $this->registerWidget('float', [
            'component' => 'InputNumber',
            'supports' => ['decimal', 'float'],
        ]);

        $this->registerWidget('monetary', [
            'component' => 'InputMoney',
            'supports' => ['decimal', 'money'],
        ]);

        $this->registerWidget('date', [
            'component' => 'DatePicker',
            'supports' => ['date'],
        ]);

        $this->registerWidget('datetime', [
            'component' => 'DateTimePicker',
            'supports' => ['datetime'],
        ]);

        $this->registerWidget('checkbox', [
            'component' => 'Checkbox',
            'supports' => ['boolean'],
        ]);

        $this->registerWidget('selection', [
            'component' => 'Select',
            'supports' => ['select'],
        ]);

        $this->registerWidget('many2one', [
            'component' => 'Many2One',
            'supports' => ['relation'],
        ]);

        $this->registerWidget('one2many', [
            'component' => 'One2Many',
            'supports' => ['relation'],
        ]);

        $this->registerWidget('many2many', [
            'component' => 'Many2Many',
            'supports' => ['relation'],
        ]);

        $this->registerWidget('image', [
            'component' => 'ImageUpload',
            'supports' => ['image'],
        ]);

        $this->registerWidget('binary', [
            'component' => 'FileUpload',
            'supports' => ['file'],
        ]);

        $this->registerWidget('progressbar', [
            'component' => 'ProgressBar',
            'supports' => ['integer', 'decimal'],
        ]);

        $this->registerWidget('statusbar', [
            'component' => 'StatusBar',
            'supports' => ['select', 'string'],
        ]);

        $this->registerWidget('priority', [
            'component' => 'PriorityStars',
            'supports' => ['integer', 'select'],
        ]);

        $this->registerWidget('color', [
            'component' => 'ColorPicker',
            'supports' => ['color', 'string'],
        ]);

        $this->registerWidget('badge', [
            'component' => 'Badge',
            'supports' => ['string', 'select'],
        ]);

        $this->registerWidget('tags', [
            'component' => 'TagInput',
            'supports' => ['json', 'relation'],
        ]);
    }

    /**
     * Clear view cache.
     */
    public function clearCache(?string $entityName = null, ?string $viewType = null): void
    {
        if ($entityName && $viewType) {
            Cache::forget(self::CACHE_PREFIX . "{$entityName}:{$viewType}");
        } elseif ($entityName) {
            foreach ([UIViewDefinition::TYPE_FORM, UIViewDefinition::TYPE_LIST, 
                      UIViewDefinition::TYPE_KANBAN, UIViewDefinition::TYPE_SEARCH] as $type) {
                Cache::forget(self::CACHE_PREFIX . "{$entityName}:{$type}");
            }
        } else {
            // Clear all view cache using tags if available, otherwise clear specific keys
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['views'])->flush();
            } else {
                // Fallback: clear known view cache keys from database
                $views = UIViewDefinition::select('entity_name', 'view_type')->distinct()->get();
                foreach ($views as $view) {
                    Cache::forget(self::CACHE_PREFIX . "{$view->entity_name}:{$view->view_type}");
                }
            }
        }
    }

    /**
     * Extend an existing view.
     */
    public function extendView(
        string $parentSlug,
        array $modifications,
        ?string $pluginSlug = null
    ): UIViewDefinition {
        $parent = UIViewDefinition::where('slug', $parentSlug)->firstOrFail();

        $slug = "{$parentSlug}_ext";
        if ($pluginSlug) {
            $slug = "{$pluginSlug}_{$slug}";
        }

        return UIViewDefinition::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $parent->name . ' Extension',
                'entity_name' => $parent->entity_name,
                'view_type' => $parent->view_type,
                'priority' => $parent->priority + 1,
                'arch' => ['_inherit' => $modifications],
                'inherit_id' => $parent->id,
                'plugin_slug' => $pluginSlug,
                'is_active' => true,
            ]
        );
    }
}
