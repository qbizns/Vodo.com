<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PluginBus\PluginBus;
use App\Services\Workflow\WorkflowEngine;
use App\Services\View\ViewRegistry;
use App\Services\ComputedField\ComputedFieldManager;
use App\Services\Tenant\TenantManager;
use App\Services\DocumentTemplate\DocumentTemplateEngine;
use App\Services\Activity\ActivityManager;
use App\Services\RecordRule\RecordRuleEngine;
use App\Contracts\PluginBusContract;

/**
 * Platform Services Provider.
 * 
 * Registers all Odoo-like platform services:
 * - Plugin Bus (inter-plugin communication)
 * - Workflow Engine (state machines)
 * - View Registry (declarative views)
 * - Computed Field Manager
 * - Tenant Manager (multi-tenancy)
 * - Document Template Engine
 * - Activity Manager (chatter/activities)
 * - Record Rule Engine (row-level security)
 */
class PlatformServicesProvider extends ServiceProvider
{
    /**
     * All of the container singletons that should be registered.
     */
    public array $singletons = [
        PluginBusContract::class => PluginBus::class,
        PluginBus::class => PluginBus::class,
        WorkflowEngine::class => WorkflowEngine::class,
        ViewRegistry::class => ViewRegistry::class,
        ComputedFieldManager::class => ComputedFieldManager::class,
        TenantManager::class => TenantManager::class,
        DocumentTemplateEngine::class => DocumentTemplateEngine::class,
        ActivityManager::class => ActivityManager::class,
        RecordRuleEngine::class => RecordRuleEngine::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/../../config/platform.php', 'platform');
        $this->mergeConfigFrom(__DIR__ . '/../../config/workflow.php', 'workflow');
        $this->mergeConfigFrom(__DIR__ . '/../../config/tenant.php', 'tenant');
        $this->mergeConfigFrom(__DIR__ . '/../../config/recordrules.php', 'recordrules');

        // Register PluginBus
        $this->app->singleton(PluginBus::class, function ($app) {
            return new PluginBus();
        });

        // Register WorkflowEngine with PluginBus dependency
        $this->app->singleton(WorkflowEngine::class, function ($app) {
            return new WorkflowEngine($app->make(PluginBus::class));
        });

        // Register ViewRegistry
        $this->app->singleton(ViewRegistry::class, function ($app) {
            return new ViewRegistry();
        });

        // Register ComputedFieldManager
        $this->app->singleton(ComputedFieldManager::class, function ($app) {
            return new ComputedFieldManager();
        });

        // Register TenantManager
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager();
        });

        // Register DocumentTemplateEngine
        $this->app->singleton(DocumentTemplateEngine::class, function ($app) {
            return new DocumentTemplateEngine();
        });

        // Register ActivityManager
        $this->app->singleton(ActivityManager::class, function ($app) {
            return new ActivityManager();
        });

        // Register RecordRuleEngine
        $this->app->singleton(RecordRuleEngine::class, function ($app) {
            return new RecordRuleEngine();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/platform.php' => config_path('platform.php'),
                __DIR__ . '/../../config/workflow.php' => config_path('workflow.php'),
                __DIR__ . '/../../config/tenant.php' => config_path('tenant.php'),
                __DIR__ . '/../../config/recordrules.php' => config_path('recordrules.php'),
            ], 'platform-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'platform-migrations');
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Register global middleware for tenant scoping
        if (config('platform.tenant.enabled', true)) {
            $this->registerTenantMiddleware();
        }

        // Register global middleware for record rules
        if (config('platform.record_rules.enabled', true)) {
            $this->registerRecordRuleMiddleware();
        }

        // Setup plugin bus event listeners
        $this->setupPluginBusListeners();

        // Register built-in workflow conditions and actions
        $this->registerWorkflowExtensions();

        // Register activity types
        $this->registerDefaultActivityTypes();
    }

    /**
     * Register tenant scoping middleware.
     */
    protected function registerTenantMiddleware(): void
    {
        // The actual middleware is registered in Http/Kernel.php
        // This method sets up any tenant-related configurations
        $manager = $this->app->make(TenantManager::class);
        
        // Configure tenant resolution from config
        // This allows plugins to customize tenant behavior
    }

    /**
     * Register record rule middleware.
     */
    protected function registerRecordRuleMiddleware(): void
    {
        // Similar to tenant middleware, actual registration in Kernel.php
    }

    /**
     * Setup plugin bus event listeners.
     */
    protected function setupPluginBusListeners(): void
    {
        $bus = $this->app->make(PluginBus::class);

        // Listen for plugin deactivation to clean up
        $bus->subscribe('plugin.deactivated', function ($event) use ($bus) {
            $pluginSlug = $event['payload']['plugin_slug'] ?? null;
            if ($pluginSlug) {
                $bus->removePlugin($pluginSlug);
                $this->app->make(ComputedFieldManager::class)->removePluginFields($pluginSlug);
            }
        });

        // Log all plugin bus events in debug mode
        if (config('platform.plugin_bus.logging', true)) {
            $bus->subscribe('*', function ($event) {
                \Log::debug('PluginBus Event', [
                    'event' => $event['event_id'],
                    'publisher' => $event['publisher'],
                ]);
            }, 999);
        }
    }

    /**
     * Register workflow extensions.
     */
    protected function registerWorkflowExtensions(): void
    {
        $workflow = $this->app->make(WorkflowEngine::class);

        // Register additional built-in conditions
        $workflow->registerCondition('is_admin', fn($record) => 
            auth()->check() && auth()->user()->hasRole('admin')
        );

        $workflow->registerCondition('is_owner', fn($record) => 
            auth()->check() && $record->user_id === auth()->id()
        );

        // Register additional built-in actions
        $workflow->registerAction('send_notification', function ($record, $instance, $data, $type = 'info') {
            // Implementation depends on notification system
            \Log::info("Workflow notification: {$type}", [
                'record' => get_class($record) . ':' . $record->getKey(),
                'state' => $instance->current_state,
            ]);
        });

        $workflow->registerAction('schedule_activity', function ($record, $instance, $data, $typeSlug, $daysFromNow = 1) {
            $this->app->make(ActivityManager::class)->schedule($record, $typeSlug, [
                'due_date' => now()->addDays($daysFromNow),
                'is_automated' => true,
            ]);
        });
    }

    /**
     * Register default activity types.
     */
    protected function registerDefaultActivityTypes(): void
    {
        // Activity types are inserted via migration
        // This method can be used to register additional types programmatically
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            PluginBus::class,
            PluginBusContract::class,
            WorkflowEngine::class,
            ViewRegistry::class,
            ComputedFieldManager::class,
            TenantManager::class,
            DocumentTemplateEngine::class,
            ActivityManager::class,
            RecordRuleEngine::class,
        ];
    }
}
