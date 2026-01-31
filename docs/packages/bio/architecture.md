---
title: Architecture
description: Technical architecture of the core-bio package
updated: 2026-01-29
---

# core-bio Architecture

This document describes the technical architecture of the `core-bio` package, which provides link-in-bio, short link, and static page functionality for the Host UK platform.

## Overview

The `core-bio` package is a Laravel package that integrates with the Core PHP Framework's event-driven module system. It provides:

- **Biolink Pages** - Block-based link-in-bio pages with 60+ block types
- **Short Links** - URL shortening with redirect tracking
- **Static Pages** - Custom HTML/CSS/JS pages with XSS sanitisation
- **vCards** - Downloadable contact cards
- **Event Pages** - Calendar event links with iCal generation
- **File Links** - Secure file downloads with tracking

## Module Registration

The package uses event-driven registration via `Boot.php`:

```php
public static array $listens = [
    ClientRoutesRegistering::class => 'onClientRoutes',
    WebRoutesRegistering::class => 'onWebRoutes',
    ApiRoutesRegistering::class => 'onApiRoutes',
];
```

This lazy-loading pattern means the module is only instantiated when its events fire.

## Directory Structure

```
core-bio/
├── Actions/              # Single-purpose business logic (CreateBiolink, UpdateBiolink, DeleteBiolink)
├── Console/
│   └── Commands/         # Artisan commands (aggregation, cleanup, domain verification)
├── Controllers/
│   ├── Api/              # REST API controllers (PageController, BlockController, etc.)
│   └── Web/              # Web controllers (public rendering, redirects, submissions)
├── Effects/
│   ├── Background/       # Background effect implementations (snow, leaves, stars, etc.)
│   ├── Block/            # Block-level effects
│   └── Catalog.php       # Effect registry
├── Exceptions/           # Custom exceptions (AI service errors)
├── Jobs/                 # Queue jobs (click tracking, notifications)
├── Lang/                 # Translation files (en_GB)
├── Mail/                 # Mailable classes (BioReport)
├── Mcp/
│   └── Tools/            # MCP AI agent tools
├── Middleware/           # HTTP middleware (domain resolution, targeting, password)
├── Migrations/           # Database migrations
├── Models/               # Eloquent models
├── Notifications/        # Laravel notifications
├── Policies/             # Authorisation policies
├── Requests/             # Form request validation
├── Services/             # Business logic services
├── View/
│   ├── Blade/            # Blade templates
│   │   ├── admin/        # Admin panel views
│   │   ├── components/   # Reusable components
│   │   └── emails/       # Email templates
│   └── Modal/
│       └── Admin/        # Livewire admin components
├── routes/               # Route definitions (web.php, api.php, console.php)
├── Boot.php              # Service provider and event handlers
├── config.php            # Package configuration (merged as 'webpage')
└── device-frames.php     # Device frame configuration for previews
```

## Core Models

### Page (Biolink)

The central model representing all page types. Uses single-table inheritance via `type` column.

```php
// Types: 'biolink', 'link' (short link), 'static', 'vcard', 'event', 'file'
$biolink = Page::create([
    'workspace_id' => $workspace->id,
    'user_id' => $user->id,
    'type' => 'biolink',
    'url' => 'mypage',
    'settings' => [...],
]);
```

**Key relationships:**
- `workspace()` - Multi-tenant isolation via `BelongsToWorkspace` trait
- `blocks()` - HasMany Block for biolink pages
- `theme()` - BelongsTo Theme for styling
- `domain()` - BelongsTo Domain for custom domains
- `project()` - BelongsTo Project for organisation
- `pixels()` - BelongsToMany Pixel for tracking
- `revisions()` - HasMany BioRevision for undo functionality
- `subPages()` - HasMany self-referential for nested pages

### Block

Represents a content block within a biolink page. Supports 60+ block types across categories:

- **Standard**: link, heading, paragraph, avatar, image, socials
- **Embeds**: youtube, spotify, tiktok, vimeo, etc.
- **Advanced**: map, email_collector, faq, countdown, etc.
- **Payments**: paypal, donation, product, service

