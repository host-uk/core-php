<?php

declare(strict_types=1);

/**
 * HLCRF Viewport Configuration
 *
 * Defines true viewport dimensions for each breakpoint.
 * Content renders at these exact CSS pixel dimensions,
 * then scales to fit the editor display area.
 *
 * Layout codes:
 * - C: Content only (single column)
 * - HCF: Header, Content, Footer
 * - HLCRF: Header, Left, Content, Right, Footer
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Viewport Definitions
    |--------------------------------------------------------------------------
    |
    | Each viewport defines the true rendering dimensions and layout type.
    | The viewport width/height are CSS pixels, not physical pixels.
    |
    */
    'viewports' => [
        'phone' => [
            'name' => 'Mobile',
            'layout' => 'C',
            'viewport' => [
                'width' => 393,   // iPhone 16 Pro CSS width
                'height' => 852, // iPhone 16 Pro CSS height
            ],
            'chrome' => [
                'type' => 'phone',
                'variant' => 'dynamic-island', // dynamic-island, notch, none
                'bezel' => 12,
                'corner_radius' => 55,
            ],
        ],

        'tablet' => [
            'name' => 'Tablet',
            'layout' => 'HCF',
            'viewport' => [
                'width' => 1180,  // iPad landscape
                'height' => 820,
            ],
            'chrome' => [
                'type' => 'tablet',
                'variant' => 'modern', // modern (no home button), classic
                'bezel' => 20,
                'corner_radius' => 20,
            ],
            'regions' => [
                'header' => ['height' => 56],
                'content' => ['max_width' => 680],
                'footer' => ['height' => 64],
            ],
        ],

        'desktop' => [
            'name' => 'Desktop',
            'layout' => 'HLCRF',
            'viewport' => [
                'width' => 1920,
                'height' => 1080,
            ],
            'chrome' => [
                'type' => 'browser',
                'variant' => 'minimal', // minimal, full
                'toolbar_height' => 40,
                'corner_radius' => 12,
            ],
            'regions' => [
                'header' => ['height' => 64],
                'left' => ['width' => 280],
                'content' => ['max_width' => 680],
                'right' => ['width' => 320],
                'footer' => ['height' => 80],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout Presets
    |--------------------------------------------------------------------------
    |
    | Predefined layout configurations for common page types.
    | Each preset defines which regions are enabled per breakpoint.
    |
    */
    'presets' => [
        'bio' => [
            'name' => 'Bio Page',
            'description' => 'Single column link-in-bio layout',
            'regions' => [
                'phone' => ['content' => true],
                'tablet' => ['content' => true],
                'desktop' => ['content' => true],
            ],
        ],

        'landing' => [
            'name' => 'Landing Page',
            'description' => 'Header, content, and footer sections',
            'regions' => [
                'phone' => ['content' => true],
                'tablet' => ['header' => true, 'content' => true, 'footer' => true],
                'desktop' => ['header' => true, 'content' => true, 'footer' => true],
            ],
        ],

        'blog' => [
            'name' => 'Blog',
            'description' => 'Content with optional sidebar',
            'regions' => [
                'phone' => ['content' => true],
                'tablet' => ['header' => true, 'content' => true, 'footer' => true],
                'desktop' => [
                    'header' => true,
                    'content' => true,
                    'right' => ['enabled' => true, 'width' => 320],
                    'footer' => true,
                ],
            ],
        ],

        'docs' => [
            'name' => 'Documentation',
            'description' => 'Navigation sidebar with content',
            'regions' => [
                'phone' => ['content' => true],
                'tablet' => ['header' => true, 'content' => true, 'footer' => true],
                'desktop' => [
                    'header' => true,
                    'left' => ['enabled' => true, 'width' => 280],
                    'content' => true,
                    'footer' => true,
                ],
            ],
        ],

        'custom' => [
            'name' => 'Custom',
            'description' => 'Full control over all regions',
            'regions' => [
                'phone' => ['content' => true],
                'tablet' => [
                    'header' => true,
                    'content' => true,
                    'footer' => true,
                ],
                'desktop' => [
                    'header' => true,
                    'left' => ['enabled' => false, 'width' => 280],
                    'content' => true,
                    'right' => ['enabled' => false, 'width' => 320],
                    'footer' => true,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'preset' => 'bio',
        'breakpoint' => 'phone',
        'content_max_width' => 680,
        'left_sidebar_width' => 280,
        'right_sidebar_width' => 320,
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Chrome Colours
    |--------------------------------------------------------------------------
    |
    | CSS colour variables for device chrome rendering.
    |
    */
    'colours' => [
        'phone' => [
            'bezel' => '#1a1a1a',
            'screen_bg' => '#000000',
            'dynamic_island' => '#000000',
            'home_indicator' => 'rgba(255, 255, 255, 0.3)',
        ],
        'tablet' => [
            'bezel' => '#2a2a2a',
            'screen_bg' => '#000000',
            'home_indicator' => 'rgba(255, 255, 255, 0.2)',
        ],
        'browser' => [
            'frame' => '#1e1e1e',
            'toolbar' => '#2d2d2d',
            'toolbar_text' => '#9ca3af',
            'controls' => '#4b5563',
        ],
    ],
];
