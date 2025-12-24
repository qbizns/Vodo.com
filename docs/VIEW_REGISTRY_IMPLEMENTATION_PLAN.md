# View Registry Pattern - Ultra Production Implementation Plan

## Executive Summary

This document outlines a comprehensive implementation plan for the View Registry Pattern in the Vodo platform. Based on analysis of Odoo 17 and Salesforce Lightning patterns, combined with our existing architecture, this plan defines 20 canonical view types and a fully pluggable view system supporting 2000+ views at scale.

---

## PART 1: CURRENT STATE ANALYSIS

### 1.1 Existing Components

| Component | Location | Status | Gaps |
|-----------|----------|--------|------|
| `ViewRegistry` | `app/Services/View/ViewRegistry.php` | Partial | Only 4 view types (form, list, kanban, search) |
| `ViewCompiler` | `app/Services/View/ViewCompiler.php` | Good | Needs view type awareness |
| `ViewExtensionRegistry` | `app/Services/View/ViewExtensionRegistry.php` | Good | Works well for XPath |
| `UIViewDefinition` | `app/Models/UIViewDefinition.php` | Partial | Only 7 type constants |
| `ViewServiceProvider` | `app/Providers/ViewServiceProvider.php` | Good | Needs view type registration |
| `ViewRegistryContract` | `app/Contracts/ViewRegistryContract.php` | Partial | Missing many methods |

### 1.2 Current View Types in UIViewDefinition

```php
TYPE_FORM = 'form'
TYPE_LIST = 'list'
TYPE_KANBAN = 'kanban'
TYPE_SEARCH = 'search'
TYPE_CALENDAR = 'calendar'
TYPE_GRAPH = 'graph'
TYPE_PIVOT = 'pivot'
```

### 1.3 Missing from Current Implementation

1. **View Type Classes** - No abstraction for different view behaviors
2. **View Type Registry** - No way for plugins to register custom view types
3. **View Renderers** - No dedicated rendering logic per view type
4. **View Validators** - No schema validation per view type
5. **Default View Generators** - Only basic implementation exists
6. **Canonical Blade Templates** - No base templates for view types
7. **Component Library** - Platform components not implemented

---

## PART 2: VIEW TYPES SPECIFICATION

### 2.1 The Sacred 20 Canonical View Types

Based on Odoo, Salesforce, and enterprise requirements:

| # | Type | Purpose | Odoo Equivalent | Salesforce Equivalent |
|---|------|---------|-----------------|----------------------|
| 1 | `list` | Tabular data with sorting/filtering/pagination | tree/list | lightning:listView |
| 2 | `form` | Create/Edit forms with validation | form | lightning-record-form |
| 3 | `detail` | Read-only record display | form (readonly) | lightning-record-view-form |
| 4 | `kanban` | Card-based board with columns | kanban | - |
| 5 | `calendar` | Date/time-based display | calendar | - |
| 6 | `tree` | Hierarchical/nested list | - | - |
| 7 | `pivot` | Matrix/crosstab display | pivot | - |
| 8 | `dashboard` | Widget container | dashboard | Lightning App Page |
| 9 | `wizard` | Multi-step guided form | wizard | Flow Screen |
| 10 | `settings` | Key-value configuration | settings | - |
| 11 | `import` | Bulk data import interface | - | Data Import Wizard |
| 12 | `export` | Export configuration | - | Report Export |
| 13 | `search` | Global search results | search | Global Search |
| 14 | `activity` | Timeline/audit display | activity | Activity Timeline |
| 15 | `report` | Parameterized report | qweb report | Salesforce Reports |
| 16 | `chart` | Standalone visualization | graph | Lightning Chart |
| 17 | `modal-form` | Quick-add modal dialog | form (dialog) | Quick Action |
| 18 | `inline-edit` | In-place row editing | tree (editable) | Inline Edit |
| 19 | `blank` | Empty canvas (REQUIRES APPROVAL) | - | Custom Component |
| 20 | `embedded` | External content container | - | iframe/Visualforce |

### 2.2 View Type Specifications

#### 2.2.1 List View (`list`)

```php
[
    'type' => 'list',
    'entity' => 'customers',
    'columns' => [
        'name' => ['label' => 'Name', 'sortable' => true, 'width' => '200px'],
        'email' => ['label' => 'Email', 'sortable' => true, 'widget' => 'email'],
        'status' => ['label' => 'Status', 'widget' => 'badge', 'colors' => [...]],
        'created_at' => ['label' => 'Created', 'widget' => 'datetime'],
    ],
    'filters' => [
        'status' => ['type' => 'select', 'options' => [...]],
        'created_at' => ['type' => 'date_range'],
    ],
    'actions' => [
        'row' => ['edit', 'delete', 'view'],
        'bulk' => ['delete', 'export', 'assign'],
    ],
    'config' => [
        'default_sort' => 'created_at desc',
        'per_page' => 25,
        'selectable' => true,
        'searchable' => true,
        'exportable' => true,
    ],
]
```

