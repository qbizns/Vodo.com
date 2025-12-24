<?php

declare(strict_types=1);

namespace App\Services\View;

use App\Contracts\ViewTypeContract;
use App\Models\UIViewDefinition;
use Illuminate\Support\Facades\App;

/**
 * Fluent API for building view definitions.
 *
 * Provides a chainable interface for constructing view definitions
 * with type safety and validation.
 *
 * @example Create a list view
 * ```php
 * ViewBuilder::list('invoice')
 *     ->name('Invoice List')
 *     ->columns(['number', 'customer', 'total', 'status'])
 *     ->sortable(['number', 'total'])
 *     ->filterable(['status', 'customer'])
 *     ->paginate(25)
 *     ->actions(['view', 'edit', 'delete'])
 *     ->register();
 * ```
 *
 * @example Create a form view
 * ```php
 * ViewBuilder::form('invoice')
 *     ->name('Invoice Form')
 *     ->group('header', fn($g) => $g
 *         ->columns(2)
 *         ->field('customer_id', 'many2one', ['required' => true])
 *         ->field('date', 'date'))
 *     ->group('lines', fn($g) => $g
 *         ->field('line_ids', 'one2many'))
 *     ->buttons(['save', 'cancel', 'confirm'])
 *     ->register();
 * ```
 *
 * @example Create a kanban view
 * ```php
 * ViewBuilder::kanban('task')
 *     ->groupBy('status')
 *     ->cardTitle('name')
 *     ->cardSubtitle('assignee_id')
 *     ->cardFields(['priority', 'due_date'])
 *     ->quickCreate(true)
 *     ->register();
 * ```
 */
class ViewBuilder
{
    /**
     * View type being built.
     */
    protected string $viewType;

    /**
     * Entity name.
     */
    protected string $entityName;

    /**
     * View definition being built.
     */
    protected array $definition = [];

    /**
     * Plugin slug.
     */
    protected ?string $pluginSlug = null;

    /**
     * Parent view slug for inheritance.
     */
    protected ?string $inheritFrom = null;

    /**
     * Current group being built.
     */
    protected ?string $currentGroup = null;

    /**
     * Create a new ViewBuilder instance.
     */
    public function __construct(string $viewType, string $entityName)
    {
        $this->viewType = $viewType;
        $this->entityName = $entityName;
        $this->definition = [
            'type' => $viewType,
            'entity' => $entityName,
        ];
    }

    /**
     * Create a list view builder.
     */
    public static function list(string $entityName): self
    {
        return new self('list', $entityName);
    }

    /**
     * Create a form view builder.
     */
    public static function form(string $entityName): self
    {
        return new self('form', $entityName);
    }

    /**
     * Create a detail view builder.
     */
    public static function detail(string $entityName): self
    {
        return new self('detail', $entityName);
    }

    /**
     * Create a kanban view builder.
     */
    public static function kanban(string $entityName): self
    {
        return new self('kanban', $entityName);
    }

    /**
     * Create a calendar view builder.
     */
    public static function calendar(string $entityName): self
    {
        return new self('calendar', $entityName);
    }

    /**
     * Create a tree view builder.
     */
    public static function tree(string $entityName): self
    {
        return new self('tree', $entityName);
    }

    /**
     * Create a pivot view builder.
     */
    public static function pivot(string $entityName): self
    {
        return new self('pivot', $entityName);
    }

    /**
     * Create a dashboard view builder.
     */
    public static function dashboard(string $entityName = ''): self
    {
        return new self('dashboard', $entityName);
    }

    /**
     * Create a wizard view builder.
     */
    public static function wizard(string $entityName): self
    {
        return new self('wizard', $entityName);
    }

    /**
     * Create a search view builder.
     */
    public static function search(string $entityName): self
    {
        return new self('search', $entityName);
    }

    /**
     * Create a report view builder.
     */
    public static function report(string $entityName): self
    {
        return new self('report', $entityName);
    }

    /**
     * Create a chart view builder.
     */
    public static function chart(string $entityName): self
    {
        return new self('chart', $entityName);
    }

    /**
     * Create a settings view builder.
     */
    public static function settings(string $entityName = ''): self
    {
        return new self('settings', $entityName);
    }

    /**
     * Create an import view builder.
     */
    public static function import(string $entityName): self
    {
        return new self('import', $entityName);
    }

