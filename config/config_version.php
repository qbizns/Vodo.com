<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration Version Control
    |--------------------------------------------------------------------------
    |
    | Settings for the Git-like configuration version control system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Version Control
    |--------------------------------------------------------------------------
    |
    | Global toggle for configuration versioning.
    |
    */
    'enabled' => env('CONFIG_VERSION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Branch
    |--------------------------------------------------------------------------
    |
    | The default branch name for configurations.
    |
    */
    'default_branch' => env('CONFIG_VERSION_DEFAULT_BRANCH', 'main'),

    /*
    |--------------------------------------------------------------------------
    | Require Review
    |--------------------------------------------------------------------------
    |
    | Whether configurations require review before promotion to production.
    |
    */
    'require_review' => env('CONFIG_VERSION_REQUIRE_REVIEW', true),

    /*
    |--------------------------------------------------------------------------
    | Auto-backup on Promote
    |--------------------------------------------------------------------------
    |
    | Automatically create a snapshot before promoting to production.
    |
    */
    'auto_backup' => env('CONFIG_VERSION_AUTO_BACKUP', true),

    /*
    |--------------------------------------------------------------------------
    | Keep History Count
    |--------------------------------------------------------------------------
    |
    | Maximum number of versions to keep per configuration per environment.
    | Older versions are archived but not deleted.
    |
    */
    'keep_history' => env('CONFIG_VERSION_KEEP_HISTORY', 100),

    /*
    |--------------------------------------------------------------------------
    | Environments
    |--------------------------------------------------------------------------
    |
    | Available environments and their promotion order.
    |
    */
    'environments' => [
        'development' => [
            'label' => 'Development',
            'order' => 1,
            'color' => 'gray',
        ],
        'staging' => [
            'label' => 'Staging',
            'order' => 2,
            'color' => 'yellow',
        ],
        'production' => [
            'label' => 'Production',
            'order' => 3,
            'color' => 'green',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Config Types
    |--------------------------------------------------------------------------
    |
    | Registered configuration types that can be versioned.
    |
    */
    'types' => [
        'entity' => [
            'label' => 'Entity Definition',
            'icon' => 'database',
        ],
        'workflow' => [
            'label' => 'Workflow',
            'icon' => 'git-branch',
        ],
        'view' => [
            'label' => 'View Definition',
            'icon' => 'layout',
        ],
        'record_rule' => [
            'label' => 'Record Rule',
            'icon' => 'shield',
        ],
        'computed_field' => [
            'label' => 'Computed Field',
            'icon' => 'calculator',
        ],
        'menu' => [
            'label' => 'Menu',
            'icon' => 'menu',
        ],
        'sequence' => [
            'label' => 'Sequence',
            'icon' => 'hash',
        ],
        'plugin' => [
            'label' => 'Plugin Config',
            'icon' => 'puzzle',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notifications for version control events.
    |
    */
    'notifications' => [
        'on_review_requested' => true,
        'on_review_completed' => true,
        'on_promoted' => true,
        'on_rollback' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache settings for active configurations.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'prefix' => 'config_version:',
    ],
];