#### 2.2.2 Form View (`form`)

```php
[
    'type' => 'form',
    'entity' => 'customers',
    'mode' => 'edit', // create, edit, view
    'sections' => [
        'basic' => [
            'label' => 'Basic Information',
            'columns' => 2,
            'fields' => [
                'name' => ['widget' => 'char', 'required' => true, 'span' => 1],
                'email' => ['widget' => 'email', 'required' => true, 'span' => 1],
                'phone' => ['widget' => 'phone', 'span' => 1],
                'status' => ['widget' => 'selection', 'span' => 1],
            ],
        ],
        'address' => [
            'label' => 'Address',
            'collapsible' => true,
            'fields' => [
                'address' => ['widget' => 'text', 'span' => 2],
                'city' => ['widget' => 'char', 'span' => 1],
                'country_id' => ['widget' => 'many2one', 'span' => 1],
            ],
        ],
        'relations' => [
            'label' => 'Related Records',
            'fields' => [
                'orders' => ['widget' => 'one2many', 'view' => 'orders_inline_list'],
            ],
        ],
    ],
    'actions' => [
        'header' => ['save', 'cancel', 'delete'],
        'custom' => [
            ['name' => 'send_email', 'label' => 'Send Email', 'type' => 'object'],
        ],
    ],
    'config' => [
        'auto_save' => false,
        'confirm_discard' => true,
    ],
]
```

#### 2.2.3 Detail View (`detail`)

```php
[
    'type' => 'detail',
    'entity' => 'customers',
    'layout' => 'card', // card, full, compact
    'header' => [
        'title_field' => 'name',
        'subtitle_field' => 'email',
        'image_field' => 'avatar',
        'status_field' => 'status',
    ],
    'sections' => [
        'overview' => [
            'label' => 'Overview',
            'fields' => ['name', 'email', 'phone', 'status'],
        ],
        'statistics' => [
            'label' => 'Statistics',
            'type' => 'stats',
            'items' => [
                ['label' => 'Total Orders', 'field' => 'orders_count'],
                ['label' => 'Total Spent', 'field' => 'total_spent', 'widget' => 'monetary'],
            ],
        ],
    ],
    'tabs' => [
        'orders' => ['label' => 'Orders', 'view' => 'orders_list', 'filter' => 'customer_id'],
        'activity' => ['label' => 'Activity', 'view' => 'activity_timeline'],
    ],
    'actions' => ['edit', 'delete', 'archive'],
]
```

#### 2.2.4 Kanban View (`kanban`)

```php
[
    'type' => 'kanban',
    'entity' => 'tasks',
    'group_by' => 'status',
    'columns' => [
        'todo' => ['label' => 'To Do', 'color' => '#gray', 'fold' => false],
        'in_progress' => ['label' => 'In Progress', 'color' => '#blue'],
        'done' => ['label' => 'Done', 'color' => '#green'],
    ],
    'card' => [
        'title' => 'name',
        'subtitle' => 'assigned_to.name',
        'image' => 'assigned_to.avatar',
        'fields' => ['priority', 'due_date', 'tags'],
        'progress' => 'completion_percentage',
        'colors' => [
            'field' => 'priority',
            'map' => ['high' => 'red', 'medium' => 'yellow', 'low' => 'gray'],
        ],
    ],
    'config' => [
        'quick_create' => true,
        'draggable' => true,
        'collapsible' => true,
    ],
]
```

#### 2.2.5 Calendar View (`calendar`)

```php
[
    'type' => 'calendar',
    'entity' => 'events',
    'date_start' => 'start_date',
    'date_end' => 'end_date',
    'all_day' => 'is_all_day',
    'display' => [
        'title' => 'name',
        'color' => 'category.color',
    ],
    'views' => ['month', 'week', 'day', 'agenda'],
    'default_view' => 'month',
    'config' => [
        'quick_create' => true,
        'drag_resize' => true,
        'first_day' => 0, // Sunday
    ],
]
```

#### 2.2.6 Tree View (`tree`)

```php
[
    'type' => 'tree',
    'entity' => 'categories',
    'parent_field' => 'parent_id',
    'display' => [
        'title' => 'name',
        'icon' => 'icon',
        'badge' => 'items_count',
    ],
    'config' => [
        'expandable' => true,
        'draggable' => true, // reorder
        'max_depth' => 5,
        'lazy_load' => true,
    ],
    'actions' => ['add_child', 'edit', 'delete'],
]
```

#### 2.2.7 Pivot View (`pivot`)

