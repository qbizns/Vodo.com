<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Field\FieldTypeRegistry;

class FieldTypeServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/field-types.php',
            'field-types'
        );

        // Register singleton
        $this->app->singleton(FieldTypeRegistry::class, function ($app) {
            return new FieldTypeRegistry();
        });

        // Alias for easier access
        $this->app->alias(FieldTypeRegistry::class, 'field-types');
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/field-types.php' => config_path('field-types.php'),
        ], 'field-types-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'field-types-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/field-type-api.php');

        // Load helpers
        require_once __DIR__ . '/../../helpers/field-type-helpers.php';

        // Register built-in types on boot if auto-register is enabled
        if (config('field-types.auto_register_builtin', true)) {
            $this->app->booted(function () {
                $this->registerBuiltInTypes();
            });
        }

        // Fire ready hook
        do_action('field_type_system_ready');
    }

    /**
     * Register built-in field types
     */
    protected function registerBuiltInTypes(): void
    {
        try {
            $registry = $this->app->make(FieldTypeRegistry::class);
            $registry->registerBuiltInTypes();
        } catch (\Exception $e) {
            // Log but don't fail boot
            \Log::warning('Failed to register built-in field types: ' . $e->getMessage());
        }
    }
}
