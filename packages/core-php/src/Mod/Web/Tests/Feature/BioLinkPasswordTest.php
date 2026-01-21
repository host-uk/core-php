<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
});

it('can enable password protection on biolink', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'protected-page',
        'settings' => [
            'password_protected' => true,
            'password' => Hash::make('secret123'),
        ],
    ]);

    expect($biolink->getSetting('password_protected'))->toBeTrue()
        ->and($biolink->getSetting('password'))->not->toBeNull();
});

it('can verify password correctly', function () {
    $password = 'mypassword';
    $hashedPassword = Hash::make($password);

    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'verify-test',
        'settings' => [
            'password_protected' => true,
            'password' => $hashedPassword,
        ],
    ]);

    $storedHash = $biolink->getSetting('password');

    expect(Hash::check($password, $storedHash))->toBeTrue()
        ->and(Hash::check('wrongpassword', $storedHash))->toBeFalse();
});

it('can disable password protection', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'unprotected',
        'settings' => [
            'password_protected' => false,
        ],
    ]);

    expect($biolink->getSetting('password_protected'))->toBeFalse();
});

it('defaults to no password protection', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'no-settings',
        'settings' => [],
    ]);

    expect($biolink->getSetting('password_protected', false))->toBeFalse();
});

it('stores password as hashed value', function () {
    $plainPassword = 'plaintext123';
    $hashedPassword = Hash::make($plainPassword);

    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'hash-test',
        'settings' => [
            'password_protected' => true,
            'password' => $hashedPassword,
        ],
    ]);

    $storedPassword = $biolink->getSetting('password');

    // Verify it's not plain text
    expect($storedPassword)->not->toBe($plainPassword)
        // Verify it starts with bcrypt prefix
        ->and($storedPassword)->toStartWith('$2y$');
});