```php
[
    'type' => 'pivot',
    'entity' => 'sales',
    'measures' => [
        'amount' => ['label' => 'Amount', 'aggregate' => 'sum', 'format' => 'currency'],
        'quantity' => ['label' => 'Qty', 'aggregate' => 'sum'],
        'count' => ['label' => 'Count', 'aggregate' => 'count'],
    ],
    'rows' => ['product_category', 'product'],
    'columns' => ['date:month', 'region'],
    'config' => [
        'show_totals' => true,
        'show_percentages' => false,
        'expandable' => true,
    ],
]
```

#### 2.2.8 Dashboard View (`dashboard`)

```php
[
    'type' => 'dashboard',
    'name' => 'Sales Dashboard',
    'layout' => 'grid', // grid, columns, free
    'widgets' => [
        [
            'type' => 'stat',
            'title' => 'Total Revenue',
            'query' => 'orders.sum(total)',
            'icon' => 'dollar',
            'color' => 'green',
            'position' => ['x' => 0, 'y' => 0, 'w' => 3, 'h' => 1],
        ],
        [
            'type' => 'chart',
            'title' => 'Sales Trend',
            'chart_type' => 'line',
            'data_source' => 'orders_by_month',
            'position' => ['x' => 0, 'y' => 1, 'w' => 6, 'h' => 2],
        ],
        [
            'type' => 'list',
            'title' => 'Recent Orders',
            'view' => 'orders_recent_list',
            'limit' => 5,
            'position' => ['x' => 6, 'y' => 0, 'w' => 6, 'h' => 3],
        ],
    ],
    'config' => [
        'refresh_interval' => 300,
        'user_customizable' => true,
    ],
]
```

#### 2.2.9 Wizard View (`wizard`)

```php
[
    'type' => 'wizard',
    'name' => 'Order Import Wizard',
    'steps' => [
        'upload' => [
            'label' => 'Upload File',
            'description' => 'Select the CSV file to import',
            'fields' => [
                'file' => ['widget' => 'file', 'accept' => '.csv,.xlsx'],
                'has_header' => ['widget' => 'checkbox', 'default' => true],
            ],
            'validation' => 'App\\Wizards\\OrderImport\\ValidateUpload',
        ],
        'mapping' => [
            'label' => 'Map Columns',
            'description' => 'Map file columns to fields',
            'component' => 'column-mapper',
            'depends_on' => 'upload',
        ],
        'preview' => [
            'label' => 'Preview',
            'description' => 'Review data before import',
            'component' => 'import-preview',
        ],
        'confirm' => [
            'label' => 'Import',
            'description' => 'Confirm and start import',
            'action' => 'App\\Wizards\\OrderImport\\ExecuteImport',
        ],
    ],
    'config' => [
        'allow_back' => true,
        'show_progress' => true,
        'cancelable' => true,
    ],
]
```

#### 2.2.10 Settings View (`settings`)

```php
[
    'type' => 'settings',
    'name' => 'Plugin Settings',
    'groups' => [
        'general' => [
            'label' => 'General Settings',
            'icon' => 'settings',
            'fields' => [
                'site_name' => ['widget' => 'char', 'label' => 'Site Name'],
                'timezone' => ['widget' => 'selection', 'options' => 'timezones'],
            ],
        ],
        'notifications' => [
            'label' => 'Notifications',
            'icon' => 'bell',
            'fields' => [
                'email_notifications' => ['widget' => 'checkbox'],
                'notification_email' => ['widget' => 'email'],
            ],
        ],
    ],
    'config' => [
        'auto_save' => false,
        'sections_collapsible' => true,
    ],
]
```

#### 2.2.11-2.2.20 (Additional View Types)

```php
// Import View
['type' => 'import', 'entity' => 'products', 'formats' => ['csv', 'xlsx'], ...]

// Export View
['type' => 'export', 'entity' => 'products', 'formats' => ['csv', 'xlsx', 'pdf'], ...]

// Search View
['type' => 'search', 'entities' => ['products', 'customers', 'orders'], 'filters' => [...], ...]

// Activity View
['type' => 'activity', 'entity' => 'customers', 'event_types' => [...], 'group_by' => 'date', ...]

// Report View
['type' => 'report', 'name' => 'Sales Report', 'parameters' => [...], 'sections' => [...], ...]

// Chart View
['type' => 'chart', 'chart_type' => 'bar', 'data_source' => 'query', 'options' => [...], ...]

// Modal Form View
['type' => 'modal-form', 'entity' => 'notes', 'size' => 'md', 'fields' => [...], ...]

// Inline Edit View
['type' => 'inline-edit', 'entity' => 'line_items', 'columns' => [...], 'config' => [...], ...]

// Blank View
['type' => 'blank', 'template' => 'custom-template', 'approval' => 'APPROVED-2024-001', ...]

// Embedded View
['type' => 'embedded', 'src' => 'https://external.com/widget', 'height' => '400px', ...]
```

