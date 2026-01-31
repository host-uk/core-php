---
title: Architecture
description: Technical architecture of the core-social package for social media management
updated: 2026-01-29
---

# Architecture

This document describes the technical architecture of `core-social`, a Laravel package for social media management including scheduling, publishing, and analytics across 20+ platforms.

## Overview

`core-social` follows the Core PHP Framework's event-driven module system. It registers as a Laravel service provider and uses lazy loading to only instantiate components when specific events fire.

```
core-social/
├── Actions/              # Single-purpose business logic classes
├── Builders/             # Query builders with filter support
├── Casts/                # Eloquent attribute casts
├── Concerns/             # Reusable traits
├── Console/Commands/     # Artisan commands
├── Contracts/            # Interfaces
├── Controllers/          # HTTP controllers (web & API)
├── Data/                 # Data transfer objects
├── Enums/                # PHP 8.1+ enums
├── Events/               # Domain events
├── Exceptions/           # Custom exceptions
├── Jobs/                 # Queue jobs
├── Listeners/            # Event listeners
├── Middleware/           # HTTP middleware
├── Migrations/           # Database migrations
├── Models/               # Eloquent models
├── Notifications/        # Laravel notifications
├── Providers/            # Social platform providers
├── Reports/              # Analytics reporting
├── Requests/             # Form requests
├── Responses/            # Response objects
├── routes/               # Route definitions
├── Services/             # Application services
├── Support/              # Helper classes
├── tests/                # Pest tests
└── View/                 # Livewire components & Blade views
```

## Core Components

### Boot.php - Module Entry Point

The `Boot` class extends Laravel's `ServiceProvider` and implements the event-driven loading pattern:

```php
class Boot extends ServiceProvider
{
    public static array $listens = [
        AdminPanelBooting::class => 'onAdminPanel',
        WebRoutesRegistering::class => 'onWebRoutes',
        ApiRoutesRegistering::class => 'onApiRoutes',
        ConsoleBooting::class => 'onConsole',
    ];
}
```

When the Core framework fires these events, the corresponding methods register routes, views, Livewire components, and commands.

### Provider System

Social platforms are implemented as providers extending `Providers\Abstracts\SocialProvider`:

```
Providers/
├── Abstracts/SocialProvider.php    # Base class
├── Contracts/SocialProvider.php    # Interface
├── SocialProviderManager.php       # Factory/registry
├── Twitter/TwitterProvider.php
├── Meta/MetaProvider.php
├── Bluesky/BlueskyProvider.php
└── ... (20+ providers)
```

**Provider Types:**

| Type | Authentication | Examples |
|------|----------------|----------|
| OAuth2 | Standard OAuth 2.0 flow | Twitter, LinkedIn, Meta, TikTok |
| Server + OAuth2 | User provides server URL, then OAuth | Mastodon |
| Credentials | Username + app password | Bluesky |
| Webhook | Webhook URL only | Discord, Slack |
| API Key/Token | Static API key | Telegram, Dev.to |

**Provider Registration:**

```php
// In Boot::registerSocialProviderManager()
$manager->register('twitter', TwitterProvider::class);
$manager->register('bluesky', BlueskyProvider::class);
```

**Provider Usage:**

```php
// Connect via OAuth
$authUrl = $providers->connect('twitter')->getAuthUrl();

// Connect with existing credentials
$provider = $providers->connectWithAccount($account);
$response = $provider->publishPost($text, $media);
```

### Action Pattern

Actions encapsulate single-purpose business logic following the command pattern:

```php
class AccountPublishPost
{
    public function __construct(
        protected SocialProviderManager $providerManager
    ) {}

    public function __invoke(Account $account, Post $post): SocialProviderResponse
    {
        $provider = $this->providerManager->connectWithAccount($account);
        return $provider->publishPost(/* ... */);
    }
}
```

**Key Actions:**

- `Account/UpdateOrCreateAccount` - Create or update social account
- `Account/StoreProviderEntitiesAsAccounts` - Bulk import (Facebook pages, etc.)
- `Post/PublishPost` - Dispatch publishing to all accounts
- `Post/AccountPublishPost` - Publish to single account
- `Webhook/TriggerWebhook` - Deliver webhook payloads

### Service Layer

Services provide higher-level orchestration and are injected into controllers and components:

| Service | Responsibility |
|---------|----------------|
| `PostService` | Post CRUD, scheduling, threads |
| `AccountService` | OAuth flow, token refresh |
| `MediaService` | File upload and storage |
| `PublishingService` | Coordinate multi-account publishing |
| `BulkPostService` | Batch operations on posts |
| `ApprovalService` | Post approval workflow |
| `WebhookTemplateService` | Render webhook payloads |
| `SocialAiService` | AI content generation |

### Model Architecture