    /**
     * Create an export view builder.
     */
    public static function export(string $entityName): self
    {
        return new self('export', $entityName);
    }

    /**
     * Create an activity view builder.
     */
    public static function activity(string $entityName): self
    {
        return new self('activity', $entityName);
    }

    /**
     * Create a modal form view builder.
     */
    public static function modalForm(string $entityName): self
    {
        return new self('modal-form', $entityName);
    }

    /**
     * Create an inline edit view builder.
     */
    public static function inlineEdit(string $entityName): self
    {
        return new self('inline-edit', $entityName);
    }

    /**
     * Create a blank view builder.
     */
    public static function blank(string $entityName = ''): self
    {
        return new self('blank', $entityName);
    }

    /**
     * Create an embedded view builder.
     */
    public static function embedded(string $entityName = ''): self
    {
        return new self('embedded', $entityName);
    }

    /**
     * Create a view builder for any type.
     */
    public static function make(string $viewType, string $entityName): self
    {
        return new self($viewType, $entityName);
    }

    // ========================================
    // Common Methods
    // ========================================

    /**
     * Set the view name.
     */
    public function name(string $name): self
    {
        $this->definition['name'] = $name;

        return $this;
    }

    /**
     * Set the view slug.
     */
    public function slug(string $slug): self
    {
        $this->definition['slug'] = $slug;

        return $this;
    }

    /**
     * Set the view priority.
     */
    public function priority(int $priority): self
    {
        $this->definition['priority'] = $priority;

        return $this;
    }

    /**
     * Set the plugin that owns this view.
     */
    public function plugin(string $pluginSlug): self
    {
        $this->pluginSlug = $pluginSlug;

        return $this;
    }

    /**
     * Inherit from another view.
     */
    public function inherit(string $parentSlug): self
    {
        $this->inheritFrom = $parentSlug;

        return $this;
    }

    /**
     * Set configuration options.
     */
    public function config(array $config): self
    {
        $this->definition['config'] = array_merge(
            $this->definition['config'] ?? [],
            $config
        );

        return $this;
    }

    /**
     * Set a single config option.
     */
    public function option(string $key, mixed $value): self
    {
        $this->definition['config'][$key] = $value;

        return $this;
    }

    // ========================================
    // List View Methods
    // ========================================

    /**
     * Set columns for list view.
     */
    public function columns(array $columns): self
    {
        $this->definition['columns'] = $this->normalizeColumns($columns);

        return $this;
    }

    /**
     * Add a column to list view.
     */
    public function column(string $name, array $options = []): self
    {
        $this->definition['columns'][$name] = array_merge([
            'label' => ucfirst(str_replace('_', ' ', $name)),
        ], $options);

        return $this;
    }

    /**
     * Set sortable columns.
     */
    public function sortable(array $columns): self
    {
        foreach ($columns as $column) {
            if (isset($this->definition['columns'][$column])) {
                $this->definition['columns'][$column]['sortable'] = true;
            }
        }

        return $this;
    }

    /**
     * Set filterable columns.
     */
    public function filterable(array $columns): self
    {
        foreach ($columns as $column) {
            if (isset($this->definition['columns'][$column])) {
                $this->definition['columns'][$column]['filterable'] = true;
            }
        }

        return $this;
    }

    /**
     * Set searchable columns.
     */
    public function searchable(array $columns): self
    {
        foreach ($columns as $column) {
            if (isset($this->definition['columns'][$column])) {
                $this->definition['columns'][$column]['searchable'] = true;
            }
        }

        return $this;
    }

    /**
     * Set pagination.
     */
    public function paginate(int $perPage = 25): self
    {
        $this->definition['config']['per_page'] = $perPage;
        $this->definition['config']['pagination'] = true;

        return $this;
    }

    /**
     * Set default ordering.
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->definition['default_order'] = "{$column} {$direction}";

        return $this;
    }

    /**
     * Enable row selection.
     */
    public function selectable(bool $selectable = true): self
    {
        $this->definition['selectable'] = $selectable;

        return $this;
    }

    /**
     * Enable editable mode (inline editing).
     */
    public function editable(bool $editable = true): self
    {
        $this->definition['editable'] = $editable;

        return $this;
    }

    // ========================================
    // Form View Methods
    // ========================================