---

## PART 3: ARCHITECTURE DESIGN

### 3.1 Core Classes Structure

```
app/
├── Contracts/
│   ├── ViewRegistryContract.php          # Main registry interface
│   ├── ViewTypeContract.php              # View type interface
│   ├── ViewRendererContract.php          # Renderer interface
│   └── ViewValidatorContract.php         # Validator interface
│
├── Services/
│   └── View/
│       ├── ViewRegistry.php              # Enhanced main registry
│       ├── ViewTypeRegistry.php          # NEW: View type management
│       ├── ViewRenderer.php              # NEW: Rendering orchestrator
│       ├── ViewCompiler.php              # Existing: XPath compilation
│       ├── ViewExtensionRegistry.php     # Existing: Extensions
│       ├── ViewValidator.php             # NEW: Schema validation
│       ├── SlotManager.php               # Existing: Slot management
│       │
│       ├── Types/                        # NEW: View type classes
│       │   ├── AbstractViewType.php      # Base class
│       │   ├── ListViewType.php
│       │   ├── FormViewType.php
│       │   ├── DetailViewType.php
│       │   ├── KanbanViewType.php
│       │   ├── CalendarViewType.php
│       │   ├── TreeViewType.php
│       │   ├── PivotViewType.php
│       │   ├── DashboardViewType.php
│       │   ├── WizardViewType.php
│       │   ├── SettingsViewType.php
│       │   ├── ImportViewType.php
│       │   ├── ExportViewType.php
│       │   ├── SearchViewType.php
│       │   ├── ActivityViewType.php
│       │   ├── ReportViewType.php
│       │   ├── ChartViewType.php
│       │   ├── ModalFormViewType.php
│       │   ├── InlineEditViewType.php
│       │   ├── BlankViewType.php
│       │   └── EmbeddedViewType.php
│       │
│       ├── Renderers/                    # NEW: View renderers
│       │   ├── AbstractRenderer.php
│       │   ├── ListRenderer.php
│       │   ├── FormRenderer.php
│       │   ├── KanbanRenderer.php
│       │   └── ...
│       │
│       ├── Validators/                   # NEW: Schema validators
│       │   ├── AbstractValidator.php
│       │   ├── ListViewValidator.php
│       │   ├── FormViewValidator.php
│       │   └── ...
│       │
│       └── Generators/                   # NEW: Default view generators
│           ├── AbstractGenerator.php
│           ├── ListViewGenerator.php
│           ├── FormViewGenerator.php
│           └── ...
│
├── Models/
│   ├── UIViewDefinition.php             # Enhanced with all types
│   ├── ViewType.php                     # NEW: View type metadata
│   └── CompiledView.php                 # Existing
│
└── Providers/
    └── ViewServiceProvider.php          # Enhanced registration
```

### 3.2 Database Schema

#### New Migration: `create_view_types_table`

```php
Schema::create('view_types', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50)->unique();           // 'list', 'form', etc.
    $table->string('label');                        // 'List View'
    $table->text('description')->nullable();
    $table->string('icon', 50)->default('layout');
    $table->string('category', 50);                 // 'data', 'layout', 'workflow'
    $table->string('handler_class');                // 'App\\Services\\View\\Types\\ListViewType'
    $table->string('renderer_class')->nullable();   // Custom renderer
    $table->string('validator_class')->nullable();  // Custom validator
    $table->json('schema')->nullable();             // JSON schema for validation
    $table->json('default_config')->nullable();     // Default configuration
    $table->json('supports')->nullable();           // ['entity', 'standalone', 'nested']
    $table->boolean('requires_entity')->default(true);
    $table->boolean('is_system')->default(false);
    $table->boolean('is_active')->default(true);
    $table->string('plugin_slug')->nullable();
    $table->timestamps();
});
```

#### Enhanced Migration: `update_ui_view_definitions_table`

```php
Schema::table('ui_view_definitions', function (Blueprint $table) {
    $table->string('view_type', 50)->change();      // Expand to allow custom types
    $table->foreignId('view_type_id')->nullable()   // Link to view_types
          ->after('view_type')
          ->constrained('view_types')
          ->nullOnDelete();
    $table->string('mode', 20)->nullable();         // 'create', 'edit', 'view'
    $table->json('permissions')->nullable();        // View-level permissions
    $table->json('conditions')->nullable();         // Display conditions
    $table->string('layout', 50)->default('default'); // Layout variant
    $table->boolean('is_default')->default(false);  // Default view for entity/type
    $table->integer('usage_count')->default(0);     // Analytics
    $table->timestamp('last_used_at')->nullable();
});
```

### 3.3 View Type Contract

