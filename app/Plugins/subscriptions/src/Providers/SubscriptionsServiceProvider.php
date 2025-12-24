<?php

namespace Subscriptions\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Subscriptions\Services\SubscriptionService;

/**
 * Subscriptions Service Provider
 */
class SubscriptionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/subscriptions.php',
            'subscriptions'
        );

        // Register services
        $this->app->singleton(SubscriptionService::class, function ($app) {
            return new SubscriptionService();
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
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'subscriptions');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'subscriptions');

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/subscriptions.php' => config_path('subscriptions.php'),
        ], 'subscriptions-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/subscriptions'),
        ], 'subscriptions-views');

        // Add subscriptions relationship to User model
        $this->extendUserModel();
    }

    /**
     * Extend the User model with subscriptions relationship.
     */
    protected function extendUserModel(): void
    {
        // This can be done via a trait or macro
        // For now, we'll rely on the User model having the relationship defined
    }
}