```php
$block = Block::create([
    'biolink_id' => $biolink->id,
    'type' => 'link',
    'region' => 'content',  // HLCRF region
    'order' => 1,
    'settings' => [
        'url' => 'https://example.com',
        'text' => 'Visit Example',
    ],
]);
```

**A/B Testing Fields:**
- `ab_test_id` - UUID grouping variants
- `is_control` - Whether this is the control variant
- `traffic_split` - Percentage of traffic for this variant
- `is_winner` - Declared winner after test

### Click / ClickStat

Two-tier analytics storage:

- **Click** - Individual click records with full attribution (IP hash, country, device, referrer, UTM)
- **ClickStat** - Pre-aggregated daily statistics for fast queries

Clicks are tracked asynchronously via `TrackBioLinkClick` job to avoid blocking page loads.

## HLCRF Layout System

The package supports multi-region layouts (Header, Left, Content, Right, Footer) with per-breakpoint configuration:

```php
// Layout presets from config
'layout_presets' => [
    'bio' => ['phone' => 'C', 'tablet' => 'C', 'desktop' => 'C'],
    'landing' => ['phone' => 'C', 'tablet' => 'HCF', 'desktop' => 'HCF'],
    'blog' => ['phone' => 'C', 'tablet' => 'HCF', 'desktop' => 'HCRF'],
    'docs' => ['phone' => 'C', 'tablet' => 'HCF', 'desktop' => 'HLCF'],
    'portfolio' => ['phone' => 'C', 'tablet' => 'HCF', 'desktop' => 'HLCRF'],
],
```

Blocks specify their region and per-region ordering:

```php
$block->region = Block::REGION_HEADER;  // 'header', 'left', 'content', 'right', 'footer'
$block->region_order = 1;
```

## Service Layer

### AnalyticsService

Queries click data with retention enforcement:

```php
$service = app(AnalyticsService::class);

// Respects workspace entitlements for data retention
$retention = $service->enforceDateRetention($start, $end, $workspace);

// Get breakdown data
$byCountry = $service->getClicksByCountry($biolink, $start, $end);
$byDevice = $service->getClicksByDevice($biolink, $start, $end);
$byReferrer = $service->getClicksByReferrer($biolink, $start, $end);
```

### StaticPageSanitiser

Security-critical service for sanitising user-provided HTML/CSS/JS:

```php
$sanitiser = app(StaticPageSanitiser::class);

$clean = $sanitiser->sanitiseStaticPage(
    html: $userHtml,
    css: $userCss,
    js: $userJs
);
```

**Security approach:**
- HTML: HTMLPurifier with strict allowlist
- CSS: Blocklist for dangerous patterns (expression, javascript:, @import)
- JS: Blocklist for eval-like constructs (documented limitations)

See `docs/security.md` for details.

### DomainVerificationService

Handles custom domain DNS verification:

```php
$service = app(DomainVerificationService::class);

// Verify via TXT record or CNAME
$verified = $service->verify($domain);

// Get DNS instructions for user
$instructions = $service->getDnsInstructions($domain);
```

### BioPasswordRateLimiter

Prevents brute force attacks on password-protected pages:

```php
$limiter = app(BioPasswordRateLimiter::class);

if ($limiter->tooManyAttempts($biolink, $request)) {
    $seconds = $limiter->availableIn($biolink, $request);
    // Show rate limit error
}

// On failed attempt - increments with exponential backoff
$limiter->increment($biolink, $request);

// On success - clear rate limit (backoff level persists)
$limiter->clear($biolink, $request);
```

## API Layer

RESTful API supporting both session auth and API key auth:

```
GET    /api/bio              # List biolinks
POST   /api/bio              # Create biolink
GET    /api/bio/{id}         # Get biolink
PUT    /api/bio/{id}         # Update biolink
DELETE /api/bio/{id}         # Delete biolink

GET    /api/bio/{id}/blocks  # List blocks
POST   /api/bio/{id}/blocks  # Add block
PUT    /api/blocks/{id}      # Update block
DELETE /api/blocks/{id}      # Delete block

GET    /api/bio/{id}/analytics        # Summary stats
GET    /api/bio/{id}/analytics/geo    # Geographic breakdown
GET    /api/bio/{id}/analytics/utm    # UTM campaign data
```

