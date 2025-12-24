<?php

/**
 * Foundation Helper Functions
 *
 * Helper functions for Action Registry, Widget Registry, and Workflow Registry.
 */

use App\Contracts\ActionContract;
use App\Contracts\WidgetContract;
use App\Contracts\WorkflowContract;
use App\Services\Action\ActionRegistry;
use App\Services\Widget\WidgetRegistry;
use App\Services\Workflow\WorkflowRegistry;
use Illuminate\Database\Eloquent\Model;

// =========================================================================
// Action Registry Helpers
// =========================================================================

if (!function_exists('register_action')) {
    /**
     * Register an action.
     *
     * @param ActionContract|string $action Action instance or name
     * @param array $config Configuration (if name provided)
     * @param string|null $pluginSlug Plugin slug
     * @return ActionRegistry
     */
    function register_action(ActionContract|string $action, array $config = [], ?string $pluginSlug = null): ActionRegistry
    {
        $registry = app(ActionRegistry::class);

        if ($action instanceof ActionContract) {
            return $registry->register($action, $pluginSlug);
        }

        return $registry->registerFromArray($action, $config, $pluginSlug);
    }
}

if (!function_exists('get_action')) {
    /**
     * Get an action by name.
     *
     * @param string $name Action name
     * @return ActionContract|null
     */
    function get_action(string $name): ?ActionContract
    {
        return app(ActionRegistry::class)->get($name);
    }
}

if (!function_exists('execute_action')) {
    /**
     * Execute an action.
     *
     * @param string $name Action name
     * @param array $context Execution context
     * @return mixed
     */
    function execute_action(string $name, array $context = []): mixed
    {
        return app(ActionRegistry::class)->execute($name, $context);
    }
}

if (!function_exists('get_actions_for_entity')) {
    /**
     * Get actions available for an entity.
     *
     * @param string $entityName Entity name
     * @return \Illuminate\Support\Collection
     */
    function get_actions_for_entity(string $entityName): \Illuminate\Support\Collection
    {
        return app(ActionRegistry::class)->getForEntity($entityName);
    }
}

// =========================================================================
// Widget Registry Helpers
// =========================================================================

if (!function_exists('register_widget')) {
    /**
     * Register a widget.
     *
     * @param WidgetContract $widget Widget instance
     * @param string|null $pluginSlug Plugin slug
     * @return WidgetRegistry
     */
    function register_widget(WidgetContract $widget, ?string $pluginSlug = null): WidgetRegistry
    {
        return app(WidgetRegistry::class)->register($widget, $pluginSlug);
    }
}

if (!function_exists('get_widget')) {
    /**
     * Get a widget by name.
     *
     * @param string $name Widget name
     * @return WidgetContract|null
     */
    function get_widget(string $name): ?WidgetContract
    {
        return app(WidgetRegistry::class)->get($name);
    }
}

if (!function_exists('get_widgets_for_type')) {
    /**
     * Get widgets that support a field type.
     *
     * @param string $fieldType Field type
     * @return \Illuminate\Support\Collection
     */
    function get_widgets_for_type(string $fieldType): \Illuminate\Support\Collection
    {
        return app(WidgetRegistry::class)->getForType($fieldType);
    }
}

if (!function_exists('format_widget_value')) {
    /**
     * Format a value using a widget.
     *
     * @param string $widgetName Widget name
     * @param mixed $value Value to format
     * @param array $options Widget options
     * @return string
     */
    function format_widget_value(string $widgetName, mixed $value, array $options = []): string
    {
        return app(WidgetRegistry::class)->format($widgetName, $value, $options);
    }
}

if (!function_exists('parse_widget_value')) {
    /**
     * Parse a value using a widget.
     *
     * @param string $widgetName Widget name
     * @param mixed $value Value to parse
     * @param array $options Widget options
     * @return mixed
     */
    function parse_widget_value(string $widgetName, mixed $value, array $options = []): mixed
    {
        return app(WidgetRegistry::class)->parse($widgetName, $value, $options);
    }
}

// =========================================================================
// Workflow Registry Helpers
// =========================================================================

if (!function_exists('register_workflow')) {
    /**
     * Register a workflow.
     *
     * @param WorkflowContract|string $workflow Workflow instance or name
     * @param array $config Configuration (if name provided)
     * @param string|null $pluginSlug Plugin slug
     * @return WorkflowRegistry
     */
    function register_workflow(WorkflowContract|string $workflow, array $config = [], ?string $pluginSlug = null): WorkflowRegistry
    {
        $registry = app(WorkflowRegistry::class);

        if ($workflow instanceof WorkflowContract) {
            return $registry->register($workflow, $pluginSlug);
        }

        return $registry->registerFromArray($workflow, $config, $pluginSlug);
    }
}

if (!function_exists('get_workflow')) {
    /**
     * Get a workflow by name.
     *
     * @param string $name Workflow name
     * @return WorkflowContract|null
     */
    function get_workflow(string $name): ?WorkflowContract
    {
        return app(WorkflowRegistry::class)->get($name);
    }
}

if (!function_exists('get_workflow_for_entity')) {
    /**
     * Get the workflow for an entity.
     *
     * @param string $entityName Entity name
     * @return WorkflowContract|null
     */
    function get_workflow_for_entity(string $entityName): ?WorkflowContract
    {
        return app(WorkflowRegistry::class)->getForEntity($entityName);
    }
}

if (!function_exists('workflow_transition')) {
    /**
     * Apply a workflow transition to a model.
     *
     * @param Model $model Model instance
     * @param string $transition Transition name
     * @param array $context Additional context
     * @return bool
     */
    function workflow_transition(Model $model, string $transition, array $context = []): bool
    {
        return app(WorkflowRegistry::class)->apply($model, $transition, $context);
    }
}

if (!function_exists('workflow_can_transition')) {
    /**
     * Check if a workflow transition is available.
     *
     * @param Model $model Model instance
     * @param string $transition Transition name
     * @return bool
     */
    function workflow_can_transition(Model $model, string $transition): bool
    {
        $transitions = app(WorkflowRegistry::class)->getAvailableTransitions($model);

        return isset($transitions[$transition]);
    }
}

if (!function_exists('get_available_transitions')) {
    /**
     * Get available transitions for a model.
     *
     * @param Model $model Model instance
     * @return array
     */
    function get_available_transitions(Model $model): array
    {
        return app(WorkflowRegistry::class)->getAvailableTransitions($model);
    }
}

if (!function_exists('get_workflow_state')) {
    /**
     * Get the current workflow state of a model.
     *
     * @param Model $model Model instance
     * @return string|null
     */
    function get_workflow_state(Model $model): ?string
    {
        return app(WorkflowRegistry::class)->getCurrentState($model);
    }
}

if (!function_exists('get_workflow_history')) {
    /**
     * Get workflow history for a model.
     *
     * @param Model $model Model instance
     * @return \Illuminate\Support\Collection
     */
    function get_workflow_history(Model $model): \Illuminate\Support\Collection
    {
        return app(WorkflowRegistry::class)->getHistory($model);
    }
}

if (!function_exists('initialize_workflow_state')) {
    /**
     * Initialize workflow state for a new model.
     *
     * @param Model $model Model instance
     * @return bool
     */
    function initialize_workflow_state(Model $model): bool
    {
        return app(WorkflowRegistry::class)->initializeState($model);
    }
}
