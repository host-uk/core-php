---
title: Social Providers
description: Guide to implementing and using social platform providers
updated: 2026-01-29
---

# Social Providers

This document covers the provider system in `core-social` - how providers work, how to use them, and how to implement new ones.

## Overview

Providers are adapters that handle authentication and API communication with social platforms. Each provider implements a common interface while handling platform-specific requirements.

## Supported Providers

| Provider | Type | Features | Thread Support |
|----------|------|----------|----------------|
| Twitter/X | OAuth 2.0 + PKCE | Posts, media, polls | Yes (reply chain) |
| LinkedIn | OAuth 2.0 | Posts, images | No |
| Meta (Facebook/Instagram) | OAuth 2.0 | Posts, stories, reels | Comments |
| TikTok | OAuth 2.0 | Videos | No |
| YouTube | OAuth 2.0 | Videos | No |
| Pinterest | OAuth 2.0 | Pins, boards | No |
| Threads | OAuth 2.0 | Posts, images | Yes |
| Reddit | OAuth 2.0 | Posts, links | No |
| Medium | OAuth 2.0 | Articles | No |
| Mastodon | OAuth 2.0 (custom server) | Posts, media | Yes |
| Bluesky | App password | Posts, images | Yes (reply chain) |
| Discord | Webhook | Messages | No |
| Slack | Webhook | Messages | No |
| Telegram | Bot token | Messages | No |
| Dev.to | API key | Articles | No |
| Hashnode | API key | Articles | No |

## Provider Architecture

### Base Class

All providers extend `Providers\Abstracts\SocialProvider`:

```php
abstract class SocialProvider implements SocialProviderContract
{
    use UsesSocialProviderResponse;

    public bool $onlyUserAccount = true;
    public array $callbackResponseKeys = [];

    protected array $accessToken = [];
    protected Request $request;
    protected string $clientId = '';
    protected string $clientSecret = '';
    protected string $redirectUrl;
    protected array $values = [];

    abstract public function getAuthUrl(): string;
    abstract public function requestAccessToken(array $params): array;
    abstract public function getAccount(): SocialProviderResponse;
    abstract public function publishPost(string $text, Collection $media, array $params = []): SocialProviderResponse;
}
```

### Provider Manager

The `SocialProviderManager` acts as a factory for creating provider instances:

```php
// Registration (in Boot.php)
$manager->register('twitter', TwitterProvider::class);

// Creating instances
$provider = $manager->connect('twitter');  // With app credentials
$provider = $manager->connectWithAccount($account);  // With stored credentials
$provider = $manager->connectWithState('twitter', $state);  // For OAuth flow
```

### Response Objects

All provider methods return `SocialProviderResponse` objects:

```php
$response = $provider->publishPost($text, $media);

if ($response->hasError()) {
    $message = $response->getMessage();
    $context = $response->context();  // Error details
}

if ($response->isUnauthorized()) {
    // Token expired, needs refresh
}

if ($response->hasExceededRateLimit()) {
    $retryAfter = $response->retryAfter();
}

// Success
$id = $response->id();  // External post ID
$url = $response->url();  // Post URL
```

## Using Providers

### OAuth Flow

```php
// 1. Generate auth URL
$accountService = app(AccountService::class);
$authUrl = $accountService->getAuthUrl('twitter', $workspace);

// 2. Redirect user to $authUrl

// 3. Handle callback (in OAuthCallbackController)
$account = $accountService->handleCallback('twitter', $workspace, $request->all());
```

### Publishing Posts

```php
// Via AccountPublishPost action
$action = app(AccountPublishPost::class);
$response = $action($account, $post);

// Or via provider directly
$provider = $providers->connectWithAccount($account);
$response = $provider->publishPost(
    text: 'Hello world!',
    media: collect([
        ['path' => '/tmp/image.jpg', 'mime_type' => 'image/jpeg']
    ]),
    params: [
        'reply_to' => null,  // For threads
        'poll' => null,  // For polls
    ]
);
```

### Token Refresh

```php
$accountService = app(AccountService::class);
$success = $accountService->refreshToken($account);

// Or via provider
$provider = $providers->connectWithAccount($account);
$response = $provider->refreshAccessToken();
```

