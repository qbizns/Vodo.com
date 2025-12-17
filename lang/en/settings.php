<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the settings section of
    | the application including general, appearance, and system settings.
    |
    */

    // Page
    'title' => 'Settings',
    'subtitle' => 'Manage your application settings',
    'general' => 'General',
    'appearance' => 'Appearance',
    'notifications' => 'Notifications',
    'security' => 'Security',
    'privacy' => 'Privacy',
    'advanced' => 'Advanced',
    'system' => 'System',
    'integrations' => 'Integrations',
    'api' => 'API',
    'plugins' => 'Plugins',

    // General Settings
    'general_settings' => 'General Settings',
    'site_name' => 'Site Name',
    'site_name_help' => 'The name of your website or application',
    'site_description' => 'Site Description',
    'site_description_help' => 'A brief description of your website',
    'site_url' => 'Site URL',
    'site_url_help' => 'The primary URL of your website',
    'contact_email' => 'Contact Email',
    'contact_email_help' => 'The main contact email address',
    'timezone' => 'Timezone',
    'timezone_help' => 'The default timezone for your application',
    'date_format' => 'Date Format',
    'date_format_help' => 'How dates are displayed throughout the application',
    'time_format' => 'Time Format',
    'time_format_help' => 'How times are displayed (12-hour or 24-hour)',
    'week_starts_on' => 'Week Starts On',
    'week_starts_on_help' => 'The first day of the week',

    // Appearance Settings
    'appearance_settings' => 'Appearance Settings',
    'theme' => 'Theme',
    'theme_help' => 'Choose the color theme for the interface',
    'theme_light' => 'Light',
    'theme_dark' => 'Dark',
    'theme_auto' => 'Auto (System)',
    'primary_color' => 'Primary Color',
    'primary_color_help' => 'The main accent color used throughout the UI',
    'logo' => 'Logo',
    'logo_help' => 'Upload your company or brand logo',
    'favicon' => 'Favicon',
    'favicon_help' => 'The small icon shown in browser tabs',
    'font' => 'Font',
    'font_help' => 'The font family used in the interface',
    'sidebar_style' => 'Sidebar Style',
    'sidebar_expanded' => 'Expanded',
    'sidebar_compact' => 'Compact',
    'sidebar_mini' => 'Mini',

    // Language & Region
    'language_region' => 'Language & Region',
    'language' => 'Language',
    'language_help' => 'Select your preferred language',
    'region' => 'Region',
    'region_help' => 'Select your region for localized content',
    'currency' => 'Currency',
    'currency_help' => 'The default currency for monetary values',
    'number_format' => 'Number Format',
    'number_format_help' => 'How numbers are formatted (decimal/thousands separators)',

    // Notification Settings
    'notification_settings' => 'Notification Settings',
    'email_notifications' => 'Email Notifications',
    'email_notifications_help' => 'Receive notifications via email',
    'push_notifications' => 'Push Notifications',
    'push_notifications_help' => 'Receive browser push notifications',
    'sms_notifications' => 'SMS Notifications',
    'sms_notifications_help' => 'Receive notifications via SMS',
    'notification_frequency' => 'Notification Frequency',
    'immediately' => 'Immediately',
    'daily_digest' => 'Daily Digest',
    'weekly_digest' => 'Weekly Digest',
    'never' => 'Never',
    'notify_on_login' => 'Notify on new login',
    'notify_on_changes' => 'Notify on important changes',
    'notify_on_updates' => 'Notify on system updates',
    'notify_marketing' => 'Marketing & promotions',

    // Security Settings
    'security_settings' => 'Security Settings',
    'password' => 'Password',
    'change_password' => 'Change Password',
    'current_password' => 'Current Password',
    'new_password' => 'New Password',
    'confirm_password' => 'Confirm Password',
    'password_requirements' => 'Password must be at least 8 characters with uppercase, lowercase, and numbers',
    'two_factor_auth' => 'Two-Factor Authentication',
    'two_factor_auth_help' => 'Add an extra layer of security to your account',
    'enable_2fa' => 'Enable Two-Factor Authentication',
    'disable_2fa' => 'Disable Two-Factor Authentication',
    'session_management' => 'Session Management',
    'active_sessions' => 'Active Sessions',
    'logout_all_sessions' => 'Logout All Sessions',
    'api_tokens' => 'API Tokens',
    'create_token' => 'Create New Token',
    'revoke_token' => 'Revoke Token',
    'login_history' => 'Login History',

    // Privacy Settings
    'privacy_settings' => 'Privacy Settings',
    'profile_visibility' => 'Profile Visibility',
    'public_profile' => 'Public',
    'private_profile' => 'Private',
    'data_collection' => 'Data Collection',
    'analytics' => 'Allow analytics',
    'personalization' => 'Allow personalization',
    'third_party_sharing' => 'Third-party sharing',
    'data_export' => 'Export My Data',
    'data_export_help' => 'Download a copy of all your data',
    'delete_account' => 'Delete Account',
    'delete_account_help' => 'Permanently delete your account and all data',

    // System Settings
    'system_settings' => 'System Settings',
    'maintenance_mode' => 'Maintenance Mode',
    'maintenance_mode_help' => 'Put the site in maintenance mode',
    'debug_mode' => 'Debug Mode',
    'debug_mode_help' => 'Enable detailed error messages (not recommended for production)',
    'cache_management' => 'Cache Management',
    'clear_cache' => 'Clear Cache',
    'clear_all_cache' => 'Clear All Cache',
    'rebuild_cache' => 'Rebuild Cache',
    'backup' => 'Backup',
    'create_backup' => 'Create Backup',
    'restore_backup' => 'Restore Backup',
    'automatic_backups' => 'Automatic Backups',
    'backup_frequency' => 'Backup Frequency',
    'logs' => 'Logs',
    'view_logs' => 'View Logs',
    'clear_logs' => 'Clear Logs',
    'log_level' => 'Log Level',

    // API Settings
    'api_settings' => 'API Settings',
    'api_enabled' => 'API Enabled',
    'api_rate_limit' => 'Rate Limit',
    'api_rate_limit_help' => 'Maximum requests per minute',
    'api_version' => 'API Version',
    'api_documentation' => 'API Documentation',
    'webhook_settings' => 'Webhook Settings',
    'webhook_url' => 'Webhook URL',
    'webhook_secret' => 'Webhook Secret',

    // Integration Settings
    'integration_settings' => 'Integration Settings',
    'connected_services' => 'Connected Services',
    'connect' => 'Connect',
    'disconnect' => 'Disconnect',
    'configure' => 'Configure',
    'sync_now' => 'Sync Now',
    'last_sync' => 'Last synced',
    'sync_status' => 'Sync Status',
    'sync_enabled' => 'Sync Enabled',
    'sync_frequency' => 'Sync Frequency',

    // Actions
    'save_settings' => 'Save Settings',
    'saving' => 'Saving...',
    'settings_saved' => 'Settings saved successfully.',
    'settings_save_error' => 'Failed to save settings.',
    'reset_to_defaults' => 'Reset to Defaults',
    'confirm_reset' => 'Are you sure you want to reset all settings to their default values?',
    'settings_reset' => 'Settings have been reset to defaults.',

];
