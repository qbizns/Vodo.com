<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Translation\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Translation API Controller.
 * 
 * Provides endpoints for:
 * - Getting translations for JavaScript
 * - Managing translations
 * - Translation statistics
 */
class TranslationController extends Controller
{
    /**
     * The translation service instance.
     */
    protected TranslationService $translator;

    /**
     * Create a new controller instance.
     */
    public function __construct(TranslationService $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get translations for JavaScript.
     * 
     * GET /api/translations/js
     * GET /api/translations/js?lang=ar
     * GET /api/translations/js?groups[]=common&groups[]=validation
     */
    public function forJavaScript(Request $request): JsonResponse
    {
        $lang = $request->query('lang', $this->translator->getCurrentLang());
        $groups = $request->query('groups');

        if (is_string($groups)) {
            $groups = explode(',', $groups);
        }

        $translations = $this->translator->getAllForJs($lang, $groups);

        return response()->json([
            'locale' => $lang,
            'direction' => $this->translator->getDirection($lang),
            'is_rtl' => $this->translator->isRtl($lang),
            'messages' => $translations,
        ]);
    }

    /**
     * Get supported languages.
     * 
     * GET /api/translations/languages
     */
    public function languages(): JsonResponse
    {
        $languages = $this->translator->getSupportedLanguages();
        $current = $this->translator->getCurrentLang();

        return response()->json([
            'current' => $current,
            'languages' => $languages,
        ]);
    }

    /**
     * Get translation statistics.
     * 
     * GET /api/translations/stats
     * GET /api/translations/stats?module=plugin-name
     */
    public function stats(Request $request): JsonResponse
    {
        $module = $request->query('module');
        $stats = $this->translator->getStats($module);

        return response()->json([
            'module' => $module,
            'stats' => $stats,
        ]);
    }

    /**
     * Get available translation files.
     * 
     * GET /api/translations/files
     * GET /api/translations/files?lang=ar
     */
    public function files(Request $request): JsonResponse
    {
        $lang = $request->query('lang', $this->translator->getCurrentLang());
        $files = $this->translator->getAvailableFiles($lang);

        return response()->json([
            'locale' => $lang,
            'files' => $files,
        ]);
    }

    /**
     * Get a specific translation group.
     * 
     * GET /api/translations/group/{group}
     * GET /api/translations/group/common?lang=ar
     */
    public function group(Request $request, string $group): JsonResponse
    {
        $lang = $request->query('lang', $this->translator->getCurrentLang());
        $translations = $this->translator->getGroup($group, $lang);

        return response()->json([
            'locale' => $lang,
            'group' => $group,
            'translations' => $translations,
        ]);
    }

    /**
     * Translate a single key.
     * 
     * POST /api/translations/translate
     * {
     *   "key": "common.save",
     *   "replace": {"name": "John"},
     *   "lang": "ar"
     * }
     */
    public function translate(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'replace' => 'array',
            'lang' => 'string',
        ]);

        $key = $request->input('key');
        $replace = $request->input('replace', []);
        $lang = $request->input('lang', $this->translator->getCurrentLang());

        $translation = $this->translator->translate($key, $replace, $lang);

        return response()->json([
            'key' => $key,
            'locale' => $lang,
            'translation' => $translation,
        ]);
    }

    /**
     * Set the current locale.
     * 
     * POST /api/translations/locale
     * {
     *   "locale": "ar"
     * }
     */
    public function setLocale(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|max:10',
        ]);

        $locale = $request->input('locale');

        if (!$this->translator->isSupported($locale)) {
            return response()->json([
                'error' => 'Unsupported locale',
                'supported' => array_keys($this->translator->getSupportedLanguages()),
            ], 422);
        }

        $this->translator->setLang($locale);
        session(['locale' => $locale]);

        return response()->json([
            'locale' => $locale,
            'direction' => $this->translator->getDirection($locale),
            'is_rtl' => $this->translator->isRtl($locale),
            'message' => 'Locale updated successfully',
        ]);
    }

    /**
     * Get current locale information.
     * 
     * GET /api/translations/current
     */
    public function current(): JsonResponse
    {
        $locale = $this->translator->getCurrentLang();
        $info = $this->translator->getLanguageInfo($locale);

        return response()->json([
            'locale' => $locale,
            'info' => $info,
            'direction' => $this->translator->getDirection($locale),
            'is_rtl' => $this->translator->isRtl($locale),
        ]);
    }
}
