<?php

declare(strict_types=1);

namespace App\Services\I18n;

use App\Contracts\I18nServiceContract;
use App\Models\Locale;
use App\Models\Translation;
use App\Models\FieldTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Internationalization Service
 *
 * Manages translations, locales, and content localization.
 * Supports both static translations and dynamic field translations.
 *
 * @example Register translations
 * ```php
 * $service->register('plugin-crm', 'en', [
 *     'leads.title' => 'Leads',
 *     'leads.create' => 'Create Lead',
 *     'leads.status.new' => 'New',
 * ]);
 * ```
 *
 * @example Translate
 * ```php
 * $text = $service->translate('plugin-crm.leads.title'); // "Leads"
 * $text = $service->translate('plugin-crm.leads.create'); // "Create Lead"
 * ```
 */
class I18nService implements I18nServiceContract
{
    /**
     * In-memory translations cache.
     *
     * @var array<string, array>
     */
    protected array $translations = [];

    /**
     * Plugin ownership.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Registered locales.
     *
     * @var array<string, array>
     */
    protected array $locales = [];

    /**
     * Current locale.
     */
    protected ?string $currentLocale = null;

    public function __construct()
    {
        $this->loadLocales();
    }

    /**
     * Load registered locales.
     */
    protected function loadLocales(): void
    {
        // Default locales
        $this->locales = [
            'en' => [
                'name' => 'English',
                'native' => 'English',
                'rtl' => false,
                'flag' => '🇺🇸',
            ],
            'es' => [
                'name' => 'Spanish',
                'native' => 'Español',
                'rtl' => false,
                'flag' => '🇪🇸',
            ],
            'fr' => [
                'name' => 'French',
                'native' => 'Français',
                'rtl' => false,
                'flag' => '🇫🇷',
            ],
            'de' => [
                'name' => 'German',
                'native' => 'Deutsch',
                'rtl' => false,
                'flag' => '🇩🇪',
            ],
            'ar' => [
                'name' => 'Arabic',
                'native' => 'العربية',
                'rtl' => true,
                'flag' => '🇸🇦',
            ],
            'zh' => [
                'name' => 'Chinese',
                'native' => '中文',
                'rtl' => false,
                'flag' => '🇨🇳',
            ],
        ];

        // Load from database
        try {
            $dbLocales = Locale::all();
            foreach ($dbLocales as $locale) {
                $this->locales[$locale->code] = [
                    'name' => $locale->name,
                    'native' => $locale->native_name,
                    'rtl' => $locale->is_rtl,
                    'flag' => $locale->flag,
                ];
            }
        } catch (\Exception $e) {
            // Database not ready
        }
    }

    public function register(string $namespace, string $locale, array $translations, ?string $pluginSlug = null): self
    {
        // Store in memory
        if (!isset($this->translations[$locale])) {
            $this->translations[$locale] = [];
        }

        foreach ($translations as $key => $value) {
            $fullKey = "{$namespace}.{$key}";
            $this->translations[$locale][$fullKey] = $value;
        }

        if ($pluginSlug) {
            $this->pluginOwnership[$namespace] = $pluginSlug;
        }

        // Persist to database
        foreach ($translations as $key => $value) {
            Translation::updateOrCreate(
                [
                    'namespace' => $namespace,
                    'key' => $key,
                    'locale' => $locale,
                ],
                [
                    'value' => $value,
                    'plugin_slug' => $pluginSlug,
                ]
            );
        }

        // Clear cache
        Cache::forget("translations.{$locale}");

        return $this;
    }

    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->getLocale();

        // Load translations if not cached
        $this->loadTranslations($locale);

        // Get translation
        $translation = $this->translations[$locale][$key] ?? null;

        // Fallback to default locale
        if ($translation === null && $locale !== config('app.fallback_locale')) {
            $fallbackLocale = config('app.fallback_locale', 'en');
            $this->loadTranslations($fallbackLocale);
            $translation = $this->translations[$fallbackLocale][$key] ?? $key;
        }

        $translation = $translation ?? $key;

        // Replace placeholders
        foreach ($replace as $placeholder => $value) {
            $translation = str_replace(":{$placeholder}", $value, $translation);
            $translation = str_replace("{{$placeholder}}", $value, $translation);
        }

