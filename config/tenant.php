<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the multi-tenant data isolation system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Enable/disable multi-tenant features globally.
    |
    */
    'enabled' => env('TENANT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Tenant Column
    |--------------------------------------------------------------------------
    |
    | The default column name used for tenant identification.
    |
    */
    'default_column' => 'tenant_id',

    /*
    |--------------------------------------------------------------------------
    | Company Column
    |--------------------------------------------------------------------------
    |
    | The column name used for company-level isolation.
    |
    */
    'company_column' => 'company_id',

    /*
    |--------------------------------------------------------------------------
    | Branch Column
    |--------------------------------------------------------------------------
    |
    | The column name used for branch-level isolation.
    |
    */
    'branch_column' => 'branch_id',

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution
    |--------------------------------------------------------------------------
    |
    | How to resolve the current tenant.
    |
    */
    'resolution' => [
        // Primary: from authenticated user
        'from_user' => true,
        'user_field' => 'tenant_id',

        // Fallback: from request header
        'from_header' => false,
        'header_name' => 'X-Tenant-ID',

        // Fallback: from subdomain
        'from_subdomain' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allow Null Tenant
    |--------------------------------------------------------------------------
    |
    | When true, records without a tenant ID are allowed.
    | This is useful for global/shared data.
    |
    */
    'allow_null' => false,

    /*
    |--------------------------------------------------------------------------
    | Super Tenants
    |--------------------------------------------------------------------------
    |
    | Tenant IDs that have access to all data (global admins).
    |
    */
    'super_tenants' => [],

    /*
    |--------------------------------------------------------------------------
    | Shared Entities
    |--------------------------------------------------------------------------
    |
    | Entities that are shared across tenants (no tenant filtering).
    |
    */
    'shared_entities' => [
        // 'countries',
        // 'currencies',
        // 'product_categories',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cross-Tenant Access
    |--------------------------------------------------------------------------
    |
    | Rules for allowing access to records from other tenants.
    |
    */
    'cross_tenant_access' => [
        'enabled' => false,
        'permission' => 'access_all_tenants',
    ],
];
