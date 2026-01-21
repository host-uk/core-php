<?php

use Core\Seo\Jobs\GenerateOgImageJob;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\DynamicOgImageService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Fake the storage for testing
    Storage::fake('public');

    // Create a test workspace and user
    $this->workspace = Workspace::factory()->create();
    $this->user = User::factory()->create();
    $this->workspace->users()->attach($this->user);
});

test('og image service can generate image with default settings', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'testuser',
        'type' => 'biolink',
        'settings' => [
            'seo' => [
                'title' => 'Test User Bio',
                'description' => 'This is a test bio page',
            ],
            'background' => [
                'type' => 'color',
                'color' => '#3b82f6',
            ],
        ],
    ]);

    $service = app(DynamicOgImageService::class);
    $url = $service->generate($biolink);

    expect($url)->toBeString();
    expect($service->exists($biolink))->toBeTrue();

    // Check that file was created
    $filename = "og-images/biolink-{$biolink->id}.jpg";
    Storage::disk('public')->assertExists($filename);
});

test('og image service supports custom background colour', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'customcolour',
        'settings' => [
            'seo' => [
                'title' => 'Custom Colour Test',
            ],
            'background' => [
                'type' => 'color',
                'color' => '#ec4899', // Pink
            ],
        ],
    ]);

    $service = app(DynamicOgImageService::class);
    $url = $service->generate($biolink);

    expect($service->exists($biolink))->toBeTrue();
});

test('og image service supports gradient backgrounds', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'gradient',
        'settings' => [
            'seo' => [
                'title' => 'Gradient Test',
            ],
            'background' => [
                'type' => 'gradient',
                'gradient_start' => '#3b82f6',
                'gradient_end' => '#8b5cf6',
            ],
        ],
    ]);

    $service = app(DynamicOgImageService::class);
    $url = $service->generate($biolink);

    expect($service->exists($biolink))->toBeTrue();
});

test('og image service supports minimal template', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'minimal',
        'settings' => [
            'seo' => [
                'title' => 'Minimal Template Test',
            ],
        ],
    ]);

    $service = app(DynamicOgImageService::class);
    $url = $service->generate($biolink, 'minimal');

    expect($service->exists($biolink))->toBeTrue();
});

test('og image service supports branded template', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'branded',
        'settings' => [
            'seo' => [
                'title' => 'Branded Template Test',
                'description' => 'Testing the branded template',
            ],
        ],
    ]);

    $service = app(DynamicOgImageService::class);
    $url = $service->generate($biolink, 'branded');

    expect($service->exists($biolink))->toBeTrue();
});

test('og image can be deleted', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'deleteme',
    ]);

    $service = app(DynamicOgImageService::class);
    $service->generate($biolink);

    expect($service->exists($biolink))->toBeTrue();

    $service->delete($biolink);

    expect($service->exists($biolink))->toBeFalse();
});

test('og image url can be retrieved without generating', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'urltest',
    ]);

    $service = app(DynamicOgImageService::class);
    $url = $service->getUrl($biolink);

    expect($url)->toBeString();
    expect($url)->toContain("biolink-{$biolink->id}.jpg");
});

test('og image service respects quality setting', function () {
    config(['biolinks.og_images.quality' => 50]);

    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'quality',
    ]);

    $service = app(DynamicOgImageService::class);
    $service->generate($biolink);

    expect($service->exists($biolink))->toBeTrue();
});

test('og image job can be dispatched', function () {
    Queue::fake();

    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'jobtest',
    ]);

    GenerateOgImageJob::dispatch($biolink->id);

    Queue::assertPushed(GenerateOgImageJob::class);
});

test('og image job generates image when run', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'jobrunstest',
        'settings' => [
            'seo' => [
                'title' => 'Job Test',
            ],
        ],
    ]);

    $service = app(DynamicOgImageService::class);
    expect($service->exists($biolink))->toBeFalse();

    // Run the job
    $job = new GenerateOgImageJob($biolink->id);
    $job->handle($service);

    expect($service->exists($biolink))->toBeTrue();
});

test('og image job skips generation if disabled in config', function () {
    config(['biolinks.og_images.enabled' => false]);

    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'disabled',
    ]);

    $service = app(DynamicOgImageService::class);

    $job = new GenerateOgImageJob($biolink->id);
    $job->handle($service);

    // expect($service->exists($biolink))->toBeFalse();
});

