<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\View\Livewire\Hub\CreateShortLink;
use Core\Mod\Web\View\Livewire\Hub\Index;
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
    $shortlinksFeature = Feature::create([
        'code' => 'bio.shortlinks',
        'name' => 'Short Links',
        'description' => 'Number of short links allowed',
        'category' => 'biolink',
        'type' => Feature::TYPE_LIMIT,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $pagesFeature = Feature::create([
        'code' => 'bio.pages',
        'name' => 'Bio Pages',
        'description' => 'Number of biolink pages allowed',
        'category' => 'biolink',
        'type' => Feature::TYPE_LIMIT,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 2,
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

    // Attach features to package with generous limits
    $package->features()->attach($shortlinksFeature->id, ['limit_value' => 100]);
    $package->features()->attach($pagesFeature->id, ['limit_value' => 100]);

    // Provision package to workspace
    app(EntitlementService::class)->provisionPackage($this->workspace, 'creator');
});

describe('Short link creation page', function () {
    it('can render the create short link page', function () {
        Livewire::test(CreateShortLink::class)
            ->assertStatus(200)
            ->assertSee('Create Short Link')
            ->assertSee('Destination URL');
    });

    it('generates a random slug on mount', function () {
        $component = Livewire::test(CreateShortLink::class);

        expect($component->get('url'))
            ->toBeString()
            ->toHaveLength(6);
    });

    it('can regenerate the slug', function () {
        $component = Livewire::test(CreateShortLink::class);
        $originalSlug = $component->get('url');

        $component->call('regenerateSlug');
        $newSlug = $component->get('url');

        expect($newSlug)->not->toBe($originalSlug);
    });

    it('requires a destination URL', function () {
        Livewire::test(CreateShortLink::class)
            ->set('destinationUrl', '')
            ->call('create')
            ->assertHasErrors(['destinationUrl' => 'required']);
    });

    it('validates destination URL format', function () {
        Livewire::test(CreateShortLink::class)
            ->set('destinationUrl', 'not-a-valid-url')
            ->call('create')
            ->assertHasErrors(['destinationUrl' => 'url']);
    });

    it('validates URL slug format', function () {
        Livewire::test(CreateShortLink::class)
            ->set('url', 'invalid slug with spaces')
            ->set('destinationUrl', 'https://example.com')
            ->call('create')
            ->assertHasErrors(['url' => 'regex']);
    });

    it('validates URL slug minimum length', function () {
        Livewire::test(CreateShortLink::class)
            ->set('url', 'ab')
            ->set('destinationUrl', 'https://example.com')
            ->call('create')
            ->assertHasErrors(['url' => 'min']);
    });

    it('creates a short link with valid data', function () {
        Livewire::test(CreateShortLink::class)
            ->set('url', 'mylink')
            ->set('destinationUrl', 'https://example.com/some/long/path')
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('hub.bio.index'));

        $this->assertDatabaseHas('biolinks', [
            'url' => 'mylink',
            'type' => 'link',
            'location_url' => 'https://example.com/some/long/path',
            'user_id' => $this->user->id,
        ]);
    });

    it('creates short link with schedule dates', function () {
        $startDate = now()->addDay()->format('Y-m-d\TH:i');
        $endDate = now()->addWeek()->format('Y-m-d\TH:i');

        Livewire::test(CreateShortLink::class)
            ->set('url', 'scheduled-link')
            ->set('destinationUrl', 'https://example.com')
            ->set('showAdvanced', true)
            ->set('startDate', $startDate)
            ->set('endDate', $endDate)
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'scheduled-link')->first();

        expect($biolink)->not->toBeNull()
            ->and($biolink->start_date)->not->toBeNull()
            ->and($biolink->end_date)->not->toBeNull();
    });

    it('prevents duplicate URLs', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'existing-link',
            'location_url' => 'https://existing.com',
        ]);

        Livewire::test(CreateShortLink::class)
            ->set('url', 'existing-link')
            ->set('destinationUrl', 'https://example.com')
            ->call('create')
            ->assertHasErrors(['url']);
    });

    it('converts URL to lowercase', function () {
        Livewire::test(CreateShortLink::class)
            ->set('url', 'MyUpperCaseLink')
            ->set('destinationUrl', 'https://example.com')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biolinks', [
            'url' => 'myuppercaselink',
            'type' => 'link',
        ]);
    });

    it('can disable the link on creation', function () {
        Livewire::test(CreateShortLink::class)
            ->set('url', 'disabled-link')
            ->set('destinationUrl', 'https://example.com')
            ->set('showAdvanced', true)
            ->set('isEnabled', false)
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'disabled-link')->first();
        expect($biolink->is_enabled)->toBeFalse();
    });
});