```php
<?php

namespace App\Contracts;

interface ViewTypeContract
{
    /**
     * Get the view type name.
     */
    public function getName(): string;

    /**
     * Get human-readable label.
     */
    public function getLabel(): string;

    /**
     * Get the JSON schema for validation.
     */
    public function getSchema(): array;

    /**
     * Get default configuration.
     */
    public function getDefaultConfig(): array;

    /**
     * Validate a view definition.
     */
    public function validate(array $definition): array; // Returns errors

    /**
     * Generate default view for an entity.
     */
    public function generateDefault(string $entityName, array $fields): array;

    /**
     * Get the Blade template path.
     */
    public function getTemplatePath(): string;

    /**
     * Get required widgets/components.
     */
    public function getRequiredWidgets(): array;

    /**
     * Check if this view type supports a feature.
     */
    public function supports(string $feature): bool;

    /**
     * Get available actions for this view type.
     */
    public function getAvailableActions(): array;

    /**
     * Get extension points (slots) for this view type.
     */
    public function getExtensionPoints(): array;

    /**
     * Pre-process view data before rendering.
     */
    public function prepareData(array $definition, array $data): array;
}
```

### 3.4 Plugin Integration

#### Plugin View Registration

```php
// In plugin's ServiceProvider

public function boot(): void
{
    // Register custom view type (rare, requires approval)
    $this->registerViewType('gantt', [
        'label' => 'Gantt Chart',
        'handler' => \MyPlugin\ViewTypes\GanttViewType::class,
        'renderer' => \MyPlugin\Renderers\GanttRenderer::class,
        'category' => 'scheduling',
    ]);

    // Register views for entities
    $this->registerViews();
}

protected function registerViews(): void
{
    // Using the fluent API
    view_registry()
        ->forEntity('projects')
        ->list([
            'columns' => ['name', 'status', 'deadline', 'owner'],
            'filters' => ['status', 'owner'],
        ])
        ->form([
            'sections' => [
                'main' => ['name', 'description', 'status'],
                'schedule' => ['start_date', 'deadline'],
            ],
        ])
        ->kanban([
            'group_by' => 'status',
            'card' => ['title' => 'name', 'fields' => ['deadline', 'owner']],
        ])
        ->register($this->pluginSlug);

    // Extend existing view
    view_registry()
        ->extend('customers_form')
        ->addField('loyalty_tier', [
            'after' => 'email',
            'widget' => 'selection',
            'options' => $this->getLoyaltyTiers(),
        ])
        ->addSection('loyalty', [
            'label' => 'Loyalty Program',
            'fields' => ['loyalty_points', 'loyalty_level'],
        ])
        ->register($this->pluginSlug);
}
```

#### Hook Integration

```php
// Hooks for view lifecycle
add_action('view_type_registered', function($type, $config) { ... });
add_action('view_registered', function($view, $definition) { ... });
add_action('view_rendering', function($view, $data) { ... });
add_action('view_rendered', function($view, $html) { ... });

// Filters for view modification
add_filter('view_definition', function($definition, $viewSlug) { ... });
add_filter('view_data', function($data, $view) { ... });
add_filter('view_columns', function($columns, $view) { ... });
add_filter('view_actions', function($actions, $view) { ... });
```

---

## PART 4: IMPLEMENTATION PHASES

### Phase 1: Foundation (Core Infrastructure)

**Duration: Core setup**

#### Tasks:

1. **Create View Type Registry**
   - `ViewTypeRegistry.php` - manages view type definitions
   - Register 20 canonical types
   - Plugin registration API

2. **Create Abstract View Type**
   - `AbstractViewType.php` - base class
   - Common validation, rendering hooks
   - Extension point definitions

3. **Database Migrations**
   - `view_types` table
   - Update `ui_view_definitions`
   - Seed canonical types

4. **Update ViewRegistryContract**
   - Add missing methods
   - View type awareness
   - Fluent API support

5. **Enhance ViewRegistry**
   - Integrate with ViewTypeRegistry
   - Add fluent builder
   - Improve caching

#### Deliverables:
- [ ] `ViewTypeRegistry.php`
- [ ] `AbstractViewType.php`
- [ ] `ViewTypeContract.php`
- [ ] Database migrations
- [ ] Database seeders
- [ ] Updated `ViewRegistry.php`

### Phase 2: View Types Implementation

**Duration: All 20 view types**

#### Group A: Data Views (list, form, detail, inline-edit)

```
ListViewType.php
FormViewType.php
DetailViewType.php
InlineEditViewType.php
```

#### Group B: Board Views (kanban, calendar, tree)

```
KanbanViewType.php
CalendarViewType.php
TreeViewType.php
```

#### Group C: Analytics Views (pivot, chart, report, dashboard)

```
PivotViewType.php
ChartViewType.php
ReportViewType.php
DashboardViewType.php
```

