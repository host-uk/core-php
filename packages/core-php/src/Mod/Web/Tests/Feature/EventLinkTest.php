<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\View\Livewire\Hub\CreateEvent;
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

    $package->features()->attach($shortlinksFeature->id, ['limit_value' => 100]);
    $package->features()->attach($pagesFeature->id, ['limit_value' => 100]);

    app(EntitlementService::class)->provisionPackage($this->workspace, 'creator');
});

describe('Event creation page', function () {
    it('can render the create event page', function () {
        Livewire::test(CreateEvent::class)
            ->assertStatus(200)
            ->assertSee('Create Event Page')
            ->assertSee('Event details');
    });

    it('generates a random slug on mount', function () {
        $component = Livewire::test(CreateEvent::class);

        expect($component->get('url'))
            ->toBeString()
            ->toHaveLength(6);
    });

    it('can regenerate the slug', function () {
        $component = Livewire::test(CreateEvent::class);
        $originalSlug = $component->get('url');

        $component->call('regenerateSlug');
        $newSlug = $component->get('url');

        expect($newSlug)->not->toBe($originalSlug);
    });

    it('requires event name', function () {
        Livewire::test(CreateEvent::class)
            ->set('eventName', '')
            ->call('create')
            ->assertHasErrors(['eventName' => 'required']);
    });

    it('requires start date', function () {
        Livewire::test(CreateEvent::class)
            ->set('eventName', 'My Event')
            ->set('startDate', '')
            ->call('create')
            ->assertHasErrors(['startDate' => 'required']);
    });

    it('requires end date', function () {
        Livewire::test(CreateEvent::class)
            ->set('eventName', 'My Event')
            ->set('startDate', now()->addDay()->format('Y-m-d'))
            ->set('endDate', '')
            ->call('create')
            ->assertHasErrors(['endDate' => 'required']);
    });

    it('validates end date is after start date', function () {
        $startDate = now()->addWeek()->format('Y-m-d');
        $endDate = now()->addDay()->format('Y-m-d'); // Before start date

        Livewire::test(CreateEvent::class)
            ->set('eventName', 'My Event')
            ->set('startDate', $startDate)
            ->set('endDate', $endDate)
            ->call('create')
            ->assertHasErrors(['endDate' => 'after_or_equal']);
    });

    it('validates URL slug format', function () {
        Livewire::test(CreateEvent::class)
            ->set('eventName', 'My Event')
            ->set('startDate', now()->addDay()->format('Y-m-d'))
            ->set('endDate', now()->addDay()->format('Y-m-d'))
            ->set('url', 'invalid slug with spaces')
            ->call('create')
            ->assertHasErrors(['url' => 'regex']);
    });

    it('creates an event with minimal data', function () {
        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addDay()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('url', 'my-event')
            ->set('eventName', 'Annual Conference')
            ->set('startDate', $startDate)
            ->set('endDate', $endDate)
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('hub.bio.index'));

        $this->assertDatabaseHas('biolinks', [
            'url' => 'my-event',
            'type' => 'event',
            'user_id' => $this->user->id,
        ]);

        $biolink = Page::where('url', 'my-event')->first();
        expect($biolink->getSetting('event_name'))->toBe('Annual Conference');
    });

    it('creates an event with full details', function () {
        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addWeek()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('url', 'full-event')
            ->set('eventName', 'Tech Summit 2024')
            ->set('description', 'Join us for the biggest tech event of the year.')
            ->set('startDate', $startDate)
            ->set('startTime', '09:00')
            ->set('endDate', $endDate)
            ->set('endTime', '17:00')
            ->set('timezone', 'Europe/London')
            ->set('allDay', false)
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'full-event')->first();

        expect($biolink->getSetting('event_name'))->toBe('Tech Summit 2024')
            ->and($biolink->getSetting('description'))->toBe('Join us for the biggest tech event of the year.')
            ->and($biolink->getSetting('timezone'))->toBe('Europe/London')
            ->and($biolink->getSetting('all_day'))->toBeFalse();
    });

    it('creates an all-day event', function () {
        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addDay()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('url', 'allday-event')
            ->set('eventName', 'Company Holiday')
            ->set('startDate', $startDate)
            ->set('endDate', $endDate)
            ->set('allDay', true)
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'allday-event')->first();
        expect($biolink->getSetting('all_day'))->toBeTrue();
    });

    it('creates an event with physical location', function () {
        $startDate = now()->addDay()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('url', 'venue-event')
            ->set('eventName', 'Meetup')
            ->set('startDate', $startDate)
            ->set('endDate', $startDate)
            ->set('locationType', 'physical')
            ->set('locationName', 'The Grand Hall')
            ->set('locationAddress', '123 Main Street, London, SW1A 1AA')
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'venue-event')->first();
        $location = $biolink->getSetting('location');

        expect($location)->toBeArray()
            ->and($location['name'])->toBe('The Grand Hall')
            ->and($location['address'])->toBe('123 Main Street, London, SW1A 1AA');
    });

    it('creates an event with online location', function () {
        $startDate = now()->addDay()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('url', 'online-event')
            ->set('eventName', 'Webinar')
            ->set('startDate', $startDate)
            ->set('endDate', $startDate)
            ->set('locationType', 'online')
            ->set('onlineUrl', 'https://zoom.us/j/123456789')
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'online-event')->first();
        $location = $biolink->getSetting('location');

        expect($location)->toBeArray()
            ->and($location['online_url'])->toBe('https://zoom.us/j/123456789');
    });

    it('creates a hybrid event with both locations', function () {
        $startDate = now()->addDay()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('url', 'hybrid-event')
            ->set('eventName', 'Hybrid Conference')
            ->set('startDate', $startDate)
            ->set('endDate', $startDate)
            ->set('locationType', 'hybrid')
            ->set('locationName', 'Conference Centre')
            ->set('locationAddress', '456 Business Park')
            ->set('onlineUrl', 'https://teams.microsoft.com/meet/123')
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'hybrid-event')->first();
        $location = $biolink->getSetting('location');

        expect($location['name'])->toBe('Conference Centre')
            ->and($location['address'])->toBe('456 Business Park')
            ->and($location['online_url'])->toBe('https://teams.microsoft.com/meet/123');
    });

    it('creates an event with organiser details', function () {
        $startDate = now()->addDay()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('url', 'organised-event')
            ->set('eventName', 'Networking Event')
            ->set('startDate', $startDate)
            ->set('endDate', $startDate)
            ->set('organiserName', 'Jane Smith')
            ->set('organiserEmail', 'jane@example.com')
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'organised-event')->first();
        $organiser = $biolink->getSetting('organiser');

        expect($organiser)->toBeArray()
            ->and($organiser['name'])->toBe('Jane Smith')
            ->and($organiser['email'])->toBe('jane@example.com');
    });

    it('validates organiser email format', function () {
        $startDate = now()->addDay()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('eventName', 'My Event')
            ->set('startDate', $startDate)
            ->set('endDate', $startDate)
            ->set('organiserEmail', 'not-an-email')
            ->call('create')
            ->assertHasErrors(['organiserEmail' => 'email']);
    });

    it('validates online URL format', function () {
        $startDate = now()->addDay()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('eventName', 'My Event')
            ->set('startDate', $startDate)
            ->set('endDate', $startDate)
            ->set('locationType', 'online')
            ->set('onlineUrl', 'not-a-url')
            ->call('create')
            ->assertHasErrors(['onlineUrl' => 'url']);
    });

    it('prevents duplicate URLs', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'event',
            'url' => 'existing-event',
        ]);

        $startDate = now()->addDay()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('url', 'existing-event')
            ->set('eventName', 'New Event')
            ->set('startDate', $startDate)
            ->set('endDate', $startDate)
            ->call('create')
            ->assertHasErrors(['url']);
    });

    it('converts URL to lowercase', function () {
        $startDate = now()->addDay()->format('Y-m-d');

        Livewire::test(CreateEvent::class)
            ->set('url', 'MyEvent2024')
            ->set('eventName', 'My Event')
            ->set('startDate', $startDate)
            ->set('endDate', $startDate)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biolinks', [
            'url' => 'myevent2024',
            'type' => 'event',
        ]);
    });
});

