<?php

declare(strict_types=1);

namespace App\Services\Translation;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

/**
 * Translation Service - Handles internationalization.
 * 
 * Features:
 * - File-based translations (Laravel lang files)
 * - Database translations with tenant support
 * - Plugin translation namespaces
 * - RTL language detection
 * - JavaScript translation exports
 * - Model field translations
 * - View label translations
 * - Selection option translations
 * - Menu translations
 * - PO file import/export
 * - Cache management
 */
class TranslationService
{
    /**
     * Cache prefix for translations.
     */
    protected const CACHE_PREFIX = 'translations:';

    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 3600;

    /**
     * Current tenant ID.
     */
    protected ?int $tenantId = null;

    /**
     * Loaded translations cache (in-memory).
     */
    protected array $loadedTranslations = [];

    /**
     * Loaded file translations cache.
     */
    protected array $fileTranslations = [];

    /**
     * Registered plugin translation namespaces.
     */
    protected array $pluginNamespaces = [];

    /**
     * Whether translations have been preloaded for the current request.
     */
    protected bool $preloaded = false;

    /**
     * Preloaded database overrides keyed by "lang:name".
     */
    protected array $preloadedOverrides = [];

    /**
     * Get i18n configuration.
     */
    protected function getConfig(): array
    {
        return config('i18n', []);
    }

