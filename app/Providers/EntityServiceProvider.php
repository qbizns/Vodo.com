<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Entity\EntityRegistry;
use App\Services\Taxonomy\TaxonomyRegistry;

class EntityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/entity.php',
            'entity'
        );

        // Register EntityRegistry as singleton
        $this->app->singleton(EntityRegistry::class, function ($app) {
            return EntityRegistry::getInstance();
        });

        // Alias for easier access
        $this->app->alias(EntityRegistry::class, 'entity.registry');

        // Register TaxonomyRegistry as singleton
        $this->app->singleton(TaxonomyRegistry::class, function ($app) {
            return TaxonomyRegistry::getInstance();
        });

        // Alias for easier access
        $this->app->alias(TaxonomyRegistry::class, 'taxonomy.registry');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/entity.php' => config_path('entity.php'),
            ], 'entity-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../../database/migrations/' => database_path('migrations'),
            ], 'entity-migrations');
        }

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/entity-api.php');

        // Register global helper functions
        $this->registerHelpers();

        // Fire hook when entity system is ready
        if (function_exists('do_action')) {
            do_action('entity_system_ready');
        }
    }

    /**
     * Register global helper functions.
     */
    protected function registerHelpers(): void
    {
        // Helper functions are defined in a separate helpers file to avoid redeclaration
        require_once __DIR__ . '/../Helpers/entity_helpers.php';
    }
}
