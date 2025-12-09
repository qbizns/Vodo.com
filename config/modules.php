<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Configuration
    |--------------------------------------------------------------------------
    |
    | Define all modules and their subdomain mappings here.
    |
    */

    'modules' => [
        'Console' => [
            'subdomain' => 'console',
            'guard' => 'console',
        ],
        'Owner' => [
            'subdomain' => 'owner',
            'guard' => 'owner',
        ],
        'Admin' => [
            'subdomain' => 'admin',
            'guard' => 'admin',
        ],
        'ClientArea' => [
            'subdomain' => 'client-area',
            'guard' => 'client',
        ],
        'Frontend' => [
            'subdomain' => null, // Main domain (vodo.com)
            'guard' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Base Domain
    |--------------------------------------------------------------------------
    |
    | The base domain for subdomain routing.
    |
    */

    'domain' => env('APP_DOMAIN', 'vodo.com'),
];

