<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Management System Configuration
    |--------------------------------------------------------------------------
    */

    // Pagination
    'users_per_page' => env('UMS_USERS_PER_PAGE', 25),

    // Registration Settings
    'enable_registration' => env('UMS_ENABLE_REGISTRATION', true),
    'require_email_verification' => env('UMS_REQUIRE_EMAIL_VERIFICATION', true),
    'default_role' => env('UMS_DEFAULT_ROLE', 'user'),

    // Security Settings
    'password_min_length' => env('UMS_PASSWORD_MIN_LENGTH', 8),
    'session_lifetime' => env('UMS_SESSION_LIFETIME', 120),
    'enable_two_factor' => env('UMS_ENABLE_TWO_FACTOR', false),
    'max_login_attempts' => env('UMS_MAX_LOGIN_ATTEMPTS', 5),
    'lockout_duration' => env('UMS_LOCKOUT_DURATION', 15),

    // Profile Settings
    'allow_avatar_upload' => env('UMS_ALLOW_AVATAR_UPLOAD', true),
    'max_avatar_size' => env('UMS_MAX_AVATAR_SIZE', 2048), // KB
    'allowed_avatar_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],

    // Cache Settings
    'cache_duration' => env('UMS_CACHE_DURATION', 60), // minutes
];

