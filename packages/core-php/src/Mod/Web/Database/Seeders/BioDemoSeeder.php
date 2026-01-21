<?php

namespace Core\Mod\Web\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Theme;

class BioDemoSeeder extends Seeder
{
    /**
     * Vi-themed demo pages showcasing all BioLink block types.
     *
     * Creates demo pages at /demo-{block-type} for testing and showcasing
     * the platform's capabilities. Vi (Host UK's mascot) is the personality
     * for all demos.
     */
    protected ?Workspace $workspace = null;

    protected ?User $systemUser = null;

    protected ?Theme $theme = null;

    public function run(): void
    {
        if (! Schema::hasTable('biolinks') || ! Schema::hasTable('users')) {
            return;
        }

        if (! $this->setupContext()) {
            return;
        }

        // Create demo index page
        $this->createDemoIndexPage();

        // Create individual block demos
        foreach ($this->getBlockDemos() as $type => $data) {
            $this->createBlockDemo($type, $data);
        }

        $this->command->info('Created '.count($this->getBlockDemos()).' demo pages with demo- prefix');
    }

    protected function setupContext(): bool
    {
        // Get system user (ID 1)
        $this->systemUser = User::find(1);

        if (! $this->systemUser) {
            $this->command->warn('System user (ID 1) not found. Run SystemUserSeeder first.');

            return false;
        }

        // Get system workspace
        $this->workspace = Workspace::where('slug', 'system')->first();

        if (! $this->workspace) {
            $this->command->warn('System workspace not found. Run SystemUserSeeder first.');

            return false;
        }

        $this->theme = Theme::where('slug', 'tokyo')->first(); // Purple gradient - matches Vi

        return true;
    }

