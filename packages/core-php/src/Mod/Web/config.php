<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Domain
    |--------------------------------------------------------------------------
    |
    | The default domain for biolinks when no custom domain is specified.
    |
    */

    'default_domain' => env('BIOLINKS_DOMAIN', 'https://bio.host.uk.com'),

    /*
    |--------------------------------------------------------------------------
    | Vanity Domain
    |--------------------------------------------------------------------------
    |
    | Premium vanity domain for paid users (shorter, branded).
    |
    */

    'vanity_domain' => env('BIOLINKS_VANITY_DOMAIN', 'https://lnktr.fyi'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Domains
    |--------------------------------------------------------------------------
    |
    | All domains that can serve biolinks (checked by middleware).
    |
    */

    'allowed_domains' => [
        'bio.host.uk.com',
        'link.host.uk.com',
        'lnktr.fyi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout Presets (HLCRF)
    |--------------------------------------------------------------------------
    |
    | Defines which regions are available at each breakpoint for layout presets.
    | Layout codes: C (content), HCF, HLCF, HCRF, HLCRF
    |
    | H = Header, L = Left sidebar, C = Content, R = Right sidebar, F = Footer
    |
    */

    'layout_presets' => [
        // Link-in-bio (content only at all breakpoints)
        'bio' => [
            'phone' => 'C',
            'tablet' => 'C',
            'desktop' => 'C',
        ],
        // Landing page (header + footer on larger screens)
        'landing' => [
            'phone' => 'C',
            'tablet' => 'HCF',
            'desktop' => 'HCF',
        ],
        // Blog layout (right sidebar on desktop)
        'blog' => [
            'phone' => 'C',
            'tablet' => 'HCF',
            'desktop' => 'HCRF',
        ],
        // Documentation (left sidebar on desktop)
        'docs' => [
            'phone' => 'C',
            'tablet' => 'HCF',
            'desktop' => 'HLCF',
        ],
        // Portfolio (full HLCRF on desktop)
        'portfolio' => [
            'phone' => 'C',
            'tablet' => 'HCF',
            'desktop' => 'HLCRF',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Block Types
    |--------------------------------------------------------------------------
    |
    | All available block types for biolink pages, merged from:
    | - Default blocks (base product)
    | - Pro blocks
    | - Ultimate blocks
    | - Payment blocks
    |
    | Each block has:
    | - icon: FontAwesome class
    | - color: Hex colour for UI
    | - category: Grouping for the editor
    | - has_statistics: Whether clicks are tracked
    | - themable: Whether it inherits page theme
    | - tier: null (free), 'pro', 'ultimate', 'payment'
    |
    */

    'block_types' => [

        // ─────────────────────────────────────────────────────────────────────
        // Standard Blocks (Default)
        // ─────────────────────────────────────────────────────────────────────

        'link' => [
            'icon' => 'fas fa-link',
            'color' => '#004ecc',
            'category' => 'standard',
            'has_statistics' => true,
            'themable' => true,
            'tier' => null,
            'allowed_regions' => ['H', 'L', 'C', 'R', 'F'],  // All regions
        ],
        'heading' => [
            'icon' => 'fas fa-heading',
            'color' => '#000000',
            'category' => 'standard',
            'allowed_regions' => ['H', 'L', 'C', 'R', 'F'],  // All regions
            'has_statistics' => false,
            'themable' => true,
            'tier' => null,
        ],
        'paragraph' => [
            'icon' => 'fas fa-paragraph',
            'color' => '#494949',
            'category' => 'standard',
            'has_statistics' => false,
            'themable' => true,
            'tier' => null,
        ],
        'avatar' => [
            'icon' => 'fas fa-user',
            'color' => '#8b2abf',
            'category' => 'standard',
            'has_statistics' => true,
            'themable' => false,
            'tier' => null,
        ],
        'image' => [
            'icon' => 'fas fa-image',
            'color' => '#0682FF',
            'category' => 'standard',
            'has_statistics' => true,
            'themable' => false,
            'tier' => null,
        ],
        'socials' => [
            'icon' => 'fas fa-users',
            'color' => '#63d2ff',
            'category' => 'standard',
            'has_statistics' => false,
            'themable' => true,
            'tier' => null,
            'allowed_regions' => ['H', 'L', 'C', 'R', 'F'],  // All regions
        ],
        'business_hours' => [
            'icon' => 'fas fa-clock',
            'color' => '#d90377',
            'category' => 'standard',
            'has_statistics' => false,
            'themable' => true,
            'tier' => null,
        ],
        'modal_text' => [
            'icon' => 'fas fa-book-open',
            'color' => '#79a978',
            'category' => 'standard',
            'has_statistics' => true,
            'themable' => true,
            'tier' => null,
        ],

        // ─────────────────────────────────────────────────────────────────────
        // Embeds (Default)
        // ─────────────────────────────────────────────────────────────────────

        'youtube' => [
            'icon' => 'fab fa-youtube',
            'color' => '#ff0000',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => null,
            'whitelisted_hosts' => ['www.youtube.com', 'youtu.be'],
        ],
        'spotify' => [
            'icon' => 'fab fa-spotify',
            'color' => '#1db954',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => null,
            'whitelisted_hosts' => ['open.spotify.com'],
        ],
        'soundcloud' => [
            'icon' => 'fab fa-soundcloud',
            'color' => '#ff8800',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => null,
            'whitelisted_hosts' => ['soundcloud.com'],
        ],
        'tiktok_video' => [
            'icon' => 'fab fa-tiktok',
            'color' => '#FD3E3E',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => null,
            'whitelisted_hosts' => ['www.tiktok.com'],
        ],
        'twitch' => [
            'icon' => 'fab fa-twitch',
            'color' => '#6441a5',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => null,
            'whitelisted_hosts' => ['www.twitch.tv'],
        ],
        'vimeo' => [
            'icon' => 'fab fa-vimeo',
            'color' => '#1ab7ea',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => null,
            'whitelisted_hosts' => ['vimeo.com'],
        ],

        // ─────────────────────────────────────────────────────────────────────
        // Pro Blocks - Embeds
        // ─────────────────────────────────────────────────────────────────────

        'applemusic' => [
            'icon' => 'fab fa-apple',
            'color' => '#FA2D48',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['music.apple.com'],
        ],
        'tidal' => [
            'icon' => 'fas fa-braille',
            'color' => '#000000',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['tidal.com'],
        ],
        'mixcloud' => [
            'icon' => 'fab fa-mixer',
            'color' => '#4e00ff',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['www.mixcloud.com'],
        ],
        'kick' => [
            'icon' => 'fab fa-kickstarter',
            'color' => '#5eff1b',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['kick.com'],
        ],
        'twitter_tweet' => [
            'icon' => 'fab fa-x-twitter',
            'color' => '#1DA1F2',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['twitter.com', 'x.com'],
        ],
        'twitter_video' => [
            'icon' => 'fab fa-x-twitter',
            'color' => '#1DA1F2',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['twitter.com', 'x.com'],
        ],
        'pinterest_profile' => [
            'icon' => 'fab fa-pinterest',
            'color' => '#c8232c',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['pinterest.com', 'www.pinterest.com'],
        ],
        'instagram_media' => [
            'icon' => 'fab fa-instagram',
            'color' => '#F56040',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['www.instagram.com'],
        ],
        'snapchat' => [
            'icon' => 'fab fa-snapchat',
            'color' => '#FFFC00',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['www.snapchat.com', 'snapchat.com'],
        ],
        'tiktok_profile' => [
            'icon' => 'fab fa-tiktok',
            'color' => '#FD3E3E',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['www.tiktok.com'],
        ],
        'vk_video' => [
            'icon' => 'fab fa-vk',
            'color' => '#0a70ff',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'whitelisted_hosts' => ['vk.com'],
        ],

        // ─────────────────────────────────────────────────────────────────────
        // Ultimate Blocks - Embeds
        // ─────────────────────────────────────────────────────────────────────

        'typeform' => [
            'icon' => 'fas fa-keyboard',
            'color' => '#000000',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
        ],
        'calendly' => [
            'icon' => 'fas fa-calendar',
            'color' => '#2d69f6',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
        ],
        'discord' => [
            'icon' => 'fab fa-discord',
            'color' => '#7289D9',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
        ],
        'facebook' => [
            'icon' => 'fab fa-facebook',
            'color' => '#4267B2',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
            'whitelisted_hosts' => ['www.facebook.com', 'fb.watch'],
        ],
        'reddit' => [
            'icon' => 'fab fa-reddit',
            'color' => '#FF4500',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
            'whitelisted_hosts' => ['www.reddit.com'],
        ],
        'iframe' => [
            'icon' => 'fas fa-crop-alt',
            'color' => '#366bff',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
        ],
        'pdf_document' => [
            'icon' => 'fas fa-file-pdf',
            'color' => '#AA1501',
            'category' => 'embeds',
            'has_statistics' => true,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'powerpoint_presentation' => [
            'icon' => 'fas fa-file-powerpoint',
            'color' => '#f58968',
            'category' => 'embeds',
            'has_statistics' => true,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'excel_spreadsheet' => [
            'icon' => 'fas fa-file-excel',
            'color' => '#3bac53',
            'category' => 'embeds',
            'has_statistics' => true,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'rumble' => [
            'icon' => 'fas fa-play',
            'color' => '#85C742',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
            'whitelisted_hosts' => ['rumble.com'],
        ],
        'telegram' => [
            'icon' => 'fab fa-telegram',
            'color' => '#0088cc',
            'category' => 'embeds',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
            'whitelisted_hosts' => ['t.me'],
        ],

        // ─────────────────────────────────────────────────────────────────────
        // Pro Blocks - Standard
        // ─────────────────────────────────────────────────────────────────────

        'header' => [
            'icon' => 'fas fa-theater-masks',
            'color' => '#61B123',
            'category' => 'standard',
            'has_statistics' => true,
            'themable' => false,
            'tier' => 'pro',
        ],
        'image_grid' => [
            'icon' => 'fas fa-images',
            'color' => '#183153',
            'category' => 'standard',
            'has_statistics' => true,
            'themable' => false,
            'tier' => 'pro',
        ],
        'divider' => [
            'icon' => 'fas fa-grip-lines',
            'color' => '#30a85a',
            'category' => 'standard',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
            'allowed_regions' => ['H', 'L', 'C', 'R', 'F'],  // All regions
        ],
        'list' => [
            'icon' => 'fas fa-list',
            'color' => '#2b385e',
            'category' => 'standard',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
        ],

        // ─────────────────────────────────────────────────────────────────────
        // Ultimate Blocks - Standard
        // ─────────────────────────────────────────────────────────────────────

        'big_link' => [
            'icon' => 'fas fa-external-link-alt',
            'color' => '#cc0084',
            'category' => 'standard',
            'has_statistics' => true,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'audio' => [
            'icon' => 'fas fa-volume-up',
            'color' => '#003b63',
            'category' => 'standard',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
        ],
        'video' => [
            'icon' => 'fas fa-video',
            'color' => '#0c3db7',
            'category' => 'standard',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
        ],
        'file' => [
            'icon' => 'fas fa-file',
            'color' => '#8c8c8c',
            'category' => 'standard',
            'has_statistics' => true,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'cta' => [
            'icon' => 'fas fa-comments',
            'color' => '#3100d6',
            'category' => 'standard',
            'has_statistics' => true,
            'themable' => true,
            'tier' => 'ultimate',
        ],

        // ─────────────────────────────────────────────────────────────────────
        // Advanced Blocks
        // ─────────────────────────────────────────────────────────────────────

        'map' => [
            'icon' => 'fas fa-map',
            'color' => '#31A952',
            'category' => 'advanced',
            'has_statistics' => true,
            'themable' => false,
            'tier' => null,
        ],
        'email_collector' => [
            'icon' => 'fas fa-envelope',
            'color' => '#c91685',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => true,
            'tier' => null,
        ],
        'phone_collector' => [
            'icon' => 'fas fa-phone-square-alt',
            'color' => '#39c640',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => true,
            'tier' => null,
        ],
        'contact_collector' => [
            'icon' => 'fas fa-address-book',
            'color' => '#7136c0',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => true,
            'tier' => null,
        ],
        'rss_feed' => [
            'icon' => 'fas fa-rss',
            'color' => '#ee802f',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => true,
            'tier' => 'pro',
        ],
        'custom_html' => [
            'icon' => 'fas fa-code',
            'color' => '#02234c',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'pro',
        ],
        'vcard' => [
            'icon' => 'fas fa-id-card',
            'color' => '#FAB005',
            'category' => 'advanced',
            'has_statistics' => true,
            'themable' => true,
            'tier' => 'pro',
        ],
        'alert' => [
            'icon' => 'fas fa-bell',
            'color' => '#1500ff',
            'category' => 'advanced',
            'has_statistics' => true,
            'themable' => false,
            'tier' => 'pro',
        ],
        'appointment_calendar' => [
            'icon' => 'fas fa-calendar',
            'color' => '#bb0c90',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'faq' => [
            'icon' => 'fas fa-feather',
            'color' => '#da2a73',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'countdown' => [
            'icon' => 'fas fa-clock',
            'color' => '#2b2b2b',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => false,
            'tier' => 'ultimate',
        ],
        'external_item' => [
            'icon' => 'fas fa-money-bill-wave',
            'color' => '#00ce18',
            'category' => 'advanced',
            'has_statistics' => true,
            'themable' => false,
            'tier' => 'ultimate',
        ],
        'share' => [
            'icon' => 'fas fa-share-square',
            'color' => '#00d3ac',
            'category' => 'advanced',
            'has_statistics' => true,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'coupon' => [
            'icon' => 'fas fa-tags',
            'color' => '#6fd9f0',
            'category' => 'advanced',
            'has_statistics' => true,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'youtube_feed' => [
            'icon' => 'fab fa-youtube',
            'color' => '#282828',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'timeline' => [
            'icon' => 'fas fa-ellipsis-v',
            'color' => '#3c526d',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'review' => [
            'icon' => 'fas fa-star',
            'color' => '#ffe100',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => true,
            'tier' => 'ultimate',
        ],
        'image_slider' => [
            'icon' => 'fas fa-clone',
            'color' => '#290b5b',
            'category' => 'advanced',
            'has_statistics' => true,
            'themable' => false,
            'tier' => 'ultimate',
        ],
        'markdown' => [
            'icon' => 'fas fa-sticky-note',
            'color' => '#ff8300',
            'category' => 'advanced',
            'has_statistics' => false,
            'themable' => true,
            'tier' => 'ultimate',
        ],

        // ─────────────────────────────────────────────────────────────────────
        // Payment Blocks
        // ─────────────────────────────────────────────────────────────────────

        'paypal' => [
            'icon' => 'fab fa-paypal',
            'color' => '#00457C',
            'category' => 'payments',
            'has_statistics' => true,
            'themable' => true,
            'tier' => null,
        ],
        'donation' => [
            'icon' => 'fas fa-hand-holding-usd',
            'color' => '#0d55ad',
            'category' => 'payments',
            'has_statistics' => false,
            'themable' => true,
            'tier' => 'payment',
        ],
        'product' => [
            'icon' => 'fas fa-cube',
            'color' => '#0d1bad',
            'category' => 'payments',
            'has_statistics' => false,
            'themable' => true,
            'tier' => 'payment',
        ],
        'service' => [
            'icon' => 'fas fa-comments-dollar',
            'color' => '#3c0daa',
            'category' => 'payments',
            'has_statistics' => false,
            'themable' => true,
            'tier' => 'payment',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Block Categories
    |--------------------------------------------------------------------------
    |
    | Categories for organising blocks in the editor.
    |
    */

    'categories' => [
        'standard' => [
            'name' => 'Standard',
            'icon' => 'fas fa-cubes',
        ],
        'embeds' => [
            'name' => 'Embeds',
            'icon' => 'fas fa-play',
        ],
        'advanced' => [
            'name' => 'Advanced',
            'icon' => 'fas fa-cogs',
        ],
        'payments' => [
            'name' => 'Payments',
            'icon' => 'fas fa-credit-card',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Platforms
    |--------------------------------------------------------------------------
    |
    | Available platforms for the socials block.
    |
    */

    'social_platforms' => [
        // Major platforms
        'instagram' => ['name' => 'Instagram', 'url' => 'https://instagram.com/', 'icon' => 'fa-brands fa-instagram', 'color' => '#e4405f'],
        'tiktok' => ['name' => 'TikTok', 'url' => 'https://tiktok.com/@', 'icon' => 'fa-brands fa-tiktok', 'color' => '#000000'],
        'youtube' => ['name' => 'YouTube', 'url' => 'https://youtube.com/', 'icon' => 'fa-brands fa-youtube', 'color' => '#ff0000'],
        'twitter' => ['name' => 'X / Twitter', 'url' => 'https://x.com/', 'icon' => 'fa-brands fa-x-twitter', 'color' => '#000000'],
        'facebook' => ['name' => 'Facebook', 'url' => 'https://facebook.com/', 'icon' => 'fa-brands fa-facebook-f', 'color' => '#1877f2'],
        'linkedin' => ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/in/', 'icon' => 'fa-brands fa-linkedin-in', 'color' => '#0a66c2'],
        'threads' => ['name' => 'Threads', 'url' => 'https://threads.net/@', 'icon' => 'fa-brands fa-threads', 'color' => '#000000'],

        // Messaging
        'whatsapp' => ['name' => 'WhatsApp', 'url' => 'https://wa.me/', 'icon' => 'fa-brands fa-whatsapp', 'color' => '#25d366'],
        'telegram' => ['name' => 'Telegram', 'url' => 'https://t.me/', 'icon' => 'fa-brands fa-telegram', 'color' => '#0088cc'],
        'discord' => ['name' => 'Discord', 'url' => '', 'icon' => 'fa-brands fa-discord', 'color' => '#5865f2'],
        'messenger' => ['name' => 'Messenger', 'url' => 'https://m.me/', 'icon' => 'fa-brands fa-facebook-messenger', 'color' => '#0084ff'],
        'snapchat' => ['name' => 'Snapchat', 'url' => 'https://snapchat.com/add/', 'icon' => 'fa-brands fa-snapchat', 'color' => '#fffc00'],

        // Creative / Music
        'spotify' => ['name' => 'Spotify', 'url' => '', 'icon' => 'fa-brands fa-spotify', 'color' => '#1db954'],
        'soundcloud' => ['name' => 'SoundCloud', 'url' => 'https://soundcloud.com/', 'icon' => 'fa-brands fa-soundcloud', 'color' => '#ff5500'],
        'twitch' => ['name' => 'Twitch', 'url' => 'https://twitch.tv/', 'icon' => 'fa-brands fa-twitch', 'color' => '#9146ff'],
        'pinterest' => ['name' => 'Pinterest', 'url' => 'https://pinterest.com/', 'icon' => 'fa-brands fa-pinterest', 'color' => '#bd081c'],
        'dribbble' => ['name' => 'Dribbble', 'url' => 'https://dribbble.com/', 'icon' => 'fa-brands fa-dribbble', 'color' => '#ea4c89'],
        'behance' => ['name' => 'Behance', 'url' => 'https://behance.net/', 'icon' => 'fa-brands fa-behance', 'color' => '#1769ff'],

        // Dev / Tech
        'github' => ['name' => 'GitHub', 'url' => 'https://github.com/', 'icon' => 'fa-brands fa-github', 'color' => '#333333'],

        // Other social
        'reddit' => ['name' => 'Reddit', 'url' => 'https://reddit.com/u/', 'icon' => 'fa-brands fa-reddit-alien', 'color' => '#ff4500'],
        'mastodon' => ['name' => 'Mastodon', 'url' => '', 'icon' => 'fa-brands fa-mastodon', 'color' => '#6364ff'],
        'bluesky' => ['name' => 'Bluesky', 'url' => 'https://bsky.app/profile/', 'icon' => 'fa-brands fa-bluesky', 'color' => '#0085ff'],
        'vk' => ['name' => 'VK', 'url' => 'https://vk.com/', 'icon' => 'fa-brands fa-vk', 'color' => '#4a76a8'],

        // Contact
        'email' => ['name' => 'Email', 'url' => 'mailto:', 'icon' => 'fa-solid fa-envelope', 'color' => '#6b7280'],
        'phone' => ['name' => 'Phone', 'url' => 'tel:', 'icon' => 'fa-solid fa-phone', 'color' => '#22c55e'],
        'website' => ['name' => 'Mod', 'url' => '', 'icon' => 'fa-solid fa-globe', 'color' => '#6366f1'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pixel Types
    |--------------------------------------------------------------------------
    |
    | Available tracking pixel types.
    |
    */

    'pixel_types' => [
        'facebook' => 'Facebook Pixel',
        'google_analytics' => 'Google Analytics',
        'google_tag_manager' => 'Google Tag Manager',
        'google_ads' => 'Google Ads',
        'tiktok' => 'TikTok Pixel',
        'twitter' => 'Twitter Pixel',
        'pinterest' => 'Pinterest Tag',
        'linkedin' => 'LinkedIn Insight',
        'snapchat' => 'Snapchat Pixel',
        'quora' => 'Quora Pixel',
        'bing' => 'Microsoft/Bing UET',
    ],

    /*
    |--------------------------------------------------------------------------
    | vCard Fields
    |--------------------------------------------------------------------------
    |
    | Field configuration for vCard block.
    |
    */

    'vcard_fields' => [
        'first_name' => ['max_length' => 64],
        'last_name' => ['max_length' => 64],
        'email' => ['max_length' => 320],
        'url' => ['max_length' => 1024],
        'company' => ['max_length' => 64],
        'job_title' => ['max_length' => 64],
        'birthday' => ['max_length' => 16],
        'street' => ['max_length' => 128],
        'city' => ['max_length' => 64],
        'zip' => ['max_length' => 32],
        'region' => ['max_length' => 32],
        'country' => ['max_length' => 32],
        'note' => ['max_length' => 512],
        'phone_number_label' => ['max_length' => 32],
        'phone_number_value' => ['max_length' => 32],
        'social_label' => ['max_length' => 32],
        'social_value' => ['max_length' => 1024],
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Settings
    |--------------------------------------------------------------------------
    |
    | Default theme settings for new biolinks.
    |
    */

    'default_theme' => [
        'background' => [
            'type' => 'color',
            'color' => '#f5f5f5',
        ],
        'buttons' => [
            'style' => 'rounded',
            'background_color' => '#000000',
            'text_color' => '#ffffff',
            'border_radius' => '8px',
        ],
        'fonts' => [
            'family' => 'Inter',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Extensions
    |--------------------------------------------------------------------------
    |
    | Allowed file extensions for uploads.
    |
    */

    'allowed_extensions' => [
        'images' => ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', 'avif'],
        'videos' => ['mp4', 'webm'],
        'audio' => ['mp3', 'm4a', 'wav'],
        'documents' => ['pdf', 'zip', 'rar', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'odp', 'ods'],
    ],

    /*
    |--------------------------------------------------------------------------
    | OG Images
    |--------------------------------------------------------------------------
    |
    | Settings for dynamically generated Open Graph images for social sharing.
    | These images improve link previews on platforms like Facebook, Twitter,
    | LinkedIn, and messaging apps.
    |
    */

    'og_images' => [
        // Enable or disable OG image generation
        'enabled' => env('BIOLINKS_OG_IMAGES_ENABLED', true),

        // Image quality (1-100, default 85)
        'quality' => env('BIOLINKS_OG_IMAGES_QUALITY', 85),

        // Cache duration in days (default 10)
        'cache_days' => env('BIOLINKS_OG_IMAGES_CACHE_DAYS', 10),

        // Default background colour when biolink has no custom background
        'default_background' => env('BIOLINKS_OG_IMAGES_BG', '#ffffff'),

        // Font to use for text (place font files in public/fonts or resources/fonts)
        'font' => env('BIOLINKS_OG_IMAGES_FONT', 'Inter'),

        // Default template (default, minimal, branded)
        'default_template' => env('BIOLINKS_OG_IMAGES_TEMPLATE', 'default'),
    ],

];
