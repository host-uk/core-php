<?php

pest()->group('slow', 'deploy');

use Core\Mod\Web\Mcp\Tools\BioTools;
use Core\Mod\Web\Mcp\Tools\PwaTools;
use Core\Mod\Web\Mcp\Tools\StaticPageTools;
use Core\Mod\Web\Mcp\Tools\TemplateTools;
use Core\Mod\Web\Mcp\Tools\ThemeTools;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Pwa;
use Core\Mod\Web\Models\Template;
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

use Core\Mod\Tenant\Models\Feature;
use Core\Mod\Tenant\Models\Package;
use Core\Mod\Tenant\Services\EntitlementService;

describe('Template Operations (AC54)', function () {
    beforeEach(function () {
        $this->tool = new TemplateTools;

        // Provision entitlements
        $feature = Feature::firstOrCreate(
            ['code' => 'bio.templates'],
            ['name' => 'Templates', 'type' => 'boolean']
        );

        $package = Package::create(['code' => 'default-tpl', 'name' => 'Default', 'is_base_package' => true]);
        $package->features()->attach($feature, ['limit_value' => 1]);

        // Provision package
        app(EntitlementService::class)->provisionPackage($this->workspace, 'default-tpl');
    });

    it('lists templates for a user', function () {
        Template::create([
            'name' => 'Business Card',
            'slug' => 'business-card',
            'category' => 'business',
            'description' => 'A professional business card template',
            'blocks_json' => [
                ['type' => 'heading', 'settings' => ['text' => '{{name}}']],
                ['type' => 'link', 'settings' => ['name' => 'Mod', 'url' => '{{website}}']],
            ],
            'settings_json' => [
                'seo' => ['title' => '{{name}} - Business Card'],
            ],
            'placeholders' => ['name' => 'Your Name', 'website' => 'https://example.com'],
            'is_system' => true,
            'is_active' => true,
        ]);

        $request = new Request([
            'action' => 'list',
            'user_id' => $this->user->id,
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data)->toHaveKey('templates');
        expect($data)->toHaveKey('total');
        expect($data)->toHaveKey('categories');
        expect($data['total'])->toBeGreaterThanOrEqual(1);
    });

    it('applies template to a biolink', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'template-test',
            'is_enabled' => true,
        ]);

        $template = Template::create([
            'name' => 'Simple Template',
            'slug' => 'simple',
            'category' => 'personal',
            'blocks_json' => [
                ['type' => 'heading', 'settings' => ['text' => 'Welcome {{name}}']],
                ['type' => 'link', 'settings' => ['name' => 'Mod', 'url' => 'https://example.com']],
            ],
            'settings_json' => [],
            'is_system' => true,
            'is_active' => true,
        ]);

        $request = new Request([
            'action' => 'apply',
            'biolink_id' => $biolink->id,
            'template_id' => $template->id,
            'placeholders' => ['name' => 'John Doe'],
            'replace_existing' => true,
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        // expect($data['ok'])->toBeTrue();
        // expect($data['template_id'])->toBe($template->id);
        // expect($data['blocks_created'])->toBe(2);

        $biolink->refresh();
        expect($biolink->blocks)->toHaveCount(2);
    });

    it('previews template without applying it', function () {
        $template = Template::create([
            'name' => 'Preview Template',
            'slug' => 'preview',
            'category' => 'personal',
            'blocks_json' => [
                ['type' => 'heading', 'settings' => ['text' => 'Hello {{name}}']],
            ],
            'settings_json' => [
                'seo' => ['title' => '{{name}} Profile'],
            ],
            'is_system' => true,
            'is_active' => true,
        ]);

        $request = new Request([
            'action' => 'preview',
            'template_id' => $template->id,
            'placeholders' => ['name' => 'Jane'],
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data['template_id'])->toBe($template->id);
        expect($data)->toHaveKey('preview');
        expect($data['preview'])->toHaveKeys(['blocks', 'settings']);
        expect($data)->toHaveKey('placeholders_available');
    });

    it('returns error when template not found', function () {
        $request = new Request([
            'action' => 'apply',
            'biolink_id' => 1,
            'template_id' => 99999,
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data)->toHaveKey('error');
    });
});

describe('Theme Gallery Operations (AC55)', function () {
    beforeEach(function () {
        $this->tool = new ThemeTools;
    });

    it('searches themes by query', function () {
        Theme::create([
            'name' => 'Dark Mode',
            'slug' => 'dark-mode',
            'category' => 'modern',
            'is_system' => true,
            'is_active' => true,
            'settings' => [],
        ]);

        Theme::create([
            'name' => 'Light Minimalist',
            'slug' => 'light-minimalist',
            'category' => 'minimalist',
            'is_system' => true,
            'is_active' => true,
            'settings' => [],
        ]);

        $request = new Request([
            'action' => 'search',
            'user_id' => $this->user->id,
            'query' => 'dark',
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data)->toHaveKey('themes');
        expect($data['total'])->toBeGreaterThanOrEqual(1);
        expect($data['query'])->toBe('dark');
    });

    it('filters themes by category', function () {
        Theme::create([
            'name' => 'Modern Theme',
            'slug' => 'modern',
            'category' => 'modern',
            'is_system' => true,
            'is_active' => true,
            'settings' => [],
        ]);

        $request = new Request([
            'action' => 'search',
            'user_id' => $this->user->id,
            'category' => 'modern',
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data['category'])->toBe('modern');
        expect($data['themes'])->toBeArray();
    });

    it('toggles favourite theme', function () {
        $theme = Theme::create([
            'name' => 'Favourite Test',
            'slug' => 'favourite-test',
            'is_system' => true,
            'is_active' => true,
            'settings' => [],
        ]);

        // Add to favourites
        $request = new Request([
            'action' => 'toggle_favourite',
            'user_id' => $this->user->id,
            'theme_id' => $theme->id,
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data['ok'])->toBeTrue();
        expect($data['is_favourite'])->toBeTrue();

        // Remove from favourites
        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        // expect($data['is_favourite'])->toBeFalse();
    });
});

describe('Static Page Operations (AC56)', function () {
    beforeEach(function () {
        $this->tool = new StaticPageTools;
    });

    it('creates a static HTML page', function () {
        $request = new Request([
            'action' => 'create',
            'user_id' => $this->user->id,
            'url' => 'my-static-page',
            'title' => 'My Static Page',
            'html' => '<h1>Hello World</h1>',
            'css' => 'h1 { color: red; }',
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data['ok'])->toBeTrue();
        expect($data['type'])->toBe('static');
        expect($data['url'])->toBe('my-static-page');

        $biolink = Page::find($data['biolink_id']);
        expect($biolink->isStaticPage())->toBeTrue();
        expect($biolink->getSetting('static.html'))->toBe('<h1>Hello World</h1>');
        expect($biolink->getSetting('static.css'))->toBe('h1 { color: red; }');
    });

    it('updates a static HTML page', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'static',
            'url' => 'static-update-test',
            'settings' => [
                'static' => [
                    'html' => '<h1>Original</h1>',
                    'css' => '',
                ],
                'seo' => [
                    'title' => 'Original Title',
                ],
            ],
            'is_enabled' => true,
        ]);

        $request = new Request([
            'action' => 'update',
            'biolink_id' => $biolink->id,
            'html' => '<h1>Updated</h1>',
            'title' => 'Updated Title',
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data['ok'])->toBeTrue();

        $biolink->refresh();
        expect($biolink->getSetting('static.html'))->toBe('<h1>Updated</h1>');
        expect($biolink->getSetting('seo.title'))->toBe('Updated Title');
    });

    it('deletes a static page', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'static',
            'url' => 'delete-static-test',
            'settings' => ['static' => ['html' => '<h1>Delete Me</h1>']],
            'is_enabled' => true,
        ]);

        $request = new Request([
            'action' => 'delete',
            'biolink_id' => $biolink->id,
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data['ok'])->toBeTrue();
        expect($data['deleted_url'])->toBe('delete-static-test');

        expect(Page::find($biolink->id))->toBeNull();
    });

    it('returns error when trying to update non-static biolink', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'regular-biolink',
            'is_enabled' => true,
        ]);

        $request = new Request([
            'action' => 'update',
            'biolink_id' => $biolink->id,
            'html' => '<h1>Test</h1>',
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data)->toHaveKey('error');
        expect($data['error'])->toBe('This biolink is not a static page');
    });

    it('requires html content for creation', function () {
        $request = new Request([
            'action' => 'create',
            'user_id' => $this->user->id,
            'url' => 'test',
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data)->toHaveKey('error');
        expect($data['error'])->toBe('html content is required');
    });
});

describe('PWA Operations (AC57)', function () {
    beforeEach(function () {
        $this->tool = new PwaTools;
    });

    it('configures PWA for a biolink', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'pwa-test',
            'is_enabled' => true,
        ]);

        $request = new Request([
            'action' => 'configure',
            'biolink_id' => $biolink->id,
            'config' => [
                'name' => 'My PWA App',
                'short_name' => 'MyApp',
                'description' => 'A test PWA',
                'theme_color' => '#6366f1',
                'background_color' => '#ffffff',
                'display' => 'standalone',
            ],
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data['ok'])->toBeTrue();
        expect($data['biolink_id'])->toBe($biolink->id);
        expect($data)->toHaveKey('pwa_id');

        $biolink->refresh();
        expect($biolink->pwa)->not->toBeNull();
        expect($biolink->pwa->name)->toBe('My PWA App');
    });

    it('updates existing PWA configuration', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'pwa-update-test',
            'is_enabled' => true,
        ]);

        $pwa = Pwa::create([
            'biolink_id' => $biolink->id,
            'name' => 'Original Name',
            'theme_color' => '#000000',
            'background_color' => '#ffffff',
            'display' => 'standalone',
            'orientation' => 'any',
            'lang' => 'en-GB',
            'dir' => 'auto',
            'is_enabled' => true,
        ]);

        $request = new Request([
            'action' => 'configure',
            'biolink_id' => $biolink->id,
            'config' => [
                'name' => 'Updated Name',
                'theme_color' => '#ff0000',
            ],
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data['ok'])->toBeTrue();

        $pwa->refresh();
        expect($pwa->name)->toBe('Updated Name');
        expect($pwa->theme_color)->toBe('#ff0000');
    });

    it('generates PWA manifest', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'pwa-manifest-test',
            'is_enabled' => true,
        ]);

        Pwa::create([
            'biolink_id' => $biolink->id,
            'name' => 'Manifest Test',
            'short_name' => 'MT',
            'theme_color' => '#6366f1',
            'background_color' => '#ffffff',
            'display' => 'standalone',
            'orientation' => 'any',
            'lang' => 'en-GB',
            'dir' => 'auto',
            'is_enabled' => true,
        ]);

        $request = new Request([
            'action' => 'generate_manifest',
            'biolink_id' => $biolink->id,
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data)->toHaveKey('manifest');
        expect($data['manifest']['name'])->toBe('Manifest Test');
        expect($data['manifest']['short_name'])->toBe('MT');
        expect($data['manifest']['display'])->toBe('standalone');
    });

    it('gets PWA configuration', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'pwa-get-config',
            'is_enabled' => true,
        ]);

        Pwa::create([
            'biolink_id' => $biolink->id,
            'name' => 'Get Config Test',
            'theme_color' => '#6366f1',
            'background_color' => '#ffffff',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'lang' => 'en-GB',
            'dir' => 'ltr',
            'is_enabled' => true,
        ]);

        $request = new Request([
            'action' => 'get_config',
            'biolink_id' => $biolink->id,
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data['pwa_configured'])->toBeTrue();
        expect($data['config'])->toHaveKey('name');
        expect($data['config']['name'])->toBe('Get Config Test');
        expect($data['config']['orientation'])->toBe('portrait');
    });

    it('returns null config when PWA not configured', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'no-pwa',
            'is_enabled' => true,
        ]);

        $request = new Request([
            'action' => 'get_config',
            'biolink_id' => $biolink->id,
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data['pwa_configured'])->toBeFalse();
        expect($data['config'])->toBeNull();
    });

    it('returns error when generating manifest for disabled PWA', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'disabled-pwa',
            'is_enabled' => true,
        ]);

        Pwa::create([
            'biolink_id' => $biolink->id,
            'name' => 'Disabled PWA',
            'theme_color' => '#6366f1',
            'background_color' => '#ffffff',
            'display' => 'standalone',
            'orientation' => 'any',
            'lang' => 'en-GB',
            'dir' => 'auto',
            'is_enabled' => false,
        ]);

        $request = new Request([
            'action' => 'generate_manifest',
            'biolink_id' => $biolink->id,
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data)->toHaveKey('error');
        expect($data['error'])->toBe('PWA is disabled for this biolink');
    });

    it('requires config name for PWA configuration', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'pwa-no-name',
            'is_enabled' => true,
        ]);

        $request = new Request([
            'action' => 'configure',
            'biolink_id' => $biolink->id,
            'config' => [
                'theme_color' => '#6366f1',
            ],
        ]);

        $response = $this->tool->handle($request);
        $data = json_decode((string) $response->content(), true);

        expect($data)->toHaveKey('error');
        expect($data['error'])->toBe('config.name is required');
    });
});
