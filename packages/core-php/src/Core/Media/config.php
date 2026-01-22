<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Image Optimization Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic image optimization for uploaded images.
    | Optimization reduces file sizes while maintaining visual quality.
    |
    */

    'optimization' => [

        /*
        | Enable or disable automatic image optimization.
        */
        'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', true),

        /*
        | Image processing driver: 'gd' or 'imagick'
        | Falls back to 'gd' if selected driver is unavailable.
        */
        'driver' => env('IMAGE_OPTIMIZATION_DRIVER', 'gd'),

        /*
        | JPEG/WebP quality setting (0-100).
        | Lower values = smaller files, lower quality.
        | Recommended: 75-85 for web, 80 is a good default.
        */
        'quality' => (int) env('IMAGE_OPTIMIZATION_QUALITY', 80),

        /*
        | PNG compression level (0-9).
        | Higher values = smaller files, slower processing.
        | 0 = no compression, 9 = maximum compression.
        | Recommended: 6-8.
        */
        'png_compression' => (int) env('IMAGE_OPTIMIZATION_PNG_COMPRESSION', 6),

        /*
        | Minimum file size to optimize (KB).
        | Files smaller than this are skipped to avoid overhead.
        | Default: 10KB.
        */
        'min_size_kb' => (int) env('IMAGE_OPTIMIZATION_MIN_SIZE_KB', 10),

        /*
        | Maximum file size to optimize (MB).
        | Files larger than this are skipped to avoid memory issues.
        | Default: 10MB.
        */
        'max_size_mb' => (int) env('IMAGE_OPTIMIZATION_MAX_SIZE_MB', 10),

    ],

];