## Implementing a New Provider

### Step 1: Create Provider Class

```php
<?php

declare(strict_types=1);

namespace Core\Mod\Social\Providers\NewPlatform;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Core\Mod\Social\Providers\Abstracts\SocialProvider;
use Core\Mod\Social\Providers\Enums\ContentType;
use Core\Mod\Social\Providers\Support\SocialProviderResponse;

class NewPlatformProvider extends SocialProvider
{
    public array $callbackResponseKeys = ['code', 'state'];

    protected string $apiUrl = 'https://api.newplatform.com';

    public static function name(): string
    {
        return 'New Platform';
    }

    public static function service(): string
    {
        return 'newplatform';
    }

    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code',
            'scope' => 'read write',
            'state' => $this->values['state'] ?? '',
        ];

        return $this->buildUrlFromBase("{$this->apiUrl}/oauth/authorize", $params);
    }

    public function requestAccessToken(array $params): array
    {
        $response = $this->http()
            ->asForm()
            ->post("{$this->apiUrl}/oauth/token", [
                'grant_type' => 'authorization_code',
                'code' => $params['code'],
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUrl,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => now()->addSeconds($data['expires_in'])->timestamp,
            ];
        }

        return [];
    }

    public function getAccount(): SocialProviderResponse
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token['access_token'])
            ->get("{$this->apiUrl}/v1/me");

        return $this->buildResponse($response, function ($data) {
            return [
                'id' => $data['id'],
                'name' => $data['display_name'],
                'username' => $data['username'],
                'image' => $data['avatar_url'] ?? null,
            ];
        });
    }

    public function publishPost(string $text, Collection $media, array $params = []): SocialProviderResponse
    {
        $token = $this->getAccessToken();

        $postData = ['content' => $text];

        // Handle media uploads
        if ($media->isNotEmpty()) {
            $mediaIds = [];
            foreach ($media as $item) {
                $uploadResult = $this->uploadMedia($item);
                if ($uploadResult->hasError()) {
                    return $uploadResult;
                }
                $mediaIds[] = $uploadResult->context()['media_id'];
            }
            $postData['media_ids'] = $mediaIds;
        }

        $response = $this->http()
            ->withToken($token['access_token'])
            ->post("{$this->apiUrl}/v1/posts", $postData);

        return $this->buildResponse($response, function ($data) {
            return [
                'id' => $data['id'],
                'url' => $data['url'] ?? null,
            ];
        });
    }

    protected function uploadMedia(array $item): SocialProviderResponse
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token['access_token'])
            ->attach('file', file_get_contents($item['path']), basename($item['path']))
            ->post("{$this->apiUrl}/v1/media");

        return $this->buildResponse($response, function ($data) {
            return ['media_id' => $data['id']];
        });
    }

    public function deletePost(string $id): SocialProviderResponse
    {
        $token = $this->getAccessToken();

        $response = $this->http()
            ->withToken($token['access_token'])
            ->delete("{$this->apiUrl}/v1/posts/{$id}");

        return $this->buildResponse($response);
    }

    public static function contentType(): ContentType
    {
        return ContentType::SINGLE;  // Or THREAD, COMMENTS, CAROUSEL
    }

    public static function externalPostUrl(string $username, string $postId): string
    {
        return "https://newplatform.com/{$username}/posts/{$postId}";
    }

    public static function externalAccountUrl(string $username): string
    {
        return "https://newplatform.com/{$username}";
    }
}
```

### Step 2: Register Provider

Add to `Boot::registerSocialProviderManager()`:

```php
$manager->register('newplatform', \Core\Mod\Social\Providers\NewPlatform\NewPlatformProvider::class);
```

### Step 3: Add Configuration

Environment variables:

```env
NEWPLATFORM_CLIENT_ID=your-client-id
NEWPLATFORM_CLIENT_SECRET=your-client-secret
```

Config (if using `config/social.php`):

```php
'providers' => [
    'newplatform' => [
        'character_limit' => 500,
        'media_limit' => [
            'images' => 4,
            'videos' => 1,
        ],
        'features' => ['posts', 'images'],
    ],
],
```

### Step 4: Add Tests

