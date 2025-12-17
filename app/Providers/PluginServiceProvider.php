<?php

namespace App\Providers;

use App\Services\Plugins\HookManager;
use App\Services\Plugins\PluginInstaller;
use App\Services\Plugins\PluginLoader;
use App\Services\Plugins\PluginManager;
use App\Services\Plugins\PluginMigrator;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register HookManager as singleton
        $this->app->singleton(HookManager::class, function ($app) {
            return new HookManager();
        });

        // Register PluginMigrator
        $this->app->singleton(PluginMigrator::class, function ($app) {
            return new PluginMigrator();
        });

        // Register PluginInstaller
        $this->app->singleton(PluginInstaller::class, function ($app) {
            return new PluginInstaller();
        });

        // Register PluginManager
        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager(
                $app->make(PluginInstaller::class),
                $app->make(PluginMigrator::class),
                $app->make(HookManager::class)
            );
        });

        // Register PluginLoader
        $this->app->singleton(PluginLoader::class, function ($app) {
            return new PluginLoader(
                $app->make(PluginManager::class),
                $app->make(HookManager::class)
            );
        });

        // Register aliases for convenience
        $this->app->alias(HookManager::class, 'plugins.hooks');
        $this->app->alias(PluginManager::class, 'plugins.manager');
        $this->app->alias(PluginLoader::class, 'plugins.loader');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load active plugins
        $this->app->make(PluginLoader::class)->loadActivePlugins();

        // Merge plugin config
        $this->mergeConfigFrom(
            config_path('plugins.php'),
            'plugins'
        );
    }
}
