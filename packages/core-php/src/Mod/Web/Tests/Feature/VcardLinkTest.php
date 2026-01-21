<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\View\Livewire\Hub\CreateVcard;
use Core\Mod\Web\View\Livewire\Hub\Index;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;
use Core\Mod\Tenant\Models\Feature;
use Core\Mod\Tenant\Models\Package;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
    Storage::fake('local');

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

describe('vCard creation page', function () {
    it('can render the create vcard page', function () {
        Livewire::test(CreateVcard::class)
            ->assertStatus(200)
            ->assertSee('Create vCard')
            ->assertSee('Contact information');
    });

    it('generates a random slug on mount', function () {
        $component = Livewire::test(CreateVcard::class);

        expect($component->get('url'))
            ->toBeString()
            ->toHaveLength(6);
    });

    it('can regenerate the slug', function () {
        $component = Livewire::test(CreateVcard::class);
        $originalSlug = $component->get('url');

        $component->call('regenerateSlug');
        $newSlug = $component->get('url');

        expect($newSlug)->not->toBe($originalSlug);
    });

    it('requires first name', function () {
        Livewire::test(CreateVcard::class)
            ->set('firstName', '')
            ->call('create')
            ->assertHasErrors(['firstName' => 'required']);
    });

    it('validates URL slug format', function () {
        Livewire::test(CreateVcard::class)
            ->set('firstName', 'John')
            ->set('url', 'invalid slug with spaces')
            ->call('create')
            ->assertHasErrors(['url' => 'regex']);
    });

    it('validates email format', function () {
        Livewire::test(CreateVcard::class)
            ->set('firstName', 'John')
            ->set('email', 'not-an-email')
            ->call('create')
            ->assertHasErrors(['email' => 'email']);
    });

    it('validates website URL format', function () {
        Livewire::test(CreateVcard::class)
            ->set('firstName', 'John')
            ->set('website', 'not-a-url')
            ->call('create')
            ->assertHasErrors(['website' => 'url']);
    });

    it('creates a vcard with minimal data', function () {
        Livewire::test(CreateVcard::class)
            ->set('url', 'john-doe')
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('hub.bio.index'));

        $this->assertDatabaseHas('biolinks', [
            'url' => 'john-doe',
            'type' => 'vcard',
            'user_id' => $this->user->id,
        ]);

        $biolink = Page::where('url', 'john-doe')->first();
        expect($biolink->getSetting('first_name'))->toBe('John');
    });

    it('creates a vcard with full contact details', function () {
        Livewire::test(CreateVcard::class)
            ->set('url', 'jane-smith')
            ->set('firstName', 'Jane')
            ->set('lastName', 'Smith')
            ->set('email', 'jane@example.com')
            ->set('phone', '+44 7700 900000')
            ->set('phoneWork', '+44 20 7946 0958')
            ->set('company', 'Acme Corp')
            ->set('jobTitle', 'Software Engineer')
            ->set('website', 'https://janesmith.dev')
            ->set('notes', 'Met at conference 2024')
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'jane-smith')->first();

        expect($biolink->getSetting('first_name'))->toBe('Jane')
            ->and($biolink->getSetting('last_name'))->toBe('Smith')
            ->and($biolink->getSetting('email'))->toBe('jane@example.com')
            ->and($biolink->getSetting('phone'))->toBe('+44 7700 900000')
            ->and($biolink->getSetting('phone_work'))->toBe('+44 20 7946 0958')
            ->and($biolink->getSetting('company'))->toBe('Acme Corp')
            ->and($biolink->getSetting('job_title'))->toBe('Software Engineer')
            ->and($biolink->getSetting('website'))->toBe('https://janesmith.dev')
            ->and($biolink->getSetting('notes'))->toBe('Met at conference 2024');
    });

    it('creates a vcard with address', function () {
        Livewire::test(CreateVcard::class)
            ->set('url', 'with-address')
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('addressStreet', '123 High Street')
            ->set('addressCity', 'London')
            ->set('addressRegion', 'Greater London')
            ->set('addressPostcode', 'SW1A 1AA')
            ->set('addressCountry', 'United Kingdom')
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'with-address')->first();
        $address = $biolink->getSetting('address');

        expect($address)->toBeArray()
            ->and($address['street'])->toBe('123 High Street')
            ->and($address['city'])->toBe('London')
            ->and($address['region'])->toBe('Greater London')
            ->and($address['postcode'])->toBe('SW1A 1AA')
            ->and($address['country'])->toBe('United Kingdom');
    });

    it('creates a vcard with social links', function () {
        Livewire::test(CreateVcard::class)
            ->set('url', 'with-socials')
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('linkedin', 'https://linkedin.com/in/johndoe')
            ->set('twitter', '@johndoe')
            ->set('facebook', 'https://facebook.com/johndoe')
            ->set('instagram', '@johndoe')
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'with-socials')->first();
        $social = $biolink->getSetting('social');

        expect($social)->toBeArray()
            ->and($social['linkedin'])->toBe('https://linkedin.com/in/johndoe')
            ->and($social['twitter'])->toBe('@johndoe')
            ->and($social['facebook'])->toBe('https://facebook.com/johndoe')
            ->and($social['instagram'])->toBe('@johndoe');
    });

    it('creates a vcard with photo', function () {
        $photo = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        Livewire::test(CreateVcard::class)
            ->set('url', 'with-photo')
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('photo', $photo)
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'with-photo')->first();
        expect($biolink->getSetting('photo_path'))->not->toBeNull();
    });

    it('validates photo file type', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        Livewire::test(CreateVcard::class)
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->set('photo', $file)
            ->call('create')
            ->assertHasErrors(['photo']);
    });

    it('prevents duplicate URLs', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'vcard',
            'url' => 'existing-vcard',
        ]);

        Livewire::test(CreateVcard::class)
            ->set('url', 'existing-vcard')
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->call('create')
            ->assertHasErrors(['url']);
    });

    it('converts URL to lowercase', function () {
        Livewire::test(CreateVcard::class)
            ->set('url', 'JohnDoe')
            ->set('firstName', 'John')
            ->set('lastName', 'Doe')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biolinks', [
            'url' => 'johndoe',
            'type' => 'vcard',
        ]);
    });
});