#### Group D: Workflow Views (wizard, import, export, activity)

```
WizardViewType.php
ImportViewType.php
ExportViewType.php
ActivityViewType.php
```

#### Group E: Special Views (search, settings, modal-form, blank, embedded)

```
SearchViewType.php
SettingsViewType.php
ModalFormViewType.php
BlankViewType.php
EmbeddedViewType.php
```

#### Deliverables:
- [ ] 20 ViewType classes
- [ ] JSON schemas for each type
- [ ] Default generators for each type

### Phase 3: Rendering System

**Duration: Blade templates and renderers**

#### Tasks:

1. **Create Canonical Blade Templates**
   ```
   resources/views/platform/views/
   ├── list.blade.php
   ├── form.blade.php
   ├── detail.blade.php
   ├── kanban.blade.php
   ├── calendar.blade.php
   ├── tree.blade.php
   ├── pivot.blade.php
   ├── dashboard.blade.php
   ├── wizard.blade.php
   ├── settings.blade.php
   ├── import.blade.php
   ├── export.blade.php
   ├── search.blade.php
   ├── activity.blade.php
   ├── report.blade.php
   ├── chart.blade.php
   ├── modal-form.blade.php
   ├── inline-edit.blade.php
   ├── blank.blade.php
   └── embedded.blade.php
   ```

2. **Create View Renderers**
   - One renderer per view type
   - Data preparation logic
   - Component orchestration

3. **Create Platform Components**
   ```
   resources/views/components/platform/
   ├── field.blade.php
   ├── input.blade.php
   ├── select.blade.php
   ├── autocomplete.blade.php
   ├── checkbox.blade.php
   ├── date-picker.blade.php
   ├── file-upload.blade.php
   ├── rich-text.blade.php
   ├── relation.blade.php
   ├── badge.blade.php
   ├── avatar.blade.php
   ├── card.blade.php
   ├── stat.blade.php
   ├── timeline.blade.php
   ├── page-header.blade.php
   ├── section.blade.php
   ├── tabs.blade.php
   ├── modal.blade.php
   ├── button.blade.php
   └── dropdown.blade.php
   ```

4. **Widget System Enhancement**
   - Extend current widget registry
   - Add formatting/parsing
   - Add validation rules

#### Deliverables:
- [ ] 20 Blade templates
- [ ] 20 Renderer classes
- [ ] 30+ Platform components
- [ ] Enhanced widget system

### Phase 4: Validation & Generators

**Duration: Schema validation and auto-generation**

#### Tasks:

1. **Create JSON Schemas**
   - One schema per view type
   - Strict validation rules
   - Clear error messages

2. **Create View Validators**
   - Schema-based validation
   - Business rule validation
   - Performance warnings

3. **Create Default Generators**
   - Entity-aware generation
   - Field type mapping
   - Smart defaults

4. **Create CLI Commands**
   ```bash
   php artisan view:make {entity} {type}
   php artisan view:validate {slug}
   php artisan view:generate-defaults {entity}
   php artisan view:export {slug}
   php artisan view:import {file}
   ```

#### Deliverables:
- [ ] 20 JSON schemas
- [ ] 20 Validator classes
- [ ] 20 Generator classes
- [ ] 5 Artisan commands

### Phase 5: Plugin SDK & Helpers

**Duration: Developer experience**

#### Tasks:

1. **Create Fluent View Builder**
   ```php
   ViewBuilder::forEntity('products')
       ->list()
           ->columns(['name', 'price', 'stock'])
           ->sortable(['name', 'price'])
           ->filterable(['category', 'status'])
       ->form()
           ->section('basic', ['name', 'sku', 'price'])
           ->section('inventory', ['stock', 'warehouse'])
       ->register('my-plugin');
   ```

2. **Create View Helpers**
   ```php
   register_list_view($entity, $columns, $config);
   register_form_view($entity, $sections, $config);
   extend_view($slug, $modifications);
   get_view_for_entity($entity, $type);
   render_view($slug, $data);
   ```

3. **Create View Testing Utilities**
   ```php
   ViewTestCase::assertViewValid($definition);
   ViewTestCase::assertViewRenders($slug);
   ViewTestCase::assertViewHasColumn($slug, 'name');
   ```

4. **Documentation**
   - View type reference
   - Plugin integration guide
   - Component library docs
   - Migration guide

#### Deliverables:
- [ ] `ViewBuilder.php`
- [ ] Enhanced `view-helpers.php`
- [ ] `ViewTestCase.php`
- [ ] Documentation files

### Phase 6: Admin UI

**Duration: Management interface**

#### Tasks:

1. **View Management Pages**
   - View type browser
   - View definition editor
   - Visual form builder (future)

2. **View Analytics**
   - Usage tracking
   - Performance metrics
   - Error logging

