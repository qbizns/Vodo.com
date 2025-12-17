<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integrate with Laravel Scheduler
    |--------------------------------------------------------------------------
    |
    | When enabled, the plugin scheduler will be automatically registered
    | with Laravel's built-in scheduler to run every minute.
    |
    */

    'integrate_laravel_scheduler' => true,

    /*
    |--------------------------------------------------------------------------
    | Check Frequency
    |--------------------------------------------------------------------------
    |
    | How often Laravel's scheduler should check for due tasks.
    | Options: everyMinute, everyFiveMinutes, everyTenMinutes
    |
    */

    'check_frequency' => 'everyMinute',

    /*
    |--------------------------------------------------------------------------
    | Default Timezone
    |--------------------------------------------------------------------------
    |
    | Default timezone for scheduled tasks.
    |
    */

    'timezone' => env('SCHEDULER_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Log Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep task execution logs (in days).
    |
    */

    'log_retention_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Max Concurrent Tasks
    |--------------------------------------------------------------------------
    |
    | Maximum number of tasks that can run simultaneously.
    |
    */

    'max_concurrent_tasks' => 10,

    /*
    |--------------------------------------------------------------------------
    | Default Task Settings
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'max_attempts' => 3,
        'retry_delay' => 60, // seconds
        'expires_after' => 60, // minutes for overlap lock
        'without_overlapping' => false,
        'run_in_background' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Dispatcher Settings
    |--------------------------------------------------------------------------
    */

    'events' => [
        'default_queue' => 'default',
        'async_by_default' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */

    'api' => [
        'prefix' => 'api/v1/scheduler',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Cron Expressions
    |--------------------------------------------------------------------------
    |
    | Named expressions for common schedules.
    |
    */

    'expressions' => [
        'every_minute' => '* * * * *',
        'every_five_minutes' => '*/5 * * * *',
        'every_ten_minutes' => '*/10 * * * *',
        'every_fifteen_minutes' => '*/15 * * * *',
        'every_thirty_minutes' => '*/30 * * * *',
        'hourly' => '0 * * * *',
        'daily' => '0 0 * * *',
        'daily_at_noon' => '0 12 * * *',
        'weekly' => '0 0 * * 0',
        'monthly' => '0 0 1 * *',
        'quarterly' => '0 0 1 */3 *',
        'yearly' => '0 0 1 1 *',
    ],

];
