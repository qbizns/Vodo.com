<?php

namespace App\Providers;

use App\Http\ViewComposers\NavigationComposer;
use App\Services\NavigationService;
use App\Services\Plugins\HookManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register NavigationService as singleton
        $this->app->singleton(NavigationService::class, function ($app) {
            return new NavigationService(
                $app->make(HookManager::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the navigation composer for all backend layouts
        View::composer([
            'backend.layouts.app',
            'admin::layouts.app',
            'console::layouts.app',
            'owner::layouts.app',
        ], NavigationComposer::class);
    }
}