3. **View Permissions**
   - Per-view access control
   - Integration with PermissionRegistry

#### Deliverables:
- [ ] View management CRUD
- [ ] View analytics dashboard
- [ ] Permission integration

---

## PART 5: HELPER FUNCTIONS

### 5.1 Enhanced view-helpers.php

```php
<?php

// =========================================================================
// View Registration Helpers
// =========================================================================

function register_view_type(string $name, array $config, ?string $pluginSlug = null): void;
function register_list_view(string $entity, array $columns, array $config = [], ?string $pluginSlug = null): UIViewDefinition;
function register_form_view(string $entity, array $sections, array $config = [], ?string $pluginSlug = null): UIViewDefinition;
function register_kanban_view(string $entity, string $groupBy, array $card, array $config = [], ?string $pluginSlug = null): UIViewDefinition;
function register_calendar_view(string $entity, string $dateStart, ?string $dateEnd = null, array $config = [], ?string $pluginSlug = null): UIViewDefinition;
function register_dashboard_view(string $name, array $widgets, array $config = [], ?string $pluginSlug = null): UIViewDefinition;

// =========================================================================
// View Retrieval Helpers
// =========================================================================

function get_view(string $slug): ?array;
function get_views_for_entity(string $entity, ?string $type = null): Collection;
function get_default_view(string $entity, string $type): ?array;
function view_exists(string $slug): bool;

// =========================================================================
// View Rendering Helpers
// =========================================================================

function render_view(string $slug, array $data = []): string;
function render_entity_view(string $entity, string $type, array $data = []): string;
function get_view_component(string $viewType): string;

// =========================================================================
// View Extension Helpers
// =========================================================================

function extend_view(string $slug, array $modifications, ?string $pluginSlug = null): void;
function add_view_column(string $slug, string $column, array $config, ?string $after = null): void;
function add_view_field(string $slug, string $field, array $config, ?string $section = null): void;
function add_view_section(string $slug, string $section, array $config, ?string $after = null): void;
function remove_view_element(string $slug, string $xpath): void;

// =========================================================================
// View Builder Helpers
// =========================================================================

function view_builder(): ViewBuilder;
function view_for_entity(string $entity): EntityViewBuilder;

// =========================================================================
// View Validation Helpers
// =========================================================================

function validate_view(array $definition): array; // Returns errors
function validate_view_type(string $type, array $definition): array;
```

---

## PART 6: CONFIGURATION

### 6.1 Enhanced config/view-system.php

```php
<?php

return [
    // ... existing config ...

    /*
    |--------------------------------------------------------------------------
    | View Types
    |--------------------------------------------------------------------------
    */
    'types' => [
        // Canonical types (cannot be disabled)
        'canonical' => [
            'list', 'form', 'detail', 'kanban', 'calendar', 'tree',
            'pivot', 'dashboard', 'wizard', 'settings', 'import',
            'export', 'search', 'activity', 'report', 'chart',
            'modal-form', 'inline-edit', 'blank', 'embedded',
        ],

        // Allow plugins to register custom types
        'allow_custom' => true,

        // Require approval for blank type
        'blank_requires_approval' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Views
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        // Auto-generate these view types for new entities
        'auto_generate' => ['list', 'form', 'search'],

        // Default view type for entity pages
        'default_type' => 'list',

        // Default form mode
        'form_mode' => 'edit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    */
    'rendering' => [
        // Template namespace
        'namespace' => 'platform',

        // Template path
        'path' => resource_path('views/platform/views'),

        // Component prefix
        'component_prefix' => 'x-platform',

        // Enable caching
        'cache' => env('VIEW_RENDER_CACHE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    'validation' => [
        // Strict mode (fail on warnings)
        'strict' => env('VIEW_VALIDATION_STRICT', false),

        // Validate on registration
        'validate_on_register' => true,

        // Log validation errors
        'log_errors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Integration
    |--------------------------------------------------------------------------
    */
    'plugins' => [
        // Track view ownership
        'track_ownership' => true,

        // Remove views on plugin deactivation
        'cleanup_on_deactivate' => false,

        // Remove views on plugin uninstall
        'cleanup_on_uninstall' => true,
    ],
];
```

---

## PART 7: MIGRATION STRATEGY

### 7.1 Backward Compatibility

All existing view registrations will continue to work:

```php
// Old API (still works)
$viewRegistry->registerFormView('customers', [...]);
$viewRegistry->registerListView('products', [...]);

// New API (recommended)
view_for_entity('customers')
    ->form([...])
    ->list([...])
    ->register();
```

### 7.2 Migration Steps

1. **Run migrations** - Add new tables/columns
2. **Run seeders** - Populate view types
3. **Update providers** - Register new services
4. **Clear caches** - Ensure fresh state
5. **Validate existing** - Check all registered views

---

## PART 8: TESTING STRATEGY

