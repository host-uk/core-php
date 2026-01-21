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

];
