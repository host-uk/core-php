---
title: Architecture
description: Technical architecture of the core-notify Web Push Notifications package
updated: 2026-01-29
---

# Architecture

The `core-notify` package provides a complete Web Push notification system for the Host UK platform. It enables users to add push notification capabilities to their websites, collect subscribers, create campaigns, and build automated notification flows.

## Package Structure

```
core-notify/
├── Boot.php                 # Module service provider
├── Service/Boot.php         # Platform service layer
├── config.php               # Configuration
├── Controllers/
│   ├── SubscriptionController.php   # Public endpoints (subscribe, click, etc.)
│   ├── ServiceWorkerController.php  # Service worker delivery
│   └── Api/                         # Authenticated API controllers
├── Services/
│   ├── NotifyService.php            # Push delivery engine
│   ├── NotifyABTestService.php      # A/B testing logic
│   ├── NotifyWebhookService.php     # Outbound webhooks
│   └── NotifySiteService.php        # Site/pixel configuration
├── Models/                  # Eloquent models (11 total)
├── Jobs/                    # Queue jobs
├── Events/Webhook/          # Webhook event classes
├── Mcp/Tools/               # MCP agent tools
├── View/Modal/              # Livewire components
└── Migrations/              # Database migrations
```

## Two-Layer Design

### Module Layer (`Boot.php`)

The module layer is the core engine that handles:

- Route registration (web, API, admin)
- Livewire component registration
- Migration loading
- Rate limiter configuration
- Artisan command registration

Lifecycle events listened to:
- `AdminPanelBooting` - Admin panel routes and components
- `ApiRoutesRegistering` - REST API routes
- `WebRoutesRegistering` - Public web routes

### Service Layer (`Service/Boot.php`)

The service layer handles platform integration:

- Service definition for platform_services table
- Admin menu registration
- Entitlement code (`core.srv.notify`)
- Version tracking

## Data Model

### Core Entities

```
PushWebsite (notify_websites)
├── PushSubscriber (notify_subscribers)      # Browser subscriptions
├── PushCampaign (notify_campaigns)          # One-time/scheduled sends
│   └── PushCampaignLog (notify_campaign_logs)
├── PushSegment (notify_segments)            # Subscriber targeting
├── PushFlow (notify_flows)                  # Automated triggers
│   └── PushFlowExecution (notify_flow_executions)
├── PushTemplate (notify_templates)          # Reusable content
├── NotifyWebhook (notify_webhooks)          # Outbound webhooks
│   └── NotifyWebhookDelivery (notify_webhook_deliveries)
├── NotifyDailyStat (notify_daily_stats)     # Aggregated metrics
└── FailedNotification (notify_failed_notifications) # Dead letter queue
```

### Relationships

- A **Website** belongs to a Workspace and User
- A **Subscriber** belongs to a Website (identified by endpoint hash)
- A **Campaign** belongs to a Website and optionally a Segment
- A **Flow** belongs to a Website and optionally a Segment
- A **Webhook** belongs to a Website and tracks multiple deliveries

### Multi-Tenancy

Models use the `BelongsToNamespace` trait for workspace scoping:
- `PushWebsite`
- `PushCampaign`
- `PushFlow`
- `PushSegment`
- `NotifyWebhook`

## Event Flow

### Subscription Flow

```
User accepts prompt on website
       │
       ▼
JavaScript SDK calls POST /api/notify/subscribe
       │
       ▼
SubscriptionController::subscribe()
       │
       ├── Validate pixel_key and subscription data
       ├── Parse device/geo info from headers
       ├── Find or create PushSubscriber
       ├── Store custom parameters if provided
       │
       ▼
NotifyService::sendWelcomeNotification()  (if enabled)
       │
       ▼
NotifyService::triggerFlowsForEvent('on_subscribe')
       │
       ▼
NotifyWebhookService::dispatch(SubscriberCreatedEvent)
```

### Campaign Send Flow

```
User schedules campaign (or API call)
       │
       ▼
PushCampaign::schedule() - Status: 'scheduled'
       │
       ▼
SendCampaign job dispatched to 'push' queue
       │
       ▼
NotifyService::sendCampaign()
       │
       ├── initForWebsite() - Load VAPID keys
       ├── Start DB transaction
       ├── campaign->startSending() - Status: 'sending'
       │
       ├── For A/B tests:
       │   ├── Determine control/variant split
       │   └── startAbTest() on all variants
       │
       ├── Query target subscribers (segment or all)
       ├── Chunk in batches of 100
       │
       │   For each subscriber:
       │   ├── assignVariant() if A/B test
       │   ├── personalisePayload() - Replace {{placeholders}}
       │   ├── Create PushCampaignLog entry
       │   └── Queue notification with WebPush library
       │
       ▼
flushAndProcessResults()
       │
       ├── Process WebPush MessageSentReport
       ├── Update subscriber stats (sent, clicks)
       ├── Update campaign stats (delivered, failed)
       ├── Handle expired subscriptions (unsubscribe)
       ├── Record failures to dead letter queue
       └── Dispatch webhook events
       │
       ▼
campaign->markAsSent() - Status: 'sent'
       │
       ▼
NotifyWebhookService::dispatch(CampaignCompletedEvent)
```

