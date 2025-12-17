<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use App\Services\View\ViewExtensionRegistry;
use App\Services\View\ViewExtender;
use App\Services\View\SlotManager;

class ViewExtensionServiceProvider extends ServiceProvider
{
    /**
     * Views that should be processed for extensions.
     */
    protected array $processedViews = [];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/view-extensions.php',
            'view-extensions'
        );

        // Register ViewExtensionRegistry as singleton
        $this->app->singleton(ViewExtensionRegistry::class, function ($app) {
            return ViewExtensionRegistry::getInstance();
        });
        $this->app->alias(ViewExtensionRegistry::class, 'view.extensions');

        // Register ViewExtender as singleton
        $this->app->singleton(ViewExtender::class, function ($app) {
            return new ViewExtender($app->make(ViewExtensionRegistry::class));
        });
        $this->app->alias(ViewExtender::class, 'view.extender');

        // Register SlotManager as singleton
        $this->app->singleton(SlotManager::class, function ($app) {
            return SlotManager::getInstance();
        });
        $this->app->alias(SlotManager::class, 'view.slots');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/view-extensions.php' => config_path('view-extensions.php'),
            ], 'view-extensions-config');
        }

        // Register Blade directives
        $this->registerBladeDirectives();

        // Register view composers for extension processing
        $this->registerViewProcessing();

        // Register global helper functions
        $this->registerHelpers();

        // Fire hook when view extension system is ready
        if (function_exists('do_action')) {
            do_action('view_extensions_ready');
        }
    }

    /**
     * Register custom Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        /**
         * @extensionSlot('slot_name')
         * Renders content added to a named slot by plugins.
         */
        Blade::directive('extensionSlot', function ($expression) {
            return "<?php echo app('view.slots')->renderSlot({$expression}); ?>";
        });

        /**
         * @hasExtensionSlot('slot_name')
         * Check if a slot has content.
         */
        Blade::directive('hasExtensionSlot', function ($expression) {
            return "<?php if(app('view.slots')->hasSlot({$expression})): ?>";
        });

        Blade::directive('endHasExtensionSlot', function () {
            return "<?php endif; ?>";
        });

        /**
         * @extensionPoint('name')
         * Creates an extension point that plugins can target.
         * This adds a marker div that can be targeted with XPath.
         */
        Blade::directive('extensionPoint', function ($expression) {
            $name = trim($expression, "\"'");
            return "<?php echo '<div data-extension-point=\"{$name}\"></div>'; ?>";
        });

        /**
         * @extensionArea('name') ... @endExtensionArea
         * Creates a named area that can be replaced or modified.
         */
        Blade::directive('extensionArea', function ($expression) {
            $name = trim($expression, "\"'");
            return "<?php echo '<div data-extension-area=\"{$name}\">'; ?>";
        });

        Blade::directive('endExtensionArea', function () {
            return "<?php echo '</div>'; ?>";
        });

        /**
         * @pluginView('plugin-slug', 'view.name', ['data' => 'value'])
         * Render a view from a plugin namespace.
         */
        Blade::directive('pluginView', function ($expression) {
            return "<?php echo view({$expression})->render(); ?>";
        });

        /**
         * @extendable
         * Mark a view as extendable (enables XPath processing).
         */
        Blade::directive('extendable', function () {
            return "<?php app('view.slots')->pushView(\$__env->getFactory()->getContainer()['view']->getName() ?? ''); ?>";
        });

        Blade::directive('endExtendable', function () {
            return "<?php app('view.slots')->popView(); ?>";
        });
    }

    /**
     * Register view processing for extensions.
     */
    protected function registerViewProcessing(): void
    {
        // Get configured views to process
        $viewsToProcess = config('view-extensions.process_views', []);
        $autoProcess = config('view-extensions.auto_process', false);

        // Listen for view rendering
        View::composer('*', function ($view) use ($viewsToProcess, $autoProcess) {
            $viewName = $view->getName();
            $slotManager = app('view.slots');
            
            // Track current view for slot rendering
            $slotManager->pushView($viewName);

            // Register composers from plugins
            $registry = app('view.extensions');
            $composers = $registry->getComposers($viewName);
            
            foreach ($composers as $composer) {
                call_user_func($composer['composer'], $view);
            }
        });

        // Process extensions after rendering (using view creator for specific views)
        if ($autoProcess || !empty($viewsToProcess)) {
            $this->app['events']->listen('composing:*', function ($event, $data) use ($viewsToProcess, $autoProcess) {
                // This captures the view being composed
            });
        }

        // Use middleware-like approach to process output
        // This is registered via a separate middleware: ProcessViewExtensions
    }

    /**
     * Register global helper functions.
     */
    protected function registerHelpers(): void
    {
        if (!function_exists('extend_view')) {
            /**
             * Register a view extension.
             *
             * @param string $viewName View to extend
             * @param array $modification XPath modification
             * @param string|null $pluginSlug Plugin slug
             * @param int $priority Priority
             */
            function extend_view(string $viewName, array $modification, ?string $pluginSlug = null, int $priority = 10): void
            {
                ViewExtensionRegistry::getInstance()->extend($viewName, $modification, $pluginSlug, $priority);
            }
        }

        if (!function_exists('add_to_slot')) {
            /**
             * Add content to a view slot.
             *
             * @param string $viewName View name
             * @param string $slotName Slot name
             * @param string|callable $content Content or callback
             * @param string|null $pluginSlug Plugin slug
             * @param int $priority Priority
             */
            function add_to_slot(
                string $viewName,
                string $slotName,
                string|callable $content,
                ?string $pluginSlug = null,
                int $priority = 10
            ): void {
                ViewExtensionRegistry::getInstance()->addToSlot($viewName, $slotName, $content, $pluginSlug, $priority);
            }
        }

        if (!function_exists('replace_view')) {
            /**
             * Replace a view completely.
             *
             * @param string $viewName Original view
             * @param string $replacementView Replacement view
             * @param string|null $pluginSlug Plugin slug
             * @param int $priority Priority
             */
            function replace_view(string $viewName, string $replacementView, ?string $pluginSlug = null, int $priority = 10): void
            {
                ViewExtensionRegistry::getInstance()->replace($viewName, $replacementView, $pluginSlug, $priority);
            }
        }

        if (!function_exists('view_composer')) {
            /**
             * Register a view composer.
             *
             * @param string|array $views View(s)
             * @param callable $composer Composer callback
             * @param string|null $pluginSlug Plugin slug
             */
            function view_composer(string|array $views, callable $composer, ?string $pluginSlug = null): void
            {
                ViewExtensionRegistry::getInstance()->composer($views, $composer, $pluginSlug);
            }
        }

        if (!function_exists('xpath_selector')) {
            /**
             * Create an XPath selector builder.
             *
             * @return \App\Services\View\ViewSelectorBuilder
             */
            function xpath_selector(): \App\Services\View\ViewSelectorBuilder
            {
                return ViewExtender::selector();
            }
        }
    }
}
