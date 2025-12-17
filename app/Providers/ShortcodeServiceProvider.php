<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Services\Shortcode\ShortcodeParser;
use App\Services\Shortcode\ShortcodeRegistry;
use App\Services\Shortcode\Handlers\BuiltInShortcodes;

class ShortcodeServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/shortcodes.php',
            'shortcodes'
        );

        // Register parser as singleton
        $this->app->singleton(ShortcodeParser::class, function ($app) {
            $parser = new ShortcodeParser();
            $parser->setMaxDepth(config('shortcodes.max_depth', 10));
            return $parser;
        });

        // Register registry as singleton
        $this->app->singleton(ShortcodeRegistry::class, function ($app) {
            return new ShortcodeRegistry($app->make(ShortcodeParser::class));
        });

        // Aliases
        $this->app->alias(ShortcodeRegistry::class, 'shortcode');
        $this->app->alias(ShortcodeParser::class, 'shortcode.parser');
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/shortcodes.php' => config_path('shortcodes.php'),
        ], 'shortcodes-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations/' => database_path('migrations'),
        ], 'shortcodes-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/shortcode-api.php');

        // Load helpers
        require_once __DIR__ . '/../../helpers/shortcode-helpers.php';

        // Register built-in shortcodes
        if (config('shortcodes.register_builtin', true)) {
            $this->app->booted(function () {
                $this->registerBuiltInShortcodes();
            });
        }

        // Register Blade directive
        if (config('shortcodes.blade_directive', true)) {
            $this->registerBladeDirectives();
        }

        // Register string macro
        if (config('shortcodes.string_macro', true)) {
            $this->registerStringMacro();
        }

        // Fire ready hook
        if (function_exists('do_action')) {
            do_action('shortcodes_ready');
        }
    }

    /**
     * Register built-in shortcodes
     */
    protected function registerBuiltInShortcodes(): void
    {
        try {
            $registry = $this->app->make(ShortcodeRegistry::class);
            
            // Check if already registered
            if (!$registry->exists('button')) {
                BuiltInShortcodes::register($registry);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to register built-in shortcodes: ' . $e->getMessage());
        }
    }

    /**
     * Register Blade directives
     */
    protected function registerBladeDirectives(): void
    {
        // @shortcode('tag', ['attr' => 'value'])
        Blade::directive('shortcode', function ($expression) {
            return "<?php echo do_shortcode({$expression}); ?>";
        });

        // @shortcodes($content)
        Blade::directive('shortcodes', function ($expression) {
            return "<?php echo parse_shortcodes({$expression}); ?>";
        });

        // @hasShortcode($content, 'tag')
        Blade::directive('hasShortcode', function ($expression) {
            return "<?php if (content_has_shortcode({$expression})): ?>";
        });

        Blade::directive('endHasShortcode', function () {
            return "<?php endif; ?>";
        });
    }

    /**
     * Register Str macro for shortcode parsing
     */
    protected function registerStringMacro(): void
    {
        \Illuminate\Support\Str::macro('parseShortcodes', function ($content, $context = []) {
            return parse_shortcodes($content, $context);
        });

        \Illuminate\Support\Str::macro('stripShortcodes', function ($content, $keepContent = false) {
            return strip_shortcodes($content, $keepContent);
        });
    }
}
