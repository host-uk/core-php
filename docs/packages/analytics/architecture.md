---
title: Architecture
description: Technical architecture of core-analytics
updated: 2026-01-29
---

# Architecture

This document describes the technical architecture of the core-analytics package, a privacy-focused website analytics module for the Core PHP Framework.

## Overview

core-analytics is a Laravel package providing website analytics with:

- Privacy-first design (IP anonymisation, DNT respect, GDPR compliance)
- Real-time visitor tracking via Redis
- Session replays and heatmaps
- A/B testing with statistical significance
- Funnel analysis
- Bot detection and filtering
- Multi-tenant workspace isolation

## Package Structure

```
core-analytics/
├── Boot.php                    # Service provider, event registration
├── config.php                  # Configuration defaults
├── Controllers/
│   ├── PixelController.php     # Public tracking endpoints
│   └── Api/                    # Authenticated API controllers
├── Services/
│   ├── AnalyticsService.php         # Core stats/aggregation
│   ├── AnalyticsTrackingService.php # Event tracking
│   ├── BotDetectionService.php      # Bot scoring
│   ├── FunnelService.php            # Funnel analysis
│   ├── GdprService.php              # Privacy compliance
│   ├── GeoIpService.php             # Geolocation
│   ├── RealtimeAnalyticsService.php # Redis-based realtime
│   ├── SessionReplayStorageService.php
│   └── AnalyticsExperimentService.php # A/B testing
├── Jobs/
│   ├── ProcessTrackingEvent.php     # Main event processor
│   ├── ProcessPageview.php
│   ├── ProcessHeatmapEvent.php
│   └── ProcessSessionReplay.php
├── Models/                     # Eloquent models
├── Migrations/                 # Database migrations
├── Console/Commands/           # Artisan commands
├── Mcp/Tools/                  # MCP tool handlers
├── View/                       # Blade/Livewire components
└── routes/                     # Route definitions
```

## Event-Driven Module Loading

The package follows the Core PHP Framework event-driven pattern:

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

Handlers are only instantiated when their events fire, enabling lazy loading.

## Data Flow

### Tracking Flow

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   JS Tracker    │────>│  PixelController │────>│ ProcessTracking │
│   (browser)     │     │   (validation)   │     │   Event (queue) │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                          │
                        ┌─────────────────────────────────┼─────────────────────────────────┐
                        │                                 │                                 │
                        v                                 v                                 v
                ┌───────────────┐              ┌──────────────────┐              ┌─────────────────┐
                │   Bot Check   │              │  Entitlement     │              │   GeoIP         │
                │   (scoring)   │              │   Check          │              │   Lookup        │
                └───────┬───────┘              └────────┬─────────┘              └────────┬────────┘
                        │                               │                                  │
                        └───────────────────────────────┼──────────────────────────────────┘
                                                        │
                                                        v
                                              ┌──────────────────┐
                                              │  Database Write  │
                                              │  (visitor,       │
                                              │   session,       │
                                              │   event)         │
                                              └────────┬─────────┘
                                                       │
                        ┌──────────────────────────────┼──────────────────────────────────┐
                        │                              │                                  │
                        v                              v                                  v
                ┌───────────────┐            ┌──────────────────┐              ┌─────────────────┐
                │  Goal Check   │            │  Cache Invalidate│              │  Realtime       │
                │  (conversion) │            │  (stats cache)   │              │  Broadcast      │
                └───────────────┘            └──────────────────┘              └─────────────────┘
```

### Statistics Query Flow

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Dashboard     │────>│  Stats Controller│────>│ AnalyticsService│
│   (request)     │     │   (auth, scope)  │     │   (query/cache) │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                          │
                                                          v
                                                 ┌──────────────────┐
                                                 │  Cache Check     │
                                                 │  (5 min TTL)     │
                                                 └────────┬─────────┘
                                                          │
                                          ┌───────────────┴───────────────┐
                                          │                               │
                                          v                               v
                                  ┌───────────────┐              ┌───────────────┐
                                  │  Cache Hit    │              │  Cache Miss   │
                                  │  (return)     │              │  (query DB)   │
                                  └───────────────┘              └───────┬───────┘
                                                                         │
                                                                         v
                                                                ┌───────────────┐
                                                                │  Store Cache  │
                                                                │  (return)     │
                                                                └───────────────┘
```

