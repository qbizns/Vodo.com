<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | This array contains all languages supported by the platform.
    | Each language entry includes:
    | - name: English name of the language
    | - native: Native name of the language (in its own script)
    | - rtl: Whether the language is right-to-left
    | - flag: Optional flag emoji for UI display
    |
    */

    'supported_languages' => [
        // LTR Languages
        'en' => ['name' => 'English', 'native' => 'English', 'rtl' => false, 'flag' => '🇺🇸'],
        'fr' => ['name' => 'French', 'native' => 'Français', 'rtl' => false, 'flag' => '🇫🇷'],
        'de' => ['name' => 'German', 'native' => 'Deutsch', 'rtl' => false, 'flag' => '🇩🇪'],
        'es' => ['name' => 'Spanish', 'native' => 'Español', 'rtl' => false, 'flag' => '🇪🇸'],
        'pt' => ['name' => 'Portuguese', 'native' => 'Português', 'rtl' => false, 'flag' => '🇵🇹'],
        'it' => ['name' => 'Italian', 'native' => 'Italiano', 'rtl' => false, 'flag' => '🇮🇹'],
        'nl' => ['name' => 'Dutch', 'native' => 'Nederlands', 'rtl' => false, 'flag' => '🇳🇱'],
        'ru' => ['name' => 'Russian', 'native' => 'Русский', 'rtl' => false, 'flag' => '🇷🇺'],
        'uk' => ['name' => 'Ukrainian', 'native' => 'Українська', 'rtl' => false, 'flag' => '🇺🇦'],
        'pl' => ['name' => 'Polish', 'native' => 'Polski', 'rtl' => false, 'flag' => '🇵🇱'],
        'cs' => ['name' => 'Czech', 'native' => 'Čeština', 'rtl' => false, 'flag' => '🇨🇿'],
        'tr' => ['name' => 'Turkish', 'native' => 'Türkçe', 'rtl' => false, 'flag' => '🇹🇷'],
        'el' => ['name' => 'Greek', 'native' => 'Ελληνικά', 'rtl' => false, 'flag' => '🇬🇷'],
        'ro' => ['name' => 'Romanian', 'native' => 'Română', 'rtl' => false, 'flag' => '🇷🇴'],
        'hu' => ['name' => 'Hungarian', 'native' => 'Magyar', 'rtl' => false, 'flag' => '🇭🇺'],
        'sv' => ['name' => 'Swedish', 'native' => 'Svenska', 'rtl' => false, 'flag' => '🇸🇪'],
        'da' => ['name' => 'Danish', 'native' => 'Dansk', 'rtl' => false, 'flag' => '🇩🇰'],
        'fi' => ['name' => 'Finnish', 'native' => 'Suomi', 'rtl' => false, 'flag' => '🇫🇮'],
        'no' => ['name' => 'Norwegian', 'native' => 'Norsk', 'rtl' => false, 'flag' => '🇳🇴'],

        // Asian Languages (LTR)
        'zh' => ['name' => 'Chinese (Simplified)', 'native' => '简体中文', 'rtl' => false, 'flag' => '🇨🇳'],
        'zh_TW' => ['name' => 'Chinese (Traditional)', 'native' => '繁體中文', 'rtl' => false, 'flag' => '🇹🇼'],
        'ja' => ['name' => 'Japanese', 'native' => '日本語', 'rtl' => false, 'flag' => '🇯🇵'],
        'ko' => ['name' => 'Korean', 'native' => '한국어', 'rtl' => false, 'flag' => '🇰🇷'],
        'vi' => ['name' => 'Vietnamese', 'native' => 'Tiếng Việt', 'rtl' => false, 'flag' => '🇻🇳'],
        'th' => ['name' => 'Thai', 'native' => 'ไทย', 'rtl' => false, 'flag' => '🇹🇭'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'rtl' => false, 'flag' => '🇮🇩'],
        'ms' => ['name' => 'Malay', 'native' => 'Bahasa Melayu', 'rtl' => false, 'flag' => '🇲🇾'],
        'tl' => ['name' => 'Filipino', 'native' => 'Filipino', 'rtl' => false, 'flag' => '🇵🇭'],
        'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी', 'rtl' => false, 'flag' => '🇮🇳'],
        'bn' => ['name' => 'Bengali', 'native' => 'বাংলা', 'rtl' => false, 'flag' => '🇧🇩'],
        'ta' => ['name' => 'Tamil', 'native' => 'தமிழ்', 'rtl' => false, 'flag' => '🇮🇳'],
        'te' => ['name' => 'Telugu', 'native' => 'తెలుగు', 'rtl' => false, 'flag' => '🇮🇳'],
        'mr' => ['name' => 'Marathi', 'native' => 'मराठी', 'rtl' => false, 'flag' => '🇮🇳'],
        'gu' => ['name' => 'Gujarati', 'native' => 'ગુજરાતી', 'rtl' => false, 'flag' => '🇮🇳'],
        'kn' => ['name' => 'Kannada', 'native' => 'ಕನ್ನಡ', 'rtl' => false, 'flag' => '🇮🇳'],
        'ml' => ['name' => 'Malayalam', 'native' => 'മലയാളം', 'rtl' => false, 'flag' => '🇮🇳'],
        'pa' => ['name' => 'Punjabi', 'native' => 'ਪੰਜਾਬੀ', 'rtl' => false, 'flag' => '🇮🇳'],
        'ne' => ['name' => 'Nepali', 'native' => 'नेपाली', 'rtl' => false, 'flag' => '🇳🇵'],
        'si' => ['name' => 'Sinhala', 'native' => 'සිංහල', 'rtl' => false, 'flag' => '🇱🇰'],
        'my' => ['name' => 'Burmese', 'native' => 'မြန်မာဘာသာ', 'rtl' => false, 'flag' => '🇲🇲'],
        'km' => ['name' => 'Khmer', 'native' => 'ភាសាខ្មែរ', 'rtl' => false, 'flag' => '🇰🇭'],
        'lo' => ['name' => 'Lao', 'native' => 'ລາວ', 'rtl' => false, 'flag' => '🇱🇦'],

        // RTL Languages
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'rtl' => true, 'flag' => '🇸🇦'],
        'he' => ['name' => 'Hebrew', 'native' => 'עברית', 'rtl' => true, 'flag' => '🇮🇱'],
        'fa' => ['name' => 'Persian', 'native' => 'فارسی', 'rtl' => true, 'flag' => '🇮🇷'],
        'ur' => ['name' => 'Urdu', 'native' => 'اردو', 'rtl' => true, 'flag' => '🇵🇰'],
        'ps' => ['name' => 'Pashto', 'native' => 'پښتو', 'rtl' => true, 'flag' => '🇦🇫'],
        'sd' => ['name' => 'Sindhi', 'native' => 'سنڌي', 'rtl' => true, 'flag' => '🇵🇰'],
        'ku' => ['name' => 'Kurdish', 'native' => 'کوردی', 'rtl' => true, 'flag' => '🇮🇶'],
        'yi' => ['name' => 'Yiddish', 'native' => 'ייִדיש', 'rtl' => true, 'flag' => '🇮🇱'],

        // African Languages
        'sw' => ['name' => 'Swahili', 'native' => 'Kiswahili', 'rtl' => false, 'flag' => '🇰🇪'],
        'am' => ['name' => 'Amharic', 'native' => 'አማርኛ', 'rtl' => false, 'flag' => '🇪🇹'],
        'zu' => ['name' => 'Zulu', 'native' => 'isiZulu', 'rtl' => false, 'flag' => '🇿🇦'],
        'af' => ['name' => 'Afrikaans', 'native' => 'Afrikaans', 'rtl' => false, 'flag' => '🇿🇦'],
    ],

    /*
    |--------------------------------------------------------------------------
    | RTL Languages
    |--------------------------------------------------------------------------
    |
    | Quick lookup array for RTL languages.
    |
    */

    'rtl_languages' => ['ar', 'he', 'fa', 'ur', 'ps', 'sd', 'ku', 'yi'],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale to use when no user preference is set.
    |
    */

    'default_locale' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The locale to use when a translation is not available.
    |
    */

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure translation caching for better performance.
    |
    */

    'cache' => [
        'enabled' => env('I18N_CACHE_ENABLED', true),
        'ttl' => env('I18N_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'i18n:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Sources Priority
    |--------------------------------------------------------------------------
    |
    | Define the priority order for translation sources.
    | Higher priority sources override lower priority ones.
    | Options: 'database', 'files'
    |
    */

    'source_priority' => ['database', 'files'],

    /*
    |--------------------------------------------------------------------------
    | Auto-detect Locale
    |--------------------------------------------------------------------------
    |
    | Whether to auto-detect user's locale from browser headers.
    |
    */

    'auto_detect' => [
        'enabled' => true,
        'sources' => ['session', 'cookie', 'user', 'browser'],
    ],

    /*
    |--------------------------------------------------------------------------
    | JavaScript Translations
    |--------------------------------------------------------------------------
    |
    | Configuration for JavaScript translation exports.
    |
    */

    'javascript' => [
        'enabled' => true,
        'groups' => ['common', 'validation', 'errors'], // Groups to export to JS
        'cache_key' => 'js_translations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Date and Number Formatting
    |--------------------------------------------------------------------------
    |
    | Locale-specific formatting options.
    |
    */

    'formatting' => [
        'date_format' => [
            'en' => 'M d, Y',
            'de' => 'd.m.Y',
            'fr' => 'd/m/Y',
            'ar' => 'Y/m/d',
            'ja' => 'Y年m月d日',
            'zh' => 'Y年m月d日',
            'ko' => 'Y년 m월 d일',
        ],
        'time_format' => [
            'en' => 'h:i A',
            'de' => 'H:i',
            'fr' => 'H:i',
            'ar' => 'H:i',
            'ja' => 'H:i',
        ],
        'number_format' => [
            'decimal_separator' => [
                'en' => '.',
                'de' => ',',
                'fr' => ',',
                'ar' => '٫',
            ],
            'thousands_separator' => [
                'en' => ',',
                'de' => '.',
                'fr' => ' ',
                'ar' => '٬',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pluralization Rules
    |--------------------------------------------------------------------------
    |
    | Custom pluralization rules for complex languages.
    |
    */

    'pluralization' => [
        // Arabic has 6 plural forms
        'ar' => 'arabic',
        // Russian has 3 plural forms
        'ru' => 'russian',
        // Default is 2 forms (singular/plural)
        'default' => 'default',
    ],

];
