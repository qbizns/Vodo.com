<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Translation\TranslationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to preload translations at the start of each request.
 * 
 * This eliminates N+1 queries by loading all translations for the current
 * language in a single database query instead of querying for each translation key.
 */
class PreloadTranslations
{
    public function __construct(
        protected TranslationService $translationService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if preloading is enabled in config
        if (!config('i18n.cache.preload_on_boot', true)) {
            return $next($request);
        }

        // Skip preloading for certain conditions
        if ($this->shouldSkipPreloading($request)) {
            return $next($request);
        }

        // Preload translations for the current locale
        $this->translationService->preloadTranslations();

        return $next($request);
    }

    /**
     * Determine if translation preloading should be skipped.
     */
    protected function shouldSkipPreloading(Request $request): bool
    {
        // Skip for API requests that don't need translations
        if ($request->is('api/*') && !$request->expectsJson()) {
            return true;
        }

        // Skip for asset requests
        if ($request->is('*.css', '*.js', '*.png', '*.jpg', '*.gif', '*.svg', '*.ico')) {
            return true;
        }

        // Skip for health check endpoints
        if ($request->is('health', 'health/*', 'up')) {
            return true;
        }

        // Skip for telescope requests
        if ($request->is('telescope', 'telescope/*')) {
            return true;
        }

        return false;
    }
}

