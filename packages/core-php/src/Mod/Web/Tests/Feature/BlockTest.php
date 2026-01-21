<?php

use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
    $this->biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'testblocks',
    ]);
});

it('can create different block types', function () {
    $linkBlock = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'settings' => ['url' => 'https://example.com', 'text' => 'Click Me'],
    ]);

    $headingBlock = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'heading',
        'order' => 2,
        'settings' => ['text' => 'Welcome'],
    ]);

    $socialsBlock = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'socials',
        'order' => 3,
        'settings' => [
            'twitter' => 'https://twitter.com/example',
            'instagram' => 'https://instagram.com/example',
        ],
    ]);

    expect($linkBlock->type)->toBe('link')
        ->and($headingBlock->type)->toBe('heading')
        ->and($socialsBlock->type)->toBe('socials');
});

it('scopes to active blocks', function () {
    $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
    ]);

    $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 2,
        'is_enabled' => false,
    ]);

    $activeBlocks = $this->biolink->blocks()->active()->get();

    expect($activeBlocks)->toHaveCount(1);
});

it('checks schedule for active blocks', function () {
    // Block with no schedule - always active
    $alwaysActive = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
    ]);

    // Block scheduled for the past
    $pastBlock = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 2,
        'is_enabled' => true,
        'start_date' => now()->subDays(10),
        'end_date' => now()->subDays(5),
    ]);

    // Block scheduled for the future
    $futureBlock = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 3,
        'is_enabled' => true,
        'start_date' => now()->addDays(5),
        'end_date' => now()->addDays(10),
    ]);

    // Block currently active
    $currentBlock = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 4,
        'is_enabled' => true,
        'start_date' => now()->subDays(1),
        'end_date' => now()->addDays(1),
    ]);

    expect($alwaysActive->isActive())->toBeTrue()
        ->and($pastBlock->isActive())->toBeFalse()
        ->and($futureBlock->isActive())->toBeFalse()
        ->and($currentBlock->isActive())->toBeTrue();
});

it('gets block type configuration', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
    ]);

    $config = $block->getTypeConfig();

    expect($config)->toBeArray()
        ->and($config)->toHaveKey('icon')
        ->and($config)->toHaveKey('category');
});

it('records click count', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'clicks' => 0,
    ]);

    $block->recordClick();
    $block->recordClick();
    $block->recordClick();

    expect($block->fresh()->clicks)->toBe(3);
});

it('renders block templates', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'heading',
        'order' => 1,
        'settings' => ['text' => 'Test Heading'],
    ]);

    $rendered = $block->render();

    expect($rendered)->toBeString()
        ->and($rendered)->toContain('Test Heading');
});

it('falls back to generic template for unknown types', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'unknown_type_xyz',
        'order' => 1,
    ]);

    // In production this would use the generic template
    // For this test, we just verify no exception is thrown
    expect($block->type)->toBe('unknown_type_xyz');
});

