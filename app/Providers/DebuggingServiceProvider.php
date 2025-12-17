<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Debugging\TracingService;
use App\Services\Debugging\DebugManager;
use App\Services\Debugging\WorkflowTracer;
use App\Services\Debugging\ExplainService;
use Illuminate\Support\ServiceProvider;

/**
 * Debugging Service Provider.
 * 
 * Registers all debugging and tracing services.
 */
class DebuggingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register TracingService as singleton
        $this->app->singleton(TracingService::class, function ($app) {
            $tracer = new TracingService();
            
            // Set tenant from auth if available
            if ($user = $app['auth']->user()) {
                $tracer->setTenant($user->tenant_id ?? null);
            }

            return $tracer;
        });

        // Register DebugManager
        $this->app->singleton(DebugManager::class, function ($app) {
            return new DebugManager($app->make(TracingService::class));
        });

        // Register WorkflowTracer
        $this->app->singleton(WorkflowTracer::class, function ($app) {
            return new WorkflowTracer($app->make(TracingService::class));
        });

        // Register ExplainService
        $this->app->singleton(ExplainService::class, function ($app) {
            return new ExplainService();
        });

        // Register Debug facade alias
        $this->app->alias(DebugManager::class, 'debug');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register middleware alias
        $this->app['router']->aliasMiddleware('debug', \App\Http\Middleware\DebugMiddleware::class);

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/debugging.php' => config_path('debugging.php'),
        ], 'debugging-config');

        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/debugging.php',
            'debugging'
        );
    }
}