```
┌──────────────────┐       ┌──────────────────┐
│     Workspace    │       │       User       │
└────────┬─────────┘       └────────┬─────────┘
         │                          │
         │ belongsTo                │ belongsTo
         ▼                          ▼
┌──────────────────┐       ┌──────────────────┐
│      Post        │◄──────│   PostApproval   │
└────────┬─────────┘       └──────────────────┘
         │
         │ belongsToMany
         ▼
┌──────────────────┐       ┌──────────────────┐
│     Account      │◄──────│   PostAccount    │ (pivot with status)
└────────┬─────────┘       └──────────────────┘
         │
         │ hasMany
         ▼
┌──────────────────┐
│    Analytics     │
└──────────────────┘
```

**Key Relationships:**

- `Post` belongs to `Workspace` and `User`
- `Post` belongs to many `Account` through `PostAccount` pivot
- `Post` belongs to many `Media` through `PostMedia` pivot
- `Post` has many `PostVersion` for account-specific content
- `Account` has many `Analytics` for historical metrics
- `Webhook` has many `WebhookDelivery` for delivery tracking

### Multi-Tenancy

All models use the `BelongsToWorkspace` and `BelongsToNamespace` traits from `core-tenant`:

```php
class Account extends Model
{
    use BelongsToNamespace;
    use BelongsToWorkspace;
    // ...
}
```

These traits:
- Auto-scope queries to the current workspace
- Auto-assign `workspace_id` on create
- Throw `MissingWorkspaceContextException` if no workspace context exists

### Entitlement System

Feature access is controlled via the entitlement service:

```php
// Check if workspace can schedule posts
$result = $entitlements->can($workspace, 'social.posts.scheduled');

if ($result->isDenied()) {
    return $result; // Contains reason, limit, used count
}
```

**Entitlement Codes:**

- `social.accounts` - Number of connected accounts
- `social.posts.scheduled` - Number of scheduled posts in queue
- `social.media.storage` - Media storage quota
- `social.analytics` - Analytics access

### Event-Driven Architecture

The package dispatches events for extensibility:

**Account Events:**
- `AddingAccount` - Before account creation
- `AccountAdded` - After account connected
- `AccountUpdated` - After account credentials refreshed
- `AccountUnauthorized` - When token becomes invalid
- `AccountDeleted` - After account removed

**Post Events:**
- `PostCreated` - After post created
- `PostScheduled` - When post is scheduled
- `PostPublished` - After successful publish
- `PostPublishedFailed` - After failed publish

**Listeners** respond to events for side effects:
- Send notifications
- Log activities
- Trigger webhooks
- Update metrics

### Job Architecture

Background jobs handle time-intensive operations:

```
Jobs/
├── AccountPublishPostJob.php      # Publish to single account
├── PublishScheduledPostJob.php    # Process scheduled posts
├── RefreshAccountTokenJob.php     # Refresh OAuth tokens
├── FetchAccountAnalyticsJob.php   # Pull analytics data
├── ProcessBulkPostAction.php      # Large batch operations
├── ProcessWebhooksJob.php         # Webhook delivery
└── TriggerWebhookJob.php          # Single webhook delivery
```

**Job Flow for Publishing:**

```
ProcessScheduledPosts (command)
    │
    ▼
PublishScheduledPostJob (per post)
    │
    ▼
AccountPublishPostJob (per account, batched)
    │
    ▼
Provider::publishPost()
```

### API Routes

Routes are organised by feature with middleware protection:

```php
// routes/api.php
Route::middleware('check.social:social.accounts')->group(function () {
    Route::get('/accounts', [SocialAccountController::class, 'index']);
    // ...
});

Route::middleware('check.social:social.posts.scheduled')->group(function () {
    Route::post('/posts', [SocialPostController::class, 'store'])
        ->middleware('throttle:social-post-create');
    // ...
});
```

**Rate Limiting:**

- `social-schedule`: 30/min per user
- `social-queue`: 20/min per user
- `social-post-create`: 60/min per user
- OAuth callback: 10/min

### Thread Support

Posts can be linked into threads for platforms like Twitter and Bluesky:

```php
$posts = $postService->createThread(
    workspace: $workspace,
    user: $user,
    posts: [
        ['content' => 'First post', 'mediaIds' => []],
        ['content' => 'Reply 1', 'mediaIds' => []],
        ['content' => 'Reply 2', 'mediaIds' => []],
    ],
    threadType: ThreadType::THREAD,
    accountIds: $accountIds
);
```

**Thread Fields:**

- `thread_id` - References parent post (null for parent)
- `thread_position` - Order within thread (0, 1, 2...)
- `thread_type` - `THREAD`, `CAROUSEL`, etc.

### Webhook System

Webhooks notify external systems of events:

**Webhook Types:**
- `custom` - Generic JSON with HMAC signature
- `discord` - Discord-formatted embeds
- `slack` - Slack Block Kit format

