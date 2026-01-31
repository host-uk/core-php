---
title: Architecture
description: Technical architecture of core-trust social proof notifications package
updated: 2026-01-29
---

# Architecture

This document describes the technical architecture of the core-trust package, a social proof notifications system for Laravel applications.

## Overview

Core-trust provides embeddable widgets that display social proof elements (recent purchases, live visitors, reviews, email collectors) on customer websites. The system consists of:

1. **Public Widget API** - Unauthenticated endpoints for widget rendering and event tracking
2. **Management API** - Authenticated endpoints for CRUD operations
3. **Admin UI** - Livewire components for workspace users
4. **Background Jobs** - Webhook delivery, data cleanup
5. **MCP Tools** - AI agent integration for analytics and management

## Module Structure

```
core-trust/
├── Boot.php                    # Laravel service provider, event registration
├── Service/Boot.php            # Service layer (tenant config, menus)
├── config.php                  # Widget types, positions, rate limits
├── Models/
│   ├── Campaign.php            # Website/project with pixel_key
│   ├── Notification.php        # Widget instances (15+ types)
│   ├── Conversion.php          # Tracked conversions with attribution
│   ├── Event.php               # Impressions, clicks, hovers, closes
│   ├── Review.php              # Customer testimonials
│   └── CollectedData.php       # Email/form submissions
├── Services/
│   ├── TrustService.php        # Widget data, tracking, stats
│   ├── ABTestService.php       # Variant assignment, significance testing
│   ├── ScheduleHelper.php      # Timezone-aware scheduling
│   ├── CssSanitiser.php        # XSS prevention for custom CSS
│   ├── TrustSiteService.php    # Host-to-campaign lookup
│   └── ReviewImportService.php # CSV review import
├── Controllers/
│   ├── PixelController.php     # Public config endpoint
│   ├── Web/WidgetController.php # Public widget/tracking endpoints
│   └── Api/                    # Authenticated management endpoints
├── Middleware/
│   ├── WidgetCors.php          # CORS for cross-origin widget requests
│   └── ThrottleConversions.php # Per-campaign rate limiting
├── Jobs/
│   └── DispatchConversionWebhook.php
├── Console/Commands/
│   └── CleanupEventsCommand.php
├── View/Modal/                 # Livewire components (Hub/, Admin/)
├── Mcp/Tools/                  # MCP tool handlers
└── Migrations/                 # Database schema
```

## Data Model

```
┌─────────────────┐
│    Campaign     │
├─────────────────┤
│ id              │
│ workspace_id    │──────┐
│ user_id         │      │
│ name            │      │
│ host            │      │
│ pixel_key (UUID)│      │
│ timezone        │      │
│ webhook_url     │      │
│ is_enabled      │      │
└────────┬────────┘      │
         │               │
    ┌────┴────┐          │
    │         │          │
┌───▼───┐ ┌───▼───┐      │
│Notif- │ │Review │      │
│ication│ │       │      │
├───────┤ ├───────┤      │
│type   │ │rating │      │
│content│ │content│      │
│style  │ │source │      │
│custom │ │       │      │
│_css   │ └───────┘      │
│ab_test│                │
│_id    │                │
└───┬───┘                │
    │                    │
┌───▼───┐         ┌──────▼──────┐
│ Event │         │ Conversion  │
├───────┤         ├─────────────┤
│type   │         │type         │
│visitor│         │name         │
│_hash  │         │attributed_  │
│page   │         │notification │
│_url   │         │_id          │
└───────┘         │visitor_hash │
                  └─────────────┘
```

### Key Relationships

- **Campaign** → has many **Notification**, **Conversion**, **Event**, **Review**, **CollectedData**
- **Notification** → belongs to **Campaign**, has many **Event**, **CollectedData**
- **Conversion** → belongs to **Campaign**, optionally attributed to **Notification**
- A/B tests link notifications via shared `ab_test_id` (UUID)

## Event-Driven Module Loading

The module uses the core-php event system for lazy loading:

```php
class Boot extends ServiceProvider
{
    public static array $listens = [
        AdminPanelBooting::class => 'onAdminPanel',
        WebRoutesRegistering::class => 'onWebRoutes',
        ApiRoutesRegistering::class => 'onApiRoutes',
    ];
}
```

This ensures the module only loads when needed, reducing boot overhead.

## Request Flow

### Widget Display Flow

