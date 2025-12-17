<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\WorkflowEngineContract;
use App\Contracts\RecordRuleEngineContract;
use App\Contracts\ComputedFieldManagerContract;
use App\Contracts\ViewRegistryContract;
use App\Services\Workflow\WorkflowEngine;
use App\Services\RecordRule\RecordRuleEngine;
use App\Services\ComputedField\ComputedFieldManager;
use App\Services\View\ViewRegistry;
use App\Services\Sequence\SequenceService;
use App\Services\Audit\AuditService;
use App\Services\ImportExport\ImportExportService;

/**
 * Enterprise Services Provider - Registers enterprise-level services.
 */
class EnterpriseServicesProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind contracts to implementations
        $this->app->singleton(WorkflowEngineContract::class, WorkflowEngine::class);
        $this->app->singleton(RecordRuleEngineContract::class, RecordRuleEngine::class);
        $this->app->singleton(ComputedFieldManagerContract::class, ComputedFieldManager::class);
        $this->app->singleton(ViewRegistryContract::class, ViewRegistry::class);

        // Register new Phase 2 services as singletons
        $this->app->singleton(SequenceService::class, function ($app) {
            $service = new SequenceService();
            $service->registerDefaults();
            return $service;
        });

        $this->app->singleton(AuditService::class, function ($app) {
            return new AuditService();
        });

        $this->app->singleton(ImportExportService::class, function ($app) {
            return new ImportExportService();
        });

        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/../../config/audit.php', 'audit');
        $this->mergeConfigFrom(__DIR__ . '/../../config/sequences.php', 'sequences');
        $this->mergeConfigFrom(__DIR__ . '/../../config/import-export.php', 'import-export');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Publish configuration files
        $this->publishes([
            __DIR__ . '/../../config/audit.php' => config_path('audit.php'),
            __DIR__ . '/../../config/sequences.php' => config_path('sequences.php'),
            __DIR__ . '/../../config/import-export.php' => config_path('import-export.php'),
        ], 'enterprise-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\AuditCleanup::class,
                \App\Console\Commands\SequenceReset::class,
                \App\Console\Commands\ImportData::class,
                \App\Console\Commands\ExportData::class,
            ]);
        }

        // Initialize sequence definitions from config
        $this->initializeSequences();

        // Initialize import/export mappings from config
        $this->initializeImportExportMappings();
    }

    /**
     * Initialize sequences from configuration.
     */
    protected function initializeSequences(): void
    {
        $sequences = config('sequences.definitions', []);
        $service = $this->app->make(SequenceService::class);

        foreach ($sequences as $name => $config) {
            $service->define($name, $config);
        }
    }

    /**
     * Initialize import/export mappings from configuration.
     */
    protected function initializeImportExportMappings(): void
    {
        $mappings = config('import-export.mappings', []);
        $service = $this->app->make(ImportExportService::class);

        foreach ($mappings as $name => $config) {
            $service->defineMapping($name, $config);
        }
    }
}
