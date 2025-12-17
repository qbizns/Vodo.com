<?php

return [
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