    /**
     * Add a field group.
     */
    public function group(string $name, callable|array $config = []): self
    {
        if (is_callable($config)) {
            $this->currentGroup = $name;
            $this->definition['groups'][$name] = [
                'label' => ucfirst(str_replace('_', ' ', $name)),
                'fields' => [],
            ];
            $config($this);
            $this->currentGroup = null;
        } else {
            $this->definition['groups'][$name] = array_merge([
                'label' => ucfirst(str_replace('_', ' ', $name)),
                'fields' => [],
            ], $config);
        }

        return $this;
    }

    /**
     * Add a field to the current group or directly.
     */
    public function field(string $name, string $widget = 'char', array $options = []): self
    {
        $fieldConfig = array_merge([
            'widget' => $widget,
            'label' => ucfirst(str_replace('_', ' ', $name)),
        ], $options);

        if ($this->currentGroup) {
            $this->definition['groups'][$this->currentGroup]['fields'][$name] = $fieldConfig;
        } else {
            $this->definition['fields'][$name] = $fieldConfig;
        }

        return $this;
    }

    /**
     * Set the number of columns in current group.
     */
    public function groupColumns(int $columns): self
    {
        if ($this->currentGroup) {
            $this->definition['groups'][$this->currentGroup]['columns'] = $columns;
        }

        return $this;
    }

    /**
     * Add buttons to form.
     */
    public function buttons(array $buttons): self
    {
        $this->definition['buttons'] = $this->normalizeButtons($buttons);

        return $this;
    }

    /**
     * Add a button to form.
     */
    public function button(string $name, array $options = []): self
    {
        $this->definition['buttons'][] = array_merge([
            'name' => $name,
            'label' => ucfirst(str_replace('_', ' ', $name)),
        ], $options);

        return $this;
    }

    // ========================================
    // Kanban View Methods
    // ========================================

    /**
     * Set group by field for kanban.
     */
    public function groupBy(string $field): self
    {
        $this->definition['group_by'] = $field;

        return $this;
    }

    /**
     * Set card title field.
     */
    public function cardTitle(string $field): self
    {
        $this->definition['card']['title'] = $field;

        return $this;
    }

    /**
     * Set card subtitle field.
     */
    public function cardSubtitle(string $field): self
    {
        $this->definition['card']['subtitle'] = $field;

        return $this;
    }

    /**
     * Set card image field.
     */
    public function cardImage(string $field): self
    {
        $this->definition['card']['image'] = $field;

        return $this;
    }

    /**
     * Set card fields.
     */
    public function cardFields(array $fields): self
    {
        $this->definition['card']['fields'] = $fields;

        return $this;
    }

    /**
     * Enable quick create on kanban.
     */
    public function quickCreate(bool $enabled = true): self
    {
        $this->definition['quick_create'] = $enabled;

        return $this;
    }

    // ========================================
    // Calendar View Methods
    // ========================================

    /**
     * Set date start field.
     */
    public function dateStart(string $field): self
    {
        $this->definition['date_start'] = $field;

        return $this;
    }

    /**
     * Set date end field.
     */
    public function dateEnd(string $field): self
    {
        $this->definition['date_end'] = $field;

        return $this;
    }

    /**
     * Set all-day field.
     */
    public function allDay(string $field): self
    {
        $this->definition['all_day'] = $field;

        return $this;
    }

    /**
     * Set default calendar mode.
     */
    public function mode(string $mode): self
    {
        $this->definition['mode'] = $mode;

        return $this;
    }

    // ========================================
    // Tree View Methods
    // ========================================

    /**
     * Set parent field for tree hierarchy.
     */
    public function parentField(string $field): self
    {
        $this->definition['parent_field'] = $field;

        return $this;
    }

    /**
     * Set child count field.
     */
    public function childCount(string $field): self
    {
        $this->definition['child_count'] = $field;

        return $this;
    }

    /**
     * Set tree depth limit.
     */
    public function maxDepth(int $depth): self
    {
        $this->definition['max_depth'] = $depth;

        return $this;
    }

    // ========================================
    // Pivot View Methods
    // ========================================

    /**
     * Set row fields for pivot.
     */
    public function rows(array $fields): self
    {
        $this->definition['rows'] = $fields;

        return $this;
    }

    /**
     * Set column fields for pivot.
     */
    public function cols(array $fields): self
    {
        $this->definition['cols'] = $fields;

        return $this;
    }

