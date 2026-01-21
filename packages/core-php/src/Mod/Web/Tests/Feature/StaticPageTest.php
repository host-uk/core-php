<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\CssScopeService;
use Core\Mod\Web\Services\StaticPageSanitiser;
use Core\Mod\Web\View\Livewire\Hub\CreateStaticPage;
use Core\Mod\Web\View\Livewire\Hub\EditStaticPage;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;
use Core\Mod\Tenant\Models\Feature;
use Core\Mod\Tenant\Models\Package;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();

    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
    $this->actingAs($this->user);

    // Set up entitlements for BioHost features
    $staticFeature = Feature::create([
        'code' => 'bio.static',
        'name' => 'Static Pages',
        'description' => 'Number of static HTML pages allowed',
        'category' => 'biolink',
        'type' => Feature::TYPE_LIMIT,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $package = Package::create([
        'code' => 'creator',
        'name' => 'Creator',
        'description' => 'For individual creators',
        'is_stackable' => false,
        'is_base_package' => true,
        'is_active' => true,
        'is_public' => true,
        'sort_order' => 1,
    ]);

    // Attach feature to package with generous limit
    $package->features()->attach($staticFeature->id, ['limit_value' => 10]);

    // Provision package to workspace
    app(EntitlementService::class)->provisionPackage($this->workspace, 'creator');
});

describe('Static page creation page', function () {
    it('can render the create static page page', function () {
        Livewire::test(CreateStaticPage::class)
            ->assertStatus(200)
            ->assertSee('Create static page')
            ->assertSee('HTML content');
    });

    it('validates required fields', function () {
        Livewire::test(CreateStaticPage::class)
            ->set('url', '')
            ->set('title', '')
            ->set('htmlContent', '')
            ->call('create')
            ->assertHasErrors(['url' => 'required', 'title' => 'required', 'htmlContent' => 'required']);
    });

    it('validates URL format', function () {
        Livewire::test(CreateStaticPage::class)
            ->set('url', 'invalid slug with spaces')
            ->set('title', 'Test Page')
            ->set('htmlContent', '<h1>Test</h1>')
            ->call('create')
            ->assertHasErrors(['url' => 'regex']);
    });

    it('validates title minimum length', function () {
        Livewire::test(CreateStaticPage::class)
            ->set('url', 'test-page')
            ->set('title', 'ab')
            ->set('htmlContent', '<h1>Test</h1>')
            ->call('create')
            ->assertHasErrors(['title' => 'min']);
    });

    it('creates a static page with valid data', function () {
        Livewire::test(CreateStaticPage::class)
            ->set('url', 'my-page')
            ->set('title', 'My Custom Page')
            ->set('htmlContent', '<h1>Welcome</h1><p>This is my page.</p>')
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('hub.bio.index'));

        $this->assertDatabaseHas('biolinks', [
            'url' => 'my-page',
            'type' => 'static',
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
        ]);

        $biolink = Page::where('url', 'my-page')->first();
        expect($biolink)->not->toBeNull()
            ->and($biolink->getSetting('title'))->toBe('My Custom Page')
            ->and($biolink->getSetting('static_html'))->toContain('Welcome');
    });

    it('creates static page with CSS and JavaScript', function () {
        Livewire::test(CreateStaticPage::class)
            ->set('url', 'styled-page')
            ->set('title', 'Styled Page')
            ->set('htmlContent', '<h1>Hello</h1>')
            ->set('cssContent', 'h1 { color: red; }')
            ->set('jsContent', 'console.log("Hello");')
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'styled-page')->first();
        expect($biolink->getSetting('static_css'))->toContain('color: red')
            ->and($biolink->getSetting('static_js'))->toContain('console.log');
    });

    it('converts URL to lowercase', function () {
        Livewire::test(CreateStaticPage::class)
            ->set('url', 'MyUpperCasePage')
            ->set('title', 'Test Page')
            ->set('htmlContent', '<h1>Test</h1>')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biolinks', [
            'url' => 'myuppercasepage',
            'type' => 'static',
        ]);
    });

    it('prevents duplicate URLs', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'static',
            'url' => 'existing-page',
            'settings' => [
                'title' => 'Existing',
                'static_html' => '<h1>Existing</h1>',
            ],
        ]);

        Livewire::test(CreateStaticPage::class)
            ->set('url', 'existing-page')
            ->set('title', 'Test Page')
            ->set('htmlContent', '<h1>Test</h1>')
            ->call('create')
            ->assertHasErrors(['url']);
    });

    it('can disable the page on creation', function () {
        Livewire::test(CreateStaticPage::class)
            ->set('url', 'disabled-page')
            ->set('title', 'Disabled Page')
            ->set('htmlContent', '<h1>Test</h1>')
            ->set('isEnabled', false)
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'disabled-page')->first();
        expect($biolink->is_enabled)->toBeFalse();
    });

    it('respects entitlement limits', function () {
        // Create pages up to the limit (10)
        for ($i = 0; $i < 10; $i++) {
            Page::create([
                'workspace_id' => $this->workspace->id,
                'user_id' => $this->user->id,
                'type' => 'static',
                'url' => "page-{$i}",
                'settings' => ['title' => "Page {$i}", 'static_html' => '<h1>Test</h1>'],
            ]);

            app(EntitlementService::class)->recordUsage(
                $this->workspace,
                'bio.static',
                1,
                $this->user
            );
        }

        // Attempting to create one more should fail
        $component = Livewire::test(CreateStaticPage::class);

        expect($component->get('canCreate'))->toBeFalse()
            ->and($component->get('entitlementError'))->toContain('limit');
    });
});

