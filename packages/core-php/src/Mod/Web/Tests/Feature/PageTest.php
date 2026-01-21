<?php

use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Domain;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Pixel;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
});

it('can create a biolink page', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'testuser',
        'settings' => [
            'seo' => [
                'title' => 'Test User',
                'description' => 'My bio page',
            ],
        ],
    ]);

    expect($biolink)->toBeInstanceOf(Page::class)
        ->and($biolink->url)->toBe('testuser')
        ->and($biolink->type)->toBe('biolink')
        ->and($biolink->getSetting('seo.title'))->toBe('Test User');
});

it('can create a short link', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'link',
        'url' => 'mylink',
        'location_url' => 'https://example.com/destination',
    ]);

    expect($biolink->type)->toBe('link')
        ->and($biolink->location_url)->toBe('https://example.com/destination');
});

it('can add blocks to a biolink', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'withblocks',
    ]);

    $block = $biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'settings' => [
            'url' => 'https://example.com',
            'text' => 'Visit Example',
        ],
    ]);

    expect($block)->toBeInstanceOf(Block::class)
        ->and($block->type)->toBe('link')
        ->and($block->getSetting('text'))->toBe('Visit Example');
});

it('can scope to active biolinks', function () {
    Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'active-link',
        'is_enabled' => true,
    ]);

    Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'disabled-link',
        'is_enabled' => false,
    ]);

    $activeLinks = Page::active()->get();

    expect($activeLinks)->toHaveCount(1)
        ->and($activeLinks->first()->url)->toBe('active-link');
});

it('generates full url attribute', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'mypage',
    ]);

    expect($biolink->full_url)->toContain('mypage');
});

it('records clicks correctly', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'clicktest',
        'clicks' => 0,
    ]);

    $biolink->recordClick();
    $biolink->recordClick();

    expect($biolink->fresh()->clicks)->toBe(2);
});

it('identifies biolink pages vs short links', function () {
    $biolinkPage = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'biopage',
    ]);

    $shortLink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'link',
        'url' => 'shortlink',
    ]);

    expect($biolinkPage->isBioLinkPage())->toBeTrue()
        ->and($shortLink->isBioLinkPage())->toBeFalse();
});

it('supports soft deletes', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'to-delete',
    ]);

    $biolink->delete();

    expect(Page::find($biolink->id))->toBeNull()
        ->and(Page::withTrashed()->find($biolink->id))->not->toBeNull();
});

it('can attach pixels via many-to-many', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'with-pixels',
    ]);

    $pixel = Pixel::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'facebook',
        'name' => 'FB Pixel',
        'pixel_id' => '123456789',
    ]);

    $biolink->pixels()->attach($pixel->id);

    expect($biolink->pixels)->toHaveCount(1)
        ->and($biolink->pixels->first()->pixel_id)->toBe('123456789');
});

it('handles domain verification workflow', function () {
    $domain = Domain::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'host' => 'custom.example.com',
    ]);

    expect($domain->verification_status)->toBe('pending')
        ->and($domain->isVerified())->toBeFalse();

    $token = $domain->generateVerificationToken();

    expect($token)->not->toBeEmpty()
        ->and($domain->getDnsVerificationRecord())->toContain($token);

    $domain->markAsVerified();

    expect($domain->isVerified())->toBeTrue()
        ->and($domain->verified_at)->not->toBeNull()
        ->and($domain->is_enabled)->toBeTrue();
});