    /**
     * Create the demo index page listing all block demos.
     */
    protected function createDemoIndexPage(): void
    {
        $biolink = Page::updateOrCreate(
            ['url' => 'demo-index'],
            [
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->systemUser->id,
                'theme_id' => $this->theme?->id,
                'type' => 'biolink',
                'settings' => [
                    'page_title' => 'Vi\'s Block Showcase',
                    'seo_title' => 'BioHost Block Demos - Vi\'s Showcase',
                    'seo_description' => 'Explore all 60+ block types available in BioHost. Live demos of every feature.',
                    'verified_badge' => true,
                ],
                'is_enabled' => true,
                'is_verified' => true,
            ]
        );

        $this->seedBlocks($biolink, [
            ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => 'images/vi/vi_dashboard.webp', 'alt' => 'Vi', 'size' => 120, 'border_radius' => 'rounded-full']],
            ['type' => 'heading', 'order' => 2, 'settings' => ['text' => 'Vi\'s Block Showcase', 'level' => 'h1']],
            ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => 'Welcome! I\'m Vi, and this is my collection of block demos. Each page shows what a block type can do. Click any link below to see it in action.']],
            ['type' => 'divider', 'order' => 4, 'settings' => ['style' => 'solid']],

            // Basic Blocks Section
            ['type' => 'heading', 'order' => 10, 'settings' => ['text' => 'Basic Blocks', 'level' => 'h3']],
            ['type' => 'link', 'order' => 11, 'location_url' => '/demo-avatar', 'settings' => ['name' => 'Avatar', 'icon' => 'user', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 12, 'location_url' => '/demo-heading', 'settings' => ['name' => 'Heading', 'icon' => 'heading', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 13, 'location_url' => '/demo-paragraph', 'settings' => ['name' => 'Paragraph', 'icon' => 'align-left', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 14, 'location_url' => '/demo-divider', 'settings' => ['name' => 'Divider', 'icon' => 'minus', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 15, 'location_url' => '/demo-link', 'settings' => ['name' => 'Link Button', 'icon' => 'link', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 16, 'location_url' => '/demo-big-link', 'settings' => ['name' => 'Big Link', 'icon' => 'external-link', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 17, 'location_url' => '/demo-image', 'settings' => ['name' => 'Image', 'icon' => 'image', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 18, 'location_url' => '/demo-header', 'settings' => ['name' => 'Header', 'icon' => 'window-maximize', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff']],

            // Social Blocks Section
            ['type' => 'heading', 'order' => 20, 'settings' => ['text' => 'Social & Profiles', 'level' => 'h3']],
            ['type' => 'link', 'order' => 21, 'location_url' => '/demo-socials', 'settings' => ['name' => 'Social Icons', 'icon' => 'share-alt', 'background_color' => '#3b82f6', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 22, 'location_url' => '/demo-discord', 'settings' => ['name' => 'Discord', 'icon' => 'discord', 'background_color' => '#5865F2', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 23, 'location_url' => '/demo-telegram', 'settings' => ['name' => 'Telegram', 'icon' => 'telegram', 'background_color' => '#0088cc', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 24, 'location_url' => '/demo-snapchat', 'settings' => ['name' => 'Snapchat', 'icon' => 'snapchat', 'background_color' => '#FFFC00', 'text_color' => '#000000']],
            ['type' => 'link', 'order' => 25, 'location_url' => '/demo-tiktok-profile', 'settings' => ['name' => 'TikTok Profile', 'icon' => 'tiktok', 'background_color' => '#000000', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 26, 'location_url' => '/demo-facebook', 'settings' => ['name' => 'Facebook', 'icon' => 'facebook', 'background_color' => '#1877F2', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 27, 'location_url' => '/demo-reddit', 'settings' => ['name' => 'Reddit', 'icon' => 'reddit', 'background_color' => '#FF4500', 'text_color' => '#ffffff']],

            // Media Embeds Section
            ['type' => 'heading', 'order' => 30, 'settings' => ['text' => 'Media Embeds', 'level' => 'h3']],
            ['type' => 'link', 'order' => 31, 'location_url' => '/demo-youtube', 'settings' => ['name' => 'YouTube', 'icon' => 'youtube', 'background_color' => '#FF0000', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 32, 'location_url' => '/demo-spotify', 'settings' => ['name' => 'Spotify', 'icon' => 'spotify', 'background_color' => '#1DB954', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 33, 'location_url' => '/demo-soundcloud', 'settings' => ['name' => 'SoundCloud', 'icon' => 'soundcloud', 'background_color' => '#FF5500', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 34, 'location_url' => '/demo-twitch', 'settings' => ['name' => 'Twitch', 'icon' => 'twitch', 'background_color' => '#9146FF', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 35, 'location_url' => '/demo-vimeo', 'settings' => ['name' => 'Vimeo', 'icon' => 'vimeo', 'background_color' => '#1ab7ea', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 36, 'location_url' => '/demo-applemusic', 'settings' => ['name' => 'Apple Music', 'icon' => 'apple', 'background_color' => '#FA243C', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 37, 'location_url' => '/demo-tiktok-video', 'settings' => ['name' => 'TikTok Video', 'icon' => 'tiktok', 'background_color' => '#000000', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 38, 'location_url' => '/demo-tidal', 'settings' => ['name' => 'Tidal', 'icon' => 'music', 'background_color' => '#000000', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 39, 'location_url' => '/demo-mixcloud', 'settings' => ['name' => 'Mixcloud', 'icon' => 'music', 'background_color' => '#5000FF', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 40, 'location_url' => '/demo-kick', 'settings' => ['name' => 'Kick', 'icon' => 'video', 'background_color' => '#53FC18', 'text_color' => '#000000']],
            ['type' => 'link', 'order' => 41, 'location_url' => '/demo-rumble', 'settings' => ['name' => 'Rumble', 'icon' => 'video', 'background_color' => '#85C742', 'text_color' => '#000000']],
            ['type' => 'link', 'order' => 42, 'location_url' => '/demo-audio', 'settings' => ['name' => 'Audio Player', 'icon' => 'music', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 43, 'location_url' => '/demo-video', 'settings' => ['name' => 'Video Player', 'icon' => 'video', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 44, 'location_url' => '/demo-pdf-document', 'settings' => ['name' => 'PDF Document', 'icon' => 'file-pdf', 'background_color' => '#ef4444', 'text_color' => '#ffffff']],

            // Social Media Embeds Section
            ['type' => 'heading', 'order' => 50, 'settings' => ['text' => 'Social Media Embeds', 'level' => 'h3']],
            ['type' => 'link', 'order' => 51, 'location_url' => '/demo-twitter-tweet', 'settings' => ['name' => 'Twitter/X Tweet', 'icon' => 'x-twitter', 'background_color' => '#000000', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 52, 'location_url' => '/demo-twitter-video', 'settings' => ['name' => 'Twitter/X Video', 'icon' => 'x-twitter', 'background_color' => '#000000', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 53, 'location_url' => '/demo-pinterest-profile', 'settings' => ['name' => 'Pinterest', 'icon' => 'pinterest', 'background_color' => '#E60023', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 54, 'location_url' => '/demo-instagram-media', 'settings' => ['name' => 'Instagram Media', 'icon' => 'instagram', 'background_color' => '#E4405F', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 55, 'location_url' => '/demo-vk-video', 'settings' => ['name' => 'VK Video', 'icon' => 'vk', 'background_color' => '#4680C2', 'text_color' => '#ffffff']],

            // Interactive Section
            ['type' => 'heading', 'order' => 60, 'settings' => ['text' => 'Interactive', 'level' => 'h3']],
            ['type' => 'link', 'order' => 61, 'location_url' => '/demo-countdown', 'settings' => ['name' => 'Countdown Timer', 'icon' => 'clock', 'background_color' => '#f59e0b', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 62, 'location_url' => '/demo-faq', 'settings' => ['name' => 'FAQ Accordion', 'icon' => 'question-circle', 'background_color' => '#f59e0b', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 63, 'location_url' => '/demo-map', 'settings' => ['name' => 'Map', 'icon' => 'map-marker-alt', 'background_color' => '#f59e0b', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 64, 'location_url' => '/demo-business-hours', 'settings' => ['name' => 'Business Hours', 'icon' => 'clock', 'background_color' => '#f59e0b', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 65, 'location_url' => '/demo-timeline', 'settings' => ['name' => 'Timeline', 'icon' => 'stream', 'background_color' => '#f59e0b', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 66, 'location_url' => '/demo-calendly', 'settings' => ['name' => 'Calendly', 'icon' => 'calendar', 'background_color' => '#006BFF', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 67, 'location_url' => '/demo-typeform', 'settings' => ['name' => 'Typeform', 'icon' => 'clipboard-list', 'background_color' => '#262627', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 68, 'location_url' => '/demo-rss-feed', 'settings' => ['name' => 'RSS Feed', 'icon' => 'rss', 'background_color' => '#f59e0b', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 69, 'location_url' => '/demo-iframe', 'settings' => ['name' => 'iFrame Embed', 'icon' => 'code', 'background_color' => '#6b7280', 'text_color' => '#ffffff']],

            // Collectors Section
            ['type' => 'heading', 'order' => 70, 'settings' => ['text' => 'Lead Collectors', 'level' => 'h3']],
            ['type' => 'link', 'order' => 71, 'location_url' => '/demo-email-collector', 'settings' => ['name' => 'Email Collector', 'icon' => 'envelope', 'background_color' => '#10b981', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 72, 'location_url' => '/demo-phone-collector', 'settings' => ['name' => 'Phone Collector', 'icon' => 'phone', 'background_color' => '#10b981', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 73, 'location_url' => '/demo-contact-collector', 'settings' => ['name' => 'Contact Form', 'icon' => 'address-card', 'background_color' => '#10b981', 'text_color' => '#ffffff']],

            // Commerce Section
            ['type' => 'heading', 'order' => 80, 'settings' => ['text' => 'Commerce', 'level' => 'h3']],
            ['type' => 'link', 'order' => 81, 'location_url' => '/demo-product', 'settings' => ['name' => 'Product Card', 'icon' => 'shopping-bag', 'background_color' => '#ec4899', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 82, 'location_url' => '/demo-service', 'settings' => ['name' => 'Service Card', 'icon' => 'concierge-bell', 'background_color' => '#ec4899', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 83, 'location_url' => '/demo-donation', 'settings' => ['name' => 'Donation', 'icon' => 'heart', 'background_color' => '#ec4899', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 84, 'location_url' => '/demo-coupon', 'settings' => ['name' => 'Coupon', 'icon' => 'ticket-alt', 'background_color' => '#ec4899', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 85, 'location_url' => '/demo-paypal', 'settings' => ['name' => 'PayPal', 'icon' => 'paypal', 'background_color' => '#003087', 'text_color' => '#ffffff']],

            // Content Section
            ['type' => 'heading', 'order' => 90, 'settings' => ['text' => 'Content', 'level' => 'h3']],
            ['type' => 'link', 'order' => 91, 'location_url' => '/demo-modal-text', 'settings' => ['name' => 'Modal Text', 'icon' => 'window-restore', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 92, 'location_url' => '/demo-image-grid', 'settings' => ['name' => 'Image Grid', 'icon' => 'th', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 93, 'location_url' => '/demo-image-slider', 'settings' => ['name' => 'Image Slider', 'icon' => 'images', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 94, 'location_url' => '/demo-list', 'settings' => ['name' => 'List', 'icon' => 'list', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 95, 'location_url' => '/demo-alert', 'settings' => ['name' => 'Alert', 'icon' => 'exclamation-circle', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 96, 'location_url' => '/demo-cta', 'settings' => ['name' => 'Call to Action', 'icon' => 'bullhorn', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 97, 'location_url' => '/demo-review', 'settings' => ['name' => 'Review', 'icon' => 'star', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 98, 'location_url' => '/demo-vcard', 'settings' => ['name' => 'vCard Download', 'icon' => 'id-card', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 99, 'location_url' => '/demo-share', 'settings' => ['name' => 'Share Buttons', 'icon' => 'share', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 100, 'location_url' => '/demo-custom-html', 'settings' => ['name' => 'Custom HTML', 'icon' => 'code', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 101, 'location_url' => '/demo-file', 'settings' => ['name' => 'File Download', 'icon' => 'file-download', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],
            ['type' => 'link', 'order' => 102, 'location_url' => '/demo-markdown', 'settings' => ['name' => 'Markdown', 'icon' => 'file-alt', 'background_color' => '#6366f1', 'text_color' => '#ffffff']],

            // Footer
            ['type' => 'divider', 'order' => 110, 'settings' => ['style' => 'solid']],
            ['type' => 'paragraph', 'order' => 111, 'settings' => ['text' => 'Built with BioHost by Host UK', 'alignment' => 'center']],
        ]);

        $this->command->info('Created demo index page: /demo-index');
    }

    /**
     * Create a demo page for a specific block type.
     */
    protected function createBlockDemo(string $type, array $data): void
    {
        $slug = 'demo-'.Str::slug(str_replace('_', '-', $type));

        $biolink = Page::updateOrCreate(
            ['url' => $slug],
            [
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->systemUser->id,
                'theme_id' => $this->theme?->id,
                'type' => 'biolink',
                'settings' => [
                    'page_title' => $data['title'] ?? ucwords(str_replace('_', ' ', $type)).' Demo',
                    'seo_title' => 'BioHost Demo: '.($data['title'] ?? ucwords(str_replace('_', ' ', $type))),
                    'seo_description' => $data['description'] ?? "Live demo of the {$type} block type in BioHost.",
                    'verified_badge' => true,
                ],
                'is_enabled' => true,
                'is_verified' => true,
            ]
        );

        $blocks = [
            // Vi header
            ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => 'images/vi/vi_dashboard.webp', 'alt' => 'Vi', 'size' => 80, 'border_radius' => 'rounded-full']],
            ['type' => 'heading', 'order' => 2, 'settings' => ['text' => $data['title'] ?? ucwords(str_replace('_', ' ', $type)), 'level' => 'h1']],
            ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => $data['vi_intro'] ?? "Here's the {$type} block in action."]],
            ['type' => 'divider', 'order' => 4, 'settings' => ['style' => 'dashed']],
        ];

        // Add the demo block(s)
        $order = 10;
        foreach ($data['blocks'] as $block) {
            $block['order'] = $order++;
            $blocks[] = $block;
        }

        // Back to index link
        $blocks[] = ['type' => 'divider', 'order' => 90, 'settings' => ['style' => 'solid']];
        $blocks[] = ['type' => 'link', 'order' => 91, 'location_url' => '/demo-index', 'settings' => ['name' => 'Back to All Demos', 'icon' => 'arrow-left', 'background_color' => '#6b7280', 'text_color' => '#ffffff']];

        $this->seedBlocks($biolink, $blocks);
    }

    /**
     * Get demo configuration for each block type.
     */
    protected function getBlockDemos(): array
    {
        return [
            // ─────────────────────────────────────────────────────────────────────
            // Basic Blocks
            // ─────────────────────────────────────────────────────────────────────

            'avatar' => [
                'title' => 'Avatar Block',
                'description' => 'Display profile pictures with customisable shapes and sizes.',
                'vi_intro' => 'The avatar block is perfect for personal branding. You can make it round, square, or anything in between.',
                'blocks' => [
                    ['type' => 'avatar', 'settings' => ['image' => 'images/vi/master_vi.webp', 'alt' => 'Vi - Full Size', 'size' => 150, 'border_radius' => 'rounded-full']],
                    ['type' => 'paragraph', 'settings' => ['text' => 'Round avatar - great for personal pages', 'alignment' => 'center']],
                    ['type' => 'avatar', 'settings' => ['image' => 'images/host-uk-raven.svg', 'alt' => 'Host UK Logo', 'size' => 100, 'border_radius' => 'rounded-lg']],
                    ['type' => 'paragraph', 'settings' => ['text' => 'Rounded square - perfect for logos', 'alignment' => 'center']],
                ],
            ],

            'heading' => [
                'title' => 'Heading Block',
                'description' => 'Text headings from H1 to H6 with alignment options.',
                'vi_intro' => 'Headings help organise your page. I\'ve got six sizes to choose from.',
                'blocks' => [
                    ['type' => 'heading', 'settings' => ['text' => 'H1 - Main Title', 'level' => 'h1', 'alignment' => 'center']],
                    ['type' => 'heading', 'settings' => ['text' => 'H2 - Section Header', 'level' => 'h2', 'alignment' => 'center']],
                    ['type' => 'heading', 'settings' => ['text' => 'H3 - Subsection', 'level' => 'h3', 'alignment' => 'center']],
                    ['type' => 'heading', 'settings' => ['text' => 'H4 - Minor heading', 'level' => 'h4', 'alignment' => 'left']],
                ],
            ],

            'paragraph' => [
                'title' => 'Paragraph Block',
                'description' => 'Body text with alignment and styling options.',
                'vi_intro' => 'Sometimes you just need to say a bit more. That\'s what paragraphs are for.',
                'blocks' => [
                    ['type' => 'paragraph', 'settings' => ['text' => 'This is a centred paragraph. Great for intros and bios where you want the text to feel balanced.', 'alignment' => 'center']],
                    ['type' => 'paragraph', 'settings' => ['text' => 'Left-aligned text works well for longer content. It\'s easier to read when there\'s more to say. Your visitors will thank you for making things scannable.', 'alignment' => 'left']],
                ],
            ],

            'divider' => [
                'title' => 'Divider Block',
                'description' => 'Visual separators in solid, dashed, or dotted styles.',
                'vi_intro' => 'Dividers help break up your content into digestible sections. Here are the three styles.',
                'blocks' => [
                    ['type' => 'paragraph', 'settings' => ['text' => 'Solid divider below:', 'alignment' => 'center']],
                    ['type' => 'divider', 'settings' => ['style' => 'solid']],
                    ['type' => 'paragraph', 'settings' => ['text' => 'Dashed divider below:', 'alignment' => 'center']],
                    ['type' => 'divider', 'settings' => ['style' => 'dashed']],
                    ['type' => 'paragraph', 'settings' => ['text' => 'Dotted divider below:', 'alignment' => 'center']],
                    ['type' => 'divider', 'settings' => ['style' => 'dotted']],
                ],
            ],

            'link' => [
                'title' => 'Link Button',
                'description' => 'Clickable buttons with customisable colours and icons.',
                'vi_intro' => 'The link button is your bread and butter. Every bio page needs these.',
                'blocks' => [
                    ['type' => 'link', 'location_url' => 'https://host.uk.com', 'settings' => ['name' => 'Visit Host UK', 'icon' => 'globe', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff', 'border_radius' => 'rounded']],
                    ['type' => 'link', 'location_url' => 'https://github.com/host-uk', 'settings' => ['name' => 'GitHub', 'icon' => 'github', 'background_color' => '#1f2937', 'text_color' => '#ffffff', 'border_radius' => 'rounded-full']],
                    ['type' => 'link', 'location_url' => 'mailto:hello@host.uk.com', 'settings' => ['name' => 'Email Me', 'icon' => 'envelope', 'background_color' => '#10b981', 'text_color' => '#ffffff', 'border_radius' => 'rounded-lg']],
                ],
            ],

            'big_link' => [
                'title' => 'Big Link Block',
                'description' => 'Prominent link cards with images and descriptions.',
                'vi_intro' => 'When you really want something to stand out, use a big link. It\'s got room for an image and description.',
                'blocks' => [
                    ['type' => 'big_link', 'location_url' => 'https://host.uk.com/services', 'settings' => ['name' => 'Our Services', 'description' => 'Bio pages, social scheduling, analytics, and more. Six tools that work together.', 'image' => 'images/vi/vi_dashboard.webp', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff']],
                ],
            ],

            'image' => [
                'title' => 'Image Block',
                'description' => 'Display images with optional links and alt text.',
                'vi_intro' => 'A picture\'s worth a thousand words. Or so they say.',
                'blocks' => [
                    ['type' => 'image', 'settings' => ['image' => 'images/vi/master_vi.webp', 'alt' => 'Vi waving hello']],
                ],
            ],

            'header' => [
                'title' => 'Header Block',
                'description' => 'Hero section with background and overlay text.',
                'vi_intro' => 'Headers make a strong first impression. Great for branding.',
                'blocks' => [
                    ['type' => 'header', 'settings' => ['text' => 'Welcome to My Page', 'size' => 'large', 'background_color' => '#8b5cf6', 'text_color' => '#ffffff']],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────────
            // Social Blocks
            // ─────────────────────────────────────────────────────────────────────

            'socials' => [
                'title' => 'Social Icons',
                'description' => 'A row of social media icons linking to your profiles.',
                'vi_intro' => 'Display all your social profiles in one neat row. I support 21+ platforms.',
                'blocks' => [
                    ['type' => 'socials', 'settings' => [
                        'platforms' => [
                            'twitter' => 'https://twitter.com/hostukcom',
                            'github' => 'https://github.com/host-uk',
                            'instagram' => 'https://instagram.com/hostuk',
                            'youtube' => 'https://youtube.com/@hostuk',
                            'linkedin' => 'https://linkedin.com/company/hostuk',
                        ],
                    ]],
                ],
            ],

            'discord' => [
                'title' => 'Discord Block',
                'description' => 'Branded Discord server invite button.',
                'vi_intro' => 'Connect your community with a Discord invite.',
                'blocks' => [
                    ['type' => 'discord', 'settings' => ['server_id' => 'hostuk', 'button_text' => 'Join Our Discord']],
                ],
            ],

            'telegram' => [
                'title' => 'Telegram Block',
                'description' => 'Telegram channel or group link button.',
                'vi_intro' => 'Telegram users can join your channel with one click.',
                'blocks' => [
                    ['type' => 'telegram', 'settings' => ['url' => 'https://t.me/hostuk', 'button_text' => 'Join on Telegram']],
                ],
            ],

            'snapchat' => [
                'title' => 'Snapchat Block',
                'description' => 'Add me on Snapchat button with branded styling.',
                'vi_intro' => 'The iconic yellow Snapchat button. Your fans know what to do.',
                'blocks' => [
                    ['type' => 'snapchat', 'settings' => ['url' => 'vi_hostuk', 'button_text' => 'Add me on Snapchat']],
                ],
            ],

            'tiktok_profile' => [
                'title' => 'TikTok Profile',
                'description' => 'Link to your TikTok profile.',
                'vi_intro' => 'For when you need that TikTok energy on your bio page.',
                'blocks' => [
                    ['type' => 'tiktok_profile', 'settings' => ['username' => 'hostuk']],
                ],
            ],

            'facebook' => [
                'title' => 'Facebook Block',
                'description' => 'Facebook page or profile link button.',
                'vi_intro' => 'Connect your Facebook presence with a branded button.',
                'blocks' => [
                    ['type' => 'facebook', 'settings' => ['url' => 'https://facebook.com/hostuk', 'button_text' => 'Follow on Facebook']],
                ],
            ],

            'reddit' => [
                'title' => 'Reddit Block',
                'description' => 'Reddit profile or subreddit link.',
                'vi_intro' => 'For the Redditors among us. Link to your profile or community.',
                'blocks' => [
                    ['type' => 'reddit', 'settings' => ['url' => 'https://reddit.com/r/webdev', 'button_text' => 'Join the Community']],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────────
            // Media Embeds
            // ─────────────────────────────────────────────────────────────────────

            'youtube' => [
                'title' => 'YouTube Embed',
                'description' => 'Embedded YouTube video player.',
                'vi_intro' => 'Embed any YouTube video directly on your page. Perfect for trailers, tutorials, or music videos.',
                'blocks' => [
                    ['type' => 'youtube', 'settings' => ['video_id' => 'dQw4w9WgXcQ']],
                    ['type' => 'paragraph', 'settings' => ['text' => 'A timeless classic. You knew it was coming.', 'alignment' => 'center']],
                ],
            ],

            'spotify' => [
                'title' => 'Spotify Embed',
                'description' => 'Embedded Spotify track, album, or playlist.',
                'vi_intro' => 'Share your favourite tracks, albums, or curated playlists.',
                'blocks' => [
                    ['type' => 'spotify', 'settings' => ['uri' => 'track/4PTG3Z6ehGkBFwjybzWkR8']],
                ],
            ],

            'soundcloud' => [
                'title' => 'SoundCloud Embed',
                'description' => 'Embedded SoundCloud track or playlist.',
                'vi_intro' => 'For independent artists and DJs, SoundCloud is essential.',
                'blocks' => [
                    ['type' => 'soundcloud', 'settings' => ['url' => 'https://soundcloud.com/miami-nights-1984/accelerated']],
                ],
            ],

            'twitch' => [
                'title' => 'Twitch Embed',
                'description' => 'Embedded Twitch channel or video.',
                'vi_intro' => 'Show off your stream or a favourite clip.',
                'blocks' => [
                    ['type' => 'twitch', 'settings' => ['channel' => 'hostuk']],
                ],
            ],

            'vimeo' => [
                'title' => 'Vimeo Embed',
                'description' => 'Embedded Vimeo video player.',
                'vi_intro' => 'For the filmmakers and creators who prefer Vimeo\'s quality.',
                'blocks' => [
                    ['type' => 'vimeo', 'settings' => ['video_id' => '76979871']],
                ],
            ],

            'applemusic' => [
                'title' => 'Apple Music Embed',
                'description' => 'Embedded Apple Music player.',
                'vi_intro' => 'Share music from the Apple ecosystem.',
                'blocks' => [
                    ['type' => 'applemusic', 'settings' => ['url' => 'https://music.apple.com/gb/album/blinding-lights/1488408555?i=1488408568']],
                ],
            ],

            'tiktok_video' => [
                'title' => 'TikTok Video Embed',
                'description' => 'Embedded TikTok video player.',
                'vi_intro' => 'Embed your best TikTok content directly on your page.',
                'blocks' => [
                    ['type' => 'tiktok_video', 'settings' => ['url' => 'https://www.tiktok.com/@tiktok/video/7106594312292453675']],
                ],
            ],

            'tidal' => [
                'title' => 'Tidal Embed',
                'description' => 'Embedded Tidal track or album.',
                'vi_intro' => 'For the audiophiles who prefer high-fidelity streaming.',
                'blocks' => [
                    ['type' => 'tidal', 'settings' => ['url' => 'https://tidal.com/browse/track/77814238']],
                ],
            ],

            'mixcloud' => [
                'title' => 'Mixcloud Embed',
                'description' => 'Embedded Mixcloud show or DJ set.',
                'vi_intro' => 'Perfect for DJs and radio show hosts.',
                'blocks' => [
                    ['type' => 'mixcloud', 'settings' => ['url' => 'https://www.mixcloud.com/NTSRadio/']],
                ],
            ],

            'kick' => [
                'title' => 'Kick Embed',
                'description' => 'Embedded Kick stream or clip.',
                'vi_intro' => 'The new streaming platform making waves. Embed your Kick content.',
                'blocks' => [
                    ['type' => 'kick', 'settings' => ['channel' => 'hostuk']],
                ],
            ],

            'rumble' => [
                'title' => 'Rumble Embed',
                'description' => 'Embedded Rumble video player.',
                'vi_intro' => 'Share your Rumble videos with your audience.',
                'blocks' => [
                    ['type' => 'rumble', 'settings' => ['video_id' => 'v1abc12']],
                ],
            ],

            'audio' => [
                'title' => 'Audio Player',
                'description' => 'Custom audio file player.',
                'vi_intro' => 'Upload and play your own audio files. Podcasts, music, whatever you like.',
                'blocks' => [
                    ['type' => 'audio', 'settings' => ['url' => 'https://example.com/sample.mp3', 'title' => 'Sample Audio Track']],
                ],
            ],

            'video' => [
                'title' => 'Video Player',
                'description' => 'Custom video file player.',
                'vi_intro' => 'Host your own videos without third-party platforms.',
                'blocks' => [
                    ['type' => 'video', 'settings' => ['url' => 'https://example.com/sample.mp4', 'poster' => null]],
                ],
            ],

            'pdf_document' => [
                'title' => 'PDF Document',
                'description' => 'Embedded PDF viewer or download link.',
                'vi_intro' => 'Share documents, menus, portfolios - anything in PDF format.',
                'blocks' => [
                    ['type' => 'pdf_document', 'settings' => ['url' => 'https://example.com/sample.pdf', 'title' => 'Download Brochure']],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────────
            // Social Media Embeds
            // ─────────────────────────────────────────────────────────────────────

            'twitter_tweet' => [
                'title' => 'Twitter/X Tweet',
                'description' => 'Embedded tweet display.',
                'vi_intro' => 'Showcase your best tweets or important announcements.',
                'blocks' => [
                    ['type' => 'twitter_tweet', 'settings' => ['tweet_id' => '1234567890']],
                ],
            ],

            'twitter_video' => [
                'title' => 'Twitter/X Video',
                'description' => 'Embedded Twitter video player.',
                'vi_intro' => 'Embed video content from Twitter/X.',
                'blocks' => [
                    ['type' => 'twitter_video', 'settings' => ['tweet_id' => '1234567890']],
                ],
            ],

            'pinterest_profile' => [
                'title' => 'Pinterest Profile',
                'description' => 'Pinterest profile or board embed.',
                'vi_intro' => 'For the visual creators and pinners out there.',
                'blocks' => [
                    ['type' => 'pinterest_profile', 'settings' => ['url' => 'https://pinterest.com/hostuk']],
                ],
            ],

            'instagram_media' => [
                'title' => 'Instagram Media',
                'description' => 'Embedded Instagram post or reel.',
                'vi_intro' => 'Feature your best Instagram content directly on your page.',
                'blocks' => [
                    ['type' => 'instagram_media', 'settings' => ['url' => 'https://www.instagram.com/p/ABC123/']],
                ],
            ],

            'vk_video' => [
                'title' => 'VK Video',
                'description' => 'Embedded VK video player.',
                'vi_intro' => 'For audiences on VKontakte.',
                'blocks' => [
                    ['type' => 'vk_video', 'settings' => ['url' => 'https://vk.com/video123456789']],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────────
            // Interactive Blocks
            // ─────────────────────────────────────────────────────────────────────

            'countdown' => [
                'title' => 'Countdown Timer',
                'description' => 'Live countdown to a specific date and time.',
                'vi_intro' => 'Build anticipation for your launch, event, or release.',
                'blocks' => [
                    ['type' => 'countdown', 'settings' => [
                        'title' => 'Something exciting is coming',
                        'end_date' => now()->addDays(30)->toDateString(),
                        'end_time' => '12:00',
                    ]],
                ],
            ],

            'faq' => [
                'title' => 'FAQ Accordion',
                'description' => 'Expandable frequently asked questions.',
                'vi_intro' => 'Answer common questions without cluttering your page.',
                'blocks' => [
                    ['type' => 'faq', 'settings' => [
                        'items' => [
                            ['question' => 'What is BioHost?', 'answer' => 'BioHost is a link-in-bio platform with 60+ block types, custom domains, and analytics.'],
                            ['question' => 'Is it free?', 'answer' => 'We have a generous free tier. Premium features are available with paid plans.'],
                            ['question' => 'Can I use my own domain?', 'answer' => 'Yes! You can connect any custom domain to your bio page.'],
                        ],
                    ]],
                ],
            ],

            'map' => [
                'title' => 'Map Block',
                'description' => 'Embedded Google Maps location.',
                'vi_intro' => 'Help people find you with an interactive map.',
                'blocks' => [
                    ['type' => 'map', 'settings' => ['address' => 'London, United Kingdom', 'zoom' => 12]],
                ],
            ],

            'business_hours' => [
                'title' => 'Business Hours',
                'description' => 'Display your operating hours.',
                'vi_intro' => 'Let visitors know when you\'re available.',
                'blocks' => [
                    ['type' => 'business_hours', 'settings' => [
                        'hours' => [
                            ['day' => 'Monday', 'open' => '09:00', 'close' => '17:00'],
                            ['day' => 'Tuesday', 'open' => '09:00', 'close' => '17:00'],
                            ['day' => 'Wednesday', 'open' => '09:00', 'close' => '17:00'],
                            ['day' => 'Thursday', 'open' => '09:00', 'close' => '17:00'],
                            ['day' => 'Friday', 'open' => '09:00', 'close' => '17:00'],
                            ['day' => 'Saturday', 'closed' => true],
                            ['day' => 'Sunday', 'closed' => true],
                        ],
                    ]],
                ],
            ],

            'timeline' => [
                'title' => 'Timeline Block',
                'description' => 'Visual timeline of events or milestones.',
                'vi_intro' => 'Tell your story through time. Perfect for portfolios and histories.',
                'blocks' => [
                    ['type' => 'timeline', 'settings' => [
                        'events' => [
                            ['date' => '2024', 'title' => 'Host UK Founded', 'description' => 'Started building the digital marketing toolkit.'],
                            ['date' => '2025', 'title' => 'BioHost Launch', 'description' => 'Released our link-in-bio platform with 60+ blocks.'],
                            ['date' => 'Coming', 'title' => 'SocialHost', 'description' => 'Full social media management platform.'],
                        ],
                    ]],
                ],
            ],

            'calendly' => [
                'title' => 'Calendly Embed',
                'description' => 'Embedded Calendly scheduling widget.',
                'vi_intro' => 'Let visitors book meetings directly from your page.',
                'blocks' => [
                    ['type' => 'calendly', 'settings' => ['url' => 'https://calendly.com/hostuk/30min']],
                ],
            ],

            'typeform' => [
                'title' => 'Typeform Embed',
                'description' => 'Embedded Typeform survey or quiz.',
                'vi_intro' => 'Collect feedback, run surveys, or create quizzes.',
                'blocks' => [
                    ['type' => 'typeform', 'settings' => ['form_id' => 'abc123']],
                ],
            ],

            'rss_feed' => [
                'title' => 'RSS Feed',
                'description' => 'Display latest posts from an RSS feed.',
                'vi_intro' => 'Pull in your blog posts, podcast episodes, or news automatically.',
                'blocks' => [
                    ['type' => 'rss_feed', 'settings' => ['url' => 'https://example.com/feed.xml', 'limit' => 5]],
                ],
            ],

            'iframe' => [
                'title' => 'iFrame Embed',
                'description' => 'Embed any external content via iframe.',
                'vi_intro' => 'For when you need to embed something we haven\'t thought of yet.',
                'blocks' => [
                    ['type' => 'iframe', 'settings' => ['url' => 'https://example.com/embed', 'height' => 400]],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────────
            // Collectors
            // ─────────────────────────────────────────────────────────────────────

            'email_collector' => [
                'title' => 'Email Collector',
                'description' => 'Capture email addresses from visitors.',
                'vi_intro' => 'Grow your mailing list directly from your bio page.',
                'blocks' => [
                    ['type' => 'email_collector', 'settings' => [
                        'title' => 'Join my newsletter',
                        'description' => 'Get updates on new content and exclusive offers.',
                        'button_text' => 'Subscribe',
                        'placeholder' => 'your@email.com',
                    ]],
                ],
            ],

            'phone_collector' => [
                'title' => 'Phone Collector',
                'description' => 'Capture phone numbers from visitors.',
                'vi_intro' => 'For SMS marketing and direct contact.',
                'blocks' => [
                    ['type' => 'phone_collector', 'settings' => [
                        'title' => 'Get SMS updates',
                        'description' => 'Be the first to know about drops and deals.',
                        'button_text' => 'Sign Up',
                    ]],
                ],
            ],

            'contact_collector' => [
                'title' => 'Contact Form',
                'description' => 'Full contact form with customisable fields.',
                'vi_intro' => 'When you need more than just an email address.',
                'blocks' => [
                    ['type' => 'contact_collector', 'settings' => [
                        'title' => 'Get in Touch',
                        'fields' => ['name', 'email', 'message'],
                        'button_text' => 'Send Message',
                    ]],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────────
            // Commerce
            // ─────────────────────────────────────────────────────────────────────

            'product' => [
                'title' => 'Product Card',
                'description' => 'Showcase a product with image, price, and buy button.',
                'vi_intro' => 'Sell directly from your bio page. No shop needed.',
                'blocks' => [
                    ['type' => 'product', 'location_url' => 'https://host.uk.com/pricing', 'settings' => [
                        'name' => 'BioHost Pro',
                        'price' => '£9.99/month',
                        'description' => 'Unlimited bio pages, custom domains, and advanced analytics.',
                        'image' => 'images/vi/vi_dashboard.webp',
                        'button_text' => 'Get Pro',
                    ]],
                ],
            ],

            'service' => [
                'title' => 'Service Card',
                'description' => 'Promote a service with description and booking link.',
                'vi_intro' => 'Perfect for freelancers, consultants, and agencies.',
                'blocks' => [
                    ['type' => 'service', 'location_url' => 'https://host.uk.com/contact', 'settings' => [
                        'name' => 'Web Development',
                        'price' => 'From £500',
                        'description' => 'Custom websites built with modern tech. Laravel, Livewire, Tailwind.',
                        'button_text' => 'Book a Call',
                    ]],
                ],
            ],

            'donation' => [
                'title' => 'Donation Block',
                'description' => 'Accept tips and donations from supporters.',
                'vi_intro' => 'Let your fans show their appreciation.',
                'blocks' => [
                    ['type' => 'donation', 'settings' => [
                        'title' => 'Support My Work',
                        'description' => 'If you find my content helpful, consider buying me a coffee.',
                        'amounts' => ['£3', '£5', '£10', 'Custom'],
                        'currency' => 'GBP',
                    ]],
                ],
            ],

            'coupon' => [
                'title' => 'Coupon Block',
                'description' => 'Display a discount code with copy button.',
                'vi_intro' => 'Give your followers exclusive discounts.',
                'blocks' => [
                    ['type' => 'coupon', 'settings' => [
                        'code' => 'VIDEMO25',
                        'description' => '25% off your first month of BioHost Pro',
                        'expiry' => now()->addMonths(3)->toDateString(),
                    ]],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────────
            // Content & Display
            // ─────────────────────────────────────────────────────────────────────

            'modal_text' => [
                'title' => 'Modal Text',
                'description' => 'Long-form content in a popup modal.',
                'vi_intro' => 'For terms, bios, or anything that needs more space.',
                'blocks' => [
                    ['type' => 'modal_text', 'settings' => [
                        'button_text' => 'Read My Story',
                        'title' => 'About Vi',
                        'content' => "Hi there! I'm Vi, the friendly raven mascot of Host UK.\n\nI help creators and businesses build their online presence with our suite of digital marketing tools. From bio pages to social scheduling, I'm here to make your digital life easier.\n\nWhen I'm not helping users, you can find me perched on a server rack somewhere, keeping an eye on things.",
                    ]],
                ],
            ],

            'image_grid' => [
                'title' => 'Image Grid',
                'description' => 'Display multiple images in a grid layout.',
                'vi_intro' => 'Show off your portfolio, products, or memories.',
                'blocks' => [
                    ['type' => 'image_grid', 'settings' => [
                        'images' => [
                            ['url' => 'images/vi/vi_dashboard.webp', 'alt' => 'Vi on dashboard'],
                            ['url' => 'images/vi/master_vi.webp', 'alt' => 'Master Vi'],
                            ['url' => 'images/host-uk-raven.svg', 'alt' => 'Host UK logo'],
                        ],
                        'columns' => 3,
                    ]],
                ],
            ],

            'image_slider' => [
                'title' => 'Image Slider',
                'description' => 'Carousel of images with navigation.',
                'vi_intro' => 'A slider lets visitors browse through multiple images.',
                'blocks' => [
                    ['type' => 'image_slider', 'settings' => [
                        'images' => [
                            ['url' => 'images/vi/vi_dashboard.webp', 'alt' => 'Vi on dashboard', 'caption' => 'Dashboard Vi'],
                            ['url' => 'images/vi/master_vi.webp', 'alt' => 'Master Vi', 'caption' => 'The Full Vi'],
                        ],
                        'autoplay' => true,
                        'interval' => 5000,
                    ]],
                ],
            ],

            'list' => [
                'title' => 'List Block',
                'description' => 'Bulleted or numbered list of items.',
                'vi_intro' => 'Sometimes a good list is all you need.',
                'blocks' => [
                    ['type' => 'list', 'settings' => [
                        'items' => [
                            '60+ block types to choose from',
                            'Custom domains supported',
                            'Analytics built in',
                            'Works on all devices',
                        ],
                        'style' => 'bullet',
                    ]],
                ],
            ],

            'alert' => [
                'title' => 'Alert Block',
                'description' => 'Attention-grabbing notification banner.',
                'vi_intro' => 'Draw attention to important announcements.',
                'blocks' => [
                    ['type' => 'alert', 'settings' => ['text' => 'This is an info alert - useful for announcements.', 'type' => 'info']],
                    ['type' => 'alert', 'settings' => ['text' => 'Success alert - great for confirmations.', 'type' => 'success']],
                    ['type' => 'alert', 'settings' => ['text' => 'Warning alert - for important notices.', 'type' => 'warning']],
                    ['type' => 'alert', 'settings' => ['text' => 'Error alert - when something needs attention.', 'type' => 'error']],
                ],
            ],

            'cta' => [
                'title' => 'Call to Action',
                'description' => 'Prominent call-to-action section.',
                'vi_intro' => 'Make your most important action unmissable.',
                'blocks' => [
                    ['type' => 'cta', 'location_url' => 'https://host.uk.com/waitlist', 'settings' => [
                        'title' => 'Ready to get started?',
                        'description' => 'Join thousands of creators using BioHost to grow their audience.',
                        'button_text' => 'Create Your Page',
                        'background_color' => '#8b5cf6',
                        'text_color' => '#ffffff',
                    ]],
                ],
            ],

            'review' => [
                'title' => 'Review Block',
                'description' => 'Display testimonials with ratings.',
                'vi_intro' => 'Social proof builds trust. Show off your happy customers.',
                'blocks' => [
                    ['type' => 'review', 'settings' => [
                        'author' => 'Sarah M.',
                        'rating' => 5,
                        'text' => 'BioHost made setting up my link-in-bio so easy. The blocks are intuitive and my page looks professional.',
                        'avatar' => null,
                    ]],
                ],
            ],

            'vcard' => [
                'title' => 'vCard Download',
                'description' => 'Downloadable contact card for phones.',
                'vi_intro' => 'Let visitors save your contact details with one tap.',
                'blocks' => [
                    ['type' => 'vcard', 'settings' => [
                        'name' => 'Vi Raven',
                        'title' => 'Brand Mascot',
                        'company' => 'Host UK',
                        'email' => 'vi@host.uk.com',
                        'phone' => '+44 20 1234 5678',
                        'website' => 'https://host.uk.com',
                    ]],
                ],
            ],

            'share' => [
                'title' => 'Share Buttons',
                'description' => 'Social sharing buttons for your page.',
                'vi_intro' => 'Help visitors spread the word about your page.',
                'blocks' => [
                    ['type' => 'share', 'settings' => [
                        'platforms' => ['twitter', 'facebook', 'linkedin', 'whatsapp', 'email'],
                        'url' => null, // Will use current page URL
                    ]],
                ],
            ],

            'custom_html' => [
                'title' => 'Custom HTML',
                'description' => 'Add your own HTML code.',
                'vi_intro' => 'For developers who want full control. Insert any HTML you like.',
                'blocks' => [
                    ['type' => 'custom_html', 'settings' => [
                        'html' => '<div style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; text-align: center; color: white;"><h3 style="margin: 0 0 10px 0;">Custom HTML Block</h3><p style="margin: 0;">You can put anything here!</p></div>',
                    ]],
                ],
            ],

            'file' => [
                'title' => 'File Download',
                'description' => 'Downloadable file with size display.',
                'vi_intro' => 'Share downloadable files like PDFs, ZIPs, or documents.',
                'blocks' => [
                    ['type' => 'file', 'settings' => [
                        'file_url' => 'https://example.com/sample.zip',
                        'name' => 'Download Resources Pack',
                        'size' => '2.4 MB',
                    ]],
                ],
            ],

            'markdown' => [
                'title' => 'Markdown Block',
                'description' => 'Rich text content using Markdown syntax.',
                'vi_intro' => 'Write content in Markdown and it renders beautifully.',
                'blocks' => [
                    ['type' => 'markdown', 'settings' => [
                        'content' => "## Hello from Markdown\n\nThis block supports **bold**, *italic*, and [links](https://host.uk.com).\n\n- List item one\n- List item two\n- List item three\n\n> A blockquote for emphasis.",
                    ]],
                ],
            ],

            'paypal' => [
                'title' => 'PayPal Button',
                'description' => 'Accept PayPal payments.',
                'vi_intro' => 'Accept payments directly through PayPal.',
                'blocks' => [
                    ['type' => 'paypal', 'settings' => [
                        'email' => 'payments@host.uk.com',
                        'amount' => '9.99',
                        'currency' => 'GBP',
                        'button_text' => 'Pay with PayPal',
                    ]],
                ],
            ],
        ];
    }

    /**
     * Create blocks for a bio page.
     */
    protected function seedBlocks(Page $biolink, array $blocks): void
    {
        // Remove existing blocks
        Block::where('biolink_id', $biolink->id)->delete();

        foreach ($blocks as $block) {
            Block::create([
                'workspace_id' => $this->workspace->id,
                'biolink_id' => $biolink->id,
                'type' => $block['type'],
                'settings' => $block['settings'] ?? [],
                'location_url' => $block['location_url'] ?? null,
                'order' => $block['order'],
                'is_enabled' => true,
            ]);
        }
    }
}
