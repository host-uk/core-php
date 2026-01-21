<?php

pest()->group('slow', 'deploy');

use Core\Mod\Web\Mcp\Tools\BioAnalyticsTools;
use Core\Mod\Web\Mcp\Tools\BioTools;
use Core\Mod\Web\Mcp\Tools\DomainTools;
use Core\Mod\Web\Mcp\Tools\NotificationTools;
use Core\Mod\Web\Mcp\Tools\PixelTools;
use Core\Mod\Web\Mcp\Tools\ProjectTools;
use Core\Mod\Web\Mcp\Tools\SubmissionTools;
use Core\Mod\Web\Mcp\Tools\ThemeTools;
use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Domain;
use Core\Mod\Web\Models\NotificationHandler;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Pixel;
use Core\Mod\Web\Models\Project;
use Core\Mod\Web\Models\Submission;
use Core\Mod\Web\Models\Theme;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Laravel\Mcp\Request;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
    $this->tool = new BioTools;
});

describe('BioTools', function () {
    // ─────────────────────────────────────────────────────────────────────────
    // Core Operations
    // ─────────────────────────────────────────────────────────────────────────

    describe('list action', function () {
        it('lists bio links for a user', function () {
            Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'test-page',
                'is_enabled' => true,
            ]);

            Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'another-page',
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'list',
                'user_id' => $this->user->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data)->toBeArray();
            expect(count($data))->toBe(2);
            expect($data[0])->toHaveKeys(['id', 'url', 'type', 'clicks', 'is_enabled']);
        });
    });

    describe('get action', function () {
        it('gets details of a specific bio link', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'my-page',
                'is_enabled' => true,
                'settings' => ['seo' => ['title' => 'My Page']],
            ]);

            Block::create([
                'workspace_id' => $this->workspace->id,
                'biolink_id' => $biolink->id,
                'type' => 'heading',
                'settings' => ['text' => 'Welcome'],
                'order' => 1,
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'get',
                'biolink_id' => $biolink->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['id'])->toBe($biolink->id);
            expect($data['url'])->toBe('my-page');
            expect($data['blocks'])->toHaveCount(1);
            expect($data['blocks'][0]['type'])->toBe('heading');
        });

        it('returns error when bio link not found', function () {
            $request = new Request([
                'action' => 'get',
                'biolink_id' => 99999,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data)->toHaveKey('error');
            expect($data['error'])->toBe('Bio link not found');
        });
    });

    describe('create action', function () {
        it('creates a new bio link', function () {
            $request = new Request([
                'action' => 'create',
                'user_id' => $this->user->id,
                'url' => 'new-page',
                'title' => 'New Page Title',
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['url'])->toBe('new-page');
            expect($data['biolink_id'])->toBeGreaterThan(0);

            $biolink = Page::find($data['biolink_id']);
            expect($biolink)->not->toBeNull();
            expect($biolink->url)->toBe('new-page');
        });

        it('creates bio link with blocks', function () {
            $request = new Request([
                'action' => 'create',
                'user_id' => $this->user->id,
                'url' => 'page-with-blocks',
                'title' => 'Page With Blocks',
                'blocks' => [
                    ['type' => 'heading', 'settings' => ['text' => 'Welcome']],
                    ['type' => 'link', 'settings' => ['name' => 'Click me', 'url' => 'https://example.com']],
                ],
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['blocks_created'])->toBe(2);

            $biolink = Page::with('blocks')->find($data['biolink_id']);
            expect($biolink->blocks)->toHaveCount(2);
        });

        it('requires user_id', function () {
            $request = new Request([
                'action' => 'create',
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data)->toHaveKey('error');
            expect($data['error'])->toBe('user_id is required');
        });
    });

    describe('update action', function () {
        it('updates an existing bio link', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'original-url',
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'update',
                'biolink_id' => $biolink->id,
                'url' => 'new-url',
                'is_enabled' => false,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['url'])->toBe('new-url');

            $biolink->refresh();
            expect($biolink->url)->toBe('new-url');
            expect($biolink->is_enabled)->toBeFalse();
        });
    });

    describe('delete action', function () {
        it('deletes a bio link', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'delete-me',
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'delete',
                'biolink_id' => $biolink->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['deleted_url'])->toBe('delete-me');

            // Soft deleted
            expect(Page::find($biolink->id))->toBeNull();
            expect(Page::withTrashed()->find($biolink->id))->not->toBeNull();
        });
    });

    describe('block operations', function () {
        it('adds a block to existing bio link', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'existing-page',
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'add_block',
                'biolink_id' => $biolink->id,
                'block_type' => 'link',
                'settings' => ['name' => 'New Link', 'url' => 'https://example.com'],
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['type'])->toBe('link');
            expect($data['order'])->toBe(1);

            $biolink->refresh();
            expect($biolink->blocks)->toHaveCount(1);
        });

        it('updates a block', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'block-update-test',
                'is_enabled' => true,
            ]);

            $block = Block::create([
                'workspace_id' => $this->workspace->id,
                'biolink_id' => $biolink->id,
                'type' => 'link',
                'settings' => ['name' => 'Old Name'],
                'order' => 1,
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'update_block',
                'block_id' => $block->id,
                'settings' => ['name' => 'New Name'],
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();

            $block->refresh();
            expect($block->settings['name'])->toBe('New Name');
        });

        it('deletes a block', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'block-delete-test',
                'is_enabled' => true,
            ]);

            $block = Block::create([
                'workspace_id' => $this->workspace->id,
                'biolink_id' => $biolink->id,
                'type' => 'link',
                'settings' => [],
                'order' => 1,
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'delete_block',
                'block_id' => $block->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect(Block::find($block->id))->toBeNull();
        });
    });

    describe('stats action', function () {
        beforeEach(function () {
            $this->tool = new BioAnalyticsTools;
        });

        it('gets statistics for a bio link', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'stats-page',
                'clicks' => 100,
                'unique_clicks' => 75,
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'stats',
                'biolink_id' => $biolink->id,
                'period' => '7d',
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['biolink_id'])->toBe($biolink->id);
            expect($data['total_clicks'])->toBe(100);
            expect($data['unique_clicks'])->toBe(75);
            expect($data['period'])->toBe('7d');
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Domain Operations (AC47)
    // ─────────────────────────────────────────────────────────────────────────

    describe('domain operations', function () {
        beforeEach(function () {
            $this->tool = new DomainTools;
        });

        it('lists domains for a user', function () {
            Domain::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'host' => 'custom.example.com',
                'scheme' => 'https',
                'is_enabled' => true,
                'verification_status' => 'verified',
            ]);

            $request = new Request([
                'action' => 'list',
                'user_id' => $this->user->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['domains'])->toHaveCount(1);
            expect($data['domains'][0]['host'])->toBe('custom.example.com');
            expect($data['total'])->toBe(1);
        });

        it('deletes a domain', function () {
            $domain = Domain::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'host' => 'delete.example.com',
                'scheme' => 'https',
            ]);

            $request = new Request([
                'action' => 'delete',
                'domain_id' => $domain->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['deleted_host'])->toBe('delete.example.com');
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Project Operations (AC47)
    // ─────────────────────────────────────────────────────────────────────────

    describe('project operations', function () {
        beforeEach(function () {
            $this->tool = new ProjectTools;
        });

        it('lists projects for a user', function () {
            Project::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'name' => 'Marketing Campaign',
                'color' => '#6366f1',
            ]);

            $request = new Request([
                'action' => 'list',
                'user_id' => $this->user->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['projects'])->toHaveCount(1);
            expect($data['projects'][0]['name'])->toBe('Marketing Campaign');
        });

        it('creates a project', function () {
            $request = new Request([
                'action' => 'create',
                'user_id' => $this->user->id,
                'name' => 'New Project',
                'color' => '#ef4444',
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['name'])->toBe('New Project');

            $project = Project::find($data['project_id']);
            expect($project->color)->toBe('#ef4444');
        });

        it('moves biolink to project', function () {
            $project = Project::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'name' => 'Target Project',
            ]);

            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'move-test',
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'move_biolink',
                'biolink_id' => $biolink->id,
                'project_id' => $project->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();

            $biolink->refresh();
            expect($biolink->project_id)->toBe($project->id);
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Pixel Operations (AC47)
    // ─────────────────────────────────────────────────────────────────────────

    describe('pixel operations', function () {
        beforeEach(function () {
            $this->tool = new PixelTools;
        });

        it('lists pixels for a user', function () {
            Pixel::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'facebook',
                'name' => 'Facebook Pixel',
                'pixel_id' => '123456789',
            ]);

            $request = new Request([
                'action' => 'list',
                'user_id' => $this->user->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['pixels'])->toHaveCount(1);
            expect($data['pixels'][0]['type'])->toBe('facebook');
            expect($data['available_types'])->toHaveKey('facebook');
        });

        it('creates a pixel', function () {
            $request = new Request([
                'action' => 'create',
                'user_id' => $this->user->id,
                'type' => 'google_analytics',
                'pixel_id' => 'G-XXXXXXXXXX',
                'name' => 'Main GA4',
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['type'])->toBe('google_analytics');
        });

        it('attaches pixel to biolink', function () {
            $pixel = Pixel::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'facebook',
                'name' => 'FB Pixel',
                'pixel_id' => '123',
            ]);

            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'pixel-test',
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'attach',
                'biolink_id' => $biolink->id,
                'pixel_id' => $pixel->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($biolink->pixels()->count())->toBe(1);
        });

        it('detaches pixel from biolink', function () {
            $pixel = Pixel::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'facebook',
                'name' => 'FB Pixel',
                'pixel_id' => '123',
            ]);

            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'detach-test',
                'is_enabled' => true,
            ]);

            $biolink->pixels()->attach($pixel->id);

            $request = new Request([
                'action' => 'detach',
                'biolink_id' => $biolink->id,
                'pixel_id' => $pixel->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($biolink->pixels()->count())->toBe(0);
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Theme Operations (AC50)
    // ─────────────────────────────────────────────────────────────────────────

    describe('theme operations', function () {
        beforeEach(function () {
            $this->tool = new ThemeTools;
        });

        it('lists available themes', function () {
            Theme::create([
                'name' => 'Dark Mode',
                'slug' => 'dark-mode',
                'is_system' => true,
                'is_active' => true,
                'settings' => ['background' => ['type' => 'color', 'color' => '#000000']],
            ]);

            $request = new Request([
                'action' => 'list',
                'user_id' => $this->user->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data)->toHaveKey('themes');
            expect($data['total'])->toBeGreaterThanOrEqual(1);
        });

        it('applies theme to biolink', function () {
            $theme = Theme::create([
                'name' => 'Apply Test Theme',
                'slug' => 'apply-test',
                'is_system' => true,
                'is_active' => true,
                'settings' => [],
            ]);

            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'theme-test',
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'apply',
                'biolink_id' => $biolink->id,
                'theme_id' => $theme->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['theme_id'])->toBe($theme->id);

            $biolink->refresh();
            expect($biolink->theme_id)->toBe($theme->id);
        });

        it('removes theme from biolink', function () {
            $theme = Theme::create([
                'name' => 'Remove Test Theme',
                'slug' => 'remove-test',
                'is_system' => true,
                'is_active' => true,
                'settings' => [],
            ]);

            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'theme-remove-test',
                'theme_id' => $theme->id,
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'apply',
                'biolink_id' => $biolink->id,
                'theme_id' => null,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['theme_id'])->toBeNull();
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Notification Handler Operations (AC51)
    // ─────────────────────────────────────────────────────────────────────────

    describe('notification handler operations', function () {
        beforeEach(function () {
            $this->tool = new NotificationTools;
        });

        it('lists notification handlers for a biolink', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'handler-list-test',
                'is_enabled' => true,
            ]);

            NotificationHandler::create([
                'biolink_id' => $biolink->id,
                'workspace_id' => $this->workspace->id,
                'name' => 'Slack Alerts',
                'type' => 'slack',
                'events' => ['form_submit'],
                'settings' => ['webhook_url' => 'https://hooks.slack.com/test'],
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'list',
                'biolink_id' => $biolink->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['handlers'])->toHaveCount(1);
            expect($data['handlers'][0]['type'])->toBe('slack');
            expect($data['available_types'])->toHaveKey('webhook');
        });

        it('creates a notification handler', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'handler-create-test',
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'create',
                'biolink_id' => $biolink->id,
                'name' => 'My Webhook',
                'type' => 'webhook',
                'events' => ['click', 'form_submit'],
                'settings' => ['url' => 'https://example.com/webhook'],
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['type'])->toBe('webhook');
        });

        it('updates a notification handler', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'handler-update-test',
                'is_enabled' => true,
            ]);

            $handler = NotificationHandler::create([
                'biolink_id' => $biolink->id,
                'workspace_id' => $this->workspace->id,
                'name' => 'Original Name',
                'type' => 'webhook',
                'events' => ['click'],
                'settings' => ['url' => 'https://example.com/old'],
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'update',
                'handler_id' => $handler->id,
                'name' => 'Updated Name',
                'is_enabled' => false,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();

            $handler->refresh();
            expect($handler->name)->toBe('Updated Name');
            expect($handler->is_enabled)->toBeFalse();
        });

        it('deletes a notification handler', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'handler-delete-test',
                'is_enabled' => true,
            ]);

            $handler = NotificationHandler::create([
                'biolink_id' => $biolink->id,
                'workspace_id' => $this->workspace->id,
                'name' => 'Delete Me',
                'type' => 'email',
                'events' => ['form_submit'],
                'settings' => ['recipients' => ['test@example.com']],
                'is_enabled' => true,
            ]);

            $request = new Request([
                'action' => 'delete',
                'handler_id' => $handler->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['ok'])->toBeTrue();
            expect($data['deleted_handler'])->toBe('Delete Me');
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Submission Operations (AC52)
    // ─────────────────────────────────────────────────────────────────────────

    describe('submission operations', function () {
        beforeEach(function () {
            $this->tool = new SubmissionTools;
        });

        it('lists submissions for a biolink', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'submission-list-test',
                'is_enabled' => true,
            ]);

            $block = Block::create([
                'workspace_id' => $this->workspace->id,
                'biolink_id' => $biolink->id,
                'type' => 'email_collector',
                'settings' => [],
                'order' => 1,
            ]);

            Submission::create([
                'biolink_id' => $biolink->id,
                'block_id' => $block->id,
                'type' => 'email',
                'data' => ['email' => 'test@example.com'],
            ]);

            $request = new Request([
                'action' => 'list',
                'biolink_id' => $biolink->id,
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['submissions'])->toHaveCount(1);
            expect($data['submissions'][0]['type'])->toBe('email');
            expect($data['total'])->toBe(1);
        });

        it('exports submissions as JSON', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'export-json-test',
                'is_enabled' => true,
            ]);

            $block = Block::create([
                'workspace_id' => $this->workspace->id,
                'biolink_id' => $biolink->id,
                'type' => 'contact_collector',
                'settings' => [],
                'order' => 1,
            ]);

            Submission::create([
                'biolink_id' => $biolink->id,
                'block_id' => $block->id,
                'type' => 'contact',
                'data' => ['name' => 'John', 'email' => 'john@example.com', 'message' => 'Hello'],
            ]);

            $request = new Request([
                'action' => 'export',
                'biolink_id' => $biolink->id,
                'format' => 'json',
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['submissions'])->toHaveCount(1);
            expect($data['submissions'][0]['email'])->toBe('john@example.com');
        });

        it('exports submissions as CSV', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'export-csv-test',
                'is_enabled' => true,
            ]);

            $block = Block::create([
                'workspace_id' => $this->workspace->id,
                'biolink_id' => $biolink->id,
                'type' => 'email_collector',
                'settings' => [],
                'order' => 1,
            ]);

            Submission::create([
                'biolink_id' => $biolink->id,
                'block_id' => $block->id,
                'type' => 'email',
                'data' => ['email' => 'csv@example.com'],
            ]);

            $request = new Request([
                'action' => 'export',
                'biolink_id' => $biolink->id,
                'format' => 'csv',
            ]);

            $response = $this->tool->handle($request);
            $content = (string) $response->content();

            expect($content)->toContain('type,name,email');
            expect($content)->toContain('csv@example.com');
        });

        it('filters submissions by type', function () {
            $biolink = Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'biolink',
                'url' => 'filter-type-test',
                'is_enabled' => true,
            ]);

            $emailBlock = Block::create([
                'workspace_id' => $this->workspace->id,
                'biolink_id' => $biolink->id,
                'type' => 'email_collector',
                'settings' => [],
                'order' => 1,
            ]);

            $phoneBlock = Block::create([
                'workspace_id' => $this->workspace->id,
                'biolink_id' => $biolink->id,
                'type' => 'phone_collector',
                'settings' => [],
                'order' => 2,
            ]);

            Submission::create([
                'biolink_id' => $biolink->id,
                'block_id' => $emailBlock->id,
                'type' => 'email',
                'data' => ['email' => 'email@example.com'],
            ]);

            Submission::create([
                'biolink_id' => $biolink->id,
                'block_id' => $phoneBlock->id,
                'type' => 'phone',
                'data' => ['phone' => '+441234567890'],
            ]);

            $request = new Request([
                'action' => 'list',
                'biolink_id' => $biolink->id,
                'type' => 'email',
            ]);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data['submissions'])->toHaveCount(1);
            expect($data['submissions'][0]['type'])->toBe('email');
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Error Handling
    // ─────────────────────────────────────────────────────────────────────────

    describe('error handling', function () {
        it('returns error for invalid action', function () {
            $request = new Request(['action' => 'invalid']);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            expect($data)->toHaveKey('error');
            expect($data['error'])->toContain('Invalid action');
            expect($data['details'])->toHaveKey('available_actions');
        });

        it('returns available actions on invalid action', function () {
            $request = new Request(['action' => 'not_real']);

            $response = $this->tool->handle($request);
            $data = json_decode((string) $response->content(), true);

            // BioTools (renamed from BioLinkTools) only returns a list of strings
            expect($data['details']['available_actions'])->toBeArray();
            expect($data['details']['available_actions'])->toContain('create');
        });
    });

    // ─────────────────────────────────────────────────────────────────────────
    // Schema
    // ─────────────────────────────────────────────────────────────────────────

    describe('schema', function () {
        it('has proper schema method', function () {
            $reflection = new ReflectionMethod($this->tool, 'schema');
            expect($reflection->getNumberOfParameters())->toBe(1);
            expect($reflection->isPublic())->toBeTrue();
        });
    });
});
