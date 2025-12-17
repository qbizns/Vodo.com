<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\Menu\MenuRegistry;
use App\Services\Menu\MenuBuilder;

class MenuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/menus.php', 'menus');

        $this->app->singleton(MenuRegistry::class, fn($app) => new MenuRegistry());
        $this->app->singleton(MenuBuilder::class, fn($app) => new MenuBuilder($app->make(MenuRegistry::class)));

        $this->app->alias(MenuRegistry::class, 'menu');
        $this->app->alias(MenuBuilder::class, 'menu.builder');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/menus.php' => config_path('menus.php'),
        ], 'menus-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'menus-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/menu-api.php');

        require_once __DIR__ . '/../../helpers/menu-helpers.php';

        if (config('menus.blade_directives', true)) {
            $this->registerBladeDirectives();
        }

        if (config('menus.register_defaults', true)) {
            $this->app->booted(fn() => $this->registerDefaultMenus());
        }

        if (function_exists('do_action')) {
            do_action('menus_ready');
        }
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('menu', fn($e) => "<?php echo render_menu({$e}); ?>");
        Blade::directive('menuNavbar', fn($e) => "<?php echo render_menu_navbar({$e}); ?>");
        Blade::directive('menuSidebar', fn($e) => "<?php echo render_menu_sidebar({$e}); ?>");
        Blade::directive('breadcrumb', fn($e) => "<?php echo render_breadcrumb({$e}); ?>");
    }

    protected function registerDefaultMenus(): void
    {
        try {
            $registry = $this->app->make(MenuRegistry::class);
            foreach (config('menus.defaults', []) as $slug => $config) {
                $registry->menu($slug, $config);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to register default menus: ' . $e->getMessage());
        }
    }
}