describe('Event index display', function () {
    it('displays events in the index', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'event',
            'url' => 'my-event',
            'is_enabled' => true,
            'settings' => ['event_name' => 'Annual Conference'],
        ]);

        Livewire::test(Index::class)
            ->assertSee('my-event')
            ->assertSee('Event');
    });

    it('can filter by type to show only events', function () {
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
            'type' => 'event',
            'url' => 'my-event',
            'is_enabled' => true,
            'settings' => ['event_name' => 'Annual Conference'],
        ]);

        Livewire::test(Index::class)
            ->set('typeFilter', 'event')
            ->assertSee('my-event')
            ->assertDontSee('my-bio-page');
    });

    it('can navigate to create event page', function () {
        Livewire::test(Index::class)
            ->call('createEvent')
            ->assertRedirect(route('hub.bio.event.create'));
    });
});

describe('Event model behaviour', function () {
    it('identifies itself as an event', function () {
        $event = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'event',
            'url' => 'test-event',
            'settings' => ['event_name' => 'Test Event'],
        ]);

        expect($event->type)->toBe('event')
            ->and($event->isBioLinkPage())->toBeFalse();
    });

    it('stores event settings correctly', function () {
        $event = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'event',
            'url' => 'settings-test',
            'settings' => [
                'event_name' => 'Tech Summit',
                'description' => 'Annual technology conference',
                'start_datetime' => '2024-06-15T09:00:00',
                'end_datetime' => '2024-06-15T17:00:00',
                'timezone' => 'Europe/London',
                'all_day' => false,
                'location' => [
                    'name' => 'ExCeL London',
                    'address' => 'Royal Victoria Dock',
                    'online_url' => 'https://zoom.us/j/123',
                ],
                'organiser' => [
                    'name' => 'Tech Events Ltd',
                    'email' => 'info@techevents.com',
                ],
            ],
        ]);

        expect($event->getSetting('event_name'))->toBe('Tech Summit')
            ->and($event->getSetting('description'))->toBe('Annual technology conference')
            ->and($event->getSetting('timezone'))->toBe('Europe/London')
            ->and($event->getSetting('all_day'))->toBeFalse()
            ->and($event->getSetting('location.name'))->toBe('ExCeL London')
            ->and($event->getSetting('organiser.email'))->toBe('info@techevents.com');
    });

    it('can record clicks', function () {
        $event = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'event',
            'url' => 'click-test',
            'clicks' => 0,
            'settings' => ['event_name' => 'Test Event'],
        ]);

        $event->recordClick();
        $event->recordClick();
        $event->recordClick();

        expect($event->fresh()->clicks)->toBe(3);
    });
});
