<?php

/**
 * UseCase: Create Bio Page (Basic Flow)
 *
 * Acceptance test for creating a bio page.
 * Tests the happy path user journey through the browser.
 *
 * Uses translation keys to get expected values - tests won't break on copy changes.
 */

use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;

describe('Create Bio Page', function () {
    beforeEach(function () {
        // Create user with workspace
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->workspace = Workspace::factory()->create();
        $this->workspace->users()->attach($this->user->id, [
            'role' => 'owner',
            'is_default' => true,
        ]);
    });

    it('can create a bio page from the index', function () {
        // Login
        $page = visit('/login');

        $page->fill('email', 'test@example.com')
             ->fill('password', 'password')
             ->click(__('pages::pages.login.submit'))
             ->assertPathContains('/hub');

        // Navigate to bio index
        $page->navigate('/hub/bio')
             ->assertSee(__('webpage::bio.index.title'))
             ->assertSee(__('webpage::bio.actions.new'));

        // Open create dropdown and click Bio Page
        $page->click(__('webpage::bio.actions.new'))
             ->assertSee(__('webpage::bio.types.bio_page'));

        $page->click(__('webpage::bio.types.bio_page'))
             ->assertSee(__('webpage::bio.modal.create.title'))
             ->assertSee(__('webpage::bio.modal.create.url_label'))
             ->assertSee(__('webpage::bio.modal.create.type_label'));

        // Fill form and submit
        $page->type('input[placeholder="your-page"]', 'my-test-page')
             ->click('button[type="submit"]')
             ->wait(3);

        // Should redirect to editor (path contains bio and edit)
        $page->assertPathContains('/bio/')
             ->assertPathContains('/edit');
    });

    it('shows validation error for empty URL', function () {
        // Login and wait for redirect
        $page = visit('/login');

        $page->fill('email', 'test@example.com')
             ->fill('password', 'password')
             ->click(__('pages::pages.login.submit'))
             ->assertPathContains('/hub')
             ->wait(1);

        // Navigate to bio and open modal
        $page->navigate('/hub/bio')
             ->wait(2)
             ->assertSee(__('webpage::bio.index.title'))
             ->click(__('webpage::bio.actions.new'))
             ->click(__('webpage::bio.types.bio_page'))
             ->assertSee(__('webpage::bio.modal.create.title'));

        // Submit empty form
        $page->click('button[type="submit"]')
             ->wait(1)
             ->assertSee('required');
    });

    todo('shows error for duplicate URL - session persistence issue');

    todo('can filter by project - session persistence issue');

    todo('works on mobile viewport - session persistence issue');
});
