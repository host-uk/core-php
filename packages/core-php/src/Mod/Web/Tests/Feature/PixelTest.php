<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Pixel;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
});

it('can create a pixel', function () {
    $pixel = Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'facebook',
        'name' => 'Main FB Pixel',
        'pixel_id' => '1234567890123456',
    ]);

    expect($pixel)->toBeInstanceOf(Pixel::class)
        ->and($pixel->type)->toBe('facebook')
        ->and($pixel->name)->toBe('Main FB Pixel')
        ->and($pixel->pixel_id)->toBe('1234567890123456');
});

it('provides available pixel types', function () {
    $types = Pixel::TYPES;

    expect($types)->toBeArray()
        ->and($types)->toHaveKey('facebook')
        ->and($types)->toHaveKey('google_analytics')
        ->and($types)->toHaveKey('google_tag_manager')
        ->and($types)->toHaveKey('tiktok')
        ->and($types)->toHaveKey('twitter')
        ->and($types)->toHaveKey('pinterest')
        ->and($types)->toHaveKey('linkedin')
        ->and($types)->toHaveKey('snapchat')
        ->and($types)->toHaveKey('bing');
});

it('generates type label attribute', function () {
    $pixel = Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'google_analytics',
        'name' => 'GA Pixel',
        'pixel_id' => 'G-XXXXXXXXXX',
    ]);

    expect($pixel->type_label)->toBe('Google Analytics');
});

it('can be attached to multiple biolinks', function () {
    $pixel = Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'facebook',
        'name' => 'Shared Pixel',
        'pixel_id' => '9999999999',
    ]);

    $biolink1 = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'link1',
    ]);

    $biolink2 = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'link2',
    ]);

    $biolink1->pixels()->attach($pixel->id);
    $biolink2->pixels()->attach($pixel->id);

    expect($pixel->biolinks)->toHaveCount(2)
        ->and($biolink1->pixels)->toHaveCount(1)
        ->and($biolink2->pixels)->toHaveCount(1);
});

it('generates facebook pixel script', function () {
    $pixel = Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'facebook',
        'name' => 'FB Pixel',
        'pixel_id' => '1234567890',
    ]);

    $script = $pixel->getScript();

    expect($script)->toContain('fbq(\'init\', \'1234567890\')')
        ->and($script)->toContain('connect.facebook.net')
        ->and($script)->toContain('fbevents.js');
});

it('generates google analytics script', function () {
    $pixel = Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'google_analytics',
        'name' => 'GA4',
        'pixel_id' => 'G-ABCDEF1234',
    ]);

    $script = $pixel->getScript();

    expect($script)->toContain('googletagmanager.com/gtag/js?id=G-ABCDEF1234')
        ->and($script)->toContain('gtag(\'config\', \'G-ABCDEF1234\')');
});

it('generates google tag manager script and body script', function () {
    $pixel = Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'google_tag_manager',
        'name' => 'GTM',
        'pixel_id' => 'GTM-ABCD123',
    ]);

    $headScript = $pixel->getScript();
    $bodyScript = $pixel->getBodyScript();

    expect($headScript)->toContain('googletagmanager.com/gtm.js')
        ->and($headScript)->toContain('GTM-ABCD123')
        ->and($bodyScript)->toContain('googletagmanager.com/ns.html?id=GTM-ABCD123')
        ->and($bodyScript)->toContain('noscript');
});

it('generates tiktok pixel script', function () {
    $pixel = Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'tiktok',
        'name' => 'TikTok',
        'pixel_id' => 'ABCDEF12345',
    ]);

    $script = $pixel->getScript();

    expect($script)->toContain('analytics.tiktok.com')
        ->and($script)->toContain('ttq.load(\'ABCDEF12345\')');
});

it('scopes by type', function () {
    Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'facebook',
        'name' => 'FB',
        'pixel_id' => '111',
    ]);

    Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'google_analytics',
        'name' => 'GA',
        'pixel_id' => '222',
    ]);

    $fbPixels = Pixel::ofType('facebook')->get();

    expect($fbPixels)->toHaveCount(1)
        ->and($fbPixels->first()->name)->toBe('FB');
});

it('scopes by workspace', function () {
    $otherWorkspace = Workspace::factory()->create();

    Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'facebook',
        'name' => 'My Pixel',
        'pixel_id' => 'aaa',
    ]);

    Pixel::create([
        'workspace_id' => $otherWorkspace->id,
        'user_id' => $this->user->id,
        'type' => 'facebook',
        'name' => 'Other Pixel',
        'pixel_id' => 'bbb',
    ]);

    $myPixels = Pixel::forWorkspace($this->workspace)->get();

    expect($myPixels)->toHaveCount(1)
        ->and($myPixels->first()->name)->toBe('My Pixel');
});

it('supports soft deletes', function () {
    $pixel = Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'facebook',
        'name' => 'To Delete',
        'pixel_id' => 'delete-me',
    ]);

    $pixel->delete();

    expect(Pixel::find($pixel->id))->toBeNull()
        ->and(Pixel::withTrashed()->find($pixel->id))->not->toBeNull();
});

it('escapes pixel id in scripts to prevent xss', function () {
    $pixel = Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'facebook',
        'name' => 'XSS Test',
        'pixel_id' => '<script>alert("xss")</script>',
    ]);

    $script = $pixel->getScript();

    // The e() function should escape special characters
    expect($script)->not->toContain('<script>alert("xss")</script>')
        ->and($script)->toContain('&lt;');
});