## Database Schema

### Core Tables

| Table | Purpose | Volume |
|-------|---------|--------|
| `analytics_websites` | Website/property configuration | Low |
| `analytics_visitors` | Unique visitor records | High |
| `analytics_sessions` | Session aggregation | High |
| `analytics_events` | Individual events (pageviews, clicks) | Very High |
| `analytics_pageviews` | Denormalised pageview data | Very High |
| `analytics_goals` | Goal definitions | Low |
| `analytics_goal_conversions` | Goal conversion records | Medium |
| `analytics_daily_stats` | Pre-aggregated daily statistics | Low |

### Feature Tables

| Table | Purpose |
|-------|---------|
| `analytics_heatmaps` | Heatmap configuration |
| `analytics_heatmap_events` | Click/scroll/move coordinates |
| `analytics_session_replays` | Session replay metadata |
| `analytics_funnels` | Funnel definitions |
| `analytics_funnel_steps` | Funnel step configuration |
| `analytics_funnel_conversions` | Funnel progress tracking |
| `analytics_experiments` | A/B test configuration |
| `analytics_variants` | Experiment variant definitions |
| `analytics_experiment_visitors` | Variant assignments |
| `analytics_bot_detections` | Bot detection logs |
| `analytics_bot_rules` | Custom whitelist/blacklist |
| `analytics_bot_ip_cache` | IP reputation cache |
| `analytics_email_reports` | Scheduled report config |
| `analytics_email_report_logs` | Report send history |

### Indexes

Key indexes for query performance:

```sql
-- Website + date range queries
INDEX (website_id, created_at)
INDEX (website_id, started_at)
INDEX (website_id, last_seen_at)

-- Visitor/session lookups
UNIQUE (website_id, visitor_uuid)
UNIQUE (website_id, session_uuid)
INDEX (visitor_id)
INDEX (session_id)

-- Path analysis
INDEX (website_id, path, created_at)
```

## Multi-Tenancy

All models use the `BelongsToWorkspace` trait from core-tenant:

```php
class AnalyticsWebsite extends Model
{
    use BelongsToWorkspace;
    // ...
}
```

This provides:
- Automatic `workspace_id` assignment on create
- Global scope filtering to current workspace
- `MissingWorkspaceContextException` safety

## Caching Strategy

### Cache Keys

```
analytics:stats:{website_id}:{start}:{end}
analytics:timeseries:{website_id}:{metric}:{start}:{end}:{interval}
analytics:top_pages:{website_id}:{start}:{end}:{limit}
analytics:traffic_sources:{website_id}:{start}:{end}
analytics:geo:{website_id}:{start}:{end}
analytics_website:{pixel_key}           # Website lookup cache (1 hour)
analytics_config:{pixel_key}            # Config cache (5 minutes)
bot_rules:{website_id}                  # Bot rules cache (5 minutes)
```

### Invalidation

Cache invalidation occurs on:
- New tracking event (debounced, selective invalidation)
- Manual invalidation via `AnalyticsService::invalidateCache()`
- TTL expiration (5 minutes for stats)

## Queue Architecture

### Queues

| Queue | Purpose | Workers |
|-------|---------|---------|
| `analytics-tracking` | High-priority tracking events | 2-4 |
| `analytics` | Heatmaps, replays, cleanup | 1-2 |
| `default` | Email reports, low-priority | 1 |

### Job Configuration

```php
class ProcessTrackingEvent implements ShouldQueue
{
    public int $tries = 3;
    public int $timeout = 30;
}
```

## Real-Time Analytics

Uses Redis sorted sets for 5-minute sliding window:

```
analytics:realtime:visitors:{website_id}           # Sorted set: visitor_id -> timestamp
analytics:realtime:visitor_page:{website_id}:{id} # String: current path
analytics:realtime:visitor_country:{website_id}:{id}
```

