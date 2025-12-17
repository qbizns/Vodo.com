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
        // Entity helpers
        if (!function_exists('register_entity')) {
            function register_entity(string $name, array $config = [], ?string $pluginSlug = null) {
                return EntityRegistry::getInstance()->register($name, $config, $pluginSlug);
            }
        }

        if (!function_exists('get_entity')) {
            function get_entity(string $name) {
                return EntityRegistry::getInstance()->get($name);
            }
        }

        if (!function_exists('entity_exists')) {
            function entity_exists(string $name): bool {
                return EntityRegistry::getInstance()->exists($name);
            }
        }

        if (!function_exists('create_entity_record')) {
            function create_entity_record(string $entityName, array $data) {
                return EntityRegistry::getInstance()->createRecord($entityName, $data);
            }
        }

        if (!function_exists('query_entity')) {
            function query_entity(string $entityName) {
                return EntityRegistry::getInstance()->query($entityName);
            }
        }

        // Taxonomy helpers
        if (!function_exists('register_taxonomy')) {
            function register_taxonomy(string $name, $entityNames, array $config = [], ?string $pluginSlug = null) {
                return TaxonomyRegistry::getInstance()->register($name, $entityNames, $config, $pluginSlug);
            }
        }

        if (!function_exists('get_taxonomy')) {
            function get_taxonomy(string $name) {
                return TaxonomyRegistry::getInstance()->get($name);
            }
        }

        if (!function_exists('get_terms')) {
            function get_terms(string $taxonomyName) {
                return TaxonomyRegistry::getInstance()->getTerms($taxonomyName);
            }
        }

        if (!function_exists('create_term')) {
            function create_term(string $taxonomyName, array $data) {
                return TaxonomyRegistry::getInstance()->createTerm($taxonomyName, $data);
            }
        }
    }
}