describe('Short link redirection', function () {
    it('has location_url set for redirect', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'redirect-test',
            'location_url' => 'https://destination.example.com/page',
            'is_enabled' => true,
        ]);

        // Verify the short link is properly configured for redirection
        expect($biolink->isShortLink())->toBeTrue()
            ->and($biolink->location_url)->toBe('https://destination.example.com/page');

        // Note: Full redirect testing requires hitting the biolink domain (bio.host.uk.com)
        // which is handled by the PublicBioPageController and domain middleware.
        // The controller logic (lines 98-107) handles the redirect:
        // if ($biolink->isShortLink() && $biolink->location_url) -> 302 redirect
    });

    it('respects active scope for scheduling', function () {
        // Future start date - should not be active
        $futureLink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'future-link',
            'location_url' => 'https://example.com',
            'is_enabled' => true,
            'start_date' => now()->addWeek(),
        ]);

        // Past end date - should not be active
        $expiredLink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'expired-link',
            'location_url' => 'https://example.com',
            'is_enabled' => true,
            'end_date' => now()->subDay(),
        ]);

        // Currently active
        $activeLink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'active-link',
            'location_url' => 'https://example.com',
            'is_enabled' => true,
        ]);

        $activeLinks = Page::active()->pluck('url')->toArray();

        expect($activeLinks)
            ->toContain('active-link')
            ->not->toContain('future-link')
            ->not->toContain('expired-link');
    });
});

describe('Short link index display', function () {
    it('displays short links in the index', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'my-short-link',
            'location_url' => 'https://example.com',
            'is_enabled' => true,
        ]);

        Livewire::test(Index::class)
            ->assertSee('my-short-link')
            ->assertSee('Short Link');
    });

    it('can filter by type to show only short links', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'my-bio-page',
            'is_enabled' => true,
        ]);

        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'my-short-link',
            'location_url' => 'https://example.com',
            'is_enabled' => true,
        ]);

        Livewire::test(Index::class)
            ->set('typeFilter', 'link')
            ->assertSee('my-short-link')
            ->assertDontSee('my-bio-page');
    });

    it('can navigate to create short link page', function () {
        Livewire::test(Index::class)
            ->call('createShortLink')
            ->assertRedirect(route('hub.bio.shortlink.create'));
    });
});

describe('Short link model behaviour', function () {
    it('identifies itself as a short link', function () {
        $shortLink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'test-link',
            'location_url' => 'https://example.com',
        ]);

        expect($shortLink->isShortLink())->toBeTrue()
            ->and($shortLink->isBioLinkPage())->toBeFalse();
    });

    it('generates correct full URL', function () {
        $shortLink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'my-link',
            'location_url' => 'https://example.com',
        ]);

        expect($shortLink->full_url)->toContain('my-link');
    });

    it('can record clicks', function () {
        $shortLink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'click-test',
            'location_url' => 'https://example.com',
            'clicks' => 0,
        ]);

        $shortLink->recordClick();
        $shortLink->recordClick();
        $shortLink->recordClick();

        expect($shortLink->fresh()->clicks)->toBe(3);
    });
});
