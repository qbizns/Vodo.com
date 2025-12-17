<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Import/Export Configuration
    |--------------------------------------------------------------------------
    |
    | Configure data import and export settings.
    |
    */

    // Default settings
    'defaults' => [
        'batch_size' => 100,
        'duplicate_mode' => 'skip', // skip, update, error
        'allow_partial' => false,
        'format' => 'csv',
    ],

    // Storage settings
    'storage' => [
        'disk' => env('IMPORT_EXPORT_DISK', 'local'),
        'import_path' => 'imports',
        'export_path' => 'exports',
        'template_path' => 'templates',
        'export_expiry_hours' => 24,
    ],

    // Import/Export mappings
    'mappings' => [
        // Example: Customer import mapping
        // 'customers' => [
        //     'model' => \App\Models\Customer::class,
        //     'unique' => ['email'],
        //     'duplicate_mode' => 'update',
        //     'fields' => [
        //         'name' => [
        //             'column' => 'Customer Name',
        //             'required' => true,
        //         ],
        //         'email' => [
        //             'column' => 'Email',
        //             'required' => true,
        //             'rules' => 'email',
        //         ],
        //         'phone' => [
        //             'column' => 'Phone',
        //         ],
        //         'country_id' => [
        //             'column' => 'Country',
        //             'type' => 'relation',
        //             'model' => \App\Models\Country::class,
        //             'match' => 'name',
        //         ],
        //     ],
        // ],
    ],

    // File size limits
    'limits' => [
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'max_rows' => 10000,
    ],

    // Supported formats
    'formats' => [
        'csv' => [
            'extension' => 'csv',
            'mime_type' => 'text/csv',
            'delimiter' => ',',
            'enclosure' => '"',
        ],
        'json' => [
            'extension' => 'json',
            'mime_type' => 'application/json',
        ],
        'xlsx' => [
            'extension' => 'xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'requires' => 'phpoffice/phpspreadsheet',
        ],
    ],

    // Queue settings for large imports
    'queue' => [
        'enabled' => env('IMPORT_QUEUE_ENABLED', false),
        'connection' => env('IMPORT_QUEUE_CONNECTION', 'default'),
        'queue' => env('IMPORT_QUEUE_NAME', 'imports'),
        'threshold' => 500, // Use queue for files with more than X rows
    ],

    // Notification settings
    'notifications' => [
        'on_complete' => true,
        'on_error' => true,
        'channels' => ['mail', 'database'],
    ],
];
