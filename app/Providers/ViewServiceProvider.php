<?php

namespace App\Providers;

use App\Contracts\ViewRegistryContract;
use App\Contracts\ViewTypeRegistryContract;
use App\Services\View\ViewBuilder;
use App\Services\View\ViewCompiler;
use App\Services\View\ViewRegistry;
use App\Services\View\ViewTypeRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/view-system.php',
            'view-system'
        );

        // Register ViewCompiler as singleton
        $this->app->singleton(ViewCompiler::class, function ($app) {
            return new ViewCompiler();
        });

        // Register ViewTypeRegistry as singleton
        $this->app->singleton(ViewTypeRegistry::class, function ($app) {
            return new ViewTypeRegistry();
        });

        // Register ViewRegistry as singleton
        $this->app->singleton(ViewRegistry::class, function ($app) {
            return new ViewRegistry(
                $app->make(ViewTypeRegistry::class)
            );
        });

        // Register aliases
        $this->app->alias(ViewRegistry::class, 'view.registry');
        $this->app->alias(ViewCompiler::class, 'view.compiler');
        $this->app->alias(ViewTypeRegistry::class, 'view.types');

        // Bind contracts
        $this->app->bind(ViewRegistryContract::class, ViewRegistry::class);
        $this->app->bind(ViewTypeRegistryContract::class, ViewTypeRegistry::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/view-system.php' => config_path('view-system.php'),
        ], 'view-system-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'view-system-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/view-api.php');

        // Register Blade directives
        $this->registerBladeDirectives();

        // Register global helper functions
        $this->registerHelpers();

        // Ensure temp directory exists
        $this->ensureTempDirectory();

        // Fire ready hook
        do_action('view_system_ready');
    }

    /**
     * Register Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        $directiveName = config('view-system.blade.directive', 'dynamicView');

        // @dynamicView('view_name', ['key' => 'value'])
        Blade::directive($directiveName, function ($expression) {
            return "<?php echo app(\App\Services\View\ViewRegistry::class)->render({$expression}); ?>";
        });

        // @dynamicViewIf('view_name', ['key' => 'value'])
        // Renders if view exists, otherwise nothing
        Blade::directive($directiveName . 'If', function ($expression) {
            return "<?php 
                \$__viewArgs = [{$expression}];
                \$__viewName = \$__viewArgs[0] ?? '';
                \$__viewData = \$__viewArgs[1] ?? [];
                if (app(\App\Services\View\ViewRegistry::class)->exists(\$__viewName)) {
                    echo app(\App\Services\View\ViewRegistry::class)->render(\$__viewName, \$__viewData);
                }
            ?>";
        });

        // @compileView('view_name')
        // Just compiles without data (useful for debugging)
        Blade::directive('compileView', function ($expression) {
            return "<?php echo app(\App\Services\View\ViewRegistry::class)->compile({$expression}); ?>";
        });

        // @viewExtensionCount('view_name')
        // Outputs the number of extensions for a view
        Blade::directive('viewExtensionCount', function ($expression) {
            return "<?php echo app(\App\Services\View\ViewRegistry::class)->getExtensionCount({$expression}); ?>";
        });
    }

    /**
     * Register global helper functions
     */
    protected function registerHelpers(): void
    {
        // Only register if functions don't exist
        if (!function_exists('register_view')) {
            require_once __DIR__ . '/../../helpers/view-helpers.php';
        }
    }

    /**
     * Ensure temp directory exists for compiled Blade views
     */
    protected function ensureTempDirectory(): void
    {
        $tempPath = config('view-system.blade.temp_path', storage_path('framework/views/dynamic'));
        
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }
    }
}
