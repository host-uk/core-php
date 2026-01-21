<?php

use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
});

it('includes biolinks with no schedule in active scope', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'no-schedule',
        'is_enabled' => true,
        'start_date' => null,
        'end_date' => null,
    ]);

    $activeLinks = Page::active()->get();

    expect($activeLinks)->toHaveCount(1)
        ->and($activeLinks->first()->id)->toBe($biolink->id);
});

it('excludes biolinks before start date', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'future-start',
        'is_enabled' => true,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => null,
    ]);

    $activeLinks = Page::active()->get();

    expect($activeLinks)->toHaveCount(0);
});

it('includes biolinks after start date', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'past-start',
        'is_enabled' => true,
        'start_date' => Carbon::now()->subDays(1),
        'end_date' => null,
    ]);

    $activeLinks = Page::active()->get();

    expect($activeLinks)->toHaveCount(1);
});

it('excludes biolinks after end date', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'past-end',
        'is_enabled' => true,
        'start_date' => Carbon::now()->subDays(5),
        'end_date' => Carbon::now()->subDays(1),
    ]);

    $activeLinks = Page::active()->get();

    expect($activeLinks)->toHaveCount(0);
});

it('includes biolinks within schedule window', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'in-window',
        'is_enabled' => true,
        'start_date' => Carbon::now()->subDays(1),
        'end_date' => Carbon::now()->addDays(1),
    ]);

    $activeLinks = Page::active()->get();

    expect($activeLinks)->toHaveCount(1);
});

it('excludes disabled biolinks even within schedule', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'disabled-scheduled',
        'is_enabled' => false,
        'start_date' => Carbon::now()->subDays(1),
        'end_date' => Carbon::now()->addDays(1),
    ]);

    $activeLinks = Page::active()->get();

    expect($activeLinks)->toHaveCount(0);
});

it('applies scheduling to blocks', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'with-blocks',
        'is_enabled' => true,
    ]);

    // Active block - no schedule
    Block::create([
        'workspace_id' => $this->workspace->id,
        'biolink_id' => $biolink->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'settings' => ['text' => 'Always visible'],
    ]);

    // Scheduled block - future
    Block::create([
        'workspace_id' => $this->workspace->id,
        'biolink_id' => $biolink->id,
        'type' => 'link',
        'order' => 2,
        'is_enabled' => true,
        'start_date' => Carbon::now()->addDays(1),
        'settings' => ['text' => 'Future block'],
    ]);

    // Scheduled block - past
    Block::create([
        'workspace_id' => $this->workspace->id,
        'biolink_id' => $biolink->id,
        'type' => 'link',
        'order' => 3,
        'is_enabled' => true,
        'end_date' => Carbon::now()->subDays(1),
        'settings' => ['text' => 'Expired block'],
    ]);

    $activeBlocks = $biolink->blocks()->active()->get();

    expect($activeBlocks)->toHaveCount(1)
        ->and($activeBlocks->first()->getSetting('text'))->toBe('Always visible');
});

it('includes blocks within schedule window', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'block-window',
        'is_enabled' => true,
    ]);

    Block::create([
        'workspace_id' => $this->workspace->id,
        'biolink_id' => $biolink->id,
        'type' => 'heading',
        'order' => 1,
        'is_enabled' => true,
        'start_date' => Carbon::now()->subHours(1),
        'end_date' => Carbon::now()->addHours(1),
        'settings' => ['text' => 'Timed promo'],
    ]);

    $activeBlocks = $biolink->blocks()->active()->get();

    expect($activeBlocks)->toHaveCount(1);
});