describe('Static page editing', function () {
    it('can render the edit static page page', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'static',
            'url' => 'test-page',
            'settings' => [
                'title' => 'Test Page',
                'static_html' => '<h1>Original</h1>',
                'static_css' => 'h1 { color: blue; }',
                'static_js' => 'console.log("test");',
            ],
        ]);

        Livewire::test(EditStaticPage::class, ['biolink' => $biolink])
            ->assertStatus(200)
            ->assertSee('Edit static page')
            ->assertSet('title', 'Test Page')
            ->assertSet('htmlContent', '<h1>Original</h1>');
    });

    it('can update static page content', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'static',
            'url' => 'test-page',
            'settings' => [
                'title' => 'Test Page',
                'static_html' => '<h1>Original</h1>',
            ],
        ]);

        Livewire::test(EditStaticPage::class, ['biolink' => $biolink])
            ->set('title', 'Updated Page')
            ->set('htmlContent', '<h1>Updated</h1>')
            ->call('save')
            ->assertHasNoErrors();

        $biolink->refresh();
        expect($biolink->getSetting('title'))->toBe('Updated Page')
            ->and($biolink->getSetting('static_html'))->toContain('Updated');
    });

    it('can delete a static page', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'static',
            'url' => 'test-page',
            'settings' => [
                'title' => 'Test Page',
                'static_html' => '<h1>Test</h1>',
            ],
        ]);

        Livewire::test(EditStaticPage::class, ['biolink' => $biolink])
            ->call('delete')
            ->assertRedirect(route('hub.bio.index'));

        expect(Page::where('id', $biolink->id)->whereNull('deleted_at')->exists())->toBeFalse();
    });

    it('prevents editing by non-owners', function () {
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);

        $biolink = Page::create([
            'workspace_id' => $otherWorkspace->id,
            'user_id' => $otherUser->id,
            'type' => 'static',
            'url' => 'test-page-auth',
            'settings' => [
                'title' => 'Test Page',
                'static_html' => '<h1>Test</h1>',
            ],
        ]);

        Livewire::test(EditStaticPage::class, ['biolink' => $biolink])
            ->assertStatus(403);
    });
});

describe('Static page model behavior', function () {
    it('identifies itself as a static page', function () {
        $staticPage = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'static',
            'url' => 'test-page',
            'settings' => ['title' => 'Test', 'static_html' => '<h1>Test</h1>'],
        ]);

        expect($staticPage->isStaticPage())->toBeTrue()
            ->and($staticPage->isBioLinkPage())->toBeFalse()
            ->and($staticPage->isShortLink())->toBeFalse();
    });

    it('generates correct full URL', function () {
        $staticPage = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'static',
            'url' => 'my-static-page',
            'settings' => ['title' => 'Test', 'static_html' => '<h1>Test</h1>'],
        ]);

        expect($staticPage->full_url)->toContain('my-static-page');
    });
});

describe('CSS and JavaScript scoping', function () {
    it('scopes CSS correctly', function () {
        $service = app(CssScopeService::class);
        $scopeId = $service->generateScopeId(123);

        $css = 'h1 { color: red; } p { font-size: 16px; }';
        $scoped = $service->scopeCss($css, $scopeId);

        expect($scoped)->toContain('#static-page-123 h1')
            ->and($scoped)->toContain('#static-page-123 p');
    });

    it('wraps HTML in scoped container', function () {
        $service = app(CssScopeService::class);
        $scopeId = $service->generateScopeId(456);

        $html = '<h1>Test</h1>';
        $wrapped = $service->wrapInScope($html, $scopeId);

        expect($wrapped)->toContain('id="static-page-456"')
            ->and($wrapped)->toContain('<h1>Test</h1>');
    });

    it('handles at-rules in CSS', function () {
        $service = app(CssScopeService::class);
        $scopeId = $service->generateScopeId(789);

        $css = '@media (max-width: 768px) { h1 { font-size: 20px; } }';
        $scoped = $service->scopeCss($css, $scopeId);

        expect($scoped)->toContain('@media')
            ->and($scoped)->toContain('#static-page-789 h1');
    });
});

describe('HTML sanitisation', function () {
    it('sanitises HTML content', function () {
        $sanitiser = app(StaticPageSanitiser::class);

        $dangerous = '<script>alert("XSS")</script><h1>Safe</h1>';
        $sanitised = $sanitiser->sanitiseHtml($dangerous);

        expect($sanitised)->not->toContain('<script>')
            ->and($sanitised)->toContain('<h1>Safe</h1>');
    });

    it('allows safe HTML tags', function () {
        $sanitiser = app(StaticPageSanitiser::class);

        $safe = '<h1>Title</h1><p>Paragraph</p><a href="https://example.com">Link</a><img src="test.jpg" alt="Test">';
        $sanitised = $sanitiser->sanitiseHtml($safe);

        expect($sanitised)->toContain('<h1>Title</h1>')
            ->and($sanitised)->toContain('<p>Paragraph</p>')
            ->and($sanitised)->toContain('<a')
            ->and($sanitised)->toContain('<img');
    });

    it('sanitises JavaScript content', function () {
        $sanitiser = app(StaticPageSanitiser::class);

        $dangerous = 'console.log("safe"); eval("alert(1)"); document.write("<script>bad</script>");';
        $sanitised = $sanitiser->sanitiseJavaScript($dangerous);

        expect($sanitised)->toContain('console.log')
            ->and($sanitised)->not->toContain('eval(')
            ->and($sanitised)->not->toContain('document.write(');
    });

    it('sanitises CSS content', function () {
        $sanitiser = app(StaticPageSanitiser::class);

        $dangerous = 'h1 { color: red; } @import url("bad.css"); body { behavior: url(xss.htc); }';
        $sanitised = $sanitiser->sanitiseCss($dangerous);

        expect($sanitised)->toContain('color: red')
            ->and($sanitised)->not->toContain('@import');
    });
});