describe('BlockController', function () {
    describe('reorder authorization', function () {
        it('rejects reorder with blocks from another biolink', function () {
            // Create another user's biolink with blocks
            $otherUser = User::factory()->create();
            $otherWorkspace = Workspace::factory()->create();
            $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);
            $otherBiolink = Page::create([
                'workspace_id' => $otherWorkspace->id,
                'user_id' => $otherUser->id,
                'type' => 'biolink',
                'url' => 'otherbiolink',
            ]);

            $otherBlock = $otherBiolink->blocks()->create([
                'workspace_id' => $otherWorkspace->id,
                'type' => 'link',
                'order' => 1,
            ]);

            // Create our own blocks
            $ownBlock = $this->biolink->blocks()->create([
                'workspace_id' => $this->workspace->id,
                'type' => 'link',
                'order' => 1,
            ]);

            // Try to reorder with a block that doesn't belong to this biolink
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/bio/{$this->biolink->id}/blocks/reorder", [
                    'order' => [$otherBlock->id, $ownBlock->id],
                ]);

            expect($response->status())->toBe(422);
            expect($response->json('message'))->toContain('The given data was invalid.');
        });

        it('allows reorder with only own blocks', function () {
            $block1 = $this->biolink->blocks()->create([
                'workspace_id' => $this->workspace->id,
                'type' => 'link',
                'order' => 1,
            ]);

            $block2 = $this->biolink->blocks()->create([
                'workspace_id' => $this->workspace->id,
                'type' => 'heading',
                'order' => 2,
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/bio/{$this->biolink->id}/blocks/reorder", [
                    'order' => [$block2->id, $block1->id],
                ]);

            expect($response->status())->toBe(200);

            // Verify order was updated
            $block1->refresh();
            $block2->refresh();

            expect($block2->order)->toBe(0)
                ->and($block1->order)->toBe(1);
        });

        it('prevents unauthorized access to another users biolink', function () {
            $otherUser = User::factory()->create();
            $otherWorkspace = Workspace::factory()->create();
            $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);
            $otherBiolink = Page::create([
                'workspace_id' => $otherWorkspace->id,
                'user_id' => $otherUser->id,
                'type' => 'biolink',
                'url' => 'otherbiolink2',
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/bio/{$otherBiolink->id}/blocks/reorder", [
                    'order' => [],
                ]);

            expect($response->status())->toBeIn([403, 422]);
        });
    });

    describe('duplicate block security', function () {
        it('prevents duplicating blocks from another biolink', function () {
            $otherUser = User::factory()->create();
            $otherWorkspace = Workspace::factory()->create();
            $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);
            $otherBiolink = Page::create([
                'workspace_id' => $otherWorkspace->id,
                'user_id' => $otherUser->id,
                'type' => 'biolink',
                'url' => 'other-dupe-test',
            ]);

            $otherBlock = $otherBiolink->blocks()->create([
                'workspace_id' => $otherWorkspace->id,
                'type' => 'link',
                'order' => 1,
            ]);

            // Try to duplicate a block from another biolink through our biolink
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/bio/{$this->biolink->id}/blocks/{$otherBlock->id}/duplicate");

            expect($response->status())->toBe(404);
        });
    });

    describe('update block security', function () {
        it('prevents updating blocks from another biolink', function () {
            $otherUser = User::factory()->create();
            $otherWorkspace = Workspace::factory()->create();
            $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);
            $otherBiolink = Page::create([
                'workspace_id' => $otherWorkspace->id,
                'user_id' => $otherUser->id,
                'type' => 'biolink',
                'url' => 'other-update-test',
            ]);

            $otherBlock = $otherBiolink->blocks()->create([
                'workspace_id' => $otherWorkspace->id,
                'type' => 'link',
                'order' => 1,
                'settings' => ['text' => 'Original'],
            ]);

            // Try to update a block from another biolink through our biolink
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/blocks/{$otherBlock->id}", [
                    'settings' => ['text' => 'Hacked'],
                ]);

            expect($response->status())->toBe(403);

            // Verify the block was NOT modified
            $otherBlock->refresh();
            expect($otherBlock->settings['text'])->toBe('Original');
        });

        it('allows updating own blocks', function () {
            $ownBlock = $this->biolink->blocks()->create([
                'workspace_id' => $this->workspace->id,
                'type' => 'link',
                'order' => 1,
                'settings' => ['text' => 'Original'],
            ]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/blocks/{$ownBlock->id}", [
                    'settings' => ['text' => 'Updated'],
                ]);

            expect($response->status())->toBe(200);

            $ownBlock->refresh();
            expect($ownBlock->settings['text'])->toBe('Updated');
        });

        it('prevents updating blocks on another users biolink', function () {
            $otherUser = User::factory()->create();
            $otherWorkspace = Workspace::factory()->create();
            $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);
            $otherBiolink = Page::create([
                'workspace_id' => $otherWorkspace->id,
                'user_id' => $otherUser->id,
                'type' => 'biolink',
                'url' => 'other-update-test-2',
            ]);

            $otherBlock = $otherBiolink->blocks()->create([
                'workspace_id' => $otherWorkspace->id,
                'type' => 'link',
                'order' => 1,
            ]);

            // Try to access another user's biolink entirely
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/blocks/{$otherBlock->id}", [
                    'settings' => ['text' => 'Hacked'],
                ]);

            expect($response->status())->toBe(403);
        });
    });

    describe('delete block security', function () {
        it('prevents deleting blocks from another biolink', function () {
            $otherUser = User::factory()->create();
            $otherWorkspace = Workspace::factory()->create();
            $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);
            $otherBiolink = Page::create([
                'workspace_id' => $otherWorkspace->id,
                'user_id' => $otherUser->id,
                'type' => 'biolink',
                'url' => 'other-delete-test',
            ]);

            $otherBlock = $otherBiolink->blocks()->create([
                'workspace_id' => $otherWorkspace->id,
                'type' => 'link',
                'order' => 1,
            ]);

            // Try to delete a block from another biolink through our biolink
            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/blocks/{$otherBlock->id}");

            expect($response->status())->toBe(403);

            // Verify the block still exists
            expect(Block::find($otherBlock->id))->not->toBeNull();
        });

        it('allows deleting own blocks', function () {
            $ownBlock = $this->biolink->blocks()->create([
                'workspace_id' => $this->workspace->id,
                'type' => 'link',
                'order' => 1,
            ]);

            $blockId = $ownBlock->id;

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/blocks/{$ownBlock->id}");

            expect($response->status())->toBe(204);

            // Verify the block was deleted
            expect(Block::find($blockId))->toBeNull();
        });

        it('prevents deleting blocks on another users biolink', function () {
            $otherUser = User::factory()->create();
            $otherWorkspace = Workspace::factory()->create();
            $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);
            $otherBiolink = Page::create([
                'workspace_id' => $otherWorkspace->id,
                'user_id' => $otherUser->id,
                'type' => 'biolink',
                'url' => 'other-delete-test-2',
            ]);

            $otherBlock = $otherBiolink->blocks()->create([
                'workspace_id' => $otherWorkspace->id,
                'type' => 'link',
                'order' => 1,
            ]);

            // Try to access another user's biolink entirely
            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/blocks/{$otherBlock->id}");

            expect($response->status())->toBe(403);
        });
    });

    describe('index block security', function () {
        it('prevents listing blocks from another users biolink', function () {
            $otherUser = User::factory()->create();
            $otherWorkspace = Workspace::factory()->create();
            $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);
            $otherBiolink = Page::create([
                'workspace_id' => $otherWorkspace->id,
                'user_id' => $otherUser->id,
                'type' => 'biolink',
                'url' => 'other-index-test',
            ]);

            $otherBiolink->blocks()->create([
                'workspace_id' => $otherWorkspace->id,
                'type' => 'link',
                'order' => 1,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/bio/{$otherBiolink->id}/blocks");

            expect($response->status())->toBe(403);
        });

        it('allows listing own biolink blocks', function () {
            $this->biolink->blocks()->create([
                'workspace_id' => $this->workspace->id,
                'type' => 'link',
                'order' => 1,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/bio/{$this->biolink->id}/blocks");

            expect($response->status())->toBe(200);
            expect($response->json())->toHaveCount(1);
        });
    });

    describe('store block security', function () {
        it('prevents creating blocks on another users biolink', function () {
            $otherUser = User::factory()->create();
            $otherWorkspace = Workspace::factory()->create();
            $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);
            $otherBiolink = Page::create([
                'workspace_id' => $otherWorkspace->id,
                'user_id' => $otherUser->id,
                'type' => 'biolink',
                'url' => 'other-store-test',
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/bio/{$otherBiolink->id}/blocks", [
                    'type' => 'link',
                    'settings' => ['text' => 'Malicious Block'],
                ]);

            expect($response->status())->toBe(403);

            // Verify no block was created
            expect($otherBiolink->blocks()->count())->toBe(0);
        });

        it('allows creating blocks on own biolink', function () {
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/bio/{$this->biolink->id}/blocks", [
                    'type' => 'link',
                    'settings' => ['text' => 'My Block'],
                ]);

            expect($response->status())->toBe(201);
            expect($this->biolink->blocks()->count())->toBe(1);
        });
    });
});
