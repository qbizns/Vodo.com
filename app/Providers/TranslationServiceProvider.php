<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Translation\TranslationService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Translation Service Provider.
 * 
 * Provides:
 * - TranslationService singleton
 * - Blade directives (@t, @tc, @rtl, @ltr, @lang, @direction)
 * 
 * Helper functions (__t, __tc, __p, is_rtl, etc.) are defined in helpers/i18n.php
 */
class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TranslationService::class, function ($app) {
            return new TranslationService();
        });

        // Alias for convenience
        $this->app->alias(TranslationService::class, 'translator.service');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set tenant context from authenticated user
        $this->app->resolving(TranslationService::class, function (TranslationService $service, $app) {
            if ($app['auth']->check() && $user = $app['auth']->user()) {
                $tenantId = $user->tenant_id ?? null;
                $service->setTenant($tenantId);
            }
        });

        $this->registerBladeDirectives();
    }

    /**
     * Register Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        /**
         * @t('key') - Basic translation
         * @t('key', ['name' => 'John']) - Translation with replacements
         * @t('key', [], 'ar') - Translation to specific language
         */
        Blade::directive('t', function ($expression) {
            return "<?php echo __t({$expression}); ?>";
        });

        /**
         * @tc('key', $count) - Pluralized translation
         * @tc('key', $count, ['name' => 'John']) - With replacements
         */
        Blade::directive('tc', function ($expression) {
            return "<?php echo __tc({$expression}); ?>";
        });

        /**
         * @p('plugin-slug', 'key') - Plugin translation
         */
        Blade::directive('p', function ($expression) {
            return "<?php echo __p({$expression}); ?>";
        });

        /**
         * @rtl ... @endrtl - Content only shown for RTL languages
         */
        Blade::directive('rtl', function () {
            return "<?php if(is_rtl()): ?>";
        });

        Blade::directive('endrtl', function () {
            return "<?php endif; ?>";
        });

        /**
         * @ltr ... @endltr - Content only shown for LTR languages
         */
        Blade::directive('ltr', function () {
            return "<?php if(!is_rtl()): ?>";
        });

        Blade::directive('endltr', function () {
            return "<?php endif; ?>";
        });

        /**
         * @lang('en') ... @endlang - Content only shown for specific language
         */
        Blade::directive('lang', function ($expression) {
            return "<?php if(app()->getLocale() === {$expression}): ?>";
        });

        Blade::directive('endlang', function () {
            return "<?php endif; ?>";
        });

        /**
         * @direction - Outputs 'rtl' or 'ltr'
         */
        Blade::directive('direction', function () {
            return "<?php echo text_direction(); ?>";
        });

        /**
         * @locale - Outputs current locale code
         */
        Blade::directive('locale', function () {
            return "<?php echo current_locale(); ?>";
        });

        /**
         * @langName - Outputs native name of current language
         */
        Blade::directive('langName', function ($expression) {
            if (empty($expression)) {
                return "<?php echo app(\App\Services\Translation\TranslationService::class)->getNativeName(current_locale()); ?>";
            }
            return "<?php echo app(\App\Services\Translation\TranslationService::class)->getNativeName({$expression}); ?>";
        });

        /**
         * @translations - Outputs JSON translations for JavaScript
         */
        Blade::directive('translations', function ($expression) {
            if (empty($expression)) {
                return "<?php echo app(\App\Services\Translation\TranslationService::class)->getJsTranslationsJson(); ?>";
            }
            return "<?php echo app(\App\Services\Translation\TranslationService::class)->getJsTranslationsJson({$expression}); ?>";
        });

        /**
         * @translationsScript - Outputs a script tag with translations for JavaScript
         */
        Blade::directive('translationsScript', function ($expression) {
            return <<<'PHP'
<?php
$__translations = app(\App\Services\Translation\TranslationService::class)->getJsTranslationsJson();
$__locale = current_locale();
$__direction = text_direction();
echo "<script>window.I18n = {locale: '{$__locale}', direction: '{$__direction}', messages: {$__translations}};</script>";
?>
PHP;
        });
    }
}
