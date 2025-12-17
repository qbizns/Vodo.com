<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-Register Built-in Types
    |--------------------------------------------------------------------------
    |
    | When enabled, the system will automatically register all built-in field
    | types when the application boots. Disable this if you want to manually
    | control which field types are available.
    |
    */

    'auto_register_builtin' => true,

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Field Type API endpoints.
    |
    */

    'api' => [
        'prefix' => 'api/v1',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Field Configuration
    |--------------------------------------------------------------------------
    |
    | Default configuration values for field types.
    |
    */

    'defaults' => [
        // Default storage type for new field types
        'storage_type' => 'string',

        // Default capabilities
        'is_searchable' => false,
        'is_filterable' => false,
        'is_sortable' => false,
        'supports_default' => true,
        'supports_unique' => false,
        'supports_multiple' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for file and image field types.
    |
    */

    'uploads' => [
        // Default storage disk
        'disk' => env('FIELD_UPLOAD_DISK', 'public'),

        // Default paths
        'paths' => [
            'files' => 'uploads/files',
            'images' => 'uploads/images',
            'gallery' => 'uploads/gallery',
        ],

        // Default file size limits (in KB)
        'max_size' => [
            'file' => 10240,    // 10MB
            'image' => 5120,    // 5MB
            'gallery' => 5120,  // 5MB per image
        ],

        // Allowed file types
        'allowed_types' => [
            'file' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'zip', 'rar'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        ],

        // Image thumbnail configurations
        'thumbnails' => [
            ['name' => 'thumb', 'width' => 150, 'height' => 150, 'mode' => 'crop'],
            ['name' => 'medium', 'width' => 400, 'height' => 400, 'mode' => 'fit'],
            ['name' => 'large', 'width' => 800, 'height' => 800, 'mode' => 'fit'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Money Field Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for money/currency field types.
    |
    */

    'money' => [
        'default_currency' => env('DEFAULT_CURRENCY', 'USD'),
        'default_symbol' => env('DEFAULT_CURRENCY_SYMBOL', '$'),
        'decimal_places' => 2,
        'thousand_separator' => ',',
        'decimal_separator' => '.',
        'symbol_position' => 'before', // 'before' or 'after'
    ],

    /*
    |--------------------------------------------------------------------------
    | Date/Time Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for date and time field types.
    |
    */

    'datetime' => [
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
        'datetime_format' => 'Y-m-d H:i:s',
        'display_date_format' => 'M j, Y',
        'display_time_format' => 'g:i A',
        'display_datetime_format' => 'M j, Y g:i A',
        'timezone' => env('APP_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rich Text Editor Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for rich text/WYSIWYG field types.
    |
    */

    'richtext' => [
        'editor' => 'tiptap', // 'tiptap', 'tinymce', 'ckeditor'
        'features' => [
            'bold',
            'italic',
            'underline',
            'strike',
            'heading',
            'bulletList',
            'orderedList',
            'link',
            'image',
            'blockquote',
            'codeBlock',
            'horizontalRule',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Location/Map Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for location/GPS field types.
    |
    */

    'location' => [
        'map_provider' => 'leaflet', // 'google', 'mapbox', 'leaflet'
        'default_zoom' => 13,
        'default_center' => [
            'lat' => 0,
            'lng' => 0,
        ],
        'google_api_key' => env('GOOGLE_MAPS_API_KEY'),
        'mapbox_token' => env('MAPBOX_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    |
    | Default validation settings for field types.
    |
    */

    'validation' => [
        // Maximum string length for text fields
        'max_string_length' => 255,

        // Maximum text length for textarea fields
        'max_text_length' => 65535,

        // Maximum URL length
        'max_url_length' => 2048,

        // Phone number pattern
        'phone_pattern' => '/^[+]?[\d\s\-().]+$/',

        // Slug pattern
        'slug_pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Components
    |--------------------------------------------------------------------------
    |
    | Component names for frontend rendering. These are the default Vue/React
    | component names used for form and list display.
    |
    */

    'components' => [
        'form_prefix' => 'Field',      // e.g., FieldText, FieldNumber
        'list_prefix' => 'Field',      // e.g., FieldTextDisplay
        'list_suffix' => 'Display',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Hooks
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific event hooks for the field type system.
    |
    */

    'hooks' => [
        'field_type_registered' => true,
        'field_type_updated' => true,
        'field_type_unregistered' => true,
        'field_type_system_ready' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, additional debug information will be logged.
    |
    */

    'debug' => env('FIELD_TYPES_DEBUG', false),

];
