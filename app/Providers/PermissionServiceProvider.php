<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use App\Services\Permission\PermissionRegistry;
use App\Services\Plugins\HookManager;
use App\Services\Plugins\Contracts\PluginInterface;
use App\Models\Permission;
use App\Models\Plugin;
use App\Models\Role;

class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/permissions.php', 'permissions');

        $this->app->singleton(PermissionRegistry::class, fn($app) => new PermissionRegistry());
        $this->app->alias(PermissionRegistry::class, 'permission');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/permissions.php' => config_path('permissions.php'),
        ], 'permissions-config');

        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'permissions-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/permission-api.php');

        require_once __DIR__ . '/../../helpers/permission-helpers.php';

        // Register middleware
        $this->registerMiddleware();

        // Register Blade directives
        if (config('permissions.blade_directives', true)) {
            $this->registerBladeDirectives();
        }

        // Register Gate permissions
        if (config('permissions.register_gate', true)) {
            $this->registerGatePermissions();
        }

        // Create default roles/permissions
        if (config('permissions.create_defaults', true)) {
            $this->app->booted(fn() => $this->createDefaults());
        }

        // Register plugin lifecycle hooks for permissions
        $this->registerPluginHooks();

        if (function_exists('do_action')) {
            do_action('permissions_ready');
        }
    }

    /**
     * Register plugin lifecycle hooks for permission management.
     */
    protected function registerPluginHooks(): void
    {
        // Only register if HookManager is available
        if (!$this->app->bound(HookManager::class)) {
            return;
        }

        $hooks = $this->app->make(HookManager::class);
        $registry = $this->app->make(PermissionRegistry::class);

        // Listen for plugin activation - register permissions
        $hooks->addAction(HookManager::HOOK_PLUGIN_ACTIVATED, function (Plugin $plugin, PluginInterface $instance) use ($registry) {
            $registry->registerPluginPermissions($plugin, $instance);
        });

        // Listen for plugin deactivation - hide permissions
        $hooks->addAction(HookManager::HOOK_PLUGIN_DEACTIVATED, function (Plugin $plugin, PluginInterface $instance) use ($registry) {
            $registry->onPluginDisabled($plugin->slug);
        });

        // Listen for plugin uninstall - remove permissions
        $hooks->addAction(HookManager::HOOK_PLUGIN_UNINSTALLED, function (Plugin $plugin) use ($registry) {
            $registry->onPluginUninstalled($plugin->slug);
        });
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        
        $router->aliasMiddleware('permission', \App\Http\Middleware\CheckPermission::class);
        $router->aliasMiddleware('permissions', \App\Http\Middleware\CheckAllPermissions::class);
        $router->aliasMiddleware('role', \App\Http\Middleware\CheckRole::class);
        $router->aliasMiddleware('role_level', \App\Http\Middleware\CheckRoleLevel::class);
    }

    protected function registerBladeDirectives(): void
    {
        // @can('permission.slug')
        // Already provided by Laravel

        // @role('admin')
        Blade::directive('role', function ($role) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>";
        });
        Blade::directive('endrole', fn() => "<?php endif; ?>");

        // @hasrole('admin', 'editor')
        Blade::directive('hasrole', function ($roles) {
            return "<?php if(auth()->check() && auth()->user()->hasAnyRole([{$roles}])): ?>";
        });
        Blade::directive('endhasrole', fn() => "<?php endif; ?>");

        // @permission('posts.create')
        Blade::directive('permission', function ($permission) {
            return "<?php if(auth()->check() && auth()->user()->hasPermission({$permission})): ?>";
        });
        Blade::directive('endpermission', fn() => "<?php endif; ?>");

        // @anypermission('posts.create', 'posts.edit')
        Blade::directive('anypermission', function ($permissions) {
            return "<?php if(auth()->check() && auth()->user()->hasAnyPermission([{$permissions}])): ?>";
        });
        Blade::directive('endanypermission', fn() => "<?php endif; ?>");
    }

    protected function registerGatePermissions(): void
    {
        Gate::before(function ($user, $ability) {
            // Super admin bypasses all checks
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            // Check if this is a registered permission
            if (method_exists($user, 'hasPermission')) {
                if (Permission::where('slug', $ability)->exists()) {
                    return $user->hasPermission($ability) ?: null;
                }
            }

            return null; // Let other gates handle it
        });
    }

    protected function createDefaults(): void
    {
        try {
            $registry = $this->app->make(PermissionRegistry::class);
            
            // Check if already initialized
            if (Role::where('slug', Role::ROLE_SUPER_ADMIN)->exists()) {
                return;
            }

            // Create default roles
            $defaults = config('permissions.default_roles', []);
            foreach ($defaults as $roleConfig) {
                $registry->registerRole(array_merge($roleConfig, ['system' => true]), 'system');
            }

            // Create default permissions
            $defaultPerms = config('permissions.default_permissions', []);
            foreach ($defaultPerms as $permConfig) {
                $registry->registerPermission(array_merge($permConfig, ['system' => true]), 'system');
            }

        } catch (\Exception $e) {
            \Log::warning('Failed to create default permissions: ' . $e->getMessage());
        }
    }
}
