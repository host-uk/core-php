<?php

/**
 * Device Frames Configuration
 *
 * Comprehensive device mockup library for biolink previews.
 * Each device includes frame dimensions and screen area coordinates
 * for accurate content positioning.
 *
 * Structure is MCP-exposable for AI agent discovery.
 *
 * Screen coordinates are percentages of the frame dimensions,
 * allowing consistent scaling at any rendered size.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Viewport Categories
    |--------------------------------------------------------------------------
    */
    'viewports' => [
        'phone' => [
            'name' => 'Phone',
            'icon' => 'fa-mobile-screen',
            'default_device' => 'iphone-16-pro',
        ],
        'tablet' => [
            'name' => 'Tablet',
            'icon' => 'fa-tablet-screen-button',
            'default_device' => 'ipad-pro-11-m4',
        ],
        'desktop' => [
            'name' => 'Desktop',
            'icon' => 'fa-display',
            'default_device' => 'macbook-pro-14',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Definitions
    |--------------------------------------------------------------------------
    |
    | Each device includes:
    | - name: Display name
    | - brand: Manufacturer
    | - viewport: phone|tablet|desktop
    | - dimensions: Native width/height of frame image
    | - screen: Percentage-based screen area (x, y, width, height, radius)
    | - variants: Available colours/styles
    | - format: svg|png
    |
    */
    'devices' => [

        // =====================================================================
        // iOS - iPhones
        // =====================================================================

        'iphone-17-pro-max' => [
            'name' => 'iPhone 17 Pro Max',
            'brand' => 'Apple',
            'viewport' => 'phone',
            'year' => 2025,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 5.0,      // 54/1406 as percentage
                'y' => 5.5,      // 120/2822 as percentage
                'width' => 90.0, // 1298/1406 as percentage
                'height' => 89.0, // 2648/2822 as percentage
                'radius' => 10.0,  // Corner radius as percentage of width
            ],
            'variants' => [
                'deep-blue' => ['name' => 'Deep Blue', 'file' => '17 Pro Max - Deep Blue'],
                'cosmic-orange' => ['name' => 'Cosmic Orange', 'file' => '17 Pro Max - Cosmic Orange'],
                'silver' => ['name' => 'Silver', 'file' => '17 Pro Max - Silver'],
            ],
            'default_variant' => 'silver',
            'format' => 'svg', // Also available as PNG
            'path' => 'iOS/iPhone 17 Pro Max',
        ],

        'iphone-17-pro' => [
            'name' => 'iPhone 17 Pro',
            'brand' => 'Apple',
            'viewport' => 'phone',
            'year' => 2025,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 5.0,
                'y' => 5.5,
                'width' => 90.0,
                'height' => 89.0,
                'radius' => 10.0,
            ],
            'variants' => [
                'deep-blue' => ['name' => 'Deep Blue', 'file' => '17 Pro - Deep Blue'],
                'cosmic-orange' => ['name' => 'Cosmic Orange', 'file' => '17 Pro - Cosmic Orange'],
                'silver' => ['name' => 'Silver', 'file' => '17 Pro - Silver'],
            ],
            'default_variant' => 'silver',
            'format' => 'svg',
            'path' => 'iOS/iPhone 17 Pro',
        ],

        'iphone-air' => [
            'name' => 'iPhone Air',
            'brand' => 'Apple',
            'viewport' => 'phone',
            'year' => 2025,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 5.0,
                'y' => 5.5,
                'width' => 90.0,
                'height' => 89.0,
                'radius' => 10.0,
            ],
            'variants' => [
                'cloud-white' => ['name' => 'Cloud White', 'file' => 'Air - Cloud White'],
                'light-gold' => ['name' => 'Light Gold', 'file' => 'Air - Light Gold'],
                'sky-blue' => ['name' => 'Sky Blue', 'file' => 'Air - Sky Blue'],
                'space-black' => ['name' => 'Space Black', 'file' => 'Air - Space Black'],
            ],
            'default_variant' => 'cloud-white',
            'format' => 'svg',
            'path' => 'iOS/iPhone Air',
        ],

        'iphone-16-pro-max' => [
            'name' => 'iPhone 16 Pro Max',
            'brand' => 'Apple',
            'viewport' => 'phone',
            'year' => 2024,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 5.0,
                'y' => 5.5,
                'width' => 90.0,
                'height' => 89.0,
                'radius' => 10.0,
            ],
            'variants' => [
                'black-titanium' => ['name' => 'Black Titanium', 'file' => '16 Pro Max - Black Titanium'],
                'white-titanium' => ['name' => 'White Titanium', 'file' => '16 Pro Max - White Titanium'],
                'natural-titanium' => ['name' => 'Natural Titanium', 'file' => '16 Pro Max - Natural Titanium'],
                'desert-titanium' => ['name' => 'Desert Titanium', 'file' => '16 Pro Max - Desert Titanium'],
            ],
            'default_variant' => 'natural-titanium',
            'format' => 'png',
            'path' => 'iOS/iPhone 16 Pro Max',
        ],

        'iphone-16-pro' => [
            'name' => 'iPhone 16 Pro',
            'brand' => 'Apple',
            'viewport' => 'phone',
            'year' => 2024,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 5.0,
                'y' => 5.5,
                'width' => 90.0,
                'height' => 89.0,
                'radius' => 10.0,
            ],
            'variants' => [
                'black-titanium' => ['name' => 'Black Titanium', 'file' => '16 Pro - Black Titanium'],
                'white-titanium' => ['name' => 'White Titanium', 'file' => '16 Pro - White Titanium'],
                'natural-titanium' => ['name' => 'Natural Titanium', 'file' => '16 Pro - Natural Titanium'],
                'desert-titanium' => ['name' => 'Desert Titanium', 'file' => '16 Pro - Desert Titanium'],
            ],
            'default_variant' => 'natural-titanium',
            'format' => 'png',
            'path' => 'iOS/iPhone 16 Pro',
        ],

        'iphone-16-plus' => [
            'name' => 'iPhone 16 Plus',
            'brand' => 'Apple',
            'viewport' => 'phone',
            'year' => 2024,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 5.0,
                'y' => 5.5,
                'width' => 90.0,
                'height' => 89.0,
                'radius' => 10.0,
            ],
            'variants' => [
                'black' => ['name' => 'Black', 'file' => '16 Plus - Black'],
                'white' => ['name' => 'White', 'file' => '16 Plus - White'],
                'pink' => ['name' => 'Pink', 'file' => '16 Plus - Pink'],
                'teal' => ['name' => 'Teal', 'file' => '16 Plus - Teal'],
                'ultramarine' => ['name' => 'Ultramarine', 'file' => '16 Plus - Ultramarine'],
            ],
            'default_variant' => 'black',
            'format' => 'png',
            'path' => 'iOS/iPhone 16 Plus',
        ],

        'iphone-16' => [
            'name' => 'iPhone 16',
            'brand' => 'Apple',
            'viewport' => 'phone',
            'year' => 2024,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 5.0,
                'y' => 5.5,
                'width' => 90.0,
                'height' => 89.0,
                'radius' => 10.0,
            ],
            'variants' => [
                'black' => ['name' => 'Black', 'file' => '16 - Black'],
                'white' => ['name' => 'White', 'file' => '16 - White'],
                'pink' => ['name' => 'Pink', 'file' => '16 - Pink'],
                'teal' => ['name' => 'Teal', 'file' => '16 - Teal'],
                'ultramarine' => ['name' => 'Ultramarine', 'file' => '16 - Ultramarine'],
            ],
            'default_variant' => 'black',
            'format' => 'png',
            'path' => 'iOS/iPhone 16',
        ],

        'iphone-15-pro-max' => [
            'name' => 'iPhone 15 Pro Max',
            'brand' => 'Apple',
            'viewport' => 'phone',
            'year' => 2023,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 5.0,
                'y' => 5.5,
                'width' => 90.0,
                'height' => 89.0,
                'radius' => 10.0,
            ],
            'variants' => [
                'black-titanium' => ['name' => 'Black Titanium', 'file' => '15 Pro Max - Black Titanium'],
                'white-titanium' => ['name' => 'White Titanium', 'file' => '15 Pro Max - White Titanium'],
                'blue-titanium' => ['name' => 'Blue Titanium', 'file' => '15 Pro Max - Blue Titanium'],
                'natural-titanium' => ['name' => 'Natural Titanium', 'file' => '15 Pro Max - Natural Titanium'],
            ],
            'default_variant' => 'natural-titanium',
            'format' => 'png',
            'path' => 'iOS/iPhone 15 Pro Max',
        ],

        'iphone-14-pro-max' => [
            'name' => 'iPhone 14 Pro Max',
            'brand' => 'Apple',
            'viewport' => 'phone',
            'year' => 2022,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 5.0,
                'y' => 5.5,
                'width' => 90.0,
                'height' => 89.0,
                'radius' => 10.0,
            ],
            'variants' => [
                'space-black' => ['name' => 'Space Black', 'file' => '14 Pro Max - Space Black'],
                'silver' => ['name' => 'Silver', 'file' => '14 Pro Max - Silver'],
                'gold' => ['name' => 'Gold', 'file' => '14 Pro Max - Gold'],
                'deep-purple' => ['name' => 'Deep Purple', 'file' => '14 Pro Max - Deep Purple'],
            ],
            'default_variant' => 'space-black',
            'format' => 'png',
            'path' => 'iOS/iPhone 14 Pro Max',
            'has_shadow_variants' => true,
        ],

        // =====================================================================
        // Android - Pixels
        // =====================================================================

        'pixel-9-pro-xl' => [
            'name' => 'Pixel 9 Pro XL',
            'brand' => 'Google',
            'viewport' => 'phone',
            'year' => 2024,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 3.5,
                'y' => 3.5,
                'width' => 93.0,
                'height' => 93.0,
                'radius' => 3.5,
            ],
            'variants' => [
                'obsidian' => ['name' => 'Obsidian', 'file' => 'Pixel 9 Pro XL Obsidian'],
                'hazel' => ['name' => 'Hazel', 'file' => 'Pixel 9 Pro XL Hazel'],
                'rose-quartz' => ['name' => 'Rose Quartz', 'file' => 'Pixel 9 Pro XL Rose Quartz'],
            ],
            'default_variant' => 'obsidian',
            'format' => 'png',
            'path' => 'Android Phone/Pixel 9 Pro XL',
        ],

        'pixel-9-pro' => [
            'name' => 'Pixel 9 Pro',
            'brand' => 'Google',
            'viewport' => 'phone',
            'year' => 2024,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 3.5,
                'y' => 3.5,
                'width' => 93.0,
                'height' => 93.0,
                'radius' => 3.5,
            ],
            'variants' => [
                'obsidian' => ['name' => 'Obsidian', 'file' => 'Pixel 9 Pro - Obsidian'],
                'hazel' => ['name' => 'Hazel', 'file' => 'Pixel 9 Pro - Hazel'],
                'rose-quartz' => ['name' => 'Rose Quartz', 'file' => 'Pixel 9 Pro - Rose Quartz'],
            ],
            'default_variant' => 'obsidian',
            'format' => 'png',
            'path' => 'Android Phone/Pixel 9 Pro',
        ],

        'pixel-8-pro' => [
            'name' => 'Pixel 8 Pro',
            'brand' => 'Google',
            'viewport' => 'phone',
            'year' => 2023,
            'dimensions' => ['width' => 1406, 'height' => 2822],
            'screen' => [
                'x' => 3.5,
                'y' => 3.5,
                'width' => 93.0,
                'height' => 93.0,
                'radius' => 3.5,
            ],
            'variants' => [
                'black' => ['name' => 'Black', 'file' => 'P8P - Black'],
                'silver' => ['name' => 'Silver', 'file' => 'P8P - Silver'],
                'blue' => ['name' => 'Blue', 'file' => 'P8P - Blue'],
            ],
            'default_variant' => 'black',
            'format' => 'png',
            'path' => 'Android Phone/Pixel 8 Pro',
        ],

        // =====================================================================
        // iPadOS - Tablets
        // =====================================================================

        'ipad-pro-13-m4' => [
            'name' => 'iPad Pro 13" M4',
            'brand' => 'Apple',
            'viewport' => 'tablet',
            'year' => 2024,
            'dimensions' => ['width' => 3270, 'height' => 2428], // Landscape
            'screen' => [
                'x' => 5.0,
                'y' => 6.0,
                'width' => 90.0,
                'height' => 88.0,
                'radius' => 4.0,
            ],
            'variants' => [
                'silver' => ['name' => 'Silver', 'file' => 'iPad Pro 13 M4 - Landscape - Silver'],
                'space-black' => ['name' => 'Space Black', 'file' => 'iPad Pro 13 M4 - Landscape - Space Black'],
            ],
            'default_variant' => 'space-black',
            'format' => 'png',
            'path' => 'iPadOS/iPad Pro 13 M4',
            'orientations' => ['landscape', 'portrait'],
        ],

        'ipad-pro-11-m4' => [
            'name' => 'iPad Pro 11" M4',
            'brand' => 'Apple',
            'viewport' => 'tablet',
            'year' => 2024,
            'dimensions' => ['width' => 2788, 'height' => 2068], // Landscape
            'screen' => [
                'x' => 5.0,
                'y' => 6.0,
                'width' => 90.0,
                'height' => 88.0,
                'radius' => 4.0,
            ],
            'variants' => [
                'silver' => ['name' => 'Silver', 'file' => 'iPad Pro 11 M4 - Landscape - Silver'],
                'space-black' => ['name' => 'Space Black', 'file' => 'iPad Pro 11 M4 - Landscape - Space Black'],
            ],
            'default_variant' => 'space-black',
            'format' => 'png',
            'path' => 'iPadOS/iPad Pro 11 M4',
            'orientations' => ['landscape', 'portrait'],
        ],

        'ipad-air-13-m2' => [
            'name' => 'iPad Air 13" M2',
            'brand' => 'Apple',
            'viewport' => 'tablet',
            'year' => 2024,
            'dimensions' => ['width' => 3270, 'height' => 2428],
            'screen' => [
                'x' => 5.0,
                'y' => 6.0,
                'width' => 90.0,
                'height' => 88.0,
                'radius' => 4.0,
            ],
            'variants' => [
                'space-gray' => ['name' => 'Space Grey', 'file' => 'iPad Air 13 M2 - Landscape - Space Gray'],
                'blue' => ['name' => 'Blue', 'file' => 'iPad Air 13 M2 - Landscape - Blue'],
                'purple' => ['name' => 'Purple', 'file' => 'iPad Air 13 M2 - Landscape - Purple'],
                'stardust' => ['name' => 'Stardust', 'file' => 'iPad Air 13 M2 - Landscape - Stardust'],
            ],
            'default_variant' => 'space-gray',
            'format' => 'png',
            'path' => 'iPadOS/iPad Air 13 M2',
            'orientations' => ['landscape', 'portrait'],
        ],

        'ipad-air-11-m2' => [
            'name' => 'iPad Air 11" M2',
            'brand' => 'Apple',
            'viewport' => 'tablet',
            'year' => 2024,
            'dimensions' => ['width' => 2788, 'height' => 2068],
            'screen' => [
                'x' => 5.0,
                'y' => 6.0,
                'width' => 90.0,
                'height' => 88.0,
                'radius' => 4.0,
            ],
            'variants' => [
                'space-gray' => ['name' => 'Space Grey', 'file' => 'iPad Air 11 M2 - Landscape - Space Gray'],
                'blue' => ['name' => 'Blue', 'file' => 'iPad Air 11 M2 - Landscape - Blue'],
                'purple' => ['name' => 'Purple', 'file' => 'iPad Air 11 M2 - Landscape - Purple'],
                'stardust' => ['name' => 'Stardust', 'file' => 'iPad Air 11 M2 - Landscape - Stardust'],
            ],
            'default_variant' => 'space-gray',
            'format' => 'png',
            'path' => 'iPadOS/iPad Air 11 M2',
            'orientations' => ['landscape', 'portrait'],
        ],

        'pixel-tablet' => [
            'name' => 'Pixel Tablet',
            'brand' => 'Google',
            'viewport' => 'tablet',
            'year' => 2023,
            'dimensions' => ['width' => 2560, 'height' => 1600],
            'screen' => [
                'x' => 5.5,
                'y' => 6.5,
                'width' => 89.0,
                'height' => 87.0,
                'radius' => 4.5,
            ],
            'variants' => [
                'hazel' => ['name' => 'Hazel', 'file' => 'Pixel Tablet - Hazel'],
                'porcelain' => ['name' => 'Porcelain', 'file' => 'Pixel Tablet - Porcelain'],
            ],
            'default_variant' => 'hazel',
            'format' => 'png',
            'path' => 'Android Tablet',
        ],

        // =====================================================================
        // MacBooks - Laptops/Desktop
        // =====================================================================

        'macbook-pro-16' => [
            'name' => 'MacBook Pro 16"',
            'brand' => 'Apple',
            'viewport' => 'desktop',
            'year' => 2023,
            'dimensions' => ['width' => 4340, 'height' => 2860],
            'screen' => [
                'x' => 11.5,
                'y' => 3.5,
                'width' => 77.0,
                'height' => 49.0,
                'radius' => 1.0,
            ],
            'variants' => [
                'default' => ['name' => 'Space Grey', 'file' => 'MacBook Pro 16'],
                'menu-bar' => ['name' => 'With Menu Bar', 'file' => 'MacBook Pro 16 - Menu Bar'],
            ],
            'default_variant' => 'default',
            'format' => 'png',
            'path' => 'MacBook',
        ],

        'macbook-pro-14' => [
            'name' => 'MacBook Pro 14"',
            'brand' => 'Apple',
            'viewport' => 'desktop',
            'year' => 2023,
            'dimensions' => ['width' => 3943, 'height' => 2564],
            'screen' => [
                'x' => 11.5,
                'y' => 3.5,
                'width' => 77.0,
                'height' => 49.0,
                'radius' => 1.0,
            ],
            'variants' => [
                'default' => ['name' => 'Space Grey', 'file' => 'MacBook Pro 14'],
                'menu-bar' => ['name' => 'With Menu Bar', 'file' => 'MacBook Pro 14 - Menu Bar'],
            ],
            'default_variant' => 'default',
            'format' => 'png',
            'path' => 'MacBook',
        ],

        'macbook-air-15' => [
            'name' => 'MacBook Air 15"',
            'brand' => 'Apple',
            'viewport' => 'desktop',
            'year' => 2023,
            'dimensions' => ['width' => 3540, 'height' => 2292],
            'screen' => [
                'x' => 11.5,
                'y' => 3.5,
                'width' => 77.0,
                'height' => 49.0,
                'radius' => 1.0,
            ],
            'variants' => [
                'default' => ['name' => 'Silver', 'file' => 'MacBook Air 15'],
                'menu-bar' => ['name' => 'With Menu Bar', 'file' => 'MacBook Air 15 - Menu Bar'],
            ],
            'default_variant' => 'default',
            'format' => 'png',
            'path' => 'MacBook',
        ],

        'macbook-air-13' => [
            'name' => 'MacBook Air 13"',
            'brand' => 'Apple',
            'viewport' => 'desktop',
            'year' => 2022,
            'dimensions' => ['width' => 3220, 'height' => 2100],
            'screen' => [
                'x' => 11.5,
                'y' => 3.5,
                'width' => 77.0,
                'height' => 49.0,
                'radius' => 1.0,
            ],
            'variants' => [
                'default' => ['name' => 'Silver', 'file' => 'MacBook Air 13'],
                'menu-bar' => ['name' => 'With Menu Bar', 'file' => 'MacBook Air 13 - Menu Bar'],
            ],
            'default_variant' => 'default',
            'format' => 'png',
            'path' => 'MacBook',
        ],

        // =====================================================================
        // Desktop Monitors
        // =====================================================================

        'imac-24' => [
            'name' => 'iMac 24"',
            'brand' => 'Apple',
            'viewport' => 'desktop',
            'year' => 2021,
            'dimensions' => ['width' => 3000, 'height' => 2500],
            'screen' => [
                'x' => 4.0,
                'y' => 3.0,
                'width' => 92.0,
                'height' => 52.0,
                'radius' => 1.5,
            ],
            'variants' => [
                'silver' => ['name' => 'Silver', 'file' => 'iMac 24 - Silver'],
                'blue' => ['name' => 'Blue', 'file' => 'iMac 24 - Blue'],
                'green' => ['name' => 'Green', 'file' => 'iMac 24 - Green'],
                'orange' => ['name' => 'Orange', 'file' => 'iMac 24 - Orange'],
                'purple' => ['name' => 'Purple', 'file' => 'iMac 24 - Purple'],
                'red' => ['name' => 'Red', 'file' => 'iMac 24 - Red'],
                'yellow' => ['name' => 'Yellow', 'file' => 'iMac 24 - Yellow'],
            ],
            'default_variant' => 'silver',
            'format' => 'png',
            'path' => 'Mac Desktop/iMac 24',
            'has_shadow_variants' => true,
        ],

        'studio-display' => [
            'name' => 'Studio Display',
            'brand' => 'Apple',
            'viewport' => 'desktop',
            'year' => 2022,
            'dimensions' => ['width' => 3000, 'height' => 2200],
            'screen' => [
                'x' => 3.5,
                'y' => 3.0,
                'width' => 93.0,
                'height' => 56.0,
                'radius' => 1.0,
            ],
            'variants' => [
                'default' => ['name' => 'Silver', 'file' => 'Studio Display'],
            ],
            'default_variant' => 'default',
            'format' => 'png',
            'path' => 'Mac Desktop/Studio Display',
            'has_shadow_variants' => true,
        ],

        'pro-display-xdr' => [
            'name' => 'Pro Display XDR',
            'brand' => 'Apple',
            'viewport' => 'desktop',
            'year' => 2019,
            'dimensions' => ['width' => 3200, 'height' => 2400],
            'screen' => [
                'x' => 3.0,
                'y' => 3.0,
                'width' => 94.0,
                'height' => 56.0,
                'radius' => 0.5,
            ],
            'variants' => [
                'default' => ['name' => 'Silver', 'file' => 'Pro Display XDR'],
            ],
            'default_variant' => 'default',
            'format' => 'png',
            'path' => 'Mac Desktop/Pro Display XDR',
            'has_shadow_variants' => true,
        ],

        // =====================================================================
        // Windows Devices
        // =====================================================================

        'surface-laptop-15' => [
            'name' => 'Surface Laptop 15"',
            'brand' => 'Microsoft',
            'viewport' => 'desktop',
            'year' => 2024,
            'dimensions' => ['width' => 3000, 'height' => 2000],
            'screen' => [
                'x' => 10.0,
                'y' => 4.0,
                'width' => 80.0,
                'height' => 50.0,
                'radius' => 1.0,
            ],
            'variants' => [
                'platinum' => ['name' => 'Platinum', 'file' => 'Surface Laptop 15 - Platinum'],
            ],
            'default_variant' => 'platinum',
            'format' => 'png',
            'path' => 'Windows Laptop/Surface Laptop',
        ],

        'dell-xps-16' => [
            'name' => 'Dell XPS 16',
            'brand' => 'Dell',
            'viewport' => 'desktop',
            'year' => 2024,
            'dimensions' => ['width' => 3000, 'height' => 2000],
            'screen' => [
                'x' => 8.0,
                'y' => 4.0,
                'width' => 84.0,
                'height' => 52.0,
                'radius' => 1.0,
            ],
            'variants' => [
                'graphite' => ['name' => 'Graphite', 'file' => '2024 XPS 16 Graphite'],
                'platinum' => ['name' => 'Platinum', 'file' => '2024 XPS 16 Platinum'],
            ],
            'default_variant' => 'graphite',
            'format' => 'png',
            'path' => 'Windows Laptop/Dell XPS',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Paths
    |--------------------------------------------------------------------------
    */
    'source_path' => env('DEVICE_FRAMES_SOURCE', '/Users/snider/Code/lib/themes/mockup-device-frames/Exports'),
    'public_path' => 'images/device-frames',

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'viewport' => 'phone',
        'device' => 'iphone-16-pro',
        'variant' => null, // Uses device default
    ],
];
