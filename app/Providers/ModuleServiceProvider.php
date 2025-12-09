<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadModules();
    }

    /**
     * Load all modules.
     */
    protected function loadModules(): void
    {
        $modules = config('modules.modules', []);
        $baseDomain = config('modules.domain', 'vodo.com');

        foreach ($modules as $module => $config) {
            $modulePath = app_path("Modules/{$module}");

            if (!is_dir($modulePath)) {
                continue;
            }

            // Load views
            $viewsPath = "{$modulePath}/Views";
            if (is_dir($viewsPath)) {
                $this->loadViewsFrom($viewsPath, strtolower($module));
            }

            // Load routes
            $routesFile = "{$modulePath}/routes.php";
            if (file_exists($routesFile)) {
                $this->loadModuleRoutes($routesFile, $module, $config, $baseDomain);
            }
        }
    }

    /**
     * Load routes for a module with subdomain configuration.
     */
    protected function loadModuleRoutes(string $routesFile, string $module, array $config, string $baseDomain): void
    {
        $subdomain = $config['subdomain'] ?? null;

        $routeGroup = Route::middleware('web');

        if ($subdomain) {
            // Subdomain routing (e.g., console.vodo.com)
            $routeGroup->domain("{$subdomain}.{$baseDomain}");
        } else {
            // Main domain routing (vodo.com)
            $routeGroup->domain($baseDomain);
        }

        $routeGroup->group($routesFile);
    }
}

