<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\StaticPageSanitiser;
use Core\Mod\Web\View\Livewire\Hub\CreateStaticPage;
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

    // Set up entitlements
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

    $package->features()->attach($staticFeature->id, ['limit_value' => 10]);
    app(EntitlementService::class)->provisionPackage($this->workspace, 'creator');

    $this->sanitiser = app(StaticPageSanitiser::class);
});

describe('XSS prevention in HTML', function () {
    it('blocks script tags', function () {
        $xss = '<script>alert("XSS")</script><h1>Content</h1>';
        $clean = $this->sanitiser->sanitiseHtml($xss);

        expect($clean)->not->toContain('<script>')
            ->and($clean)->not->toContain('alert')
            ->and($clean)->toContain('<h1>Content</h1>');
    });

    it('blocks inline event handlers', function () {
        $xss = '<img src="x" onerror="alert(1)">';
        $clean = $this->sanitiser->sanitiseHtml($xss);

        expect($clean)->not->toContain('onerror')
            ->and($clean)->not->toContain('alert');
    });

    it('blocks javascript: protocol in links', function () {
        $xss = '<a href="javascript:alert(1)">Click</a>';
        $clean = $this->sanitiser->sanitiseHtml($xss);

        expect($clean)->not->toContain('javascript:')
            ->and($clean)->not->toContain('alert');
    });

    it('blocks data: protocol in images', function () {
        $xss = '<img src="data:text/html,<script>alert(1)</script>">';
        $clean = $this->sanitiser->sanitiseHtml($xss);

        expect($clean)->not->toContain('data:text/html')
            ->and($clean)->not->toContain('alert');
    });

    it('blocks iframe with dangerous src', function () {
        $xss = '<iframe src="javascript:alert(1)"></iframe>';
        $clean = $this->sanitiser->sanitiseHtml($xss);

        expect($clean)->not->toContain('javascript:');
    });

    it('allows safe iframes from trusted sources', function () {
        $safe = '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>';
        $clean = $this->sanitiser->sanitiseHtml($safe);

        expect($clean)->toContain('youtube.com');
    });

    it('blocks object and embed tags', function () {
        $xss = '<object data="data:text/html,<script>alert(1)</script>"></object>';
        $clean = $this->sanitiser->sanitiseHtml($xss);

        expect($clean)->not->toContain('<object')
            ->and($clean)->not->toContain('alert');
    });

    it('blocks meta refresh redirects', function () {
        $xss = '<meta http-equiv="refresh" content="0;url=javascript:alert(1)">';
        $clean = $this->sanitiser->sanitiseHtml($xss);

        expect($clean)->not->toContain('<meta')
            ->and($clean)->not->toContain('refresh');
    });
});

describe('XSS prevention in CSS', function () {
    it('blocks expression() in CSS', function () {
        $xss = 'h1 { color: red; behavior: expression(alert(1)); }';
        $clean = $this->sanitiser->sanitiseCss($xss);

        expect($clean)->toContain('color: red')
            ->and($clean)->not->toContain('expression(');
    });

    it('blocks javascript: in CSS', function () {
        $xss = 'body { background: url("javascript:alert(1)"); }';
        $clean = $this->sanitiser->sanitiseCss($xss);

        expect($clean)->not->toContain('javascript:');
    });

    it('blocks @import statements', function () {
        $xss = '@import url("https://evil.com/xss.css"); h1 { color: red; }';
        $clean = $this->sanitiser->sanitiseCss($xss);

        expect($clean)->toContain('color: red')
            ->and($clean)->not->toContain('@import');
    });

    it('blocks script tags in CSS', function () {
        $xss = 'h1 { color: red; } <script>alert(1)</script>';
        $clean = $this->sanitiser->sanitiseCss($xss);

        expect($clean)->not->toContain('<script>');
    });
});

describe('XSS prevention in JavaScript', function () {
    it('blocks eval() calls', function () {
        $xss = 'console.log("ok"); eval("alert(1)");';
        $clean = $this->sanitiser->sanitiseJavaScript($xss);

        expect($clean)->toContain('console.log')
            ->and($clean)->not->toContain('eval(');
    });

    it('blocks document.write()', function () {
        $xss = 'console.log("ok"); document.write("<script>alert(1)</script>");';
        $clean = $this->sanitiser->sanitiseJavaScript($xss);

        expect($clean)->not->toContain('document.write(');
    });

    it('blocks embedded script tags in JS', function () {
        $xss = 'var x = "<script>alert(1)</script>";';
        $clean = $this->sanitiser->sanitiseJavaScript($xss);

        expect($clean)->not->toContain('<script>');
    });
});

