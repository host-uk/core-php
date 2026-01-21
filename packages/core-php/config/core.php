<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Branding
    |--------------------------------------------------------------------------
    |
    | These settings control the public-facing website branding.
    | Override these in your application's config/core.php to customise.
    |
    */

    'app' => [
        'name' => env('APP_NAME', 'Core PHP'),
        'description' => env('APP_DESCRIPTION', 'A modular monolith framework'),
        'tagline' => env('APP_TAGLINE', 'Build powerful applications with a clean, modular architecture.'),
        'cta_text' => env('APP_CTA_TEXT', 'Join developers building with our framework.'),
        'icon' => env('APP_ICON', 'cube'),
        'color' => env('APP_COLOR', 'violet'),
        'logo' => env('APP_LOGO'),  // Path relative to public/, e.g. 'images/logo.svg'
        'privacy_url' => env('APP_PRIVACY_URL'),
        'terms_url' => env('APP_TERMS_URL'),
        'powered_by' => env('APP_POWERED_BY'),
        'powered_by_url' => env('APP_POWERED_BY_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Paths
    |--------------------------------------------------------------------------
    |
    | Directories to scan for module Boot.php files with $listens declarations.
    | Each path should be an absolute path to a directory containing modules.
    |
    | Example:
    |     'module_paths' => [
    |         app_path('Core'),
    |         app_path('Mod'),
    |     ],
    |
    */

    'module_paths' => [
        // app_path('Core'),
        // app_path('Mod'),
    ],

    /*
    |--------------------------------------------------------------------------
    | FontAwesome Configuration
    |--------------------------------------------------------------------------
    |
    | Configure FontAwesome Pro detection and fallback behaviour.
    |
    */

    'fontawesome' => [
        // Set to true if you have a FontAwesome Pro licence
        'pro' => env('FONTAWESOME_PRO', false),

        // Your FontAwesome Kit ID (optional)
        'kit' => env('FONTAWESOME_KIT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pro Fallback Behaviour
    |--------------------------------------------------------------------------
    |
    | How to handle Pro-only components when Pro packages aren't installed.
    |
    | Options:
    |   - 'error': Throw exception in dev, silent in production
    |   - 'fallback': Use free alternatives where possible
    |   - 'silent': Render nothing for Pro-only components
    |
    */

    'pro_fallback' => env('CORE_PRO_FALLBACK', 'error'),

    /*
    |--------------------------------------------------------------------------
    | Icon Defaults
    |--------------------------------------------------------------------------
    |
    | Default icon style when not specified. Only applies when not using
    | auto-detection (brand/jelly lists).
    |
    */

    'icon' => [
        'default_style' => 'solid',
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the unified search feature including searchable API endpoints.
    | Add your application's API endpoints here to include them in search results.
    |
    */

    'search' => [
        'api_endpoints' => [
            // Example endpoints - override in your application's config
            // ['method' => 'GET', 'path' => '/api/v1/users', 'description' => 'List users'],
            // ['method' => 'POST', 'path' => '/api/v1/users', 'description' => 'Create user'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Menu Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the admin menu caching behaviour. Menu items are cached per
    | user/workspace combination to improve performance on repeated requests.
    |
    */

    'admin_menu' => [
        // Whether to enable caching for static menu items.
        // Set to false during development for instant menu updates.
        'cache_enabled' => env('CORE_ADMIN_MENU_CACHE', true),

        // Cache TTL in seconds (default: 5 minutes).
        // Lower values mean more frequent cache misses but fresher menus.
        'cache_ttl' => env('CORE_ADMIN_MENU_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Resilience Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the application handles Redis failures. When Redis becomes
    | unavailable, the system can either silently fall back to database storage
    | or throw an exception.
    |
    */

    'storage' => [
        // Whether to silently fall back to database when Redis fails.
        // Set to false to throw exceptions on Redis failure.
        'silent_fallback' => env('CORE_STORAGE_SILENT_FALLBACK', true),

        // Log level for fallback events: 'debug', 'info', 'notice', 'warning', 'error', 'critical'
        'fallback_log_level' => env('CORE_STORAGE_FALLBACK_LOG_LEVEL', 'warning'),

        // Whether to dispatch RedisFallbackActivated events for monitoring/alerting
        'dispatch_fallback_events' => env('CORE_STORAGE_DISPATCH_EVENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Language & Translation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure translation fallback chains and missing key validation.
    | The fallback chain allows regional locales to fall back to their base
    | locale before using the application's fallback locale.
    |
    | Example chain: en_GB -> en -> fallback_locale (from config/app.php)
    |
    */

    'lang' => [
        // Enable locale chain fallback (e.g., en_GB -> en -> fallback)
        // When true, regional locales like 'en_GB' will first try 'en' before
        // falling back to the application's fallback_locale.
        'fallback_chain' => env('CORE_LANG_FALLBACK_CHAIN', true),

        // Warn about missing translation keys in development environments.
        // Set to true to always enable, false to always disable, or leave
        // null to auto-enable in local/development/testing environments.
        'validate_keys' => env('CORE_LANG_VALIDATE_KEYS'),

        // Log missing translation keys when validation is enabled.
        'log_missing_keys' => env('CORE_LANG_LOG_MISSING_KEYS', true),

        // Log level for missing translation key warnings.
        // Options: 'debug', 'info', 'notice', 'warning', 'error', 'critical'
        'missing_key_log_level' => env('CORE_LANG_MISSING_KEY_LOG_LEVEL', 'debug'),
    ],

];
