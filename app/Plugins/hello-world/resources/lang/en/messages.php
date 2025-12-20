<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hello World Plugin Translations
    |--------------------------------------------------------------------------
    |
    | English translations for the Hello World plugin.
    | Usage: @t('hello-world::messages.key') or __p('hello-world', 'messages.key')
    |
    */

    // Page Titles
    'title' => 'Hello World',
    'dashboard_title' => 'Hello World Dashboard',
    'greetings_title' => 'Greetings',

    // Navigation
    'nav_dashboard' => 'Dashboard',
    'nav_greetings' => 'Greetings',
    'nav_chart_of_accounts' => 'Chart of Accounts',

    // Dashboard
    'welcome_message' => 'This is a demonstration of the Vodo plugin system capabilities.',
    'about_title' => 'About This Plugin',
    'about_description' => 'The Hello World plugin demonstrates the core capabilities of the Vodo plugin system:',
    'plugin_info_title' => 'Plugin Information',

    // Features
    'feature_routes' => 'Custom routes prefixed with',
    'feature_views' => 'Blade templates with plugin namespace',
    'feature_migrations' => 'Database migrations for plugin data',
    'feature_models' => 'Eloquent models for data management',
    'feature_navigation' => 'Sidebar menu integration',
    'feature_hooks' => 'Action and filter hooks for extensibility',

    // Stats
    'total_greetings' => 'Total Greetings',
    'plugin_version' => 'Plugin Version',
    'status' => 'Status',
    'greetings_today' => 'Greetings Today',
    'recent_greetings' => 'Recent Greetings',
    'current_greeting' => 'Current Greeting',

    // Actions
    'view_greetings' => 'View Greetings',
    'add_greeting' => 'Add Greeting',
    'edit_greeting' => 'Edit Greeting',
    'delete_greeting' => 'Delete Greeting',

    // Form Labels
    'greeting_message' => 'Greeting Message',
    'greeting_name' => 'Name',
    'greeting_language' => 'Language',

    // Settings
    'settings_title' => 'Plugin Settings',
    'settings_general' => 'General Settings',
    'settings_general_desc' => 'Configure basic Hello World plugin options',
    'setting_greeting' => 'Greeting Message',
    'setting_greeting_desc' => 'The greeting message to display',
    'setting_show_count' => 'Show Greeting Count',
    'setting_show_count_desc' => 'Display the total number of greetings',
    'settings_display' => 'Display Options',
    'settings_display_desc' => 'Customize how greetings are displayed',
    'setting_display_mode' => 'Display Mode',
    'setting_display_mode_desc' => 'Choose how greetings appear on the page',
    'display_card' => 'Card View',
    'display_list' => 'List View',
    'display_grid' => 'Grid View',
    'setting_max_greetings' => 'Maximum Greetings',
    'setting_max_greetings_desc' => 'Maximum number of greetings to display per page',
    'settings_advanced' => 'Advanced Settings',
    'settings_advanced_desc' => 'Advanced configuration options',
    'setting_enable_api' => 'Enable API Access',
    'setting_enable_api_desc' => 'Allow external access to greetings via API',
    'setting_cache_duration' => 'Cache Duration (minutes)',
    'setting_cache_duration_desc' => 'How long to cache greetings data',

    // Messages
    'greeting_saved' => 'Greeting saved successfully!',
    'greeting_deleted' => 'Greeting deleted.',
    'greeting_not_found' => 'Greeting not found.',
    'no_greetings' => 'No greetings yet. Create your first one!',

    // Default greeting
    'default_greeting' => 'Hello, World!',

    // Widget titles
    'widget_greeting_stats' => 'Greeting Statistics',
    'widget_recent' => 'Recent Activity',

    // Status
    'active' => 'Active',
    'inactive' => 'Inactive',
    'activated_at' => 'Activated At',
    'not_activated' => 'Not activated',

    // Table headers
    'name' => 'Name',
    'slug' => 'Slug',
    'version' => 'Version',
    'author' => 'Author',
    'description' => 'Description',

];
