<?php

namespace Ums\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Ums\Services\UserService;
use Ums\Listeners\LogUserActivity;

/**
 * UMS Service Provider
 */
class UmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/ums.php',
            'ums'
        );

        // Register services
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'ums');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'ums');

        // Register event listeners
        $this->registerEventListeners();

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/ums.php' => config_path('ums.php'),
        ], 'ums-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/ums'),
        ], 'ums-views');
    }

    /**
     * Register event listeners.
     */
    protected function registerEventListeners(): void
    {
        // Listen for user events
        Event::listen('Illuminate\Auth\Events\Login', [LogUserActivity::class, 'handleLogin']);
        Event::listen('Illuminate\Auth\Events\Logout', [LogUserActivity::class, 'handleLogout']);
        Event::listen('Illuminate\Auth\Events\Failed', [LogUserActivity::class, 'handleFailed']);
    }
}