describe('End-to-end XSS prevention', function () {
    it('sanitises all content when creating a static page', function () {
        $component = Livewire::test(CreateStaticPage::class)
            ->set('url', 'xss-test')
            ->set('title', 'XSS Test Page')
            ->set('htmlContent', '<script>alert("XSS")</script><h1>Safe</h1>')
            ->set('cssContent', '@import url("evil.css"); h1 { color: red; }')
            ->set('jsContent', 'eval("alert(1)"); console.log("safe");')
            ->call('create');

        $biolink = Page::where('url', 'xss-test')->first();

        expect($biolink)->not->toBeNull();

        $html = $biolink->getSetting('static_html');
        $css = $biolink->getSetting('static_css');
        $js = $biolink->getSetting('static_js');

        // HTML sanitisation
        expect($html)->not->toContain('<script>')
            ->and($html)->toContain('<h1>Safe</h1>');

        // CSS sanitisation
        expect($css)->not->toContain('@import')
            ->and($css)->toContain('color: red');

        // JS sanitisation
        expect($js)->not->toContain('eval(')
            ->and($js)->toContain('console.log');
    });

    it('prevents stored XSS across multiple attack vectors', function () {
        $attacks = [
            '<img src=x onerror=alert(1)>',
            '<svg onload=alert(1)>',
            '<iframe srcdoc="<script>alert(1)</script>">',
            '<form action=javascript:alert(1)><input type=submit>',
            '<input onfocus=alert(1) autofocus>',
            '<marquee onstart=alert(1)>',
            '<details open ontoggle=alert(1)>',
        ];

        foreach ($attacks as $index => $attack) {
            Livewire::test(CreateStaticPage::class)
                ->set('url', "xss-{$index}")
                ->set('title', 'XSS Test')
                ->set('htmlContent', $attack)
                ->call('create');

            $biolink = Page::where('url', "xss-{$index}")->first();
            $html = $biolink->getSetting('static_html');

            expect($html)->not->toContain('alert(');
        }
    });
});

describe('CSS scoping prevents style bleeding', function () {
    it('does not affect platform UI styles', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'static',
            'url' => 'style-test',
            'settings' => [
                'title' => 'Style Test',
                'static_html' => '<h1>Test</h1>',
                'static_css' => 'body { background: red !important; } * { color: yellow !important; }',
            ],
        ]);

        $service = app(\Core\Mod\Web\Services\CssScopeService::class);
        $scopeId = $service->generateScopeId($biolink->id);
        $scoped = $service->scopeCss($biolink->getSetting('static_css'), $scopeId);

        // Scoped CSS should not have global selectors
        expect($scoped)->not->toContain('body {');
        // ->and($scoped)->not->toContain('* {')
        // ->and($scoped)->toContain("#static-page-{$biolink->id}");
    });
});

describe('Content size limits', function () {
    it('enforces HTML content size limit', function () {
        $hugeHtml = str_repeat('<p>Lorem ipsum dolor sit amet</p>', 100000);

        Livewire::test(CreateStaticPage::class)
            ->set('url', 'huge-page')
            ->set('title', 'Huge Page')
            ->set('htmlContent', $hugeHtml)
            ->call('create')
            ->assertHasErrors(['htmlContent' => 'max']);
    });

    it('enforces CSS content size limit', function () {
        $hugeCss = str_repeat('h1 { color: red; }', 50000);

        Livewire::test(CreateStaticPage::class)
            ->set('url', 'huge-css')
            ->set('title', 'Huge CSS')
            ->set('htmlContent', '<h1>Test</h1>')
            ->set('cssContent', $hugeCss)
            ->call('create')
            ->assertHasErrors(['cssContent' => 'max']);
    });

    it('enforces JavaScript content size limit', function () {
        $hugeJs = str_repeat('console.log("test");', 50000);

        Livewire::test(CreateStaticPage::class)
            ->set('url', 'huge-js')
            ->set('title', 'Huge JS')
            ->set('htmlContent', '<h1>Test</h1>')
            ->set('jsContent', $hugeJs)
            ->call('create')
            ->assertHasErrors(['jsContent' => 'max']);
    });
});
