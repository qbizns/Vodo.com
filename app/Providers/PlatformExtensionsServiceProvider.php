<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Plugin\ContractRegistry;
use App\Services\Storage\PluginStorage;
use App\Services\Theme\ThemeRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Platform Extensions Service Provider
 *
 * Registers platform extension services:
 * - ThemeRegistry: Theme management for storefronts
 * - PluginStorage: Scoped file storage for plugins
 * - ContractRegistry: Inter-plugin contract management
 */
class PlatformExtensionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Theme Registry - singleton for theme management
        $this->app->singleton(ThemeRegistry::class, function ($app) {
            $registry = new ThemeRegistry();

            // Register default slots from config
            $slots = config('themes.slots', []);
            foreach ($slots as $name => $config) {
                $registry->registerSlot($name, $config);
            }

            return $registry;
        });

        // Plugin Storage - singleton for scoped storage
        $this->app->singleton(PluginStorage::class, function ($app) {
            return new PluginStorage();
        });

        // Contract Registry - singleton for inter-plugin contracts
        $this->app->singleton(ContractRegistry::class, function ($app) {
            return new ContractRegistry();
        });

        // Alias for convenience
        $this->app->alias(ThemeRegistry::class, 'themes');
        $this->app->alias(PluginStorage::class, 'plugin.storage');
        $this->app->alias(ContractRegistry::class, 'contracts');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Blade directives for themes
        $this->registerBladeDirectives();
    }

    /**
     * Register Blade directives for theme system.
     */
    protected function registerBladeDirectives(): void
    {
        $blade = $this->app['blade.compiler'];

        // @themeSlot('slot-name', ['data' => 'value'])
        $blade->directive('themeSlot', function ($expression) {
            return "<?php echo app(\App\Services\Theme\ThemeRegistry::class)->renderSlot({$expression}); ?>";
        });

        // @themeAsset('path/to/asset.css')
        $blade->directive('themeAsset', function ($expression) {
            return "<?php echo app(\App\Services\Theme\ThemeRegistry::class)->assetUrl(app(\App\Services\Theme\ThemeRegistry::class)->getActive()['slug'] ?? '', {$expression}); ?>";
        });

        // @themeSetting('setting_key', 'default')
        $blade->directive('themeSetting', function ($expression) {
            $parts = explode(',', $expression, 2);
            $key = trim($parts[0]);
            $default = isset($parts[1]) ? trim($parts[1]) : "''";

            return "<?php
                \$__themeSettings = app(\App\Services\Theme\ThemeRegistry::class)->getSettings(
                    app(\App\Services\Theme\ThemeRegistry::class)->getActive()['slug'] ?? ''
                );
                echo \$__themeSettings[{$key}] ?? {$default};
            ?>";
        });

        // @extends theme layout: @themeLayout('main')
        $blade->directive('themeLayout', function ($expression) {
            return "<?php
                \$__theme = app(\App\Services\Theme\ThemeRegistry::class)->getActive();
                \$__layoutName = {$expression};
                \$__layout = 'theme-' . (\$__theme['slug'] ?? 'default') . '::layouts.' . \$__layoutName;
            ?>
            @extends(\$__layout)";
        });
    }
}