**Template Formats:**
- `SIMPLE` - `{{variable}}` substitution
- `MUSTACHE` - Conditionals and loops
- `JSON` - Structured JSON with variables

```php
// Template example
{
    "event": "{{event.type}}",
    "post": {
        "id": "{{data.uuid}}",
        "content": "{{data.content | default:N/A}}"
    },
    "timestamp": "{{timestamp | iso8601}}"
}
```

### Media Handling

Media uploads support images and videos with validation:

**Allowed Types:**
- Images: JPEG, PNG, GIF, WebP (max 10MB)
- Videos: MP4, QuickTime, WebM (max 100MB)

**Storage Structure:**
```
social/{workspace_id}/{YYYY}/{MM}/{uuid}.{ext}
```

### Livewire Components

Admin UI uses Livewire + Flux Pro components:

| Component | Purpose |
|-----------|---------|
| `PostComposer` | Create/edit posts with scheduling |
| `PostKanban` | Drag-and-drop status board |
| `PostCalendar` | Calendar view of scheduled posts |
| `MediaLibrary` | Browse and manage uploaded media |
| `AccountIndex` | Manage connected accounts |
| `WebhookEditor` | Configure webhook endpoints |
| `TemplateEditor` | Create post templates |

## Data Flow Examples

### OAuth Account Connection

```
1. User clicks "Connect Twitter"
2. AccountService::getAuthUrl() generates state, stores in session
3. User redirected to Twitter OAuth
4. Twitter redirects to /callback/{provider}
5. OAuthCallbackController validates state (CSRF protection)
6. AccountService::handleCallback() exchanges code for token
7. Provider::getAccount() fetches user info
8. Account created/updated in database
9. AccountAdded event dispatched
```

### Post Scheduling

```
1. User creates post in PostComposer
2. PostService::create() creates draft
3. PostService::schedule() validates entitlements
4. Post status -> SCHEDULED, scheduled_at set
5. PostScheduled event dispatched
6. Cron runs ProcessScheduledPosts
7. PublishScheduledPostJob dispatched for due posts
8. Bus::batch() creates AccountPublishPostJob per account
9. Each job calls Provider::publishPost()
10. PostAccount pivot updated with status/external_id
11. PostPublished/PostPublishedFailed events dispatched
12. Overall post status updated
```

## Configuration

**Environment Variables:**

```env
# Provider credentials (per provider)
TWITTER_CLIENT_ID=
TWITTER_CLIENT_SECRET=

# Media storage
SOCIAL_MEDIA_DISK=public

# Rate limits (optional overrides)
SOCIAL_RATE_LIMIT_SCHEDULE=30
SOCIAL_RATE_LIMIT_QUEUE=20
```

**Config File:** `config/social.php` (if published)

```php
return [
    'providers' => [
        'twitter' => [
            'character_limit' => 280,
            'media_limit' => ['images' => 4, 'videos' => 1],
            'features' => ['threads', 'polls'],
        ],
        // ...
    ],
];
```

## Testing

Tests use Pest with Orchestra Testbench:

```bash
composer test                     # Run all tests
composer test -- --filter=Twitter # Run specific tests
```

**Test Organisation:**

- `tests/Feature/Api/` - API endpoint tests
- `tests/Feature/Livewire/` - Component tests
- `tests/Feature/Providers/` - Provider integration tests
- `tests/Feature/Services/` - Service tests

**Mocking HTTP:**

```php
Http::fake([
    'api.twitter.com/*' => Http::response([
        'data' => ['id' => '123', 'text' => 'Hello']
    ])
]);
```

## Extension Points

### Adding a New Provider

1. Create `Providers/{Name}/{Name}Provider.php`
2. Extend `Providers\Abstracts\SocialProvider`
3. Implement required methods (`getAuthUrl`, `requestAccessToken`, `getAccount`, `publishPost`)
4. Register in `Boot::registerSocialProviderManager()`
5. Add provider config to `config/social.php`
6. Add tests in `tests/Feature/Providers/`

### Adding a New Webhook Event

1. Create event class implementing `Contracts\WebhookEvent`
2. Implement `name()`, `nameLocalised()`, `payload()`, `message()`
3. Register listener `HandleWorkspaceWebhookEvent`
4. Add to webhook event selector UI

### Adding a New Bulk Action

1. Add action name to `BulkPostController::getAllowedActions()`
2. Add validation in `validateActionParams()`
3. Add handler in `BulkPostService::processAction()`
4. Add UI config in `BulkPostService::getAvailableActions()`

## Dependencies

**Required:**
- `host-uk/core` - Core framework
- `host-uk/core-tenant` - Multi-tenancy

**Suggested:**
- `spatie/laravel-activitylog` - Activity logging
- `livewire/livewire` - Admin UI components
