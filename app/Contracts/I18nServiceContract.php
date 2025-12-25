<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for Internationalization Service.
 *
 * Manages translations, locales, and content localization.
 */
interface I18nServiceContract
{
    /**
     * Register translations for a namespace.
     *
     * @param string $namespace Translation namespace
     * @param string $locale Locale code
     * @param array $translations Key-value translations
     * @param string|null $pluginSlug Owner plugin
     * @return self
     */
    public function register(string $namespace, string $locale, array $translations, ?string $pluginSlug = null): self;

    /**
     * Get a translation.
     *
     * @param string $key Translation key (namespace.key)
     * @param array $replace Replacement values
     * @param string|null $locale Override locale
     * @return string
     */
    public function translate(string $key, array $replace = [], ?string $locale = null): string;

    /**
     * Check if translation exists.
     *
     * @param string $key Translation key
     * @param string|null $locale Locale
     * @return bool
     */
    public function has(string $key, ?string $locale = null): bool;

    /**
     * Get all translations for a namespace.
     *
     * @param string $namespace Namespace
     * @param string|null $locale Locale
     * @return array
     */
    public function getNamespace(string $namespace, ?string $locale = null): array;

    /**
     * Get available locales.
     *
     * @return Collection
     */
    public function getLocales(): Collection;

    /**
     * Set the current locale.
     *
     * @param string $locale Locale code
     * @return void
     */
    public function setLocale(string $locale): void;

    /**
     * Get the current locale.
     *
     * @return string
     */
    public function getLocale(): string;

    /**
     * Register a new locale.
     *
     * @param string $code Locale code
     * @param array $config Locale configuration
     * @return self
     */
    public function registerLocale(string $code, array $config): self;

    /**
     * Get translated content for an entity field.
     *
     * @param string $entityName Entity name
     * @param int $recordId Record ID
     * @param string $field Field name
     * @param string|null $locale Locale
     * @return string|null
     */
    public function getFieldTranslation(string $entityName, int $recordId, string $field, ?string $locale = null): ?string;

    /**
     * Set translated content for an entity field.
     *
     * @param string $entityName Entity name
     * @param int $recordId Record ID
     * @param string $field Field name
     * @param string $value Translated value
     * @param string|null $locale Locale
     * @return void
     */
    public function setFieldTranslation(string $entityName, int $recordId, string $field, string $value, ?string $locale = null): void;

    /**
     * Export translations.
     *
     * @param string|null $namespace Filter by namespace
     * @param string|null $locale Filter by locale
     * @return array
     */
    public function export(?string $namespace = null, ?string $locale = null): array;

    /**
     * Import translations.
     *
     * @param array $translations Translations to import
     * @param bool $overwrite Overwrite existing
     * @return int Number of translations imported
     */
    public function import(array $translations, bool $overwrite = false): int;
}
