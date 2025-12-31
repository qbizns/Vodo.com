<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Commerce Plugin Configuration
    |--------------------------------------------------------------------------
    */

    // Default currency for new stores
    'default_currency' => env('COMMERCE_DEFAULT_CURRENCY', 'USD'),

    // Default timezone for new stores
    'default_timezone' => env('COMMERCE_DEFAULT_TIMEZONE', 'UTC'),

    // Stock management
    'low_stock_threshold' => env('COMMERCE_LOW_STOCK_THRESHOLD', 5),
    'track_inventory' => env('COMMERCE_TRACK_INVENTORY', true),

    // Checkout settings
    'enable_guest_checkout' => env('COMMERCE_GUEST_CHECKOUT', true),
    'require_phone_at_checkout' => env('COMMERCE_REQUIRE_PHONE', false),

    // Cart settings
    'cart_expiration_days' => env('COMMERCE_CART_EXPIRATION', 7),
    'abandoned_cart_hours' => env('COMMERCE_ABANDONED_CART_HOURS', 24),

    // Order settings
    'order_number_prefix' => env('COMMERCE_ORDER_PREFIX', 'ORD'),

    // Email notifications
    'notifications' => [
        'order_confirmation' => true,
        'order_shipped' => true,
        'order_cancelled' => true,
        'low_stock_alert' => true,
    ],

    // Supported currencies
    'currencies' => [
        'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'decimals' => 2],
        'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'decimals' => 2],
        'SAR' => ['name' => 'Saudi Riyal', 'symbol' => 'ر.س', 'decimals' => 2],
        'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ', 'decimals' => 2],
        'KWD' => ['name' => 'Kuwaiti Dinar', 'symbol' => 'د.ك', 'decimals' => 3],
        'BHD' => ['name' => 'Bahraini Dinar', 'symbol' => 'د.ب', 'decimals' => 3],
        'QAR' => ['name' => 'Qatari Riyal', 'symbol' => 'ر.ق', 'decimals' => 2],
        'OMR' => ['name' => 'Omani Rial', 'symbol' => 'ر.ع', 'decimals' => 3],
        'EGP' => ['name' => 'Egyptian Pound', 'symbol' => 'ج.م', 'decimals' => 2],
        'JOD' => ['name' => 'Jordanian Dinar', 'symbol' => 'د.أ', 'decimals' => 3],
        'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'decimals' => 2],
        'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'decimals' => 2],
        'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹', 'decimals' => 2],
        'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥', 'decimals' => 2],
        'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'decimals' => 0],
    ],

    // Default theme
    'default_theme' => 'commerce-default',
];
