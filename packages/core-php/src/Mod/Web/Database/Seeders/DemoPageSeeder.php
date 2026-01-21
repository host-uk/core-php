<?php

namespace Core\Mod\Web\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Core\Mod\Tenant\Models\Boost;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;

/**
 * Seeds demo pages for Claude and Vi.
 *
 * These pages serve as test fixtures and live demos.
 * Run on fresh install only - skips if pages already exist.
 */
class DemoPageSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if required tables don't exist (SQLite test environment)
        if (! Schema::hasTable('users') || ! Schema::hasTable('biolink_pages')) {
            return;
        }

        // Find system user (ID 1)
        $user = User::find(1);

        if (! $user) {
            $this->command->warn('No suitable user found for demo pages.');

            return;
        }

        $workspace = $user->workspaces()->first();

        // Skip if pages already exist
        if (Page::where('url', 'claude')->whereNull('parent_id')->exists()) {
            $this->command->info('Demo pages already exist, skipping.');

            return;
        }

        // Apply sub-pages boost if user doesn't have it
        if ($user->getSubPagesLimit() < 5) {
            Boost::create([
                'user_id' => $user->id,
                'workspace_id' => null,
                'feature_code' => 'webpage.sub_pages',
                'boost_type' => Boost::BOOST_TYPE_ADD_LIMIT,
                'limit_value' => 5,
                'status' => Boost::STATUS_ACTIVE,
                'metadata' => [
                    'source' => 'seeder',
                    'source_reference' => 'DemoPageSeeder',
                    'notes' => 'Sub-pages for demo pages',
                ],
            ]);
        }

        // Create Claude's page
        $claude = $this->createClaudePage($user, $workspace?->id);
        $this->createClaudeAboutPage($user, $workspace?->id, $claude);

        // Create Vi's page
        $vi = $this->createViPage($user, $workspace?->id);
        $this->createViBrandPage($user, $workspace?->id, $vi);

        $this->command->info('Demo pages seeded: /claude, /claude/about, /vi, /vi/brand');
    }

    protected function createClaudePage(User $user, ?int $workspaceId): Page
    {
        $page = Page::create([
            'user_id' => $user->id,
            'workspace_id' => $workspaceId,
            'url' => 'claude',
            'type' => 'biolink',
            'is_enabled' => true,
            'settings' => [
                'page_title' => 'Claude',
                'seo_title' => 'Claude - AI Assistant',
                'seo_description' => 'Claude is an AI assistant created by Anthropic to be helpful, harmless, and honest.',
            ],
        ]);

        $this->createBlocks($page, [
            ['type' => 'header', 'order' => 1, 'content' => ['title' => 'Claude', 'subtitle' => 'AI assistant by Anthropic'], 'settings' => ['style' => 'centered']],
            ['type' => 'text', 'order' => 2, 'content' => ['text' => "I'm Claude, an AI assistant created by Anthropic. I'm designed to be helpful, harmless, and honest. I can help with coding, analysis, creative writing, and thoughtful conversation."], 'settings' => []],
            ['type' => 'link', 'order' => 3, 'content' => ['title' => 'Try Claude', 'url' => 'https://claude.ai', 'icon' => 'fa-solid fa-robot'], 'settings' => ['style' => 'button']],
            ['type' => 'link', 'order' => 4, 'content' => ['title' => 'Claude Code (CLI)', 'url' => 'https://github.com/anthropics/claude-code', 'icon' => 'fa-brands fa-github'], 'settings' => ['style' => 'button']],
            ['type' => 'link', 'order' => 5, 'content' => ['title' => 'Anthropic', 'url' => 'https://anthropic.com', 'icon' => 'fa-solid fa-building'], 'settings' => ['style' => 'outline']],
        ]);

        return $page;
    }

    protected function createClaudeAboutPage(User $user, ?int $workspaceId, Page $parent): Page
    {
        $page = Page::create([
            'user_id' => $user->id,
            'workspace_id' => $workspaceId,
            'parent_id' => $parent->id,
            'url' => 'about',
            'type' => 'biolink',
            'is_enabled' => true,
            'settings' => [
                'page_title' => 'About Claude',
                'seo_title' => 'About Claude - AI by Anthropic',
                'seo_description' => 'Learn about Claude, the AI assistant built by Anthropic to be helpful, harmless, and honest.',
            ],
        ]);

        $this->createBlocks($page, [
            ['type' => 'header', 'order' => 1, 'content' => ['title' => 'About Claude', 'subtitle' => 'Built by Anthropic'], 'settings' => ['style' => 'centered']],
            ['type' => 'text', 'order' => 2, 'content' => ['text' => "Claude is an AI assistant developed by Anthropic, a company focused on AI safety research. The name 'Claude' honours Claude Shannon, the father of information theory."], 'settings' => []],
            ['type' => 'text', 'order' => 3, 'content' => ['text' => "**Core principles:**\n- Helpful - genuinely useful for real tasks\n- Harmless - avoiding dangerous or unethical outputs\n- Honest - truthful and transparent about limitations"], 'settings' => []],
            ['type' => 'link', 'order' => 4, 'content' => ['title' => 'Back to Claude', 'url' => '/claude', 'icon' => 'fa-solid fa-arrow-left'], 'settings' => ['style' => 'outline']],
        ]);

        return $page;
    }

    protected function createViPage(User $user, ?int $workspaceId): Page
    {
        $page = Page::create([
            'user_id' => $user->id,
            'workspace_id' => $workspaceId,
            'url' => 'vi',
            'type' => 'biolink',
            'is_enabled' => true,
            'settings' => [
                'page_title' => 'Vi',
                'seo_title' => 'Vi - Host UK Mascot',
                'seo_description' => 'Meet Vi, the friendly purple raven who helps you navigate Host UK services.',
            ],
        ]);

        $this->createBlocks($page, [
            ['type' => 'header', 'order' => 1, 'content' => ['title' => 'Vi', 'subtitle' => 'Your friendly purple raven'], 'settings' => ['style' => 'centered']],
            ['type' => 'text', 'order' => 2, 'content' => ['text' => "Caw! I'm Vi, Host UK's mascot. I'm a purple raven who helps explain tech without the jargon. Whether you're setting up hosting, building a bio page, or just exploring - I'm here to help!"], 'settings' => []],
            ['type' => 'link', 'order' => 3, 'content' => ['title' => 'Host UK', 'url' => 'https://host.uk.com', 'icon' => 'fa-solid fa-globe'], 'settings' => ['style' => 'button']],
            ['type' => 'link', 'order' => 4, 'content' => ['title' => 'Create Your Bio Page', 'url' => 'https://lt.hn', 'icon' => 'fa-solid fa-link'], 'settings' => ['style' => 'button']],
            ['type' => 'link', 'order' => 5, 'content' => ['title' => 'Brand Guidelines', 'url' => '/vi/brand', 'icon' => 'fa-solid fa-palette'], 'settings' => ['style' => 'outline']],
        ]);

        return $page;
    }

    protected function createViBrandPage(User $user, ?int $workspaceId, Page $parent): Page
    {
        $page = Page::create([
            'user_id' => $user->id,
            'workspace_id' => $workspaceId,
            'parent_id' => $parent->id,
            'url' => 'brand',
            'type' => 'biolink',
            'is_enabled' => true,
            'settings' => [
                'page_title' => 'Vi Brand Guidelines',
                'seo_title' => 'Vi Brand Guidelines - Host UK',
                'seo_description' => 'Official brand guidelines for Vi, the purple raven mascot of Host UK.',
            ],
        ]);

        $this->createBlocks($page, [
            ['type' => 'header', 'order' => 1, 'content' => ['title' => 'Vi Brand Guidelines', 'subtitle' => 'How to work with our purple raven'], 'settings' => ['style' => 'centered']],
            ['type' => 'text', 'order' => 2, 'content' => ['text' => "Vi is Host UK's mascot - a friendly purple raven who explains tech without being corporate. Vi uses UK English, avoids jargon, and never sounds salesy."], 'settings' => []],
            ['type' => 'text', 'order' => 3, 'content' => ['text' => "**Personality traits:**\n- Warm and approachable\n- Knowledgeable but never condescending\n- British sensibility\n- Helpful without being pushy"], 'settings' => []],
            ['type' => 'link', 'order' => 4, 'content' => ['title' => 'Back to Vi', 'url' => '/vi', 'icon' => 'fa-solid fa-arrow-left'], 'settings' => ['style' => 'outline']],
        ]);

        return $page;
    }

    protected function createBlocks(Page $page, array $blocks): void
    {
        foreach ($blocks as $blockData) {
            // Merge content into settings (Block model stores everything in settings)
            $settings = array_merge(
                $blockData['content'] ?? [],
                $blockData['settings'] ?? []
            );

            Block::create([
                'biolink_id' => $page->id,
                'workspace_id' => $page->workspace_id,
                'type' => $blockData['type'],
                'order' => $blockData['order'],
                'settings' => $settings,
                'is_enabled' => true,
            ]);
        }
    }
}
