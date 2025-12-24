<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscriptions Plugin Configuration
    |--------------------------------------------------------------------------
    */

    // Currency Settings
    'currency' => env('SUBSCRIPTIONS_CURRENCY', 'USD'),
    'currency_symbol' => env('SUBSCRIPTIONS_CURRENCY_SYMBOL', '$'),

    // Trial & Grace Period
    'trial_days' => env('SUBSCRIPTIONS_TRIAL_DAYS', 14),
    'grace_period_days' => env('SUBSCRIPTIONS_GRACE_PERIOD_DAYS', 3),

    // Plan Changes
    'allow_plan_changes' => env('SUBSCRIPTIONS_ALLOW_PLAN_CHANGES', true),
    'prorate_plan_changes' => env('SUBSCRIPTIONS_PRORATE_PLAN_CHANGES', true),

    // Expiration
    'auto_cancel_expired' => env('SUBSCRIPTIONS_AUTO_CANCEL_EXPIRED', false),

    // Notifications
    'send_renewal_reminders' => env('SUBSCRIPTIONS_SEND_REMINDERS', true),
    'reminder_days_before' => env('SUBSCRIPTIONS_REMINDER_DAYS', 7),
    'notify_on_expiration' => env('SUBSCRIPTIONS_NOTIFY_EXPIRATION', true),
    'notify_admins_on_new' => env('SUBSCRIPTIONS_NOTIFY_ADMINS', false),

    // Invoice Settings
    'invoice_prefix' => env('SUBSCRIPTIONS_INVOICE_PREFIX', 'INV-'),
    'invoice_number_length' => 8,

    // Cache Settings
    'cache_duration' => env('SUBSCRIPTIONS_CACHE_DURATION', 60), // minutes

    // Billing Intervals
    'intervals' => [
        'monthly' => ['days' => 30, 'label' => 'Monthly'],
        'quarterly' => ['days' => 90, 'label' => 'Quarterly'],
        'semi_annual' => ['days' => 180, 'label' => 'Semi-Annual'],
        'yearly' => ['days' => 365, 'label' => 'Yearly'],
        'lifetime' => ['days' => null, 'label' => 'Lifetime'],
    ],

    // Subscription Statuses
    'statuses' => [
        'active' => 'Active',
        'trialing' => 'Trialing',
        'past_due' => 'Past Due',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
        'paused' => 'Paused',
    ],

    // Invoice Statuses
    'invoice_statuses' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ],
];