    /**
     * Set tenant context.
     */
    public function setTenant(?int $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Get current language.
     */
    public function getCurrentLang(): string
    {
        return App::getLocale();
    }

    /**
     * Set current language.
     */
    public function setLang(string $lang): void
    {
        if ($this->isSupported($lang)) {
            App::setLocale($lang);
        }
    }

    /**
     * Get supported languages from config.
     */
    public function getSupportedLanguages(): array
    {
        return $this->getConfig()['supported_languages'] ?? [];
    }

    /**
     * Check if a language is supported.
     */
    public function isSupported(string $lang): bool
    {
        return array_key_exists($lang, $this->getSupportedLanguages());
    }

    /**
     * Get language info.
     */
    public function getLanguageInfo(string $lang): ?array
    {
        return $this->getSupportedLanguages()[$lang] ?? null;
    }

    /**
     * Check if a language is RTL.
     */
    public function isRtl(?string $lang = null): bool
    {
        $lang = $lang ?? $this->getCurrentLang();
        $rtlLanguages = $this->getConfig()['rtl_languages'] ?? [];
        
        return in_array($lang, $rtlLanguages, true);
    }

    /**
     * Get text direction for a language.
     */
    public function getDirection(?string $lang = null): string
    {
        return $this->isRtl($lang) ? 'rtl' : 'ltr';
    }

    /**
     * Get the native name of a language.
     */
    public function getNativeName(string $lang): string
    {
        $info = $this->getLanguageInfo($lang);
        return $info['native'] ?? $info['name'] ?? $lang;
    }

    /**
     * Get the English name of a language.
     */
    public function getEnglishName(string $lang): string
    {
        $info = $this->getLanguageInfo($lang);
        return $info['name'] ?? $lang;
    }

    /**
     * Register a plugin translation namespace.
     */
    public function registerPluginNamespace(string $pluginSlug, string $path): void
    {
        $this->pluginNamespaces[$pluginSlug] = $path;
    }

    /**
     * Get all registered plugin namespaces.
     */
    public function getPluginNamespaces(): array
    {
        return $this->pluginNamespaces;
    }

    /**
     * Preload all translations for the current language and tenant.
     * This should be called early in the request lifecycle to avoid N+1 queries.
     */
    public function preloadTranslations(?string $lang = null): void
    {
        $lang = $lang ?? $this->getCurrentLang();
        
        // Skip if already preloaded for this language
        if ($this->preloaded) {
            return;
        }

        $cacheConfig = $this->getConfig()['cache'] ?? [];
        $cacheEnabled = $cacheConfig['enabled'] ?? true;
        $cacheTtl = $cacheConfig['ttl'] ?? $this->cacheTtl;
        $cachePrefix = $cacheConfig['prefix'] ?? 'i18n:';
        
        $bulkCacheKey = "{$cachePrefix}bulk:{$lang}:{$this->tenantId}";

        if ($cacheEnabled) {
            // Try to load from persistent cache first
            $cached = Cache::get($bulkCacheKey);
            if ($cached !== null) {
                $this->preloadedOverrides = $cached;
                $this->preloaded = true;
                return;
            }
        }

        // Load from database
        $this->preloadDatabaseOverrides($lang);

        // Store in persistent cache
        if ($cacheEnabled && !empty($this->preloadedOverrides)) {
            Cache::put($bulkCacheKey, $this->preloadedOverrides, $cacheTtl);
        }

        $this->preloaded = true;
    }

    /**
     * Preload all database overrides for a specific language.
     * Loads TYPE_CODE translations which are used for view/code string overrides.
     */
    protected function preloadDatabaseOverrides(string $lang): void
    {
        // Only load overrides for non-English (English is typically the source)
        if ($lang === 'en') {
            return;
        }

        try {
            $translations = Translation::forLang($lang)
                ->ofType(Translation::TYPE_CODE)
                ->forTenant($this->tenantId)
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->get(['name', 'value', 'module']);

            foreach ($translations as $translation) {
                // Key format: "lang:name" or "lang:name:module" if module exists
                $key = $this->getPreloadedKey($lang, $translation->name, $translation->module);
                $this->preloadedOverrides[$key] = $translation->value;
            }
        } catch (\Throwable $e) {
            // Database may not be ready during migrations
            Log::warning('Failed to preload translations', [
                'lang' => $lang,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the key for preloaded overrides lookup.
     */
    protected function getPreloadedKey(string $lang, string $name, ?string $module = null): string
    {
        return $module ? "{$lang}:{$name}:{$module}" : "{$lang}:{$name}";
    }

    /**
     * Check if translations have been preloaded.
     */
    public function isPreloaded(): bool
    {
        return $this->preloaded;
    }

    /**
     * Clear the preloaded state (useful for testing or language changes).
     */
    public function clearPreloaded(): void
    {
        $this->preloaded = false;
        $this->preloadedOverrides = [];
    }

    /**
     * Clear the bulk translation cache for a specific language.
     */
    public function clearBulkCache(?string $lang = null): void
    {
        $cachePrefix = $this->getConfig()['cache']['prefix'] ?? 'i18n:';
        
        if ($lang) {
            Cache::forget("{$cachePrefix}bulk:{$lang}:{$this->tenantId}");
        } else {
            // Clear all supported languages
            foreach ($this->getSupportedLanguages() as $langCode => $info) {
                Cache::forget("{$cachePrefix}bulk:{$langCode}:{$this->tenantId}");
            }
        }

        $this->clearPreloaded();
    }

    /**
     * Translate a string using the hybrid approach (files + database).
     * 
     * Key format:
     * - 'group.key' - Core translation
     * - 'plugin-slug::group.key' - Plugin translation
     * - Plain string - Database-only translation
     */
    public function translate(
        string $key,
        array $replace = [],
        ?string $lang = null,
        ?string $module = null
    ): string {
        $lang = $lang ?? $this->getCurrentLang();
        
        // Check if this is a namespaced key (plugin::group.key)
        if (str_contains($key, '::')) {
            return $this->translateNamespaced($key, $replace, $lang);
        }

        // Check if this is a dotted key (group.key)
        if (str_contains($key, '.')) {
            return $this->translateFromFiles($key, $replace, $lang, $module);
        }

        // Plain string - use database translation
        return $this->translateFromDatabase($key, $lang, Translation::TYPE_CODE, $module);
    }

    /**
     * Shorthand for translate.
     */
    public function __(string $key, array $replace = [], ?string $lang = null): string
    {
        return $this->translate($key, $replace, $lang);
    }

    /**
     * Translate a namespaced key (plugin::group.key).
     */
    protected function translateNamespaced(string $key, array $replace, string $lang): string
    {
        [$namespace, $group, $item] = $this->parseKey($key);
        
        // Try plugin files first
        if (isset($this->pluginNamespaces[$namespace])) {
            $translation = $this->loadPluginTranslation($namespace, $group, $item, $lang);
            if ($translation !== null) {
                return $this->makeReplacements($translation, $replace);
            }
        }

        // Fallback to Laravel's translator
        $fallback = trans($key, $replace, $lang);
        
        // If Laravel returns the key, the translation doesn't exist
        return $fallback !== $key ? $fallback : $item;
    }

    /**
     * Translate from language files with database override support.
     */
    protected function translateFromFiles(string $key, array $replace, string $lang, ?string $module = null): string
    {
        // Check database for override first
        $cacheKey = $this->getCacheKey('file', $key, $lang, $module);
        
        if (isset($this->loadedTranslations[$cacheKey])) {
            return $this->makeReplacements($this->loadedTranslations[$cacheKey], $replace);
        }

        // Check database override
        $dbOverride = $this->getDatabaseOverride($key, $lang, $module);
        if ($dbOverride !== null) {
            $this->loadedTranslations[$cacheKey] = $dbOverride;
            return $this->makeReplacements($dbOverride, $replace);
        }

        // Load from file
        $translation = $this->loadFromFile($key, $lang);
        
        if ($translation !== null) {
            $this->loadedTranslations[$cacheKey] = $translation;
            return $this->makeReplacements($translation, $replace);
        }

        // Fallback to English
        if ($lang !== 'en') {
            $fallback = $this->loadFromFile($key, 'en');
            if ($fallback !== null) {
                return $this->makeReplacements($fallback, $replace);
            }
        }

        // Return the key part after the last dot as human-readable fallback
        return $this->humanize(Arr::last(explode('.', $key)));
    }

    /**
     * Translate from database only.
     */
    protected function translateFromDatabase(
        string $source,
        string $lang,
        string $type = Translation::TYPE_CODE,
        ?string $module = null
    ): string {
        // English is typically the source language
        if ($lang === 'en') {
            return $source;
        }

        $cacheKey = $this->getCacheKey($type, $source, $lang, $module);
        
        // Check memory cache first
        if (isset($this->loadedTranslations[$cacheKey])) {
            return $this->loadedTranslations[$cacheKey];
        }

        // Check persistent cache
        $translation = Cache::remember($cacheKey, $this->cacheTtl, function () use ($source, $lang, $type, $module) {
            $query = Translation::forLang($lang)
                ->ofType($type)
                ->where('source', $source)
                ->forTenant($this->tenantId);

            if ($module) {
                $query->forModule($module);
            }

            return $query->value('value');
        });

        $result = $translation ?: $source;
        $this->loadedTranslations[$cacheKey] = $result;

        return $result;
    }

    /**
     * Load translation from language files.
     */
    protected function loadFromFile(string $key, string $lang): ?string
    {
        [$group, $item] = $this->parseGroupKey($key);
        
        // Check cache
        $fileKey = "{$lang}.{$group}";
        if (!isset($this->fileTranslations[$fileKey])) {
            $this->fileTranslations[$fileKey] = $this->loadTranslationFile($group, $lang);
        }

        return Arr::get($this->fileTranslations[$fileKey], $item);
    }

    /**
     * Load a translation file.
     */
    protected function loadTranslationFile(string $group, string $lang): array
    {
        $path = base_path("lang/{$lang}/{$group}.php");
        
        if (File::exists($path)) {
            return require $path;
        }

        return [];
    }

    /**
     * Load plugin translation.
     */
    protected function loadPluginTranslation(string $namespace, string $group, string $item, string $lang): ?string
    {
        $path = $this->pluginNamespaces[$namespace] ?? null;
        
        if (!$path) {
            return null;
        }

        $filePath = "{$path}/{$lang}/{$group}.php";
        
        if (!File::exists($filePath)) {
            // Try fallback language
            if ($lang !== 'en') {
                $filePath = "{$path}/en/{$group}.php";
                if (!File::exists($filePath)) {
                    return null;
                }
            } else {
                return null;
            }
        }

        $translations = require $filePath;
        
        return Arr::get($translations, $item);
    }

    /**
     * Get database override for a file translation.
     * First checks the preloaded cache, then falls back to database query.
     */
    protected function getDatabaseOverride(string $key, string $lang, ?string $module = null): ?string
    {
        // Check preloaded cache first (if translations have been preloaded)
        if ($this->preloaded) {
            $preloadedKey = $this->getPreloadedKey($lang, $key, $module);
            
            // Also try without module if module-specific key not found
            if (isset($this->preloadedOverrides[$preloadedKey])) {
                return $this->preloadedOverrides[$preloadedKey];
            }
            
            // Try without module fallback
            if ($module !== null) {
                $fallbackKey = $this->getPreloadedKey($lang, $key, null);
                if (isset($this->preloadedOverrides[$fallbackKey])) {
                    return $this->preloadedOverrides[$fallbackKey];
                }
            }
            
            // Not found in preloaded cache - return null (no DB query needed)
            return null;
        }

        // Fallback to direct database query (when not preloaded)
        return Translation::forLang($lang)
            ->ofType(Translation::TYPE_CODE)
            ->where('name', $key)
            ->forTenant($this->tenantId)
            ->when($module, fn($q) => $q->forModule($module))
            ->value('value');
    }

    /**
     * Parse a namespaced key (namespace::group.item).
     */
    protected function parseKey(string $key): array
    {
        $segments = explode('::', $key, 2);
        
        if (count($segments) === 2) {
            $namespace = $segments[0];
            [$group, $item] = $this->parseGroupKey($segments[1]);
            return [$namespace, $group, $item];
        }

        return [null, ...$this->parseGroupKey($key)];
    }

    /**
     * Parse a group.item key.
     */
    protected function parseGroupKey(string $key): array
    {
        $parts = explode('.', $key, 2);
        
        if (count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }

        return ['messages', $key];
    }

    /**
     * Make replacements in a translation string.
     */
    protected function makeReplacements(string $translation, array $replace): string
    {
        if (empty($replace)) {
            return $translation;
        }

        foreach ($replace as $key => $value) {
            $translation = str_replace(
                [':' . $key, ':' . strtoupper($key), ':' . ucfirst($key)],
                [$value, strtoupper((string) $value), ucfirst((string) $value)],
                $translation
            );
        }

        return $translation;
    }

    /**
     * Translate with pluralization.
     */
    public function choice(string $key, int $count, array $replace = [], ?string $lang = null): string
    {
        $lang = $lang ?? $this->getCurrentLang();
        $replace['count'] = $count;

        // Try file-based translation with pluralization
        if (str_contains($key, '.')) {
            $translation = $this->loadFromFile($key, $lang);
            
            if (is_array($translation)) {
                // Handle Laravel-style pluralization
                $selected = $this->selectPlural($translation, $count, $lang);
                return $this->makeReplacements($selected, $replace);
            }
            
            if ($translation !== null) {
                // Handle pipe-separated pluralization: "one item|:count items"
                return $this->handlePipePluralization($translation, $count, $replace);
            }
        }

        // Fallback to Laravel's trans_choice
        return trans_choice($key, $count, $replace, $lang);
    }

    /**
     * Select the appropriate plural form.
     */
    protected function selectPlural(array $options, int $count, string $lang): string
    {
        // Arabic has complex pluralization
        if ($lang === 'ar') {
            return $this->selectArabicPlural($options, $count);
        }

        // Russian has 3 forms
        if ($lang === 'ru') {
            return $this->selectRussianPlural($options, $count);
        }

        // Default: 1 = singular, else plural
        if ($count === 1) {
            return $options['one'] ?? $options[0] ?? (string) reset($options);
        }

        return $options['other'] ?? $options[1] ?? (string) end($options);
    }

    /**
     * Select Arabic plural form (6 forms).
     */
    protected function selectArabicPlural(array $options, int $count): string
    {
        if ($count === 0) {
            return $options['zero'] ?? $options['other'] ?? '';
        }
        if ($count === 1) {
            return $options['one'] ?? '';
        }
        if ($count === 2) {
            return $options['two'] ?? $options['few'] ?? '';
        }
        if ($count >= 3 && $count <= 10) {
            return $options['few'] ?? '';
        }
        if ($count >= 11 && $count <= 99) {
            return $options['many'] ?? '';
        }

        return $options['other'] ?? '';
    }

    /**
     * Select Russian plural form (3 forms).
     */
    protected function selectRussianPlural(array $options, int $count): string
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return $options['one'] ?? $options[0] ?? '';
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return $options['few'] ?? $options[1] ?? '';
        }

        return $options['many'] ?? $options['other'] ?? $options[2] ?? '';
    }

    /**
     * Handle pipe-separated pluralization.
     */
    protected function handlePipePluralization(string $translation, int $count, array $replace): string
    {
        $parts = explode('|', $translation);
        
        if (count($parts) === 1) {
            return $this->makeReplacements($translation, $replace);
        }

        $selected = $count === 1 ? $parts[0] : ($parts[1] ?? $parts[0]);
        
        return $this->makeReplacements(trim($selected), $replace);
    }

    /**
     * Get all translations for JavaScript.
     */
    public function getAllForJs(?string $lang = null, ?array $groups = null): array
    {
        $lang = $lang ?? $this->getCurrentLang();
        $groups = $groups ?? $this->getConfig()['javascript']['groups'] ?? ['common', 'validation', 'errors'];
        
        $cacheKey = "js_translations:{$lang}:" . md5(implode(',', $groups));
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($lang, $groups) {
            $translations = [];
            
            foreach ($groups as $group) {
                $path = base_path("lang/{$lang}/{$group}.php");
                
                if (File::exists($path)) {
                    $translations[$group] = require $path;
                } elseif ($lang !== 'en') {
                    // Fallback to English
                    $enPath = base_path("lang/en/{$group}.php");
                    if (File::exists($enPath)) {
                        $translations[$group] = require $enPath;
                    }
                }
            }

            return $translations;
        });
    }

    /**
     * Get JavaScript translations as JSON string.
     */
    public function getJsTranslationsJson(?string $lang = null): string
    {
        return json_encode($this->getAllForJs($lang), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Translate a model field value.
     */
    public function translateField(
        Model $model,
        string $field,
        ?string $lang = null
    ): ?string {
        $lang = $lang ?? $this->getCurrentLang();
        
        if ($lang === 'en') {
            return $model->$field;
        }

        $modelName = get_class($model);
        $name = "{$modelName},{$field}";

        $translation = Translation::forLang($lang)
            ->ofType(Translation::TYPE_MODEL)
            ->forName($name)
            ->forResource($model->getKey())
            ->forTenant($this->tenantId)
            ->value('value');

        return $translation ?: $model->$field;
    }

    /**
     * Translate a field label.
     */
    public function translateFieldLabel(
        string $entityName,
        string $fieldName,
        ?string $lang = null
    ): string {
        $lang = $lang ?? $this->getCurrentLang();
        
        $source = $this->humanize($fieldName);
        
        if ($lang === 'en') {
            return $source;
        }

        $name = "{$entityName},{$fieldName}";
        
        $translation = Translation::forLang($lang)
            ->ofType(Translation::TYPE_FIELD)
            ->forName($name)
            ->forTenant($this->tenantId)
            ->value('value');

        return $translation ?: $source;
    }

    /**
     * Translate selection options.
     */
    public function translateSelection(
        string $entityName,
        string $fieldName,
        array $options,
        ?string $lang = null
    ): array {
        $lang = $lang ?? $this->getCurrentLang();
        
        if ($lang === 'en') {
            return $options;
        }

        $translated = [];
        foreach ($options as $key => $label) {
            $name = "{$entityName},{$fieldName},{$key}";
            
            $translation = Translation::forLang($lang)
                ->ofType(Translation::TYPE_SELECTION)
                ->forName($name)
                ->forTenant($this->tenantId)
                ->value('value');

            $translated[$key] = $translation ?: $label;
        }

        return $translated;
    }

    /**
     * Translate a view definition.
     */
    public function translateView(array $viewDefinition, ?string $lang = null): array
    {
        $lang = $lang ?? $this->getCurrentLang();
        
        if ($lang === 'en') {
            return $viewDefinition;
        }

        return $this->translateViewRecursive($viewDefinition, $lang);
    }

    /**
     * Recursively translate view elements.
     */
    protected function translateViewRecursive(array $element, string $lang): array
    {
        // Translate label if present
        if (isset($element['label'])) {
            $element['label'] = $this->translateFromDatabase($element['label'], $lang, Translation::TYPE_VIEW);
        }

        // Translate placeholder if present
        if (isset($element['placeholder'])) {
            $element['placeholder'] = $this->translateFromDatabase($element['placeholder'], $lang, Translation::TYPE_VIEW);
        }

        // Translate help text if present
        if (isset($element['help'])) {
            $element['help'] = $this->translateFromDatabase($element['help'], $lang, Translation::TYPE_VIEW);
        }

        // Translate button text
        if (isset($element['text'])) {
            $element['text'] = $this->translateFromDatabase($element['text'], $lang, Translation::TYPE_VIEW);
        }

        // Recursively translate children
        if (isset($element['children']) && is_array($element['children'])) {
            foreach ($element['children'] as $i => $child) {
                $element['children'][$i] = $this->translateViewRecursive($child, $lang);
            }
        }

        // Translate fields
        if (isset($element['fields']) && is_array($element['fields'])) {
            foreach ($element['fields'] as $i => $field) {
                $element['fields'][$i] = $this->translateViewRecursive($field, $lang);
            }
        }

        // Translate columns
        if (isset($element['columns']) && is_array($element['columns'])) {
            foreach ($element['columns'] as $i => $column) {
                $element['columns'][$i] = $this->translateViewRecursive($column, $lang);
            }
        }

        return $element;
    }

    /**
     * Set a translation.
     */
    public function setTranslation(
        string $source,
        string $value,
        string $lang,
        string $type = Translation::TYPE_CODE,
        ?string $module = null,
        ?int $resId = null,
        ?string $name = null
    ): Translation {
        $data = [
            'type' => $type,
            'name' => $name ?? $source,
            'lang' => $lang,
            'source' => $source,
        ];

        if ($resId) {
            $data['res_id'] = $resId;
        }

        $translation = Translation::updateOrCreate(
            array_merge($data, ['tenant_id' => $this->tenantId]),
            [
                'value' => $value,
                'module' => $module,
                'state' => 'translated',
            ]
        );

        // Clear individual cache entry
        $this->clearCache($type, $source, $lang, $module);
        
        // Clear bulk cache for the language to ensure fresh data on next request
        $this->clearBulkCache($lang);

        return $translation;
    }

    /**
     * Set field translation.
     */
    public function setFieldTranslation(
        Model $model,
        string $field,
        string $value,
        string $lang
    ): Translation {
        $modelName = get_class($model);
        $name = "{$modelName},{$field}";
        
        return $this->setTranslation(
            $model->$field,
            $value,
            $lang,
            Translation::TYPE_MODEL,
            null,
            $model->getKey(),
            $name
        );
    }

    /**
     * Import translations from PO file.
     */
    public function importPo(string $filePath, string $lang, ?string $module = null): array
    {
        $content = file_get_contents($filePath);
        $translations = $this->parsePo($content);
        
        $imported = 0;
        $errors = [];

        foreach ($translations as $entry) {
            if (empty($entry['msgid']) || empty($entry['msgstr'])) {
                continue;
            }

            try {
                $this->setTranslation(
                    $entry['msgid'],
                    $entry['msgstr'],
                    $lang,
                    Translation::TYPE_CODE,
                    $module
                );
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Failed to import '{$entry['msgid']}': {$e->getMessage()}";
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * Parse PO file content.
     */
    protected function parsePo(string $content): array
    {
        $translations = [];
        $current = [];
        $lines = explode("\n", $content);
        $multilineKey = null;

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                if (!empty($current)) {
                    $translations[] = $current;
                    $current = [];
                }
                $multilineKey = null;
                continue;
            }

            // msgid
            if (str_starts_with($line, 'msgid ')) {
                $current['msgid'] = $this->extractPoString($line, 'msgid ');
                $multilineKey = 'msgid';
                continue;
            }

            // msgstr
            if (str_starts_with($line, 'msgstr ')) {
                $current['msgstr'] = $this->extractPoString($line, 'msgstr ');
                $multilineKey = 'msgstr';
                continue;
            }

            // Multiline continuation
            if (str_starts_with($line, '"') && $multilineKey) {
                $current[$multilineKey] .= $this->extractPoString($line, '');
            }
        }

        if (!empty($current)) {
            $translations[] = $current;
        }

        return $translations;
    }

    /**
     * Extract string from PO line.
     */
    protected function extractPoString(string $line, string $prefix): string
    {
        $str = substr($line, strlen($prefix));
        $str = trim($str, '"');
        
        // Unescape
        $str = str_replace(['\\n', '\\"', '\\\\'], ["\n", '"', '\\'], $str);
        
        return $str;
    }

    /**
     * Export translations to PO file.
     */
    public function exportPo(
        string $lang,
        ?string $module = null,
        ?string $type = null
    ): string {
        $query = Translation::forLang($lang)->forTenant($this->tenantId);
        
        if ($module) {
            $query->forModule($module);
        }
        
        if ($type) {
            $query->ofType($type);
        }

        $translations = $query->get();
        
        $output = [];
        $output[] = '# Translation file';
        $output[] = '# Language: ' . $lang;
        $output[] = '# Generated: ' . now()->toIso8601String();
        $output[] = 'msgid ""';
        $output[] = 'msgstr ""';
        $output[] = '"Content-Type: text/plain; charset=UTF-8\\n"';
        $output[] = '"Language: ' . $lang . '\\n"';
        $output[] = '';

        foreach ($translations as $translation) {
            $output[] = $this->formatPoEntry($translation);
        }

        return implode("\n", $output);
    }

    /**
     * Format a PO entry.
     */
    protected function formatPoEntry(Translation $translation): string
    {
        $lines = [];
        
        // Add comment with context
        $lines[] = "#. Type: {$translation->type}";
        if ($translation->name !== $translation->source) {
            $lines[] = "#. Name: {$translation->name}";
        }
        if ($translation->module) {
            $lines[] = "#. Module: {$translation->module}";
        }

        // msgid
        $lines[] = 'msgid ' . $this->escapePoString($translation->source);
        
        // msgstr
        $lines[] = 'msgstr ' . $this->escapePoString($translation->value ?? '');
        
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * Escape string for PO format.
     */
    protected function escapePoString(string $str): string
    {
        $str = str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], $str);
        return '"' . $str . '"';
    }

    /**
     * Get untranslated strings.
     */
    public function getUntranslated(
        string $lang,
        ?string $module = null,
        ?string $type = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = Translation::forLang($lang)
            ->forTenant($this->tenantId)
            ->where(function ($q) {
                $q->whereNull('value')
                    ->orWhere('value', '')
                    ->orWhere('state', 'to_translate');
            });
        
        if ($module) {
            $query->forModule($module);
        }
        
        if ($type) {
            $query->ofType($type);
        }

        return $query->get();
    }

    /**
     * Sync translations from source code/entities.
     */
    public function syncFromSource(string $module, array $sources): array
    {
        $created = 0;
        $updated = 0;
        $supportedLanguages = $this->getSupportedLanguages();

        foreach ($sources as $source) {
            foreach ($supportedLanguages as $lang => $info) {
                if ($lang === 'en') {
                    continue; // Skip source language
                }

                $existing = Translation::forLang($lang)
                    ->forModule($module)
                    ->where('source', $source['source'])
                    ->forTenant($this->tenantId)
                    ->first();

                if (!$existing) {
                    Translation::create([
                        'tenant_id' => $this->tenantId,
                        'type' => $source['type'] ?? Translation::TYPE_CODE,
                        'name' => $source['name'] ?? $source['source'],
                        'lang' => $lang,
                        'source' => $source['source'],
                        'value' => null,
                        'module' => $module,
                        'state' => 'to_translate',
                    ]);
                    $created++;
                } else {
                    // Update source if changed
                    if ($existing->source !== $source['source']) {
                        $existing->source = $source['source'];
                        $existing->state = 'to_translate';
                        $existing->save();
                        $updated++;
                    }
                }
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Bulk translate using callback (e.g., AI translation service).
     */
    public function bulkTranslate(
        string $lang,
        callable $translator,
        ?string $module = null,
        int $batchSize = 50
    ): array {
        $translated = 0;
        $errors = [];

        $untranslated = $this->getUntranslated($lang, $module);
        
        foreach ($untranslated->chunk($batchSize) as $batch) {
            $sources = $batch->pluck('source')->toArray();
            
            try {
                $translations = $translator($sources, $lang);
                
                foreach ($batch as $i => $record) {
                    if (isset($translations[$i]) && !empty($translations[$i])) {
                        $record->value = $translations[$i];
                        $record->state = 'translated';
                        $record->save();
                        $translated++;
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = "Batch translation failed: {$e->getMessage()}";
                Log::error('Bulk translation error', [
                    'lang' => $lang,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'translated' => $translated,
            'errors' => $errors,
        ];
    }

    /**
     * Clear translation cache.
     */
    public function clearCache(
        ?string $type = null,
        ?string $source = null,
        ?string $lang = null,
        ?string $module = null
    ): void {
        if ($type && $source && $lang) {
            $cacheKey = $this->getCacheKey($type, $source, $lang, $module);
            Cache::forget($cacheKey);
            unset($this->loadedTranslations[$cacheKey]);
        } else {
            // Clear all translation cache
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['translations'])->flush();
            }
            $this->loadedTranslations = [];
            $this->fileTranslations = [];
        }

        // Clear JS translations cache
        foreach ($this->getSupportedLanguages() as $lang => $info) {
            Cache::forget("js_translations:{$lang}:*");
        }
    }

    /**
     * Get cache key for a translation.
     */
    protected function getCacheKey(
        string $type,
        string $source,
        string $lang,
        ?string $module
    ): string {
        $key = self::CACHE_PREFIX . md5("{$type}:{$source}:{$lang}:{$module}:{$this->tenantId}");
        return $key;
    }

    /**
     * Convert snake_case to human-readable.
     */
    protected function humanize(string $str): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $str));
    }

    /**
     * Get translation statistics.
     */
    public function getStats(?string $module = null): array
    {
        $stats = [];
        $supportedLanguages = $this->getSupportedLanguages();

        foreach ($supportedLanguages as $lang => $info) {
            if ($lang === 'en') {
                continue;
            }

            $query = Translation::forLang($lang)->forTenant($this->tenantId);
            
            if ($module) {
                $query->forModule($module);
            }

            $total = (clone $query)->count();
            $translated = (clone $query)->where('state', 'translated')->count();
            $pending = $total - $translated;

            $stats[$lang] = [
                'name' => $info['name'],
                'native' => $info['native'],
                'rtl' => $info['rtl'] ?? false,
                'total' => $total,
                'translated' => $translated,
                'pending' => $pending,
                'percentage' => $total > 0 ? round(($translated / $total) * 100, 1) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Get available language files.
     */
    public function getAvailableFiles(?string $lang = null): array
    {
        $lang = $lang ?? $this->getCurrentLang();
        $path = base_path("lang/{$lang}");
        
        if (!File::isDirectory($path)) {
            return [];
        }

        $files = [];
        foreach (File::files($path) as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getFilenameWithoutExtension();
            }
        }

        return $files;
    }

    /**
     * Get all translations from a file group.
     */
    public function getGroup(string $group, ?string $lang = null): array
    {
        $lang = $lang ?? $this->getCurrentLang();
        
        $fileKey = "{$lang}.{$group}";
        if (!isset($this->fileTranslations[$fileKey])) {
            $this->fileTranslations[$fileKey] = $this->loadTranslationFile($group, $lang);
        }

        return $this->fileTranslations[$fileKey];
    }
}
