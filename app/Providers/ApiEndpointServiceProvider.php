<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Api\ApiRegistry;
use App\Http\Middleware\ApiKeyAuth;
use App\Http\Middleware\ApiRequestLogger;

class ApiEndpointServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/api-endpoints.php',
            'api-endpoints'
        );

        // Register singleton
        $this->app->singleton(ApiRegistry::class, function ($app) {
            return new ApiRegistry();
        });

        // Alias for easier access
        $this->app->alias(ApiRegistry::class, 'api-registry');
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/api-endpoints.php' => config_path('api-endpoints.php'),
        ], 'api-endpoints-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'api-endpoints-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api-endpoints.php');

        // Load helpers
        require_once __DIR__ . '/../../helpers/api-helpers.php';

        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('api.key', ApiKeyAuth::class);
        $router->aliasMiddleware('api.log', ApiRequestLogger::class);

        // Add global API middleware if configured
        if (config('api-endpoints.global_logging', true)) {
            $router->pushMiddlewareToGroup('api', ApiRequestLogger::class);
        }

        // Register plugin routes after all providers have booted
        $this->app->booted(function () {
            $this->registerPluginRoutes();
        });

        // Fire ready hook
        if (function_exists('do_action')) {
            do_action('api_endpoints_ready');
        }
    }

    /**
     * Register plugin-defined routes
     */
    protected function registerPluginRoutes(): void
    {
        if (config('api-endpoints.auto_register_routes', true)) {
            try {
                $registry = $this->app->make(ApiRegistry::class);
                $registry->registerRoutes();
            } catch (\Exception $e) {
                \Log::warning('Failed to register plugin API routes: ' . $e->getMessage());
            }
        }
    }
}
