<?php

namespace Database\Seeders;

use App\Models\UIViewDefinition;
use App\Services\View\ViewTypeRegistry;
use Illuminate\Database\Seeder;

/**
 * Seeds view type information and sample view definitions.
 *
 * Note: The ViewTypeRegistry auto-registers all 20 canonical view types
 * when instantiated. This seeder creates sample UI view definitions
 * for common entities to demonstrate the view system.
 */
class ViewTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedViewTypeDocumentation();
        $this->seedSampleViews();
    }

    /**
     * Seed view type documentation data.
     *
     * This creates a reference of all available view types that can
     * be used by the admin UI for view type selection.
     */
    protected function seedViewTypeDocumentation(): void
    {
        $registry = app(ViewTypeRegistry::class);

        $this->command->info('Registered view types:');

        foreach ($registry->allSorted() as $type) {
            $this->command->line(sprintf(
                '  - %s (%s): %s',
                $type->getName(),
                $type->getCategory(),
                $type->getDescription()
            ));
        }

        $this->command->info(sprintf('Total: %d view types registered', $registry->count()));
    }

    /**
     * Seed sample view definitions for demonstration.
     */
    protected function seedSampleViews(): void
    {
        // Only seed if no UI view definitions exist
        if (UIViewDefinition::count() > 0) {
            $this->command->info('UI view definitions already exist, skipping sample data.');

            return;
        }

        $this->command->info('Seeding sample UI view definitions...');

        // Sample list view for contacts
        $this->createSampleListView();

        // Sample form view for contacts
        $this->createSampleFormView();

        // Sample kanban view for tasks
        $this->createSampleKanbanView();

        // Sample dashboard view
        $this->createSampleDashboardView();

        $this->command->info('Sample views created successfully.');
    }

    /**
     * Create a sample list view.
     */
    protected function createSampleListView(): void
    {
        UIViewDefinition::create([
            'name' => 'Contacts List',
            'slug' => 'contacts_list',
            'entity_name' => 'contact',
            'view_type' => UIViewDefinition::TYPE_LIST,
            'priority' => 16,
            'description' => 'Default list view for contacts',
            'icon' => 'list',
            'arch' => [
                'type' => 'list',
                'entity' => 'contact',
                'columns' => [
                    'name' => ['label' => 'Name', 'sortable' => true, 'searchable' => true],
                    'email' => ['label' => 'Email', 'sortable' => true],
                    'phone' => ['label' => 'Phone'],
                    'company' => ['label' => 'Company', 'sortable' => true],
                    'status' => ['label' => 'Status', 'filterable' => true],
                    'created_at' => ['label' => 'Created', 'sortable' => true],
                ],
                'default_order' => 'name asc',
                'selectable' => true,
                'editable' => false,
                'config' => [
                    'per_page' => 25,
                    'pagination' => true,
                ],
            ],
            'config' => ['per_page' => 25],
            'is_active' => true,
        ]);
    }

    /**
     * Create a sample form view.
     */
    protected function createSampleFormView(): void
    {
        UIViewDefinition::create([
            'name' => 'Contact Form',
            'slug' => 'contact_form',
            'entity_name' => 'contact',
            'view_type' => UIViewDefinition::TYPE_FORM,
            'priority' => 16,
            'description' => 'Default form view for creating/editing contacts',
            'icon' => 'edit',
            'arch' => [
                'type' => 'form',
                'entity' => 'contact',
                'groups' => [
                    'basic' => [
                        'label' => 'Basic Information',
                        'columns' => 2,
                        'fields' => [
                            'name' => ['widget' => 'char', 'required' => true, 'label' => 'Full Name'],
                            'email' => ['widget' => 'email', 'required' => true, 'label' => 'Email Address'],
                            'phone' => ['widget' => 'phone', 'label' => 'Phone Number'],
                            'company' => ['widget' => 'char', 'label' => 'Company'],
                        ],
                    ],
                    'details' => [
                        'label' => 'Additional Details',
                        'columns' => 1,
                        'fields' => [
                            'notes' => ['widget' => 'text', 'label' => 'Notes'],
                            'tags' => ['widget' => 'tags', 'label' => 'Tags'],
                        ],
                    ],
                ],
                'buttons' => [
                    ['name' => 'save', 'label' => 'Save', 'type' => 'submit', 'class' => 'primary'],
                    ['name' => 'cancel', 'label' => 'Cancel', 'type' => 'cancel'],
                ],
            ],
            'config' => [],
            'is_active' => true,
        ]);
    }

    /**
     * Create a sample kanban view.
     */
    protected function createSampleKanbanView(): void
    {
        UIViewDefinition::create([
            'name' => 'Tasks Kanban',
            'slug' => 'tasks_kanban',
            'entity_name' => 'task',
            'view_type' => UIViewDefinition::TYPE_KANBAN,
            'priority' => 16,
            'description' => 'Kanban board for task management',
            'icon' => 'columns',
            'arch' => [
                'type' => 'kanban',
                'entity' => 'task',
                'group_by' => 'status',
                'card' => [
                    'title' => 'name',
                    'subtitle' => 'assignee_id',
                    'image' => null,
                    'fields' => ['priority', 'due_date', 'tags'],
                    'colors' => [
                        'priority' => [
                            'high' => 'danger',
                            'medium' => 'warning',
                            'low' => 'success',
                        ],
                    ],
                ],
                'columns' => [
                    'todo' => ['label' => 'To Do', 'color' => 'gray'],
                    'in_progress' => ['label' => 'In Progress', 'color' => 'blue'],
                    'review' => ['label' => 'Review', 'color' => 'yellow'],
                    'done' => ['label' => 'Done', 'color' => 'green'],
                ],
                'quick_create' => true,
                'config' => [
                    'draggable' => true,
                    'show_count' => true,
                ],
            ],
            'config' => ['draggable' => true],
            'is_active' => true,
        ]);
    }

    /**
     * Create a sample dashboard view.
     */
    protected function createSampleDashboardView(): void
    {
        UIViewDefinition::create([
            'name' => 'Main Dashboard',
            'slug' => 'main_dashboard',
            'entity_name' => '',
            'view_type' => UIViewDefinition::TYPE_DASHBOARD,
            'priority' => 16,
            'description' => 'Main application dashboard with KPIs and charts',
            'icon' => 'layout-dashboard',
            'arch' => [
                'type' => 'dashboard',
                'widgets' => [
                    [
                        'name' => 'total_contacts',
                        'type' => 'stat',
                        'title' => 'Total Contacts',
                        'entity' => 'contact',
                        'aggregate' => 'count',
                        'icon' => 'users',
                        'color' => 'primary',
                        'position' => ['x' => 0, 'y' => 0, 'w' => 3, 'h' => 1],
                    ],
                    [
                        'name' => 'open_tasks',
                        'type' => 'stat',
                        'title' => 'Open Tasks',
                        'entity' => 'task',
                        'aggregate' => 'count',
                        'filter' => ['status' => ['!=', 'done']],
                        'icon' => 'check-square',
                        'color' => 'warning',
                        'position' => ['x' => 3, 'y' => 0, 'w' => 3, 'h' => 1],
                    ],
                    [
                        'name' => 'tasks_chart',
                        'type' => 'chart',
                        'title' => 'Tasks by Status',
                        'entity' => 'task',
                        'chart_type' => 'pie',
                        'group_by' => 'status',
                        'aggregate' => 'count',
                        'position' => ['x' => 0, 'y' => 1, 'w' => 6, 'h' => 2],
                    ],
                    [
                        'name' => 'recent_activity',
                        'type' => 'list',
                        'title' => 'Recent Activity',
                        'source' => 'activity_log',
                        'limit' => 10,
                        'position' => ['x' => 6, 'y' => 0, 'w' => 6, 'h' => 3],
                    ],
                ],
                'layout' => [
                    'columns' => 12,
                    'row_height' => 100,
                ],
                'config' => [
                    'refresh_interval' => 300,
                    'draggable' => false,
                    'resizable' => false,
                ],
            ],
            'config' => ['refresh_interval' => 300],
            'is_active' => true,
        ]);
    }
}
