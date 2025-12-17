<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Record Rules Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the row-level security system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Enable/disable record rules globally.
    |
    */
    'enabled' => env('RECORD_RULES_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Deny
    |--------------------------------------------------------------------------
    |
    | When true, records without any applicable rules are hidden.
    | When false, records without rules are accessible (permissive).
    |
    */
    'default_deny' => false,

    /*
    |--------------------------------------------------------------------------
    | Superuser Roles
    |--------------------------------------------------------------------------
    |
    | Roles that bypass all record rules.
    |
    */
    'superuser_roles' => [
        'admin',
        'superuser',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Cache settings for record rules.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 300, // 5 minutes
        'prefix' => 'record_rules:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log access denied events.
    |
    */
    'logging' => [
        'enabled' => false,
        'channel' => 'security',
    ],

    /*
    |--------------------------------------------------------------------------
    | Built-in Operators
    |--------------------------------------------------------------------------
    |
    | Enable/disable built-in domain operators.
    |
    */
    'operators' => [
        'child_of' => true,
        'parent_of' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain Syntax Examples
    |--------------------------------------------------------------------------
    |
    | Reference for domain syntax (for documentation):
    |
    | Simple comparisons:
    |   ['field', '=', 'value']
    |   ['field', '!=', 'value']
    |   ['field', '>', 10]
    |   ['field', '<', 100]
    |   ['field', 'in', ['a', 'b', 'c']]
    |   ['field', 'not in', ['x', 'y']]
    |   ['field', 'like', '%search%']
    |   ['field', 'is null', true]
    |
    | Dynamic values (resolved at runtime):
    |   ['user_id', '=', '{user.id}']
    |   ['company_id', '=', '{user.company_id}']
    |   ['team_id', 'in', '{user.team_ids}']
    |
    */
];
