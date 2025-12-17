<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model used for author relationships.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Delete Records on Entity Unregister
    |--------------------------------------------------------------------------
    |
    | When an entity is unregistered (usually when a plugin is uninstalled),
    | should all records for that entity be deleted?
    |
    | Set to true for clean uninstalls, false to preserve data.
    |
    */
    'delete_records_on_unregister' => false,

    /*
    |--------------------------------------------------------------------------
    | Delete Terms on Taxonomy Unregister
    |--------------------------------------------------------------------------
    |
    | When a taxonomy is unregistered, should all terms be deleted?
    |
    */
    'delete_terms_on_unregister' => false,

    /*
    |--------------------------------------------------------------------------
    | API Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for entity API routes.
    |
    */
    'api_prefix' => 'api/v1/entities',

    /*
    |--------------------------------------------------------------------------
    | API Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to entity API routes.
    |
    */
    'api_middleware' => ['api', 'auth:sanctum'],

    /*
    |--------------------------------------------------------------------------
    | Admin Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for admin routes.
    |
    */
    'admin_prefix' => 'admin',

    /*
    |--------------------------------------------------------------------------
    | Admin Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to admin routes.
    |
    */
    'admin_middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings.
    |
    */
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Settings
    |--------------------------------------------------------------------------
    |
    | Settings for media/file upload fields.
    |
    */
    'media' => [
        'disk' => 'public',
        'path' => 'entity-uploads',
        'max_size' => 10240, // KB
        'allowed_types' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
            'video' => ['mp4', 'webm', 'mov'],
            'audio' => ['mp3', 'wav', 'ogg'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rich Text Editor
    |--------------------------------------------------------------------------
    |
    | Configuration for rich text fields.
    |
    */
    'richtext' => [
        'editor' => 'tiptap', // tiptap, tinymce, ckeditor
        'features' => [
            'bold', 'italic', 'underline', 'strike',
            'heading', 'bulletList', 'orderedList',
            'link', 'image', 'blockquote', 'codeBlock',
            'horizontalRule', 'table',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Slug Generation
    |--------------------------------------------------------------------------
    |
    | Settings for automatic slug generation.
    |
    */
    'slug' => [
        'max_length' => 100,
        'separator' => '-',
        'lowercase' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache configuration for entity definitions and taxonomies.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // seconds
        'prefix' => 'entity_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Date/Time Format
    |--------------------------------------------------------------------------
    |
    | Default formats for date and datetime fields.
    |
    */
    'formats' => [
        'date' => 'Y-m-d',
        'datetime' => 'Y-m-d H:i:s',
        'time' => 'H:i:s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Money/Currency Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for money fields.
    |
    */
    'money' => [
        'currency' => 'USD',
        'decimal_places' => 2,
        'decimal_separator' => '.',
        'thousands_separator' => ',',
    ],
];