    /**
     * Set measure fields for pivot.
     */
    public function measures(array $fields): self
    {
        $this->definition['measures'] = $fields;

        return $this;
    }

    // ========================================
    // Dashboard View Methods
    // ========================================

    /**
     * Add a widget to dashboard.
     */
    public function widget(string $name, string $type, array $options = []): self
    {
        $this->definition['widgets'][] = array_merge([
            'name' => $name,
            'type' => $type,
        ], $options);

        return $this;
    }

    /**
     * Set dashboard layout.
     */
    public function layout(array $layout): self
    {
        $this->definition['layout'] = $layout;

        return $this;
    }

    // ========================================
    // Wizard View Methods
    // ========================================

    /**
     * Add a step to wizard.
     */
    public function step(string $name, callable|array $config = []): self
    {
        if (is_callable($config)) {
            $stepBuilder = new self('form', $this->entityName);
            $config($stepBuilder);
            $this->definition['steps'][] = array_merge(
                ['name' => $name],
                $stepBuilder->getDefinition()
            );
        } else {
            $this->definition['steps'][] = array_merge(
                ['name' => $name],
                $config
            );
        }

        return $this;
    }

    // ========================================
    // Common Action Methods
    // ========================================

    /**
     * Set available actions.
     */
    public function actions(array $actions): self
    {
        $this->definition['actions'] = $this->normalizeActions($actions);

        return $this;
    }

    /**
     * Add a single action.
     */
    public function action(string $name, array $options = []): self
    {
        $this->definition['actions'][] = array_merge([
            'name' => $name,
            'label' => ucfirst(str_replace('_', ' ', $name)),
        ], $options);

        return $this;
    }

    /**
     * Set access restrictions.
     */
    public function accessGroups(array $groups): self
    {
        $this->definition['access_groups'] = $groups;

        return $this;
    }

    // ========================================
    // Build Methods
    // ========================================

    /**
     * Get the built definition array.
     */
    public function getDefinition(): array
    {
        return $this->definition;
    }

    /**
     * Build and return the definition without registering.
     */
    public function build(): array
    {
        return $this->definition;
    }

    /**
     * Register the view with the ViewRegistry.
     */
    public function register(): UIViewDefinition
    {
        $registry = App::make(ViewRegistry::class);

        return $registry->registerView(
            $this->entityName,
            $this->viewType,
            $this->definition,
            $this->pluginSlug,
            $this->inheritFrom
        );
    }

    /**
     * Validate the definition without registering.
     */
    public function validate(): array
    {
        $registry = App::make(ViewTypeRegistry::class);
        $type = $registry->get($this->viewType);

        if (!$type) {
            return ['type' => "Unknown view type: {$this->viewType}"];
        }

        return $type->validate($this->definition);
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Normalize column definitions.
     */
    protected function normalizeColumns(array $columns): array
    {
        $normalized = [];

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                // Simple column name
                $normalized[$value] = [
                    'label' => ucfirst(str_replace('_', ' ', $value)),
                ];
            } else {
                // Column with config
                $normalized[$key] = is_array($value) ? $value : ['label' => $value];
            }
        }

        return $normalized;
    }

    /**
     * Normalize button definitions.
     */
    protected function normalizeButtons(array $buttons): array
    {
        $normalized = [];

        foreach ($buttons as $key => $value) {
            if (is_int($key)) {
                // Simple button name
                $normalized[] = [
                    'name' => $value,
                    'label' => ucfirst(str_replace('_', ' ', $value)),
                    'type' => 'action',
                ];
            } else {
                // Button with config
                $normalized[] = array_merge([
                    'name' => $key,
                    'label' => ucfirst(str_replace('_', ' ', $key)),
                ], is_array($value) ? $value : ['label' => $value]);
            }
        }

        return $normalized;
    }

    /**
     * Normalize action definitions.
     */
    protected function normalizeActions(array $actions): array
    {
        $normalized = [];

        foreach ($actions as $key => $value) {
            if (is_int($key)) {
                // Simple action name
                $normalized[] = [
                    'name' => $value,
                    'label' => ucfirst(str_replace('_', ' ', $value)),
                ];
            } else {
                // Action with config
                $normalized[] = array_merge([
                    'name' => $key,
                ], is_array($value) ? $value : ['label' => $value]);
            }
        }

        return $normalized;
    }
}