### 8.1 Unit Tests

```php
class ViewTypeRegistryTest extends TestCase
{
    public function test_can_register_canonical_types(): void;
    public function test_can_register_custom_type(): void;
    public function test_validates_view_definitions(): void;
    public function test_generates_default_views(): void;
}

class ListViewTypeTest extends TestCase
{
    public function test_validates_column_configuration(): void;
    public function test_generates_default_from_entity(): void;
    public function test_renders_correctly(): void;
}
```

### 8.2 Integration Tests

```php
class ViewRegistrationTest extends TestCase
{
    public function test_plugin_can_register_views(): void;
    public function test_plugin_can_extend_views(): void;
    public function test_views_respect_permissions(): void;
}
```

### 8.3 Performance Tests

```php
class ViewPerformanceTest extends TestCase
{
    public function test_renders_list_with_1000_records(): void;
    public function test_caching_improves_performance(): void;
    public function test_handles_50_extensions(): void;
}
```

---

## PART 9: DELIVERABLES CHECKLIST

### Phase 1: Foundation
- [ ] `app/Services/View/ViewTypeRegistry.php`
- [ ] `app/Services/View/Types/AbstractViewType.php`
- [ ] `app/Contracts/ViewTypeContract.php`
- [ ] `database/migrations/xxxx_create_view_types_table.php`
- [ ] `database/migrations/xxxx_update_ui_view_definitions_table.php`
- [ ] `database/seeders/ViewTypeSeeder.php`
- [ ] Updated `app/Services/View/ViewRegistry.php`
- [ ] Updated `app/Contracts/ViewRegistryContract.php`

### Phase 2: View Types
- [ ] 20 ViewType classes in `app/Services/View/Types/`
- [ ] JSON schemas in `config/view-schemas/`

### Phase 3: Rendering
- [ ] 20 Blade templates in `resources/views/platform/views/`
- [ ] 20 Renderer classes in `app/Services/View/Renderers/`
- [ ] 30+ Component files in `resources/views/components/platform/`

### Phase 4: Validation & Generators
- [ ] 20 Validator classes in `app/Services/View/Validators/`
- [ ] 20 Generator classes in `app/Services/View/Generators/`
- [ ] Artisan commands

### Phase 5: Plugin SDK
- [ ] `app/Services/View/ViewBuilder.php`
- [ ] Updated `helpers/view-helpers.php`
- [ ] `tests/View/ViewTestCase.php`
- [ ] Documentation in `docs/`

### Phase 6: Admin UI
- [ ] View management pages
- [ ] Analytics integration

---

## APPENDIX A: ODOO VIEW REFERENCE

Based on [Odoo Documentation](https://www.odoo.com/documentation/15.0/developer/reference/backend/views.html) and [Odoo 18 View Types](https://muchconsulting.com/blog/odoo-2/odoo-view-types-33):

| Odoo View | Our Equivalent | Notes |
|-----------|---------------|-------|
| form | form | Direct mapping |
| tree/list | list | Combined as list |
| kanban | kanban | Direct mapping |
| calendar | calendar | Direct mapping |
| pivot | pivot | Direct mapping |
| graph | chart | Renamed for clarity |
| search | search | Direct mapping |
| activity | activity | Direct mapping |
| cohort | report | Subsumed into reports |
| gantt | (plugin) | Specialized, plugin territory |
| map | embedded | Can embed maps |
| grid | pivot | Similar functionality |
| dashboard | dashboard | Direct mapping |

## APPENDIX B: SALESFORCE REFERENCE

Based on [Salesforce Lightning Components](https://developer.salesforce.com/docs/component-library/bundle/lightning-record-form):

| Salesforce Component | Our Equivalent | Notes |
|---------------------|---------------|-------|
| lightning-record-form | form | With mode support |
| lightning-record-view-form | detail | Read-only variant |
| lightning:listView | list | Direct mapping |
| Lightning Record Page | detail + form | Combined |
| Dynamic Forms | form | Field-level control |
| Flow Screen | wizard | Multi-step |
| Quick Action | modal-form | Dialog forms |
| Report | report | Direct mapping |
| Dashboard | dashboard | Direct mapping |

---

## APPENDIX C: PRIORITY ORDER

Recommended implementation priority:

1. **Critical (Must Have First)**
   - list, form, detail (core data views)
   - search (navigation essential)

2. **High (Business Essential)**
   - kanban, calendar (workflow views)
   - dashboard (overview pages)
   - settings (configuration)

3. **Medium (Feature Complete)**
   - wizard, import, export (data operations)
   - chart, report (analytics)
   - activity (audit trail)

4. **Lower (Nice to Have)**
   - tree, pivot (specialized data views)
   - modal-form, inline-edit (UX enhancements)
   - blank, embedded (escape hatches)
