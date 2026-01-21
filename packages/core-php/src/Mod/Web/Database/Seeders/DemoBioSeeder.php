<?php

namespace Core\Mod\Web\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Theme;

class DemoBioSeeder extends Seeder
{
    /**
     * Seed demo bio pages for the platform.
     *
     * Creates sample bio pages to showcase the platform's capabilities:
     * - Host UK official page
     * - Vi mascot page
     * - Demo creator page
     */
    public function run(): void
    {
        if (! Schema::hasTable('biolinks') || ! Schema::hasTable('users')) {
            return;
        }

        $systemUser = User::find(1);
        $workspace = Workspace::where('slug', 'system')->first();

        if (! $systemUser || ! $workspace) {
            $this->command->warn('System user or workspace not found. Run SystemUserSeeder first.');

            return;
        }

        // Get themes for variety
        $tokyoTheme = Theme::where('slug', 'tokyo')->first();
        $londonTheme = Theme::where('slug', 'london')->first();
        $losAngelesTheme = Theme::where('slug', 'los-angeles')->first();

        // ─────────────────────────────────────────────────────────────
        // Host UK Official Page
        // ─────────────────────────────────────────────────────────────
        $hostUkPage = Page::updateOrCreate(
            ['url' => 'hostuk'],
            [
                'workspace_id' => $workspace->id,
                'user_id' => $systemUser->id,
                'theme_id' => $londonTheme?->id,
                'type' => 'biolink',
                'settings' => [
                    'page_title' => 'Host UK',
                    'seo_title' => 'Host UK - Digital Marketing Toolkit',
                    'seo_description' => 'Professional tools for agencies, marketers, and businesses. EU hosted, GDPR compliant.',
                    'verified_badge' => true,
                ],
                'is_enabled' => true,
                'is_verified' => true,
            ]
        );

        $this->seedBlocks($hostUkPage, $workspace->id, [
            ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => '/images/host-uk-raven.svg', 'alt' => 'Host UK']],
            ['type' => 'heading', 'order' => 2, 'settings' => ['text' => 'Host UK', 'level' => 'h1']],
            ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => 'Your digital marketing toolkit. Six services that work together.']],
            ['type' => 'divider', 'order' => 4, 'settings' => []],
            ['type' => 'link', 'order' => 5, 'settings' => ['url' => 'https://host.uk.com', 'text' => 'Visit Mod', 'icon' => 'globe']],
            ['type' => 'link', 'order' => 6, 'settings' => ['url' => 'https://host.uk.com/services', 'text' => 'Our Services', 'icon' => 'grid']],
            ['type' => 'link', 'order' => 7, 'settings' => ['url' => 'https://host.uk.com/waitlist', 'text' => 'Join Waitlist', 'icon' => 'mail']],
            ['type' => 'socials', 'order' => 8, 'settings' => ['github' => 'https://github.com/hostuk', 'twitter' => 'https://twitter.com/hostukcom']],
        ]);

        // ─────────────────────────────────────────────────────────────
        // Vi Mascot Page
        // ─────────────────────────────────────────────────────────────
        $viPage = Page::updateOrCreate(
            ['url' => 'vi'],
            [
                'workspace_id' => $workspace->id,
                'user_id' => $systemUser->id,
                'theme_id' => $tokyoTheme?->id,
                'type' => 'biolink',
                'settings' => [
                    'page_title' => 'Vi',
                    'seo_title' => 'Vi - Host UK Mascot',
                    'seo_description' => 'Meet Vi, your friendly AI assistant at Host UK.',
                ],
                'is_enabled' => true,
                'is_verified' => true,
            ]
        );

        $this->seedBlocks($viPage, $workspace->id, [
            ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => '/images/vi/master_vi.webp', 'alt' => 'Vi']],
            ['type' => 'heading', 'order' => 2, 'settings' => ['text' => 'Vi', 'level' => 'h1']],
            ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => 'Your friendly AI assistant at Host UK']],
            ['type' => 'link', 'order' => 4, 'settings' => ['url' => 'https://host.uk.com', 'text' => 'Visit Host UK', 'icon' => 'home']],
            ['type' => 'link', 'order' => 5, 'settings' => ['url' => 'https://host.uk.com/services/bio', 'text' => 'Create Your Bio Page', 'icon' => 'link']],
        ]);

        // ─────────────────────────────────────────────────────────────
        // Demo Creator Page (showcasing features)
        // ─────────────────────────────────────────────────────────────
        $demoPage = Page::updateOrCreate(
            ['url' => 'demo'],
            [
                'workspace_id' => $workspace->id,
                'user_id' => $systemUser->id,
                'theme_id' => $losAngelesTheme?->id,
                'type' => 'biolink',
                'settings' => [
                    'page_title' => 'Demo Creator',
                    'seo_title' => 'Demo - Bio.Host Example Page',
                    'seo_description' => 'See what you can build with Bio.Host. Social links, media embeds, and more.',
                ],
                'is_enabled' => true,
                'is_verified' => false,
            ]
        );

        $this->seedBlocks($demoPage, $workspace->id, [
            ['type' => 'avatar', 'order' => 1, 'settings' => ['image' => null, 'alt' => 'Demo Creator']],
            ['type' => 'heading', 'order' => 2, 'settings' => ['text' => 'Demo Creator', 'level' => 'h1']],
            ['type' => 'paragraph', 'order' => 3, 'settings' => ['text' => 'Content creator & digital strategist based in London']],
            ['type' => 'link', 'order' => 4, 'settings' => ['url' => 'https://youtube.com', 'text' => 'YouTube Channel', 'icon' => 'youtube']],
            ['type' => 'link', 'order' => 5, 'settings' => ['url' => 'https://instagram.com', 'text' => 'Instagram', 'icon' => 'instagram']],
            ['type' => 'link', 'order' => 6, 'settings' => ['url' => 'https://tiktok.com', 'text' => 'TikTok', 'icon' => 'tiktok']],
            ['type' => 'link', 'order' => 7, 'settings' => ['url' => 'https://twitter.com', 'text' => 'Twitter/X', 'icon' => 'twitter']],
            ['type' => 'divider', 'order' => 8, 'settings' => []],
            ['type' => 'link', 'order' => 9, 'settings' => ['url' => 'https://example.com/merch', 'text' => 'Shop Merch', 'icon' => 'shopping-bag']],
            ['type' => 'link', 'order' => 10, 'settings' => ['url' => 'mailto:demo@example.com', 'text' => 'Business Enquiries', 'icon' => 'mail']],
        ]);

        $this->command->info('Seeded 3 demo bio pages: /hostuk, /vi, /demo');
    }

    /**
     * Create blocks for a bio page.
     */
    protected function seedBlocks(Page $biolink, int $workspaceId, array $blocks): void
    {
        // Remove existing blocks
        Block::where('biolink_id', $biolink->id)->delete();

        foreach ($blocks as $block) {
            Block::create([
                'workspace_id' => $workspaceId,
                'biolink_id' => $biolink->id,
                'type' => $block['type'],
                'settings' => $block['settings'],
                'order' => $block['order'],
                'is_enabled' => true,
            ]);
        }
    }
}
