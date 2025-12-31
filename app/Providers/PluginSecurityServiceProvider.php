<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Plugins\Security\PluginApiKeyManager;
use App\Services\Plugins\Security\PluginPermissionRegistry;
use App\Services\Plugins\Security\PluginSandbox;
use App\Services\Plugins\Security\ScopeValidator;
use Illuminate\Support\ServiceProvider;

/**
 * Plugin Security Service Provider
 *
 * Registers all plugin security services:
 * - PluginPermissionRegistry: Permission management
 * - ScopeValidator: Scope validation and enforcement
 * - PluginSandbox: Resource limiting and isolation
 * - PluginApiKeyManager: API key authentication
 */
class PluginSecurityServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register PluginPermissionRegistry as singleton
        $this->app->singleton(PluginPermissionRegistry::class, function ($app) {
            return new PluginPermissionRegistry();
        });

        // Register ScopeValidator as singleton
        $this->app->singleton(ScopeValidator::class, function ($app) {
            return new ScopeValidator(
                $app->make(PluginPermissionRegistry::class)
            );
        });

        // Register PluginSandbox as singleton
        $this->app->singleton(PluginSandbox::class, function ($app) {
            return new PluginSandbox();
        });

        // Register PluginApiKeyManager as singleton
        $this->app->singleton(PluginApiKeyManager::class, function ($app) {
            return new PluginApiKeyManager();
        });

        // Register aliases for convenience
        $this->app->alias(PluginPermissionRegistry::class, 'plugin.permissions');
        $this->app->alias(ScopeValidator::class, 'plugin.scopes');
        $this->app->alias(PluginSandbox::class, 'plugin.sandbox');
        $this->app->alias(PluginApiKeyManager::class, 'plugin.apikeys');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Merge sandbox configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/plugin-security.php',
            'plugin.sandbox'
        );

        // Register middleware alias
        $router = $this->app['router'];
        $router->aliasMiddleware('plugin.security', \App\Http\Middleware\PluginSecurityMiddleware::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            PluginPermissionRegistry::class,
            ScopeValidator::class,
            PluginSandbox::class,
            PluginApiKeyManager::class,
            'plugin.permissions',
            'plugin.scopes',
            'plugin.sandbox',
            'plugin.apikeys',
        ];
    }
}
