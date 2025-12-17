<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the workflow/state machine system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Auto-initialize Workflows
    |--------------------------------------------------------------------------
    |
    | When true, workflows are automatically initialized when a record is created.
    | Set to false to manually initialize workflows.
    |
    */
    'auto_initialize' => true,

    /*
    |--------------------------------------------------------------------------
    | Sync State Field
    |--------------------------------------------------------------------------
    |
    | When true, the workflow engine will update the record's state field
    | when a transition occurs.
    |
    */
    'sync_state_field' => true,

    /*
    |--------------------------------------------------------------------------
    | Default State Field
    |--------------------------------------------------------------------------
    |
    | The default field name used to store the state on records.
    |
    */
    'default_state_field' => 'state',

    /*
    |--------------------------------------------------------------------------
    | History Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep workflow history. Set to 0 to keep forever.
    |
    */
    'history_retention_days' => 365,

    /*
    |--------------------------------------------------------------------------
    | Transition Hooks
    |--------------------------------------------------------------------------
    |
    | Enable/disable various transition hooks.
    |
    */
    'hooks' => [
        'publish_events' => true,
        'log_transitions' => true,
        'track_data_changes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Diagram Generation
    |--------------------------------------------------------------------------
    |
    | Settings for workflow diagram generation.
    |
    */
    'diagrams' => [
        'format' => 'mermaid', // mermaid, plantuml, graphviz
        'cache' => true,
        'cache_ttl' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Built-in Conditions
    |--------------------------------------------------------------------------
    |
    | Enable/disable built-in condition handlers.
    |
    */
    'conditions' => [
        'exists' => true,
        'has_field' => true,
        'field_equals' => true,
        'has_relation' => true,
        'relation_count_min' => true,
        'user_can' => true,
        'created_within' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Built-in Actions
    |--------------------------------------------------------------------------
    |
    | Enable/disable built-in action handlers.
    |
    */
    'actions' => [
        'log_activity' => true,
        'update_field' => true,
        'touch_timestamp' => true,
        'dispatch_event' => true,
    ],
];