### Broadcast Throttling

Updates are throttled to 2-second intervals to prevent flooding WebSocket channels during high traffic.

## Bot Detection

### Scoring Algorithm

Signals are weighted (sum = 100):

| Signal | Weight | Description |
|--------|--------|-------------|
| User-Agent | 35% | Bot patterns, headless browsers, HTTP libraries |
| Headers | 20% | Missing Accept/Accept-Language, automation indicators |
| IP Reputation | 15% | Datacenter IPs, known crawler ranges |
| Behaviour | 20% | JS indicators, screen dimensions, timing |
| Custom Rules | 10% | Whitelist/blacklist matches |

### Thresholds

- `threshold` (50): Score >= threshold = classified as bot
- `block_threshold` (70): Score >= threshold = blocked from tracking
- `min_log_score` (30): Minimum score to log detection

### Legitimate Crawlers

Known search engine IPs (Google, Bing, etc.) receive a 30-point score reduction and are logged but not blocked.

## A/B Testing

### Variant Assignment

Uses deterministic hashing for consistent assignment:

```php
$hash = abs(crc32($visitorId . $experimentId)) % 100;
```

This ensures the same visitor always gets the same variant, even across sessions.

### Statistical Significance

Uses two-proportion z-test:

1. Calculate conversion rates (p1, p2)
2. Calculate pooled proportion
3. Calculate standard error
4. Compute z-score and p-value
5. Compare against confidence level (default 95%)

Minimum sample size per variant: 100 (configurable).

## Data Retention

Tier-based retention:

| Tier | Days |
|------|------|
| Free | 30 |
| Pro | 90 |
| Business | 365 |
| Enterprise | 3650 |

Cleanup via `analytics:cleanup` command:
1. Aggregate data into `analytics_daily_stats`
2. Delete raw events/sessions/pageviews
3. Clean orphaned visitors

## Privacy Features

### IP Anonymisation

Last octet zeroed by default:
```
192.168.1.123 -> 192.168.1.0
```

### Do Not Track

Respects DNT header when `analytics.privacy.respect_dnt` is enabled.

### GDPR Compliance

- `GdprService::exportVisitorData()` - Full data export
- `GdprService::deleteVisitorData()` - Complete deletion
- `GdprService::anonymiseVisitor()` - Preserve aggregates, remove PII
- Consent tracking per-visitor

## External Dependencies

### Required

- `host-uk/core` - Core PHP Framework
- Redis - Real-time analytics, caching
- Queue worker - Event processing

### Optional

- MaxMind GeoLite2 - IP geolocation (falls back to CDN headers)
- S3/compatible - Session replay storage (falls back to local)

## Configuration

Key configuration options in `config.php`:

```php
return [
    'session_replay' => [
        'disk' => 'local',      // or 's3'
        'expiry_days' => 90,
        'max_size' => 10 * 1024 * 1024,
    ],
    'bot_detection' => [
        'enabled' => true,
        'threshold' => 50,
        'block_threshold' => 70,
    ],
    'privacy' => [
        'anonymise_ip' => true,
        'respect_dnt' => true,
    ],
    'retention' => [
        'tiers' => [
            'free' => 30,
            'pro' => 90,
            'business' => 365,
            'enterprise' => 3650,
        ],
    ],
];
```

## Scaling Considerations

### High Volume Sites

For sites with >1M daily pageviews:

1. **Separate analytics database** - Isolate from application DB
2. **Read replicas** - Route stats queries to replicas
3. **Redis Cluster** - Scale real-time tracking
4. **Dedicated queue workers** - Scale event processing
5. **ClickHouse** - Consider columnar storage for aggregations

### Recommended Worker Configuration

```
# Low traffic (<100k/day)
php artisan queue:work --queue=analytics-tracking,analytics

# Medium traffic (100k-1M/day)
php artisan queue:work --queue=analytics-tracking --workers=2
php artisan queue:work --queue=analytics

# High traffic (>1M/day)
php artisan queue:work --queue=analytics-tracking --workers=4
php artisan queue:work --queue=analytics --workers=2
```