describe('vCard index display', function () {
    it('displays vcards in the index', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'vcard',
            'url' => 'my-vcard',
            'is_enabled' => true,
            'settings' => ['first_name' => 'John', 'last_name' => 'Doe'],
        ]);

        Livewire::test(Index::class)
            ->assertSee('my-vcard')
            ->assertSee('vCard');
    });

    it('can filter by type to show only vcards', function () {
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
            'type' => 'vcard',
            'url' => 'my-vcard',
            'is_enabled' => true,
            'settings' => ['first_name' => 'John'],
        ]);

        Livewire::test(Index::class)
            ->set('typeFilter', 'vcard')
            ->assertSee('my-vcard')
            ->assertDontSee('my-bio-page');
    });

    it('can navigate to create vcard page', function () {
        Livewire::test(Index::class)
            ->call('createVcard')
            ->assertRedirect(route('hub.bio.vcard.create'));
    });
});

describe('vCard model behaviour', function () {
    it('identifies itself as a vcard', function () {
        $vcard = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'vcard',
            'url' => 'test-vcard',
            'settings' => ['first_name' => 'John'],
        ]);

        expect($vcard->type)->toBe('vcard')
            ->and($vcard->isBioLinkPage())->toBeFalse();
    });

    it('stores contact settings correctly', function () {
        $vcard = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'vcard',
            'url' => 'settings-test',
            'settings' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '+44 7700 900000',
                'company' => 'Acme Corp',
                'job_title' => 'Developer',
                'address' => [
                    'street' => '123 Main St',
                    'city' => 'London',
                ],
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/johndoe',
                ],
            ],
        ]);

        expect($vcard->getSetting('first_name'))->toBe('John')
            ->and($vcard->getSetting('last_name'))->toBe('Doe')
            ->and($vcard->getSetting('email'))->toBe('john@example.com')
            ->and($vcard->getSetting('company'))->toBe('Acme Corp')
            ->and($vcard->getSetting('address.street'))->toBe('123 Main St')
            ->and($vcard->getSetting('social.linkedin'))->toBe('https://linkedin.com/in/johndoe');
    });

    it('can record clicks', function () {
        $vcard = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'vcard',
            'url' => 'click-test',
            'clicks' => 0,
            'settings' => ['first_name' => 'John'],
        ]);

        $vcard->recordClick();
        $vcard->recordClick();
        $vcard->recordClick();

        expect($vcard->fresh()->clicks)->toBe(3);
    });
});
