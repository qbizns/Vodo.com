<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    |
    | Configure database backup behavior including scheduling, retention,
    | and storage destination.
    |
    */

    'enabled' => env('BACKUP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Backup Schedule
    |--------------------------------------------------------------------------
    |
    | Cron expression for automatic backups.
    | Default: Daily at 2 AM
    |
    */

    'schedule' => env('BACKUP_SCHEDULE', '0 2 * * *'),

    /*
    |--------------------------------------------------------------------------
    | Retention Period
    |--------------------------------------------------------------------------
    |
    | Number of days to keep backup files before automatic cleanup.
    |
    */

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk to use for storing backups.
    | Recommended: s3 for production, local for development.
    |
    */

    'disk' => env('BACKUP_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Storage Path
    |--------------------------------------------------------------------------
    |
    | The path within the storage disk where backups will be stored.
    |
    */

    'path' => env('BACKUP_PATH', 'backups/database'),

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    |
    | Whether to encrypt backup files. Uses APP_KEY for encryption.
    |
    */

    'encrypt' => env('BACKUP_ENCRYPT', true),

    /*
    |--------------------------------------------------------------------------
    | Compression
    |--------------------------------------------------------------------------
    |
    | Whether to compress backup files using gzip.
    |
    */

    'compress' => env('BACKUP_COMPRESS', true),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure backup notifications.
    |
    */

    'notifications' => [
        'enabled' => env('BACKUP_NOTIFY', false),
        'channels' => ['mail', 'slack'],

        'mail' => [
            'to' => env('BACKUP_NOTIFY_EMAIL'),
        ],

        'slack' => [
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connections to Backup
    |--------------------------------------------------------------------------
    |
    | List of database connections to include in backups.
    | Default uses the primary database connection.
    |
    */

    'databases' => [
        env('DB_CONNECTION', 'mysql'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables to Exclude
    |--------------------------------------------------------------------------
    |
    | Tables to exclude from database backups.
    | Useful for large log tables or temporary data.
    |
    */

    'exclude_tables' => [
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
        'cache',
        'cache_locks',
        'jobs',
        'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Temporary Directory
    |--------------------------------------------------------------------------
    |
    | Directory for temporary backup files during creation.
    |
    */

    'temp_directory' => storage_path('backups/temp'),

];