        return $translation;
    }

    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->getLocale();
        $this->loadTranslations($locale);

        return isset($this->translations[$locale][$key]);
    }

    public function getNamespace(string $namespace, ?string $locale = null): array
    {
        $locale = $locale ?? $this->getLocale();
        $this->loadTranslations($locale);

        $prefix = "{$namespace}.";
        $result = [];

        foreach ($this->translations[$locale] ?? [] as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $shortKey = substr($key, strlen($prefix));
                $result[$shortKey] = $value;
            }
        }

        return $result;
    }

    public function getLocales(): Collection
    {
        return collect($this->locales);
    }

    public function setLocale(string $locale): void
    {
        if (!isset($this->locales[$locale])) {
            throw new \InvalidArgumentException("Locale not registered: {$locale}");
        }

        $this->currentLocale = $locale;
        App::setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->currentLocale ?? App::getLocale();
    }

    public function registerLocale(string $code, array $config): self
    {
        $this->locales[$code] = $config;

        // Persist to database
        Locale::updateOrCreate(
            ['code' => $code],
            [
                'name' => $config['name'],
                'native_name' => $config['native'] ?? $config['name'],
                'is_rtl' => $config['rtl'] ?? false,
                'flag' => $config['flag'] ?? null,
                'is_active' => $config['active'] ?? true,
            ]
        );

        return $this;
    }

    public function getFieldTranslation(string $entityName, int $recordId, string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? $this->getLocale();

        $translation = FieldTranslation::where('entity_name', $entityName)
            ->where('record_id', $recordId)
            ->where('field', $field)
            ->where('locale', $locale)
            ->first();

        return $translation?->value;
    }

    public function setFieldTranslation(string $entityName, int $recordId, string $field, string $value, ?string $locale = null): void
    {
        $locale = $locale ?? $this->getLocale();

        FieldTranslation::updateOrCreate(
            [
                'entity_name' => $entityName,
                'record_id' => $recordId,
                'field' => $field,
                'locale' => $locale,
            ],
            ['value' => $value]
        );
    }

    public function export(?string $namespace = null, ?string $locale = null): array
    {
        $query = Translation::query();

        if ($namespace) {
            $query->where('namespace', $namespace);
        }

        if ($locale) {
            $query->where('locale', $locale);
        }

        $translations = $query->get();

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->locale][$translation->namespace][$translation->key] = $translation->value;
        }

        return $result;
    }

    public function import(array $translations, bool $overwrite = false): int
    {
        $count = 0;

        foreach ($translations as $locale => $namespaces) {
            foreach ($namespaces as $namespace => $keys) {
                foreach ($keys as $key => $value) {
                    $exists = Translation::where('namespace', $namespace)
                        ->where('key', $key)
                        ->where('locale', $locale)
                        ->exists();

                    if (!$exists || $overwrite) {
                        Translation::updateOrCreate(
                            [
                                'namespace' => $namespace,
                                'key' => $key,
                                'locale' => $locale,
                            ],
                            ['value' => $value]
                        );
                        $count++;
                    }
                }
            }
        }

        // Clear cache
        Cache::tags('translations')->flush();

        return $count;
    }

    /**
     * Load translations for a locale.
     */
    protected function loadTranslations(string $locale): void
    {
        if (isset($this->translations[$locale]) && !empty($this->translations[$locale])) {
            return;
        }

        // Try cache first
        $cached = Cache::get("translations.{$locale}");
        if ($cached) {
            $this->translations[$locale] = $cached;
            return;
        }

        // Load from database
        $this->translations[$locale] = [];

        try {
            $dbTranslations = Translation::where('locale', $locale)->get();
            foreach ($dbTranslations as $translation) {
                $fullKey = "{$translation->namespace}.{$translation->key}";
                $this->translations[$locale][$fullKey] = $translation->value;
            }

            // Cache for 1 hour
            Cache::put("translations.{$locale}", $this->translations[$locale], 3600);
        } catch (\Exception $e) {
            // Database not ready
        }
    }

    /**
     * Check if current locale is RTL.
     */
    public function isRtl(): bool
    {
        $locale = $this->getLocale();

        return $this->locales[$locale]['rtl'] ?? false;
    }

    /**
     * Get missing translations for a locale.
     */
    public function getMissing(string $locale, string $referenceLocale = 'en'): Collection
    {
        $this->loadTranslations($locale);
        $this->loadTranslations($referenceLocale);

        $reference = $this->translations[$referenceLocale] ?? [];
        $current = $this->translations[$locale] ?? [];

        $missing = array_diff_key($reference, $current);

        return collect($missing);
    }
}
