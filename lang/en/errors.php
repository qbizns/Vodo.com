<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Error Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various error messages
    | throughout the application including HTTP errors and system errors.
    |
    */

    // HTTP Errors
    '400' => 'Bad Request',
    '400_title' => 'Bad Request',
    '400_message' => 'The server could not understand the request due to invalid syntax.',

    '401' => 'Unauthorized',
    '401_title' => 'Unauthorized',
    '401_message' => 'You must be authenticated to access this resource.',

    '403' => 'Forbidden',
    '403_title' => 'Access Denied',
    '403_message' => 'You do not have permission to access this resource.',

    '404' => 'Not Found',
    '404_title' => 'Page Not Found',
    '404_message' => 'The page you are looking for could not be found.',

    '405' => 'Method Not Allowed',
    '405_title' => 'Method Not Allowed',
    '405_message' => 'The request method is not supported for this resource.',

    '408' => 'Request Timeout',
    '408_title' => 'Request Timeout',
    '408_message' => 'The server timed out waiting for the request.',

    '419' => 'Page Expired',
    '419_title' => 'Page Expired',
    '419_message' => 'Your session has expired. Please refresh and try again.',

    '422' => 'Unprocessable Entity',
    '422_title' => 'Validation Error',
    '422_message' => 'The submitted data was invalid. Please check your input.',

    '429' => 'Too Many Requests',
    '429_title' => 'Too Many Requests',
    '429_message' => 'You have made too many requests. Please try again later.',

    '500' => 'Server Error',
    '500_title' => 'Server Error',
    '500_message' => 'An unexpected error occurred. Our team has been notified.',

    '502' => 'Bad Gateway',
    '502_title' => 'Bad Gateway',
    '502_message' => 'The server received an invalid response from an upstream server.',

    '503' => 'Service Unavailable',
    '503_title' => 'Service Unavailable',
    '503_message' => 'The service is temporarily unavailable. Please try again later.',

    '504' => 'Gateway Timeout',
    '504_title' => 'Gateway Timeout',
    '504_message' => 'The server did not receive a timely response from an upstream server.',

    // Error Page Actions
    'go_home' => 'Go to Homepage',
    'go_back' => 'Go Back',
    'try_again' => 'Try Again',
    'contact_support' => 'Contact Support',
    'report_issue' => 'Report this Issue',

    // Generic Errors
    'generic_error' => 'Something went wrong',
    'generic_error_message' => 'An error occurred while processing your request.',
    'unexpected_error' => 'An unexpected error occurred',
    'unknown_error' => 'Unknown error',
    'operation_failed' => 'Operation failed',
    'action_failed' => 'Action could not be completed',

    // Form Errors
    'form_error' => 'Please correct the errors below',
    'invalid_input' => 'Invalid input provided',
    'missing_required_fields' => 'Please fill in all required fields',
    'invalid_format' => 'Invalid format',
    'value_too_short' => 'Value is too short',
    'value_too_long' => 'Value is too long',
    'invalid_email' => 'Please enter a valid email address',
    'invalid_phone' => 'Please enter a valid phone number',
    'invalid_url' => 'Please enter a valid URL',
    'invalid_date' => 'Please enter a valid date',
    'invalid_number' => 'Please enter a valid number',
    'passwords_dont_match' => 'Passwords do not match',
    'weak_password' => 'Password is too weak',

    // File Errors
    'file_not_found' => 'File not found',
    'file_too_large' => 'File is too large',
    'invalid_file_type' => 'Invalid file type',
    'upload_failed' => 'File upload failed',
    'download_failed' => 'File download failed',
    'max_file_size_exceeded' => 'Maximum file size exceeded (max: :size)',
    'allowed_file_types' => 'Allowed file types: :types',

    // Database Errors
    'database_error' => 'Database error',
    'connection_failed' => 'Database connection failed',
    'query_failed' => 'Database query failed',
    'record_not_found' => 'Record not found',
    'duplicate_entry' => 'This entry already exists',
    'foreign_key_constraint' => 'Cannot delete: This item is referenced by other records',
    'save_failed' => 'Failed to save data',
    'delete_failed' => 'Failed to delete data',
    'update_failed' => 'Failed to update data',

    // Network Errors
    'network_error' => 'Network error',
    'connection_timeout' => 'Connection timed out',
    'no_internet' => 'No internet connection',
    'server_unreachable' => 'Server is unreachable',
    'request_failed' => 'Request failed',
    'response_error' => 'Invalid server response',

    // Authentication Errors
    'auth_error' => 'Authentication error',
    'invalid_credentials' => 'Invalid credentials',
    'account_locked' => 'Your account has been locked',
    'account_disabled' => 'Your account has been disabled',
    'session_expired' => 'Your session has expired',
    'token_invalid' => 'Invalid or expired token',
    'token_expired' => 'Token has expired',
    'unauthorized_access' => 'Unauthorized access',

    // Permission Errors
    'permission_denied' => 'Permission denied',
    'insufficient_permissions' => 'You do not have sufficient permissions',
    'role_required' => 'This action requires the :role role',
    'admin_only' => 'This action is restricted to administrators',

    // Resource Errors
    'resource_not_found' => ':resource not found',
    'resource_already_exists' => ':resource already exists',
    'resource_locked' => ':resource is locked',
    'resource_in_use' => ':resource is currently in use',

    // API Errors
    'api_error' => 'API error',
    'invalid_api_key' => 'Invalid API key',
    'api_rate_limit' => 'API rate limit exceeded',
    'invalid_request' => 'Invalid request',
    'invalid_response' => 'Invalid response from API',
    'api_unavailable' => 'API service is unavailable',

    // Plugin Errors
    'plugin_error' => 'Plugin error',
    'plugin_not_found' => 'Plugin not found',
    'plugin_activation_failed' => 'Plugin activation failed',
    'plugin_deactivation_failed' => 'Plugin deactivation failed',
    'plugin_incompatible' => 'Plugin is incompatible with this version',
    'plugin_missing_dependencies' => 'Plugin has missing dependencies',

    // System Errors
    'system_error' => 'System error',
    'maintenance_mode' => 'System is under maintenance',
    'service_unavailable' => 'Service temporarily unavailable',
    'feature_disabled' => 'This feature is currently disabled',
    'quota_exceeded' => 'Quota exceeded',
    'storage_full' => 'Storage space is full',

    // Contact Support
    'error_reference' => 'Error Reference',
    'error_logged' => 'This error has been logged',
    'support_message' => 'If this problem persists, please contact support with the error reference above.',

];