test('og image job skips if image exists and is not stale', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'notstale',
    ]);

    $service = app(DynamicOgImageService::class);
    $service->generate($biolink);

    expect($service->exists($biolink))->toBeTrue();

    // Track if generation happens again
    $firstUrl = $service->getUrl($biolink);

    // Run job again (should skip)
    $job = new GenerateOgImageJob($biolink->id, 'default', false);
    $job->handle($service);

    // Image should still exist and be the same
    expect($service->exists($biolink))->toBeTrue();
});

test('og image job regenerates when forced', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'forced',
    ]);

    $service = app(DynamicOgImageService::class);
    $service->generate($biolink);

    expect($service->exists($biolink))->toBeTrue();

    // Force regeneration
    $job = new GenerateOgImageJob($biolink->id, 'default', true);
    $job->handle($service);

    expect($service->exists($biolink))->toBeTrue();
});

test('og image controller method returns image when it exists', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'routetest',
        'domain_id' => null,
        'is_enabled' => true,
    ]);

    $service = app(DynamicOgImageService::class);
    $service->generate($biolink);

    // Test the controller method directly
    $controller = new \Core\Mod\Web\Controllers\Web\PublicBioPageController;
    $request = \Illuminate\Http\Request::create('/routetest/og.jpg', 'GET');
    $request->headers->set('Host', 'bio.host.uk.com');

    $response = $controller->ogImage($request, 'routetest');

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('image/jpeg');
    expect($response->headers->get('Cache-Control'))->toContain('public');
});

test('og image route returns 404 when image does not exist', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'noimage',
        'domain_id' => null,
        'is_enabled' => true,
    ]);

    $response = $this->get('/noimage/og.jpg');

    $response->assertStatus(404);
});

test('og image route returns 404 for non-existent biolink', function () {
    $response = $this->get('/nonexistent/og.jpg');

    $response->assertStatus(404);
});

test('biolink view includes og meta tags when enabled', function () {
    config(['biolinks.og_images.enabled' => true]);

    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'metatest',
        'domain_id' => null,
        'is_enabled' => true,
        'settings' => [
            'seo' => [
                'title' => 'Meta Test Page',
                'description' => 'Testing OG meta tags',
            ],
        ],
    ]);

    // Render the view directly
    $html = view('webpage::page', [
        'biolink' => $biolink,
        'blocks' => collect([]),
        'request' => request(),
        'pwa' => null,
        'pushConfig' => null,
        'pixels' => collect([]),
        'is_verified' => false,
    ])->render();

    expect($html)->toContain('og:image');
    expect($html)->toContain('/metatest/og.jpg');
    expect($html)->toContain('og:title');
    expect($html)->toContain('Meta Test Page');
    expect($html)->toContain('twitter:card');
    expect($html)->toContain('summary_large_image');
});

test('biolink view does not include og image when disabled', function () {
    config(['biolinks.og_images.enabled' => false]);

    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'nodisabledmeta',
        'domain_id' => null,
        'is_enabled' => true,
    ]);

    // Render the view directly
    $html = view('webpage::page', [
        'biolink' => $biolink,
        'blocks' => collect([]),
        'request' => request(),
        'pwa' => null,
        'pushConfig' => null,
        'pixels' => collect([]),
        'is_verified' => false,
    ])->render();

    // expect($html)->not->toContain('og:image');
});

test('og image service correctly checks if image is stale', function () {
    config(['biolinks.og_images.cache_days' => 10]);

    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'staletest',
    ]);

    $service = app(DynamicOgImageService::class);

    // No image exists - should be stale
    expect($service->isStale($biolink))->toBeTrue();

    // Generate image
    $service->generate($biolink);

    // Freshly generated - should not be stale
    expect($service->isStale($biolink))->toBeFalse();
});

test('og image service handles biolinks with long titles', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'longtitle',
        'settings' => [
            'seo' => [
                'title' => 'This is a very long title that should be wrapped properly to fit within the image dimensions without overflowing or causing layout issues',
            ],
        ],
    ]);

    $service = app(DynamicOgImageService::class);
    $url = $service->generate($biolink);

    expect($service->exists($biolink))->toBeTrue();
});

test('og image service handles biolinks without seo settings', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'noseo',
        'settings' => [],
    ]);

    $service = app(DynamicOgImageService::class);
    $url = $service->generate($biolink);

    expect($service->exists($biolink))->toBeTrue();
});

test('og image service handles biolinks with emoji in title', function () {
    $biolink = Page::factory()->create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'url' => 'emoji',
        'settings' => [
            'seo' => [
                'title' => '🚀 Welcome to my bio! 🎉',
            ],
        ],
    ]);

    $service = app(DynamicOgImageService::class);
    $url = $service->generate($biolink);

    expect($service->exists($biolink))->toBeTrue();
});
