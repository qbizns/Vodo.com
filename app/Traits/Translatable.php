<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Translation\TranslationService;
use Illuminate\Support\Facades\App;

/**
 * Translatable Trait - Adds translation support to Eloquent models.
 * 
 * Usage:
 * 1. Use the trait in your model
 * 2. Define $translatable array with field names
 * 3. Call $model->translated('field_name') to get translation
 */
trait Translatable
{
    /**
     * Get translatable fields.
     */
    public function getTranslatableFields(): array
    {
        return $this->translatable ?? [];
    }

    /**
     * Get translated value for a field.
     */
    public function translated(string $field, ?string $lang = null): ?string
    {
        if (!in_array($field, $this->getTranslatableFields())) {
            return $this->$field;
        }

        /** @var TranslationService $translator */
        $translator = App::make(TranslationService::class);
        
        return $translator->translateField($this, $field, $lang);
    }

    /**
     * Set translation for a field.
     */
    public function setTranslation(string $field, string $value, string $lang): void
    {
        if (!in_array($field, $this->getTranslatableFields())) {
            throw new \InvalidArgumentException("Field '{$field}' is not translatable");
        }

        /** @var TranslationService $translator */
        $translator = App::make(TranslationService::class);
        
        $translator->setFieldTranslation($this, $field, $value, $lang);
    }

    /**
     * Get all translations for a field.
     */
    public function getTranslations(string $field): array
    {
        /** @var TranslationService $translator */
        $translator = App::make(TranslationService::class);
        
        $languages = $translator->getSupportedLanguages();
        $translations = [];

        foreach ($languages as $code => $name) {
            $translations[$code] = $this->translated($field, $code);
        }

        return $translations;
    }

    /**
     * Set multiple translations for a field.
     */
    public function setTranslations(string $field, array $translations): void
    {
        foreach ($translations as $lang => $value) {
            if (!empty($value)) {
                $this->setTranslation($field, $value, $lang);
            }
        }
    }

    /**
     * Override getAttribute to auto-translate.
     */
    public function getAttributeValue($key)
    {
        $value = parent::getAttributeValue($key);
        
        // Auto-translate if accessing a translatable field
        if (in_array($key, $this->getTranslatableFields())) {
            $lang = App::getLocale();
            if ($lang !== 'en') {
                $translated = $this->translated($key, $lang);
                if ($translated !== null) {
                    return $translated;
                }
            }
        }

        return $value;
    }

    /**
     * Get raw (untranslated) attribute value.
     */
    public function getRawAttribute(string $key): mixed
    {
        return parent::getAttributeValue($key);
    }
}