```
Customer Website                    Trust API                         Database
      │                                │                                 │
      │  <script data-pixel-key>       │                                 │
      │────────────────────────────────>│                                 │
      │                                │  GET /api/trust/widgets         │
      │                                │  ?pixel_key=xxx                 │
      │                                │  &visitor_id=yyy                │
      │                                │  &visitor_tz=Europe/London      │
      │                                │─────────────────────────────────>│
      │                                │        Campaign + Notifications  │
      │                                │<─────────────────────────────────│
      │                                │                                  │
      │                                │  A/B variant selection           │
      │                                │  Schedule filtering              │
      │                                │  Dynamic data (live count, etc)  │
      │                                │                                  │
      │  { notifications: [...] }      │                                  │
      │<───────────────────────────────│                                  │
      │                                │                                  │
      │  (Render widget)               │                                  │
      │                                │                                  │
      │  POST /api/trust/track         │                                  │
      │  { type: 'impression' }        │                                  │
      │───────────────────────────────>│                                  │
      │                                │  Update notification.impressions │
      │                                │  Create Event record             │
      │                                │─────────────────────────────────>│
```

### Conversion Attribution Flow

```
1. Visitor views widget → Event(type='impression', visitor_hash=hash(IP))
2. Visitor clicks widget → Event(type='click', visitor_hash=hash(IP))
3. Visitor converts      → POST /api/trust/conversion
4. Attribution lookup:
   - Find most recent click event for visitor_hash (24h window)
   - If found: attribute to that notification (click > impression)
   - Else: find most recent impression event
   - If found: attribute to that notification
   - Else: unattributed conversion
5. Create Conversion with attributed_notification_id
6. If campaign.webhook_url: dispatch DispatchConversionWebhook job
```

## A/B Testing Architecture

### Variant Assignment

```php
// Deterministic assignment using consistent hashing
$hash = crc32($visitorId . $abTestId) % 100;
$variant = $hash < $trafficSplit ? $variant : $control;
```

This ensures:
- Same visitor always sees same variant
- No session storage required
- Stateless operation

### Statistical Significance

Uses two-proportion z-test:
- Minimum sample size: 100 impressions per variant
- Default confidence level: 95%
- Calculates p-value, lift, and winner recommendation

## Scheduling System

Three timezone modes for widget display:

1. **Campaign timezone** - Check against campaign's configured timezone
2. **Visitor timezone** - Check against visitor's browser timezone (from JS)
3. **Specific timezone** - Check against explicit IANA timezone

Schedule restrictions:
- Date range (start_date, end_date)
- Time of day (schedule_time_start, schedule_time_end)
- Day of week (schedule_days array)

## Security Considerations

### Rate Limiting

```
/api/trust/widgets    → 300/min (general)
/api/trust/conversion → 30/min per IP per campaign
                      → 100/min per campaign (all IPs)
/api/trust/collect    → 30/min per IP
```

### CSS Sanitisation

Custom CSS goes through `CssSanitiser`:
1. Remove dangerous patterns (javascript:, expression(), etc.)
2. Validate URL schemes (only https://, /, #)
3. Scope all selectors to widget ID prefix
4. Limit total length (10KB)

### Privacy

- IP addresses hashed for visitor tracking (`PrivacyHelper::hashIp()`)
- Conversion names anonymised for display (first name + initial)
- GeoIP for country only, not precise location

## Caching Strategy

| Cache Key | TTL | Purpose |
|-----------|-----|---------|
| `trust:pixel_key:{host}` | 1 hour | Host → pixel_key lookup |
| `trust:campaign:{host}` | 1 hour | Host → campaign lookup |
| `trust_config:{pixel_key}` | 5 min | Pixel config for JS SDK |
| `socialproof:live:{campaign}` | 1 min | Live visitor count |
| `socialproof:latest:{campaign}` | 5 min | Latest conversion |
| `socialproof:feed:{campaign}` | 5 min | Conversions feed |
| `socialproof:conversions_count:{campaign}` | 15 min | Daily conversion count |

Cache invalidation:
- Notification create/update/delete clears campaign's socialproof:* keys
- Campaign delete clears all associated cache keys

## Multi-Tenancy

Uses `BelongsToWorkspace` and `BelongsToNamespace` traits:
- Campaign scoped to workspace_id
- Notification scoped to workspace_id via campaign
- User authorization via user_id ownership check

## Error Handling

Public endpoints return consistent 404 for:
- Invalid pixel_key format
- Non-existent pixel_key
- Disabled campaign

This prevents enumeration attacks.

## Dependencies

Internal:
- `core-php` - Event system, base framework
- `core-tenant` - Workspace, User models, multi-tenancy traits

External:
- Laravel HTTP client (webhooks)
- Carbon (timezone handling)
- Laravel cache (Redis/file)

## Performance Considerations

1. **Event table growth** - High-volume, use `trust:cleanup-events` command
2. **Denormalised counters** - impressions/clicks on Notification avoid COUNT queries
3. **Eager loading** - Widget queries should eager load campaign for timezone
4. **Index coverage** - Key queries have composite indexes

## Extension Points

1. **New widget types** - Add to `config.php` and handle in `TrustService::prepareNotificationData()`
2. **Custom attribution** - Override `TrustService::findAttribution()`
3. **Webhook events** - Extend `DispatchConversionWebhook` for new event types
4. **Review sources** - Add to `Review::SOURCE_*` constants, implement in `ReviewImportService`
