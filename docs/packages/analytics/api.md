---
title: API Reference
description: REST API documentation for core-analytics
updated: 2026-01-29
---

# API Reference

This document describes the REST API for the core-analytics package.

## Base URL

All authenticated endpoints are prefixed with `/analytics`.

## Authentication

### Public Endpoints

Tracking endpoints do not require authentication but need a valid `pixel_key`:

```
POST /analytics/track/{pixelKey}
POST /analytics/track
GET  /analytics/pixel?key={pixelKey}
POST /analytics/heartbeat
POST /analytics/leave
POST /analytics/event
```

### Authenticated Endpoints

Management endpoints require authentication via the Core PHP Framework auth system:

```
Authorization: Bearer {token}
```

## Rate Limiting

Public tracking endpoints: 10,000 requests/minute (shared pool)

## Tracking Endpoints

### Track Pageview (POST)

Track a pageview or event.

**Endpoint:** `POST /analytics/track/{pixelKey}`

**Response:** 1x1 transparent GIF (always returns success to avoid exposing errors)

---

### Track Pageview (JSON)

Track with full JSON payload.

**Endpoint:** `POST /analytics/track`

**Request Body:**
```json
{
  "key": "uuid-pixel-key",
  "type": "pageview",
  "visitor_id": "visitor-uuid",
  "session_id": "session-uuid",
  "path": "/page/path",
  "title": "Page Title",
  "referrer": "https://referrer.com",
  "utm_source": "google",
  "utm_medium": "cpc",
  "utm_campaign": "summer-sale",
  "screen_width": 1920,
  "screen_height": 1080,
  "language": "en-GB"
}
```

**Response:**
```json
{
  "ok": true,
  "event_id": 12345,
  "visitor_id": "visitor-uuid",
  "session_id": "session-uuid"
}
```

---

### Track Pixel (GET)

Lightweight tracking for noscript fallback.

**Endpoint:** `GET /analytics/pixel?key={pixelKey}&p={path}&t={title}&r={referrer}`

**Response:** 1x1 transparent GIF

---

### Heartbeat

Update time on page and scroll depth.

**Endpoint:** `POST /analytics/heartbeat`

**Request Body:**
```json
{
  "event_id": 12345,
  "time_on_page": 120,
  "scroll_depth": 75,
  "session_id": "session-uuid"
}
```

**Response:**
```json
{
  "ok": true
}
```

---

### Leave

End session on page unload (sendBeacon).

**Endpoint:** `POST /analytics/leave`

**Request Body:**
```json
{
  "session_id": "session-uuid"
}
```

---

### Custom Event

Track a custom event.

**Endpoint:** `POST /analytics/event`

**Request Body:**
```json
{
  "key": "uuid-pixel-key",
  "name": "button_click",
  "visitor_id": "visitor-uuid",
  "session_id": "session-uuid",
  "properties": {
    "button_id": "signup",
    "variant": "blue"
  }
}
```

---

## Website Management

### List Websites

