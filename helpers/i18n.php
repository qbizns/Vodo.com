<?php

/**
 * I18n Helper Functions
 * 
 * Global helper functions for internationalization.
 * These are automatically loaded via composer autoload.
 */

use App\Services\Translation\TranslationService;

if (!function_exists('__t')) {
    /**
     * Translate a string using TranslationService.
     *
     * @param string $key Translation key (e.g., 'common.save', 'plugin::messages.key')
     * @param array $replace Replacement values for placeholders
     * @param string|null $lang Target language (defaults to current locale)
     * @return string
     */
    function __t(string $key, array $replace = [], ?string $lang = null): string
    {
        return app(TranslationService::class)->translate($key, $replace, $lang);
    }
}

if (!function_exists('__tc')) {
    /**
     * Translate with pluralization.
     *
     * @param string $key Translation key
     * @param int $count Count for pluralization
     * @param array $replace Replacement values
     * @param string|null $lang Target language
     * @return string
     */
    function __tc(string $key, int $count, array $replace = [], ?string $lang = null): string
    {
        return app(TranslationService::class)->choice($key, $count, $replace, $lang);
    }
}

if (!function_exists('__p')) {
    /**
     * Translate a plugin string.
     *
     * @param string $plugin Plugin slug
     * @param string $key Translation key within plugin
     * @param array $replace Replacement values
     * @param string|null $lang Target language
     * @return string
     */
    function __p(string $plugin, string $key, array $replace = [], ?string $lang = null): string
    {
        return app(TranslationService::class)->translate("{$plugin}::{$key}", $replace, $lang);
    }
}

if (!function_exists('is_rtl')) {
    /**
     * Check if the current or specified language is RTL.
     *
     * @param string|null $lang Language code (defaults to current locale)
     * @return bool
     */
    function is_rtl(?string $lang = null): bool
    {
        return app(TranslationService::class)->isRtl($lang);
    }
}

if (!function_exists('text_direction')) {
    /**
     * Get text direction for current or specified language.
     *
     * @param string|null $lang Language code (defaults to current locale)
     * @return string 'rtl' or 'ltr'
     */
    function text_direction(?string $lang = null): string
    {
        return app(TranslationService::class)->getDirection($lang);
    }
}

if (!function_exists('lang_info')) {
    /**
     * Get information about a language.
     *
     * @param string|null $lang Language code (defaults to current locale)
     * @return array|null
     */
    function lang_info(?string $lang = null): ?array
    {
        $lang = $lang ?? app()->getLocale();
        return app(TranslationService::class)->getLanguageInfo($lang);
    }
}

if (!function_exists('current_locale')) {
    /**
     * Get the current locale.
     *
     * @return string
     */
    function current_locale(): string
    {
        return app(TranslationService::class)->getCurrentLang();
    }
}

if (!function_exists('supported_languages')) {
    /**
     * Get all supported languages.
     *
     * @return array
     */
    function supported_languages(): array
    {
        return app(TranslationService::class)->getSupportedLanguages();
    }
}