API key routes mirror session routes with `api.auth` and `api.scope.enforce` middleware.

## MCP Tools

AI agent tools via Model Context Protocol:

```php
// Available actions
$actions = [
    'list', 'get', 'create', 'update', 'delete',
    'add_block', 'update_block', 'delete_block',
];

// Example: Create biolink
$response = $bioTools->handle(new Request([
    'action' => 'create',
    'user_id' => $userId,
    'url' => 'my-page',
    'title' => 'My Page',
    'blocks' => [...],
]));
```

Additional MCP tools in separate classes:
- `BioAnalyticsTools` - Analytics queries
- `DomainTools` - Custom domain management
- `PixelTools` - Tracking pixel management
- `ProjectTools` - Project/folder management
- `QrTools` - QR code generation
- `ThemeTools` - Theme management

## Effects System

Extensible background effects via `Effects/Catalog.php`:

```php
// Register effect
Catalog::registerBackgroundEffect('snow', SnowEffect::class);

// Get effect for rendering
$effect = $page->getBackgroundEffect();
$html = $effect?->render();
```

Available effects: snow, rain, leaves, autumn_leaves, stars, bubbles, waves, lava_lamp, grid_motion.

## Multi-Tenancy

All data is scoped to workspaces using the `BelongsToWorkspace` trait:

```php
class Page extends Model
{
    use BelongsToWorkspace;  // Auto-scopes queries, sets workspace_id on create
}
```

The trait:
- Adds global scope to filter by current workspace
- Auto-assigns `workspace_id` on model creation
- Throws `MissingWorkspaceContextException` without valid context

## Caching Strategy

- **Domain resolution**: 1-hour cache per domain
- **Public pages**: Cached with `biopage:{domain_id}:{url}` key
- **Analytics**: No caching (queries pre-aggregated ClickStat table)
- **Themes**: System themes cached, user themes not cached

Cache invalidation triggers:
- Page update clears page cache
- Theme update clears all biolinks using that theme
- Domain update clears domain cache

## Queue Jobs

| Job | Purpose | Queue |
|-----|---------|-------|
| `TrackBioLinkClick` | Record individual click with attribution | default |
| `BatchTrackClicks` | Bulk click tracking for high traffic | default |
| `SendBioLinkNotification` | Webhook/email notifications | notifications |
| `SendSubmissionNotification` | Form submission notifications | notifications |

## Configuration

The package configuration is merged into Laravel's config as `webpage`:

```php
// Access config
$defaultDomain = config('webpage.default_domain');
$blockTypes = config('webpage.block_types');
$reservedSlugs = config('webpage.reserved_slugs');
```

Key configuration areas:
- `default_domain` - Base domain for biolinks
- `allowed_domains` - Domains that can serve biolinks
- `reserved_slugs` - URLs that cannot be claimed
- `block_types` - All available block types with metadata
- `layout_presets` - HLCRF layout configurations
- `og_images` - Dynamic OG image generation settings
- `revisions` - Revision history limits

## Database Schema

Main tables (prefixed `biolink_`):
- `biolinks` - Pages/links
- `biolink_blocks` - Page blocks
- `biolink_themes` - Theme definitions
- `biolink_domains` - Custom domains
- `biolink_projects` - Organisation folders
- `biolink_pixels` - Tracking pixels
- `biolink_clicks` - Individual click records
- `biolink_click_stats` - Aggregated statistics
- `biolink_submissions` - Form submissions
- `biolink_notification_handlers` - Notification configs
- `biolink_pwas` - PWA configurations
- `biolink_push_*` - Push notification tables
- `biolink_templates` - Page templates
- `biolink_revisions` - Change history (separate migration)
- `biolink_edit_locks` - Collaborative editing locks

## Testing

Tests use Pest with Orchestra Testbench:

```bash
# Run all tests
./vendor/bin/pest

# Run with coverage
./vendor/bin/pest --coverage

# Run specific test
./vendor/bin/pest --filter=PageTest
```

Test categories:
- **Feature tests** - Full integration tests for workflows
- **Unit tests** - Isolated service tests
- **Security tests** - XSS, CSRF, injection prevention
- **Use cases** - Example usage patterns
