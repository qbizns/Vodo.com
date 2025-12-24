<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ActionRegistryContract;
use App\Contracts\WidgetRegistryContract;
use App\Contracts\WorkflowRegistryContract;
use App\Services\Action\ActionRegistry;
use App\Services\Widget\WidgetRegistry;
use App\Services\Workflow\WorkflowRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Foundation Service Provider
 *
 * Registers core enterprise services:
 * - Action Registry
 * - Widget Registry
 * - Workflow Registry
 */
class FoundationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Action Registry
        $this->app->singleton(ActionRegistry::class, fn() => new ActionRegistry());
        $this->app->alias(ActionRegistry::class, 'action.registry');
        $this->app->bind(ActionRegistryContract::class, ActionRegistry::class);

        // Register Widget Registry
        $this->app->singleton(WidgetRegistry::class, fn() => new WidgetRegistry());
        $this->app->alias(WidgetRegistry::class, 'widget.registry');
        $this->app->bind(WidgetRegistryContract::class, WidgetRegistry::class);

        // Register Workflow Registry
        $this->app->singleton(WorkflowRegistry::class, fn() => new WorkflowRegistry());
        $this->app->alias(WorkflowRegistry::class, 'workflow.registry');
        $this->app->bind(WorkflowRegistryContract::class, WorkflowRegistry::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load helper functions
        $this->loadHelpers();

        // Fire ready hook
        do_action('foundation_ready');
    }

    /**
     * Load helper functions.
     */
    protected function loadHelpers(): void
    {
        if (!function_exists('register_action')) {
            require_once __DIR__ . '/../../helpers/foundation-helpers.php';
        }
    }
}
