<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Services\Marketplace\MarketplaceClient;
use App\Services\Marketplace\PluginManager;
use App\Services\Marketplace\LicenseManager;
use App\Services\Marketplace\UpdateManager;
use App\Console\Commands\PluginListCommand;
use App\Console\Commands\PluginActivateCommand;
use App\Console\Commands\PluginDeactivateCommand;
use App\Console\Commands\PluginInstallCommand;
use App\Console\Commands\PluginUninstallCommand;
use App\Console\Commands\PluginUpdateCommand;
use App\Console\Commands\LicenseActivateCommand;
use App\Console\Commands\LicenseVerifyCommand;
use App\Console\Commands\MarketplaceSyncCommand;

class MarketplaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/marketplace.php', 'marketplace');

        // Register services
        $this->app->singleton(MarketplaceClient::class);
        
        $this->app->singleton(LicenseManager::class, function ($app) {
            return new LicenseManager($app->make(MarketplaceClient::class));
        });

        $this->app->singleton(UpdateManager::class, function ($app) {
            return new UpdateManager(
                $app->make(MarketplaceClient::class),
                $app->make(LicenseManager::class)
            );
        });

        $this->app->singleton(PluginManager::class, function ($app) {
            return new PluginManager(
                $app->make(MarketplaceClient::class),
                $app->make(LicenseManager::class),
                $app->make(UpdateManager::class)
            );
        });

        // Aliases
        $this->app->alias(MarketplaceClient::class, 'marketplace');
        $this->app->alias(PluginManager::class, 'plugins');
        $this->app->alias(LicenseManager::class, 'licenses');
        $this->app->alias(UpdateManager::class, 'plugin.updates');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/marketplace.php' => config_path('marketplace.php'),
        ], 'marketplace-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'marketplace-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/marketplace-api.php');

        require_once __DIR__ . '/../../helpers/marketplace-helpers.php';

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                PluginListCommand::class,
                PluginActivateCommand::class,
                PluginDeactivateCommand::class,
                PluginInstallCommand::class,
                PluginUninstallCommand::class,
                PluginUpdateCommand::class,
                LicenseActivateCommand::class,
                LicenseVerifyCommand::class,
                MarketplaceSyncCommand::class,
            ]);
        }

        // Schedule update checks
        if (config('marketplace.auto_update') || config('marketplace.auto_update_security')) {
            $this->scheduleUpdateChecks();
        }

        // Schedule license verification
        $this->scheduleLicenseVerification();

        // Boot installed plugins
        $this->bootInstalledPlugins();

        if (function_exists('do_action')) {
            do_action('marketplace_ready');
        }
    }

    protected function scheduleUpdateChecks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->call(function () {
                $updateManager = app(UpdateManager::class);
                $updateManager->checkAll();

                if (config('marketplace.auto_update_security')) {
                    $updateManager->updateSecurity();
                }

                if (config('marketplace.auto_update')) {
                    $updateManager->updateAll();
                }
            })->daily();
        });
    }

    protected function scheduleLicenseVerification(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->call(function () {
                app(LicenseManager::class)->verifyAll();
            })->daily();
        });
    }

    protected function bootInstalledPlugins(): void
    {
        try {
            $plugins = \App\Models\InstalledPlugin::active()->get();

            foreach ($plugins as $plugin) {
                $this->bootPlugin($plugin);
            }
        } catch (\Exception $e) {
            // Database might not be ready yet
            \Log::warning('Could not boot plugins: ' . $e->getMessage());
        }
    }

    protected function bootPlugin(\App\Models\InstalledPlugin $plugin): void
    {
        try {
            $instance = $plugin->getInstance();

            if ($instance && method_exists($instance, 'boot')) {
                $instance->boot();
            }

            if (function_exists('do_action')) {
                do_action('plugin_booted', $plugin);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to boot plugin {$plugin->slug}: " . $e->getMessage());
            $plugin->markError($e->getMessage());
        }
    }
}
