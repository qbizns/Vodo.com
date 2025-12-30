<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Performance & Safety Configuration
    |--------------------------------------------------------------------------
    |
    | Phase 1, Task 1.2: Query Pagination Enforcement
    |
    | These settings prevent unbounded queries that could exhaust server
    | memory when dealing with large datasets (millions of records).
    |
    */
    'query' => [
        // Enforce automatic limit on queries without explicit limit
        'enforce_limit' => env('QUERY_ENFORCE_LIMIT', true),

        // Maximum records returned when no limit is specified
        'max_limit' => env('QUERY_MAX_LIMIT', 1000),

        // Default records per page for pagination
        'default_per_page' => env('QUERY_DEFAULT_PER_PAGE', 15),

        // Maximum records per page (prevents abuse via ?per_page=999999)
        'max_per_page' => env('QUERY_MAX_PER_PAGE', 100),

        // Maximum chunk size for batch processing
        'max_chunk_size' => env('QUERY_MAX_CHUNK_SIZE', 1000),

        // Log queries that would have returned more than this many records
        'log_large_queries' => env('QUERY_LOG_LARGE', true),
        'large_query_threshold' => env('QUERY_LARGE_THRESHOLD', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Phase 1, Task 1.4: Cache Key Tenant Isolation
    |
    | These settings control registry caching behavior with proper
    | tenant isolation to prevent cache data leaking between tenants.
    |
    */
    'cache' => [
        // Enable/disable registry caching globally
        'enabled' => env('PLATFORM_CACHE_ENABLED', true),

        // Default TTL for registry caches (in seconds)
        'default_ttl' => env('PLATFORM_CACHE_TTL', 3600),

        // TTL overrides per registry
        'ttl' => [
            'entity_registry' => env('ENTITY_CACHE_TTL', 3600),
            'view_registry' => env('VIEW_CACHE_TTL', 3600),
            'permission_registry' => env('PERMISSION_CACHE_TTL', 1800),
            'field_type_registry' => env('FIELD_TYPE_CACHE_TTL', 7200),
            'menu_registry' => env('MENU_CACHE_TTL', 3600),
        ],

        // Enable tenant isolation in cache keys
        'tenant_isolation' => env('CACHE_TENANT_ISOLATION', true),

        // Cache key prefix for all platform caches
        'prefix' => env('PLATFORM_CACHE_PREFIX', 'vodo:'),

        // Use cache tags if supported by driver (Redis, Memcached)
        'use_tags' => env('CACHE_USE_TAGS', true),

        // Log cache hits/misses for debugging
        'log_operations' => env('CACHE_LOG_OPERATIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Phase 2, Task 2.2: Hook Circuit Breaker
    |
    | The circuit breaker pattern prevents cascading failures by automatically
    | disabling hooks that fail repeatedly, protecting the application from
    | misbehaving plugins.
    |
    */
    'circuit_breaker' => [
        // Enable circuit breaker for hooks
        'enabled' => env('CIRCUIT_BREAKER_ENABLED', true),

        // Number of failures before opening the circuit
        'failure_threshold' => env('CIRCUIT_BREAKER_THRESHOLD', 5),

        // Seconds to wait before attempting recovery
        'recovery_timeout' => env('CIRCUIT_BREAKER_RECOVERY', 300),

        // Number of successes in half-open state to close circuit
        'success_threshold' => env('CIRCUIT_BREAKER_SUCCESS_THRESHOLD', 2),

        // Window in seconds for counting failures
        'failure_window' => env('CIRCUIT_BREAKER_WINDOW', 60),

        // Log circuit state changes
        'log_state_changes' => env('CIRCUIT_BREAKER_LOG', true),

        // Hooks that should never be circuit-broken (critical)
        'excluded_hooks' => [
            'plugin_activated',
            'plugin_deactivated',
            'plugins_loaded',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Autoloader Configuration
    |--------------------------------------------------------------------------
    |
    | Phase 2, Task 2.3: Central Plugin Autoloader
    |
    | Settings for the centralized plugin class autoloader that replaces
    | per-plugin SPL autoload callbacks for better performance.
    |
    */
    'autoloader' => [
        // Enable central autoloader (vs per-plugin SPL)
        'centralized' => env('PLUGIN_AUTOLOADER_CENTRALIZED', true),

        // Cache autoloader mappings
        'cache_mappings' => env('PLUGIN_AUTOLOADER_CACHE', true),

        // Mapping cache TTL in seconds
        'cache_ttl' => env('PLUGIN_AUTOLOADER_CACHE_TTL', 3600),

        // Log class loading for debugging
        'log_loading' => env('PLUGIN_AUTOLOADER_LOG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Bus Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for inter-plugin communication.
    |
    */
    'plugin_bus' => [
        'enabled' => true,
        'async_queue' => env('PLUGIN_BUS_QUEUE', 'default'),
        'logging' => env('PLUGIN_BUS_LOG', true),
        'strict_dependencies' => env('PLUGIN_BUS_STRICT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the workflow/state machine system.
    |
    */
    'workflow' => [
        'enabled' => true,
        
        // Automatically initialize workflows on record creation
        'auto_initialize' => true,
        
        // Log all transitions
        'log_transitions' => true,
        
        // Keep history for this many days (0 = forever)
        'history_retention_days' => 0,
        
        // Maximum transition depth (prevent infinite loops)
        'max_transition_depth' => 10,
        
        // Publish events to plugin bus
        'publish_events' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | View Registry Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the declarative view system.
    |
    */
    'views' => [
        'enabled' => true,
        
        // Cache compiled views
        'cache' => env('VIEW_REGISTRY_CACHE', true),
        'cache_ttl' => 3600,
        
        // Auto-generate default views from entity definitions
        'auto_generate' => true,
        
        // Default widget mappings
        'default_widgets' => [
            'string' => 'char',
            'text' => 'text',
            'integer' => 'integer',
            'decimal' => 'float',
            'boolean' => 'checkbox',
            'date' => 'date',
            'datetime' => 'datetime',
            'select' => 'selection',
            'relation' => 'many2one',
            'file' => 'binary',
            'image' => 'image',
            'money' => 'monetary',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Computed Fields Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for computed fields and on-change handlers.
    |
    */
    'computed_fields' => [
        'enabled' => true,
        
        // Automatically compute on save
        'auto_compute' => true,
        
        // Store computed values by default
        'default_store' => false,
        
        // Log computation errors
        'log_errors' => true,
        
        // Maximum dependency chain depth
        'max_dependency_depth' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for multi-tenant data isolation.
    |
    */
    'tenant' => [
        'enabled' => env('TENANT_ENABLED', true),
        
        // Default tenant column
        'column' => 'tenant_id',
        
        // Allow null tenant (global records)
        'allow_null' => false,
        
        // Resolve tenant from user
        'resolve_from' => 'tenant_id', // User attribute
        
        // Multi-company support
        'multi_company' => [
            'enabled' => false,
            'column' => 'company_id',
        ],
        
        // Branch-level isolation
        'branch' => [
            'enabled' => false,
            'column' => 'branch_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Template Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the document template system.
    |
    */
    'documents' => [
        'enabled' => true,
        
        // Default PDF library (dompdf, snappy, browsershot)
        'pdf_driver' => env('PDF_DRIVER', 'dompdf'),
        
        // Storage path for generated documents
        'storage_path' => 'documents',
        
        // Keep generated documents for this many days
        'retention_days' => 30,
        
        // Default page settings
        'defaults' => [
            'paper' => 'A4',
            'orientation' => 'portrait',
            'margin' => [
                'top' => '20mm',
                'right' => '15mm',
                'bottom' => '20mm',
                'left' => '15mm',
            ],
        ],
        
        // Company information for templates
        'company' => [
            'name' => env('COMPANY_NAME', config('app.name')),
            'address' => env('COMPANY_ADDRESS', ''),
            'phone' => env('COMPANY_PHONE', ''),
            'email' => env('COMPANY_EMAIL', ''),
            'logo' => env('COMPANY_LOGO', ''),
            'website' => env('COMPANY_WEBSITE', ''),
            'vat_number' => env('COMPANY_VAT', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity/Chatter Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for activities and message threads.
    |
    */
    'activity' => [
        'enabled' => true,
        
        // Send email notifications for mentions
        'notify_mentions' => true,
        
        // Send reminders for overdue activities
        'send_reminders' => true,
        'reminder_hours_before' => 24,
        
        // Track field changes
        'track_changes' => true,
        
        // Message retention (0 = forever)
        'message_retention_days' => 0,
        
        // Max attachments per message
        'max_attachments' => 10,
        'max_attachment_size' => 10 * 1024 * 1024, // 10MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Record Rules Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for row-level security.
    |
    */
    'record_rules' => [
        'enabled' => env('RECORD_RULES_ENABLED', true),
        
        // Default behavior when no rules exist
        'default_deny' => false,
        
        // Cache rules (per user, per entity)
        'cache' => true,
        'cache_ttl' => 300, // 5 minutes
        
        // Superuser roles (bypass all rules)
        'superuser_roles' => ['admin', 'superuser'],
        
        // Log access denials
        'log_denials' => true,
    ],
];
