<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\PushConfig;
use Core\Mod\Web\Models\PushSubscriber;
use Core\Mod\Web\Models\Pwa;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
});

it('can create a PWA config for a biolink', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'streamer-demo',
    ]);

    $pwa = Pwa::create([
        'biolink_id' => $biolink->id,
        'name' => 'Streamer Fan Page',
        'short_name' => 'Streamer',
        'description' => 'Follow your favourite streamer',
        'theme_color' => '#6366f1',
        'background_color' => '#ffffff',
        'display' => Pwa::DISPLAY_STANDALONE,
        'is_enabled' => true,
    ]);

    expect($pwa)->toBeInstanceOf(Pwa::class);
    expect($pwa->biolink->id)->toBe($biolink->id);
    expect($pwa->display)->toBe('standalone');
});

it('generates a valid manifest.json', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'creator-page',
    ]);

    $pwa = Pwa::create([
        'biolink_id' => $biolink->id,
        'name' => 'Creator Hub',
        'short_name' => 'Creator',
        'description' => 'Your creator destination',
        'theme_color' => '#10b981',
        'background_color' => '#f9fafb',
        'display' => Pwa::DISPLAY_STANDALONE,
        'orientation' => 'portrait',
        'icon_url' => 'https://cdn.example.com/icon.png',
        'lang' => 'en-GB',
        'dir' => 'ltr',
        'is_enabled' => true,
    ]);

    $manifest = $pwa->toManifest();

    expect($manifest)->toHaveKey('name', 'Creator Hub');
    expect($manifest)->toHaveKey('short_name', 'Creator');
    expect($manifest)->toHaveKey('description');
    expect($manifest)->toHaveKey('display', 'standalone');
    expect($manifest)->toHaveKey('theme_color', '#10b981');
    expect($manifest)->toHaveKey('icons');
    expect($manifest['icons'])->toBeArray()->toHaveCount(1);
    expect($manifest['icons'][0]['src'])->toBe('https://cdn.example.com/icon.png');
});

it('can create push config with VAPID keys', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'push-test',
    ]);

    $pushConfig = PushConfig::createForBiolink($biolink->id, [
        'is_enabled' => true,
        'prompt_enabled' => true,
        'prompt_delay_seconds' => 5,
        'prompt_min_pageviews' => 2,
    ]);

    expect($pushConfig)->toBeInstanceOf(PushConfig::class);
    expect($pushConfig->vapid_public_key)->not->toBeNull();
    expect($pushConfig->vapid_private_key)->not->toBeNull();
    expect($pushConfig->prompt_enabled)->toBeTrue();
    expect($pushConfig->prompt_min_pageviews)->toBe(2);
});

it('generates unique VAPID keys per biolink', function () {
    $biolink1 = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'vapid-test-1',
    ]);
    $biolink2 = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'vapid-test-2',
    ]);

    $config1 = PushConfig::createForBiolink($biolink1->id);
    $config2 = PushConfig::createForBiolink($biolink2->id);

    expect($config1->vapid_public_key)->not->toBe($config2->vapid_public_key);
    expect($config1->vapid_private_key)->not->toBe($config2->vapid_private_key);
});

it('can subscribe a fan to push notifications', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'subscriber-test',
    ]);

    $subscriber = PushSubscriber::create([
        'biolink_id' => $biolink->id,
        'subscriber_hash' => PushSubscriber::generateHash('https://fcm.googleapis.com/fcm/send/abc123'),
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'key_auth' => 'auth_key_here',
        'key_p256dh' => 'p256dh_key_here',
        'country_code' => 'GB',
        'device_type' => 'mobile',
        'browser_name' => 'Chrome',
        'os_name' => 'Android',
        'is_active' => true,
        'subscribed_at' => now(),
    ]);

    expect($subscriber)->toBeInstanceOf(PushSubscriber::class);
    expect($subscriber->is_active)->toBeTrue();
    expect($subscriber->device_type)->toBe('mobile');

    // Test the toSubscription method for web-push library
    $subscription = $subscriber->toSubscription();
    expect($subscription)->toHaveKey('endpoint');
    expect($subscription)->toHaveKey('keys');
    expect($subscription['keys'])->toHaveKey('auth');
    expect($subscription['keys'])->toHaveKey('p256dh');
});

it('can unsubscribe a fan', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'unsub-test',
    ]);

    $subscriber = PushSubscriber::create([
        'biolink_id' => $biolink->id,
        'subscriber_hash' => PushSubscriber::generateHash('https://fcm.googleapis.com/fcm/send/def456'),
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/def456',
        'key_auth' => 'auth_key',
        'key_p256dh' => 'p256dh_key',
        'is_active' => true,
        'subscribed_at' => now(),
    ]);

    expect($subscriber->is_active)->toBeTrue();

    $subscriber->unsubscribe();
    $subscriber->refresh();

    expect($subscriber->is_active)->toBeFalse();
    expect($subscriber->unsubscribed_at)->not->toBeNull();
});

it('scopes to active subscribers', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'scope-test',
    ]);

    // Create active subscriber
    PushSubscriber::create([
        'biolink_id' => $biolink->id,
        'subscriber_hash' => 'hash1',
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/active',
        'key_auth' => 'auth1',
        'key_p256dh' => 'p256dh1',
        'is_active' => true,
        'subscribed_at' => now(),
    ]);

    // Create inactive subscriber
    PushSubscriber::create([
        'biolink_id' => $biolink->id,
        'subscriber_hash' => 'hash2',
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/inactive',
        'key_auth' => 'auth2',
        'key_p256dh' => 'p256dh2',
        'is_active' => false,
        'subscribed_at' => now(),
        'unsubscribed_at' => now(),
    ]);

    $activeCount = PushSubscriber::active()->count();

    expect($activeCount)->toBe(1);
});

it('tracks PWA installs', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'installs-test',
    ]);

    $pwa = Pwa::create([
        'biolink_id' => $biolink->id,
        'name' => 'Install Tracker',
        'theme_color' => '#000000',
        'background_color' => '#ffffff',
        'display' => Pwa::DISPLAY_STANDALONE,
        'is_enabled' => true,
        'installs' => 0,
    ]);

    expect($pwa->installs)->toBe(0);

    $pwa->recordInstall();
    $pwa->refresh();

    expect($pwa->installs)->toBe(1);

    $pwa->recordInstall();
    $pwa->recordInstall();
    $pwa->refresh();

    expect($pwa->installs)->toBe(3);
});

it('biolink has pwa and push config relationships', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'relations-test',
    ]);

    Pwa::create([
        'biolink_id' => $biolink->id,
        'name' => 'My PWA',
        'theme_color' => '#ffffff',
        'background_color' => '#000000',
        'display' => 'standalone',
        'is_enabled' => true,
    ]);

    PushConfig::createForBiolink($biolink->id, [
        'is_enabled' => true,
    ]);

    $biolink->refresh();

    expect($biolink->pwa)->not->toBeNull();
    expect($biolink->pwa->name)->toBe('My PWA');
    expect($biolink->pushConfig)->not->toBeNull();
    expect($biolink->pushConfig->vapid_public_key)->not->toBeNull();
});