**Endpoint:** `GET /analytics/websites`

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "My Website",
      "host": "example.com",
      "pixel_key": "uuid",
      "tracking_enabled": true,
      "is_enabled": true,
      "created_at": "2026-01-01T00:00:00Z"
    }
  ]
}
```

---

### Create Website

**Endpoint:** `POST /analytics/websites`

**Request Body:**
```json
{
  "name": "My Website",
  "host": "example.com",
  "tracking_type": "lightweight",
  "channel_type": "website"
}
```

---

### Get Website

**Endpoint:** `GET /analytics/websites/{id}`

---

### Update Website

**Endpoint:** `PUT /analytics/websites/{id}`

---

### Delete Website

**Endpoint:** `DELETE /analytics/websites/{id}`

---

## Statistics

### Get Website Stats

**Endpoint:** `GET /analytics/websites/{id}/stats`

**Query Parameters:**
- `start_date` - ISO 8601 date (default: 7 days ago)
- `end_date` - ISO 8601 date (default: now)
- `timezone` - Timezone string (default: UTC)

**Response:**
```json
{
  "total_pageviews": 12543,
  "unique_visitors": 3421,
  "bounce_rate": 42.5,
  "avg_session_duration": 185,
  "period": {
    "start": "2026-01-22T00:00:00Z",
    "end": "2026-01-29T00:00:00Z"
  }
}
```

---

### Get Time Series

**Endpoint:** `GET /analytics/websites/{id}/timeseries`

**Query Parameters:**
- `metric` - `pageviews`, `visitors`, `sessions`
- `start_date` - ISO 8601 date
- `end_date` - ISO 8601 date
- `interval` - `day`, `week`, `month`

**Response:**
```json
{
  "2026-01-22": 1543,
  "2026-01-23": 1621,
  "2026-01-24": 1489
}
```

---

### Get Real-time Stats

**Endpoint:** `GET /analytics/websites/{id}/realtime`

**Response:**
```json
{
  "active_visitors": 23,
  "active_pages": [
    {"path": "/", "viewers": 12},
    {"path": "/pricing", "viewers": 8}
  ],
  "locations": [
    {"country_code": "GB", "visitors": 15},
    {"country_code": "US", "visitors": 8}
  ],
  "timestamp": "2026-01-29T12:00:00Z"
}
```

---

## Goals

### List Goals

**Endpoint:** `GET /analytics/websites/{id}/goals`

---

### Create Goal

**Endpoint:** `POST /analytics/websites/{id}/goals`

**Request Body:**
```json
{
  "name": "Signup Completion",
  "type": "pageview",
  "match_type": "equals",
  "match_value": "/signup/complete"
}
```

**Goal Types:**
- `pageview` - URL match
- `event` - Custom event match
- `duration` - Session duration threshold
- `pages_per_session` - Pageview count threshold

**Match Types (for pageview):**
- `equals` - Exact match
- `contains` - Substring match
- `starts_with` - Prefix match
- `ends_with` - Suffix match
- `regex` - Regular expression

---

### Get Goal Conversions

**Endpoint:** `GET /analytics/websites/{id}/goals/{goalId}/conversions`

**Query Parameters:**
- `start_date`
- `end_date`
- `limit`

---

### Get Goal Stats

**Endpoint:** `GET /analytics/websites/{id}/goals/{goalId}/conversions/stats`

**Response:**
```json
{
  "total_conversions": 342,
  "conversion_rate": 4.2,
  "total_value": 15230.50,
  "average_value": 44.53
}
```

---

## Funnels

### List Funnels

**Endpoint:** `GET /analytics/websites/{id}/funnels`

---

### Create Funnel

**Endpoint:** `POST /analytics/websites/{id}/funnels`

**Request Body:**
```json
{
  "name": "Checkout Funnel",
  "is_strict": false,
  "window_hours": 24
}
```

---

### Get Funnel Analysis

**Endpoint:** `GET /analytics/websites/{id}/funnels/{funnelId}/analysis`

**Query Parameters:**
- `start_date`
- `end_date`

**Response:**
```json
{
  "funnel_id": 1,
  "funnel_name": "Checkout Funnel",
  "summary": {
    "total_entrants": 1000,
    "completed": 120,
    "completion_rate": 12.0,
    "avg_completion_time": 540,
    "total_steps": 4
  },
  "steps": [
    {
      "step_id": 1,
      "name": "Add to Cart",
      "visitors": 1000,
      "conversion_rate": 100.0,
      "drop_off": 0,
      "drop_off_rate": 0
    },
    {
      "step_id": 2,
      "name": "View Cart",
      "visitors": 650,
      "conversion_rate": 65.0,
      "drop_off": 350,
      "drop_off_rate": 35.0
    }
  ]
}
```

---

### Add Funnel Step

**Endpoint:** `POST /analytics/websites/{id}/funnels/{funnelId}/steps`

**Request Body:**
```json
{
  "name": "Add to Cart",
  "match_type": "pageview",
  "match_value": "/cart/add",
  "is_optional": false
}
```

---

## A/B Experiments

### List Experiments

**Endpoint:** `GET /analytics/websites/{id}/experiments`

---

### Create Experiment

**Endpoint:** `POST /analytics/websites/{id}/experiments`

**Request Body:**
```json
{
  "name": "Button Colour Test",
  "goal_type": "pageview",
  "goal_value": "/signup/complete",
  "traffic_percentage": 100
}
```

---

### Start Experiment

**Endpoint:** `POST /analytics/websites/{id}/experiments/{experimentId}/start`

---

### Pause Experiment

**Endpoint:** `POST /analytics/websites/{id}/experiments/{experimentId}/pause`

---

### Stop Experiment

**Endpoint:** `POST /analytics/websites/{id}/experiments/{experimentId}/stop`

---

### Get Results

**Endpoint:** `GET /analytics/websites/{id}/experiments/{experimentId}/results`

**Response:**
```json
{
  "experiment": {
    "id": 1,
    "name": "Button Colour Test",
    "status": "running"
  },
  "metrics": {
    "total_visitors": 5000,
    "total_conversions": 250,
    "overall_conversion_rate": 5.0
  },
  "analysis": {
    "is_significant": true,
    "confidence": 95.5,
    "p_value": 0.045,
    "winner": 2,
    "recommendation": "\"Blue Button\" is the winner with 95% confidence (+12% lift)",
    "results": {
      "1": {
        "name": "Control",
        "visitors": 2500,
        "conversions": 100,
        "conversion_rate": 4.0,
        "is_control": true
      },
      "2": {
        "name": "Blue Button",
        "visitors": 2500,
        "conversions": 150,
        "conversion_rate": 6.0,
        "lift": 50.0,
        "is_significant": true
      }
    }
  }
}
```

---

### Add Variant

**Endpoint:** `POST /analytics/websites/{id}/experiments/{experimentId}/variants`

**Request Body:**
```json
{
  "name": "Blue Button",
  "is_control": false,
  "weight": 50,
  "config": {
    "button_color": "#0066cc"
  }
}
```

---

### Get Variant (Public)

For client-side variant assignment.

**Endpoint:** `GET /analytics/experiment/variant`

**Query Parameters:**
- `experiment_id`
- `visitor_id`

**Response:**
```json
{
  "variant_id": 2,
  "variant_name": "Blue Button",
  "config": {
    "button_color": "#0066cc"
  }
}
```

---

## Heatmaps

### List Heatmaps

**Endpoint:** `GET /analytics/websites/{id}/heatmaps`

---

### Create Heatmap

**Endpoint:** `POST /analytics/websites/{id}/heatmaps`

**Request Body:**
```json
{
  "name": "Homepage Clicks",
  "url_pattern": "/",
  "type": "click"
}
```

**Heatmap Types:**
- `click` - Click positions
- `move` - Mouse movement
- `scroll` - Scroll depth

---

### Get Heatmap Data

**Endpoint:** `GET /analytics/websites/{id}/heatmaps/{heatmapId}/data`

**Response:**
```json
{
  "heatmap_id": 1,
  "data": [
    {"x": 500, "y": 300, "count": 45},
    {"x": 750, "y": 450, "count": 32}
  ],
  "viewport": {
    "width": 1920,
    "height": 1080
  }
}
```

---

## Session Replays

### List Replays

**Endpoint:** `GET /analytics/websites/{id}/replays`

**Query Parameters:**
- `limit`
- `device_type`
- `country_code`

---

### Get Replay

**Endpoint:** `GET /analytics/websites/{id}/replays/{replayId}`

---

### Get Playback Data

**Endpoint:** `GET /analytics/websites/{id}/replays/{replayId}/playback`

**Response:** rrweb-compatible event array

---

### Delete Replay

**Endpoint:** `DELETE /analytics/websites/{id}/replays/{replayId}`

---

## Bot Detection

### Get Bot Stats

**Endpoint:** `GET /analytics/websites/{id}/bots/stats`

**Response:**
```json
{
  "period": {
    "from": "2026-01-01",
    "to": "2026-01-29"
  },
  "totals": {
    "total_requests": 50000,
    "blocked_requests": 2500,
    "allowed_requests": 47500,
    "block_rate": 5.0
  },
  "bot_types": {
    "crawler": 1500,
    "scraper": 800,
    "headless": 200
  },
  "top_bots": {
    "Googlebot": 800,
    "Bingbot": 400,
    "Ahrefs": 300
  }
}
```

---

### List Detections

**Endpoint:** `GET /analytics/websites/{id}/bots/detections`

---

### List Rules

**Endpoint:** `GET /analytics/websites/{id}/bots/rules`

---

### Create Rule

**Endpoint:** `POST /analytics/websites/{id}/bots/rules`

**Request Body:**
```json
{
  "rule_type": "whitelist",
  "match_type": "ip",
  "match_value": "192.168.1.100",
  "description": "Office IP"
}
```

**Rule Types:**
- `whitelist` - Always allow
- `blacklist` - Always block

**Match Types:**
- `ip` - Exact IP match
- `ip_range` - CIDR range (e.g., `192.168.1.0/24`)
- `user_agent` - Substring match in User-Agent

---

## GDPR

### Export Visitor Data

**Endpoint:** `GET /analytics/gdpr/export/{visitorHash}`

**Response:** JSON file download with all visitor data

---

### Delete Visitor Data

**Endpoint:** `DELETE /analytics/gdpr/visitor/{visitorHash}`

**Response:**
```json
{
  "deleted_counts": {
    "events": 152,
    "sessions": 12,
    "pageviews": 145,
    "conversions": 3,
    "visitor": 1
  }
}
```

---

### Anonymise Visitor

**Endpoint:** `POST /analytics/gdpr/anonymise/{visitorHash}`

Preserves aggregate data while removing PII.

---

### Record Consent (Public)

**Endpoint:** `POST /analytics/gdpr/consent`

**Request Body:**
```json
{
  "visitor_id": "visitor-uuid",
  "pixel_key": "uuid-pixel-key"
}
```

---

### Withdraw Consent (Public)

**Endpoint:** `DELETE /analytics/gdpr/consent`

---

### Check Consent Status (Public)

**Endpoint:** `GET /analytics/gdpr/consent/status?visitor_id={id}&pixel_key={key}`

---

## Email Reports

### List Reports

**Endpoint:** `GET /analytics/websites/{id}/email-reports`

---

### Create Report

**Endpoint:** `POST /analytics/websites/{id}/email-reports`

**Request Body:**
```json
{
  "name": "Weekly Summary",
  "frequency": "weekly",
  "recipients": ["user@example.com"],
  "metrics": ["pageviews", "visitors", "bounce_rate"]
}
```

**Frequencies:**
- `daily`
- `weekly`
- `monthly`

---

### Preview Report

**Endpoint:** `POST /analytics/websites/{id}/email-reports/{reportId}/preview`

---

### Send Report Now

**Endpoint:** `POST /analytics/websites/{id}/email-reports/{reportId}/send`

---

## Error Responses

### Standard Error Format

```json
{
  "ok": false,
  "error": "Error message",
  "code": "ERROR_CODE"
}
```

### Common Error Codes

| HTTP Status | Code | Description |
|-------------|------|-------------|
| 400 | `VALIDATION_ERROR` | Invalid request parameters |
| 401 | `UNAUTHENTICATED` | Missing or invalid authentication |
| 403 | `FORBIDDEN` | Insufficient permissions |
| 404 | `NOT_FOUND` | Resource not found |
| 429 | `RATE_LIMITED` | Too many requests |
| 500 | `SERVER_ERROR` | Internal server error |

---

## SDK Integration

### JavaScript Tracker

```html
<script defer data-key="{pixelKey}" src="/js/analytics.js"></script>
```

### Server-Side Tracking

```php
use Core\Mod\Analytics\Services\AnalyticsTrackingService;

$tracking = app(AnalyticsTrackingService::class);
$tracking->track($website, [
    'type' => 'pageview',
    'path' => '/api/endpoint',
    'visitor_id' => $request->header('X-Visitor-ID'),
], $request);
```
