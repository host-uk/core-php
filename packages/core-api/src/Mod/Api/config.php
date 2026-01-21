<?php

/**
 * API Configuration
 *
 * Rate limiting, versioning, and API-specific settings.
 * Integrated with EntitlementService for tier-based rate limits.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The current API version. Used in URL prefix and documentation.
    |
    */

    'version' => env('API_VERSION', '1'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for API requests.
    | Limits can be tier-based when integrated with entitlements.
    |
    */

    'rate_limits' => [
        // Unauthenticated requests (by IP)
        'default' => [
            'requests' => 60,
            'per_minutes' => 1,
        ],

        // Authenticated requests (by user/key)
        'authenticated' => [
            'requests' => 1000,
            'per_minutes' => 1,
        ],

        // Tier-based limits (integrate with EntitlementService)
        'by_tier' => [
            'starter' => [
                'requests' => 1000,
                'per_minutes' => 1,
            ],
            'pro' => [
                'requests' => 5000,
                'per_minutes' => 1,
            ],
            'agency' => [
                'requests' => 20000,
                'per_minutes' => 1,
            ],
            'enterprise' => [
                'requests' => 100000,
                'per_minutes' => 1,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Key Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for API key generation and validation.
    |
    */

    'keys' => [
        // Prefix for all API keys
        'prefix' => 'hk_',

        // Default scopes for new API keys
        'default_scopes' => ['read', 'write'],

        // Maximum API keys per workspace
        'max_per_workspace' => 10,

        // Auto-expire keys after this many days (null = never)
        'default_expiry_days' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | Webhook delivery settings.
    |
    */

    'webhooks' => [
        // Maximum webhook endpoints per workspace
        'max_per_workspace' => 5,

        // Timeout for webhook delivery in seconds
        'timeout' => 30,

        // Max retries for failed deliveries
        'max_retries' => 5,

        // Disable endpoint after this many consecutive failures
        'disable_after_failures' => 10,

        // Events that are high-volume and opt-in only
        'high_volume_events' => [
            'link.clicked',
            'qrcode.scanned',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for API responses.
    |
    */

    'pagination' => [
        'default_per_page' => 25,
        'max_per_page' => 100,
    ],

];