### Flow Execution

```
Event occurs (subscribe, visit, custom)
       │
       ▼
NotifyService::triggerFlowsForEvent()
       │
       ├── Query enabled flows for trigger type
       ├── For each flow:
       │   ├── Check flow->appliesToSubscriber() (segment match)
       │   ├── Check flow->checkTriggerConditions() (URL pattern, params)
       │   └── Check for existing pending execution (dedupe)
       │
       ▼
PushFlowExecution::createForTrigger()
       │
       ├── If wait_time == 0: Dispatch immediately
       └── If wait_time > 0: Schedule for later
       │
       ▼
SendFlowNotification job
       │
       ▼
NotifyService::sendFlowNotification()
       │
       ├── Check subscriber still subscribed
       ├── Send via WebPush
       └── Update execution status
```

## A/B Testing

Campaigns support A/B testing with deterministic variant assignment:

### Variant Assignment

```php
// Hash-based assignment ensures same subscriber always gets same variant
$hash = crc32($subscriberId . $abTestId) % 100;
return $hash < $trafficSplit ? $variant : $control;
```

### Test Lifecycle

1. Create campaign (becomes control)
2. Call `createVariant()` to create variant B
3. Schedule campaign - both variants sent proportionally
4. View stats via `NotifyABTestService::getTestResults()`
5. Check significance via `calculateStatisticalSignificance()`
6. Declare winner via `declareWinner()` or `applyWinner()`

### Statistical Analysis

Uses two-proportion z-test:
- Minimum sample size: 100 per variant
- Default confidence level: 95%
- P-value calculation with pooled proportion
- Lift percentage and winner determination

## Webhook System

Outbound webhooks notify external systems of events:

### Available Events

| Event | Trigger |
|-------|---------|
| `subscriber.created` | New subscription |
| `subscriber.updated` | Subscriber data changed |
| `subscriber.deleted` | Unsubscribe or expired |
| `campaign.sent` | Campaign starts sending |
| `campaign.completed` | Campaign finishes |
| `notification.delivered` | Successful delivery |
| `notification.clicked` | User clicked notification |
| `notification.failed` | Delivery failed |

### Delivery

- HMAC-SHA256 signing with webhook secret
- 10-second timeout
- Circuit breaker: 5 consecutive failures disables webhook
- Manual retry available for failed deliveries

## Rate Limiting

Defined in `Boot::configureRateLimiting()`:

| Limiter | Limit | Purpose |
|---------|-------|---------|
| `notify-subscribe` | 5/min/IP | Prevent mass subscription |
| `notify-unsubscribe` | 10/min/IP | Allow unsubscribe availability |
| `notify-vapid` | 30/min/IP | Read-only key retrieval |
| `notify-click` | 60/min/IP | Click tracking |
| `notify-tracking` | 30/min/IP | Visit/event tracking |
| `notify-gdpr` | 3/min/IP | Data export (sensitive) |
| `notify-api` | Per API key | Programmatic API |

## Queues

| Queue | Job | Purpose |
|-------|-----|---------|
| `push` | SendCampaign | Campaign delivery |
| `push` | SendFlowNotification | Flow delivery |
| `default` | ProcessScheduledFlows | Check for due flows |
| `default` | AggregateDailyStats | Daily metrics rollup |

## Configuration

Key configuration options in `config.php`:

```php
return [
    'vapid_subject' => 'mailto:...',
    'service_worker_path' => '/push-sw.js',
    'defaults' => [
        'ttl' => 86400,        // 24 hours
        'urgency' => 'normal', // low, normal, high
        'icon' => '...',
        'badge' => '...',
    ],
    'widget' => [
        'position' => 'bottom-right',
        'delay' => 3,
        'style' => 'bell',
        // ...
    ],
    'limits' => [
        'max_subscribers_per_website' => 100000,
        'max_campaigns_per_day' => 10,
        'max_notifications_per_subscriber_per_day' => 5,
        'batch_size' => 100,
        'max_payload_bytes' => 4096,
    ],
    'cleanup' => [
        'unsubscribed_retention_days' => 30,
        'campaign_log_retention_days' => 90,
        'flow_execution_retention_days' => 30,
    ],
];
```

## MCP Integration

The `NotifyTools` class provides agent access:

| Action | Description |
|--------|-------------|
| `list_websites` | List push websites with subscriber counts |
| `list_campaigns` | List campaigns for a website |
| `get_campaign` | Get campaign details and stats |
| `create_campaign` | Create a draft campaign |
| `subscriber_stats` | Get subscriber breakdown |

## External Dependencies

- **minishlink/web-push** - Web Push protocol implementation
- **spatie/laravel-activitylog** - Audit logging
- **Core\Headers\DetectDevice** - User agent parsing
- **Mod\Analytics\GeoIpService** - Geolocation lookup
