<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to detect and set the application locale.
 * 
 * Detection priority:
 * 1. URL parameter (?lang=xx)
 * 2. Session storage
 * 3. User preference (if authenticated)
 * 4. Cookie
 * 5. Browser Accept-Language header
 * 6. Default locale from config
 */
class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);
        $supportedLanguages = config('i18n.supported_languages', []);

        // Validate and set the locale
        if ($locale && array_key_exists($locale, $supportedLanguages)) {
            App::setLocale($locale);
            
            // Store in session for persistence
            Session::put('locale', $locale);
        }

        // Determine RTL status
        $currentLocale = App::getLocale();
        $rtlLanguages = config('i18n.rtl_languages', ['ar', 'he', 'fa', 'ur', 'ps', 'sd', 'ku', 'yi']);
        $isRtl = in_array($currentLocale, $rtlLanguages, true);
        $direction = $isRtl ? 'rtl' : 'ltr';

        // Add locale info to request for use in controllers/views
        $request->attributes->set('locale', $currentLocale);
        $request->attributes->set('direction', $direction);
        $request->attributes->set('is_rtl', $isRtl);

        $response = $next($request);

        // Set locale cookie for future requests
        if ($locale && array_key_exists($locale, $supportedLanguages)) {
            $response->headers->setCookie(
                cookie('locale', $locale, 60 * 24 * 365) // 1 year
            );
        }

        // Add Content-Language header
        $response->headers->set('Content-Language', $currentLocale);

        return $response;
    }

    /**
     * Detect the locale from various sources.
     */
    protected function detectLocale(Request $request): ?string
    {
        // 1. Check URL parameter
        if ($locale = $request->query('lang')) {
            return $this->normalizeLocale($locale);
        }

        // 2. Check route parameter
        if ($locale = $request->route('locale')) {
            return $this->normalizeLocale($locale);
        }

        // 3. Check session
        if ($locale = Session::get('locale')) {
            return $this->normalizeLocale($locale);
        }

        // 4. Check authenticated user preference
        if ($user = $request->user()) {
            $locale = $user->locale ?? $user->language ?? $user->preferred_language ?? null;
            if ($locale) {
                return $this->normalizeLocale($locale);
            }
        }

        // 5. Check cookie
        if ($locale = $request->cookie('locale')) {
            return $this->normalizeLocale($locale);
        }

        // 6. Check browser Accept-Language header
        $locale = $this->detectFromBrowser($request);
        if ($locale) {
            return $locale;
        }

        // 7. Return default locale
        return config('i18n.default_locale', config('app.locale', 'en'));
    }

    /**
     * Detect locale from browser Accept-Language header.
     */
    protected function detectFromBrowser(Request $request): ?string
    {
        $autoDetect = config('i18n.auto_detect', []);
        
        if (!($autoDetect['enabled'] ?? true) || !in_array('browser', $autoDetect['sources'] ?? [])) {
            return null;
        }

        $acceptLanguage = $request->header('Accept-Language');
        
        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $languages = $this->parseAcceptLanguage($acceptLanguage);
        $supportedLanguages = config('i18n.supported_languages', []);

        // Find the best match
        foreach ($languages as $lang) {
            $code = $this->normalizeLocale($lang['code']);
            
            // Exact match
            if (isset($supportedLanguages[$code])) {
                return $code;
            }

            // Try base language (e.g., 'en' from 'en-US')
            $baseCode = explode('-', $code)[0];
            $baseCode = explode('_', $baseCode)[0];
            
            if (isset($supportedLanguages[$baseCode])) {
                return $baseCode;
            }
        }

        return null;
    }

    /**
     * Parse the Accept-Language header.
     */
    protected function parseAcceptLanguage(string $header): array
    {
        $languages = [];
        
        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            
            if (empty($part)) {
                continue;
            }

            $segments = explode(';', $part);
            $code = trim($segments[0]);
            $quality = 1.0;

            if (isset($segments[1])) {
                $qualityPart = trim($segments[1]);
                if (str_starts_with($qualityPart, 'q=')) {
                    $quality = (float) substr($qualityPart, 2);
                }
            }

            $languages[] = [
                'code' => $code,
                'quality' => $quality,
            ];
        }

        // Sort by quality (highest first)
        usort($languages, fn($a, $b) => $b['quality'] <=> $a['quality']);

        return $languages;
    }

    /**
     * Normalize a locale code.
     */
    protected function normalizeLocale(string $locale): string
    {
        // Convert to lowercase
        $locale = strtolower(trim($locale));

        // Handle common variants
        $mappings = [
            'zh-cn' => 'zh',
            'zh-hans' => 'zh',
            'zh-tw' => 'zh_TW',
            'zh-hant' => 'zh_TW',
            'pt-br' => 'pt',
            'en-us' => 'en',
            'en-gb' => 'en',
        ];

        return $mappings[$locale] ?? $locale;
    }
}
