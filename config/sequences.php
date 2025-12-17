<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sequence Configuration
    |--------------------------------------------------------------------------
    |
    | Configure sequence generation for various document types.
    |
    */

    // Default sequence configuration
    'defaults' => [
        'prefix' => '',
        'suffix' => '',
        'pattern' => '{####}',
        'padding' => 4,
        'reset_on' => null, // null, 'year', 'month', 'day'
        'start_value' => 1,
        'increment' => 1,
    ],

    // Sequence definitions
    'definitions' => [
        'invoice' => [
            'prefix' => 'INV-',
            'pattern' => '{YYYY}-{####}',
            'reset_on' => 'year',
            'padding' => 4,
        ],

        'credit_note' => [
            'prefix' => 'CN-',
            'pattern' => '{YYYY}-{####}',
            'reset_on' => 'year',
            'padding' => 4,
        ],

        'order' => [
            'prefix' => 'SO-',
            'pattern' => '{YYYY}{MM}-{####}',
            'reset_on' => 'month',
            'padding' => 4,
        ],

        'purchase_order' => [
            'prefix' => 'PO-',
            'pattern' => '{YYYY}{MM}-{####}',
            'reset_on' => 'month',
            'padding' => 4,
        ],

        'quote' => [
            'prefix' => 'QT-',
            'pattern' => '{YYYY}-{####}',
            'reset_on' => 'year',
            'padding' => 4,
        ],

        'payment' => [
            'prefix' => 'PAY-',
            'pattern' => '{YYYY}{MM}{DD}-{####}',
            'reset_on' => 'day',
            'padding' => 4,
        ],

        'receipt' => [
            'prefix' => 'RCP-',
            'pattern' => '{YYYY}{MM}{DD}-{####}',
            'reset_on' => 'day',
            'padding' => 4,
        ],

        'customer' => [
            'prefix' => 'CUS-',
            'pattern' => '{#####}',
            'reset_on' => null,
            'padding' => 5,
        ],

        'vendor' => [
            'prefix' => 'VND-',
            'pattern' => '{#####}',
            'reset_on' => null,
            'padding' => 5,
        ],

        'product' => [
            'prefix' => 'PRD-',
            'pattern' => '{#####}',
            'reset_on' => null,
            'padding' => 5,
        ],

        'ticket' => [
            'prefix' => 'TKT-',
            'pattern' => '{YYYY}{MM}-{#####}',
            'reset_on' => 'month',
            'padding' => 5,
        ],

        'journal_entry' => [
            'prefix' => 'JE-',
            'pattern' => '{YYYY}-{#####}',
            'reset_on' => 'year',
            'padding' => 5,
        ],

        'delivery' => [
            'prefix' => 'DEL-',
            'pattern' => '{YYYY}{MM}{DD}-{####}',
            'reset_on' => 'day',
            'padding' => 4,
        ],

        'return' => [
            'prefix' => 'RET-',
            'pattern' => '{YYYY}{MM}-{####}',
            'reset_on' => 'month',
            'padding' => 4,
        ],
    ],

    // Pattern tokens documentation:
    // {YYYY} - 4-digit year (2025)
    // {YY}   - 2-digit year (25)
    // {MM}   - 2-digit month (01-12)
    // {M}    - Month without leading zero (1-12)
    // {DD}   - 2-digit day (01-31)
    // {D}    - Day without leading zero (1-31)
    // {####} - Sequence number with padding (0001)
    // {N}    - Sequence number without padding
    // {N4}   - Sequence with explicit padding of 4
];
