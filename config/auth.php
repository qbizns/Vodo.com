<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'console' => [
            'driver' => 'session',
            'provider' => 'console_users',
        ],

        'owner' => [
            'driver' => 'session',
            'provider' => 'owners',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],

        'client' => [
            'driver' => 'session',
            'provider' => 'clients',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        'console_users' => [
            'driver' => 'eloquent',
            'model' => App\Modules\Console\Models\ConsoleUser::class,
        ],

        'owners' => [
            'driver' => 'eloquent',
            'model' => App\Modules\Owner\Models\Owner::class,
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Modules\Admin\Models\Admin::class,
        ],

        'clients' => [
            'driver' => 'eloquent',
            'model' => App\Modules\ClientArea\Models\Client::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        'console_users' => [
            'provider' => 'console_users',
            'table' => 'console_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'owners' => [
            'provider' => 'owners',
            'table' => 'owner_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'admins' => [
            'provider' => 'admins',
            'table' => 'admin_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'clients' => [
            'provider' => 'clients',
            'table' => 'client_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
