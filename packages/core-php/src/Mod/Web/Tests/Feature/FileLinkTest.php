<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\View\Livewire\Hub\CreateFileLink;
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

describe('File link creation page', function () {
    it('can render the create file link page', function () {
        Livewire::test(CreateFileLink::class)
            ->assertStatus(200)
            ->assertSee('Create File Link')
            ->assertSee('File');
    });

    it('generates a random slug on mount', function () {
        $component = Livewire::test(CreateFileLink::class);

        expect($component->get('url'))
            ->toBeString()
            ->toHaveLength(6);
    });

    it('can regenerate the slug', function () {
        $component = Livewire::test(CreateFileLink::class);
        $originalSlug = $component->get('url');

        $component->call('regenerateSlug');
        $newSlug = $component->get('url');

        expect($newSlug)->not->toBe($originalSlug);
    });

    it('requires a file to be uploaded', function () {
        Livewire::test(CreateFileLink::class)
            ->call('create')
            ->assertHasErrors(['file' => 'required']);
    });

    it('validates file size limit', function () {
        // Create a file larger than the 50MB limit
        $file = UploadedFile::fake()->create('large-file.zip', 60000); // 60MB

        Livewire::test(CreateFileLink::class)
            ->set('file', $file)
            ->call('create')
            ->assertHasErrors(['file']);
    });

    it('validates URL slug format', function () {
        $file = UploadedFile::fake()->create('test.pdf', 100);

        Livewire::test(CreateFileLink::class)
            ->set('file', $file)
            ->set('url', 'invalid slug with spaces')
            ->call('create')
            ->assertHasErrors(['url' => 'regex']);
    });

    it('creates a file link with valid data', function () {
        $file = UploadedFile::fake()->create('test-document.pdf', 100);

        Livewire::test(CreateFileLink::class)
            ->set('url', 'myfile')
            ->set('file', $file)
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('hub.bio.index'));

        $this->assertDatabaseHas('biolinks', [
            'url' => 'myfile',
            'type' => 'file',
            'user_id' => $this->user->id,
        ]);

        $biolink = Page::where('url', 'myfile')->first();
        expect($biolink->getSetting('file_name'))->toBe('test-document.pdf')
            ->and($biolink->getSetting('file_extension'))->toBe('pdf')
            ->and($biolink->getSetting('file_path'))->not->toBeNull();
    });

    it('creates file link with password protection', function () {
        $file = UploadedFile::fake()->create('secret.pdf', 100);

        Livewire::test(CreateFileLink::class)
            ->set('url', 'protected-file')
            ->set('file', $file)
            ->set('passwordEnabled', true)
            ->set('password', 'mysecretpassword')
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'protected-file')->first();
        expect($biolink->getSetting('password_protected'))->toBeTrue()
            ->and($biolink->getSetting('password'))->not->toBeNull();
    });

    it('creates file link with schedule dates', function () {
        $file = UploadedFile::fake()->create('scheduled.pdf', 100);
        $startDate = now()->addDay()->format('Y-m-d\TH:i');
        $endDate = now()->addWeek()->format('Y-m-d\TH:i');

        Livewire::test(CreateFileLink::class)
            ->set('url', 'scheduled-file')
            ->set('file', $file)
            ->set('startDate', $startDate)
            ->set('endDate', $endDate)
            ->call('create')
            ->assertHasNoErrors();

        $biolink = Page::where('url', 'scheduled-file')->first();

        expect($biolink)->not->toBeNull()
            ->and($biolink->start_date)->not->toBeNull()
            ->and($biolink->end_date)->not->toBeNull();
    });

    it('prevents duplicate URLs', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'file',
            'url' => 'existing-file',
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100);

        Livewire::test(CreateFileLink::class)
            ->set('url', 'existing-file')
            ->set('file', $file)
            ->call('create')
            ->assertHasErrors(['url']);
    });

    it('converts URL to lowercase', function () {
        $file = UploadedFile::fake()->create('test.pdf', 100);

        Livewire::test(CreateFileLink::class)
            ->set('url', 'MyUpperCaseFile')
            ->set('file', $file)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('biolinks', [
            'url' => 'myuppercasefile',
            'type' => 'file',
        ]);
    });
});

describe('File link index display', function () {
    it('displays file links in the index', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'file',
            'url' => 'my-file-link',
            'is_enabled' => true,
            'settings' => ['file_name' => 'document.pdf'],
        ]);

        Livewire::test(Index::class)
            ->assertSee('my-file-link')
            ->assertSee('File Link');
    });

    it('can filter by type to show only file links', function () {
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
            'type' => 'file',
            'url' => 'my-file-link',
            'is_enabled' => true,
            'settings' => ['file_name' => 'document.pdf'],
        ]);

        Livewire::test(Index::class)
            ->set('typeFilter', 'file')
            ->assertSee('my-file-link')
            ->assertDontSee('my-bio-page');
    });

    it('can navigate to create file link page', function () {
        Livewire::test(Index::class)
            ->call('createFileLink')
            ->assertRedirect(route('hub.bio.file.create'));
    });
});

describe('File link model behaviour', function () {
    it('identifies itself as a file link', function () {
        $fileLink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'file',
            'url' => 'test-file',
            'settings' => ['file_name' => 'test.pdf'],
        ]);

        expect($fileLink->type)->toBe('file')
            ->and($fileLink->isBioLinkPage())->toBeFalse();
    });

    it('stores file settings correctly', function () {
        $fileLink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'file',
            'url' => 'settings-test',
            'settings' => [
                'file_name' => 'report.pdf',
                'file_size' => 1024000,
                'file_extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'password_protected' => true,
            ],
        ]);

        expect($fileLink->getSetting('file_name'))->toBe('report.pdf')
            ->and($fileLink->getSetting('file_size'))->toBe(1024000)
            ->and($fileLink->getSetting('file_extension'))->toBe('pdf')
            ->and($fileLink->getSetting('password_protected'))->toBeTrue();
    });

    it('can record clicks', function () {
        $fileLink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'file',
            'url' => 'click-test',
            'clicks' => 0,
            'settings' => ['file_name' => 'test.pdf'],
        ]);

        $fileLink->recordClick();
        $fileLink->recordClick();
        $fileLink->recordClick();

        expect($fileLink->fresh()->clicks)->toBe(3);
    });
});