```php
<?php

use Illuminate\Support\Facades\Http;
use Core\Mod\Social\Providers\NewPlatform\NewPlatformProvider;

it('can authenticate via OAuth', function () {
    $provider = new NewPlatformProvider(
        request: app('request'),
        clientId: 'test-client',
        clientSecret: 'test-secret',
        redirectUrl: 'https://example.com/callback',
        values: ['state' => 'test-state']
    );

    $authUrl = $provider->getAuthUrl();

    expect($authUrl)->toContain('client_id=test-client')
        ->and($authUrl)->toContain('state=test-state');
});

it('can publish a post', function () {
    Http::fake([
        'api.newplatform.com/v1/posts' => Http::response([
            'id' => '12345',
            'url' => 'https://newplatform.com/user/posts/12345',
        ]),
    ]);

    $provider = new NewPlatformProvider(/* ... */);
    $provider->useAccessToken(['access_token' => 'test-token']);

    $response = $provider->publishPost('Hello world!', collect([]));

    expect($response->hasError())->toBeFalse()
        ->and($response->id())->toBe('12345');
});
```

## Provider-Specific Features

### Twitter Polls

```php
$response = $provider->publishPost(
    text: 'Which do you prefer?',
    media: collect([]),
    params: [
        'poll' => [
            'is_poll' => true,
            'poll_options' => ['Option A', 'Option B', 'Option C'],
            'poll_duration_minutes' => 1440,  // 24 hours
        ],
    ]
);
```

### Thread Publishing

For providers that support threads (Twitter, Bluesky, Mastodon):

```php
// First post in thread
$firstResponse = $provider->publishPost($firstText, $media);

// Subsequent posts
$secondResponse = $provider->publishPost(
    text: $secondText,
    media: collect([]),
    params: [
        'reply_to' => $firstResponse->id(),
        'first_response' => $firstResponse,
        'last_response' => $firstResponse,
    ]
);
```

### Mastodon Custom Instances

Mastodon requires the instance URL:

```php
// During account connection
$accountService->connectWithCredentials('mastodon', $workspace, [
    'server' => 'https://mastodon.social',
    // OAuth params follow
]);
```

### Bluesky App Passwords

Bluesky uses app passwords instead of OAuth:

```php
$account = $accountService->connectWithCredentials('bluesky', $workspace, [
    'identifier' => 'user.bsky.social',
    'password' => 'xxxx-xxxx-xxxx-xxxx',  // App password, not main password
]);
```

### Webhook Providers (Discord/Slack)

These providers don't use OAuth - just a webhook URL:

```php
$account = $accountService->connectWebhook('discord', $workspace,
    webhookUrl: 'https://discord.com/api/webhooks/xxx/yyy',
    name: 'My Discord Channel'
);
```

## Error Handling

### Rate Limits

Providers should detect rate limits and return appropriate responses:

```php
protected function buildHttpResponse(Response $response, ?Closure $okResult = null): SocialProviderResponse
{
    if ($response->status() === 429) {
        $retryAfter = (int) $response->header('Retry-After', 60);
        return $this->rateLimitResponse($retryAfter);
    }
    // ...
}
```

### Token Expiry

Handle 401 responses:

```php
if ($response->status() === 401) {
    return $this->unauthorizedResponse('Access token expired or invalid');
}
```

### Custom Error Messages

Override error mapping for provider-specific messages:

```php
public static function mapErrorMessage(string $key): string
{
    return match ($key) {
        'duplicate' => __('You have already posted this content.'),
        'content_too_long' => __('Your post exceeds the character limit.'),
        default => parent::mapErrorMessage($key),
    };
}
```

## Debugging

### HTTP Logging

Enable HTTP client logging in development:

```php
protected function http(): \Illuminate\Http\Client\PendingRequest
{
    $client = parent::http();

    if (app()->environment('local')) {
        $client->withOptions(['debug' => true]);
    }

    return $client;
}
```

### Response Inspection

```php
$response = $provider->publishPost($text, $media);

Log::debug('Provider response', [
    'has_error' => $response->hasError(),
    'message' => $response->getMessage(),
    'context' => $response->context(),
    'id' => $response->id(),
]);
```
