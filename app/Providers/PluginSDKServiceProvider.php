<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\PluginSDK\PluginGenerator;
use App\Services\PluginSDK\EntityGenerator;
use App\Services\PluginSDK\PluginTester;
use App\Services\PluginSDK\PluginAnalyzer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;

/**
 * Plugin SDK Service Provider.
 * 
 * Registers plugin development tools.
 */
class PluginSDKServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register PluginGenerator
        $this->app->singleton(PluginGenerator::class, function ($app) {
            return new PluginGenerator($app->make(Filesystem::class));
        });

        // Register EntityGenerator
        $this->app->singleton(EntityGenerator::class, function ($app) {
            return new EntityGenerator($app->make(Filesystem::class));
        });

        // Register PluginTester
        $this->app->singleton(PluginTester::class, function ($app) {
            return new PluginTester($app->make(Filesystem::class));
        });

        // Register PluginAnalyzer
        $this->app->singleton(PluginAnalyzer::class, function ($app) {
            return new PluginAnalyzer($app->make(Filesystem::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\PluginMakeCommand::class,
                \App\Console\Commands\PluginAddEntityCommand::class,
                \App\Console\Commands\PluginTestCommand::class,
                \App\Console\Commands\PluginAnalyzeCommand::class,
                \App\Console\Commands\ConfigVersionCommand::class,
            ]);
        }

        // Ensure plugins directory exists
        $pluginsPath = base_path('plugins');
        if (!is_dir($pluginsPath)) {
            mkdir($pluginsPath, 0755, true);
        }
    }
}
