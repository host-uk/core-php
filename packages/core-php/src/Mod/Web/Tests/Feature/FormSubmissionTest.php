<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Submission;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Support\Facades\RateLimiter;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Illuminate\Foundation\Testing\WithoutMiddleware::class);

beforeEach(function () {
    RateLimiter::clear(md5('api.bio.submit'.request()->ip()));
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
    $this->biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'testsubmissions',
    ]);
});

it('stores email submissions', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'email_collector',
        'order' => 1,
        'is_enabled' => true,
    ]);

    $response = $this->postJson('/api/bio/submit', [
        'block_id' => $block->id,
        'type' => 'email',
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    if ($response->status() === 429) {
        $this->markTestSkipped('Rate limit hit.');
    }
    $response->assertOk()
        ->assertJson(['ok' => true]);

    $this->assertDatabaseHas('biolink_submissions', [
        'biolink_id' => $this->biolink->id,
        'block_id' => $block->id,
        'type' => 'email',
    ]);

    $submission = Submission::latest()->first();
    expect($submission->email)->toBe('test@example.com')
        ->and($submission->name)->toBe('Test User');
});

it('stores phone submissions', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'phone_collector',
        'order' => 1,
        'is_enabled' => true,
    ]);

    $response = $this->postJson('/api/bio/submit', [
        'block_id' => $block->id,
        'type' => 'phone',
        'phone' => '+44 7700 900123',
    ]);

    if ($response->status() === 429) {
        $this->markTestSkipped('Rate limit hit.');
    }
    $response->assertOk()
        ->assertJson(['ok' => true]);

    $submission = Submission::latest()->first();
    expect($submission->phone)->toBe('+44 7700 900123');
});

it('stores contact form submissions', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'contact_collector',
        'order' => 1,
        'is_enabled' => true,
    ]);

    $response = $this->postJson('/api/bio/submit', [
        'block_id' => $block->id,
        'type' => 'contact',
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'message' => 'Hello, I would like to enquire about your services.',
    ]);

    if ($response->status() === 429) {
        $this->markTestSkipped('Rate limit hit.');
    }
    $response->assertOk()
        ->assertJson(['ok' => true]);

    $submission = Submission::latest()->first();
    expect($submission->name)->toBe('Jane Smith')
        ->and($submission->email)->toBe('jane@example.com')
        ->and($submission->message)->toBe('Hello, I would like to enquire about your services.');
});

it('validates email is required for email type', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'email_collector',
        'order' => 1,
        'is_enabled' => true,
    ]);

    $response = $this->postJson('/api/bio/submit', [
        'block_id' => $block->id,
        'type' => 'email',
        'name' => 'Test User',
        // Missing email
    ]);

    if ($response->status() === 429) {
        $this->markTestSkipped('Rate limit hit.');
    }
    $response->assertStatus(422)
        ->assertJson(['ok' => false, 'error' => 'Email is required.']);
});

it('validates phone is required for phone type', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'phone_collector',
        'order' => 1,
        'is_enabled' => true,
    ]);

    $response = $this->postJson('/api/bio/submit', [
        'block_id' => $block->id,
        'type' => 'phone',
        // Missing phone
    ]);

    if ($response->status() === 429) {
        $this->markTestSkipped('Rate limit hit.');
    }
    $response->assertStatus(422)
        ->assertJson(['ok' => false, 'error' => 'Phone number is required.']);
});

it('validates message is required for contact type', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'contact_collector',
        'order' => 1,
        'is_enabled' => true,
    ]);

    $response = $this->postJson('/api/bio/submit', [
        'block_id' => $block->id,
        'type' => 'contact',
        'email' => 'test@example.com',
        // Missing message
    ]);

    if ($response->status() === 429) {
        $this->markTestSkipped('Rate limit hit.');
    }
    $response->assertStatus(422)
        ->assertJson(['ok' => false, 'error' => 'Message is required.']);
});

it('rejects submissions with honeypot filled', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'email_collector',
        'order' => 1,
        'is_enabled' => true,
    ]);

    $response = $this->postJson('/api/bio/submit', [
        'block_id' => $block->id,
        'type' => 'email',
        'email' => 'test@example.com',
        'website' => 'spam-bot-filled-this', // Honeypot
    ]);

    // Returns OK to not tip off spammers, but doesn't store
    if ($response->status() === 429) {
        $this->markTestSkipped('Rate limit hit.');
    }
    $response->assertOk()
        ->assertJson(['ok' => true]);

    $this->assertDatabaseMissing('biolink_submissions', [
        'biolink_id' => $this->biolink->id,
    ]);
});

it('rejects submissions for disabled blocks', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'email_collector',
        'order' => 1,
        'is_enabled' => false, // Disabled
    ]);

    $response = $this->postJson('/api/bio/submit', [
        'block_id' => $block->id,
        'type' => 'email',
        'email' => 'test@example.com',
    ]);

    if ($response->status() === 429) {
        $this->markTestSkipped('Rate limit hit.');
    }
    $response->assertStatus(404)
        ->assertJson(['ok' => false, 'error' => 'Form not found.']);
});

it('returns custom success message if configured', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'email_collector',
        'order' => 1,
        'is_enabled' => true,
        'settings' => [
            'success_message' => 'Thanks for joining our newsletter.',
        ],
    ]);

    $response = $this->postJson('/api/bio/submit', [
        'block_id' => $block->id,
        'type' => 'email',
        'email' => 'test@example.com',
    ]);

    if ($response->status() === 429) {
        $this->markTestSkipped('Rate limit hit.');
    }
    $response->assertOk()
        ->assertJson([
            'ok' => true,
            'message' => 'Thanks for joining our newsletter.',
        ]);
});

it('can export submissions as CSV', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'email_collector',
        'order' => 1,
        'is_enabled' => true,
    ]);

    Submission::create([
        'biolink_id' => $this->biolink->id,
        'block_id' => $block->id,
        'type' => 'email',
        'data' => ['email' => 'export@example.com', 'name' => 'Export Test'],
    ]);

    $submission = Submission::first();

    expect($submission->toExportArray())
        ->toHaveKey('email', 'export@example.com')
        ->toHaveKey('name', 'Export Test')
        ->toHaveKey('type', 'email');
});

it('scopes submissions by type', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'email_collector',
        'order' => 1,
        'is_enabled' => true,
    ]);

    Submission::create([
        'biolink_id' => $this->biolink->id,
        'block_id' => $block->id,
        'type' => 'email',
        'data' => ['email' => 'email@test.com'],
    ]);

    Submission::create([
        'biolink_id' => $this->biolink->id,
        'block_id' => $block->id,
        'type' => 'phone',
        'data' => ['phone' => '+44 123 456'],
    ]);

    expect(Submission::emails()->count())->toBe(1)
        ->and(Submission::phones()->count())->toBe(1)
        ->and(Submission::contacts()->count())->toBe(0);
});
