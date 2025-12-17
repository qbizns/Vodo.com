<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ConfigVersion\ConfigVersionService;
use App\Services\ConfigVersion\ConfigVersionBuilder;
use Illuminate\Support\ServiceProvider;

/**
 * Config Version Service Provider.
 * 
 * Registers configuration version control services.
 */
class ConfigVersionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register ConfigVersionService as singleton
        $this->app->singleton(ConfigVersionService::class, function ($app) {
            $service = new ConfigVersionService();
            
            // Set tenant from auth if available
            if ($user = $app['auth']->user()) {
                $service->setTenant($user->tenant_id ?? null);
            }

            return $service;
        });

        // Register alias for facade-like access
        $this->app->alias(ConfigVersionService::class, 'config.version');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/config_version.php' => config_path('config_version.php'),
        ], 'config-version');

        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/config_version.php',
            'config_version'
        );
    }
}
