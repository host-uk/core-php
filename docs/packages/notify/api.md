---
title: API Reference
description: REST API documentation for core-notify
updated: 2026-01-29
---

# API Reference

The core-notify package exposes three API surfaces:

1. **Public API** - Called by JavaScript SDK (no auth)
2. **Authenticated API** - Session-based for web UI
3. **Programmatic API** - API key-based for external integrations

## Public API

Base path: `/api/notify`

No authentication required. Identified by `pixel_key`.

### Subscribe

Register a push subscription.

```http
POST /api/notify/subscribe
Content-Type: application/json
```

**Request:**
```json
{
  "pixel_key": "uuid",
  "subscription": {
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "keys": {
      "p256dh": "base64-encoded-key",
      "auth": "base64-encoded-auth"
    }
  },
  "custom": {
    "user_id": "optional-external-id",
    "plan": "premium"
  }
}
```

**Response (200):**
```json
{
  "success": true,
  "subscriber_id": 123
}
```

**Rate limit:** 5 per minute per IP

### Unsubscribe

Remove a push subscription.

```http
POST /api/notify/unsubscribe
Content-Type: application/json
```

**Request:**
```json
{
  "pixel_key": "uuid",
  "endpoint": "https://fcm.googleapis.com/fcm/send/..."
}
```

**Response (200):**
```json
{
  "success": true
}
```

**Rate limit:** 10 per minute per IP

### Get VAPID Key

Get public key for subscription.

```http
GET /api/notify/vapid?pixel_key={uuid}
```

**Response (200):**
```json
{
  "success": true,
  "vapid_public_key": "base64-encoded-public-key",
  "widget_settings": {
    "position": "bottom-right",
    "delay": 3,
    "style": "bell",
    "primary_color": "#3b82f6",
    "prompt_title": "Stay Updated",
    "prompt_message": "Would you like to receive notifications?",
    "accept_button": "Yes, notify me",
    "decline_button": "No thanks"
  }
}
```

**Rate limit:** 30 per minute per IP

### Record Click

Track notification click.

```http
POST /api/notify/click
Content-Type: application/json
```

**Request:**
```json
{
  "pixel_key": "uuid",
  "type": "campaign|flow",
  "entity_id": 123,
  "subscriber_id": 456
}
```

**Rate limit:** 60 per minute per IP

### Track Visit

Track page visit for flow triggers.

```http
POST /api/notify/visit
Content-Type: application/json
```

**Request:**
```json
{
  "pixel_key": "uuid",
  "subscriber_id": 456,
  "url": "/products/shoes"
}
```

**Rate limit:** 30 per minute per IP

### Track Event

Track custom event for flow triggers.

```http
POST /api/notify/event
Content-Type: application/json
```

**Request:**
```json
{
  "pixel_key": "uuid",
  "subscriber_id": 456,
  "event": "purchase_completed",
  "data": {
    "order_id": "ABC123",
    "total": 99.99
  }
}
```

**Rate limit:** 30 per minute per IP

### GDPR Export

Export subscriber data.

```http
POST /api/notify/gdpr/export
Content-Type: application/json
```

**Request:**
```json
{
  "pixel_key": "uuid",
  "endpoint": "https://fcm.googleapis.com/fcm/send/..."
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "export_metadata": { ... },
    "subscriber": { ... },
    "device_info": { ... },
    "location": { ... },
    "engagement": { ... },
    "campaign_interactions": [ ... ],
    "flow_interactions": [ ... ]
  }
}
```

**Rate limit:** 3 per minute per IP

---

## Programmatic API (v1)

Base path: `/api/v1/notify`

Requires API key authentication via `agent.api` middleware.

### Authentication

Include API key in request:
- Header: `Authorization: Bearer {api_key}`
- Or query: `?api_key={api_key}`

### Permissions

| Scope | Description |
|-------|-------------|
| `notify:read` | Read campaigns, subscribers |
| `notify:write` | Create/update/delete campaigns |
| `notify:send` | Send notifications |

### Campaigns

#### List Campaigns

```http
GET /api/v1/notify/campaigns
```

**Scope required:** `notify:read`

**Query parameters:**
- `website_id` (optional) - Filter by website
- `status` (optional) - Filter by status
- `page`, `per_page` - Pagination

#### Get Campaign

```http
GET /api/v1/notify/campaigns/{id}
```

**Scope required:** `notify:read`

#### Create Campaign

```http
POST /api/v1/notify/campaigns
Content-Type: application/json
```

**Scope required:** `notify:write`

**Request:**
```json
{
  "website_id": 1,
  "name": "Winter Sale",
  "title": "50% Off Everything!",
  "description": "Shop now and save big",
  "url": "https://example.com/sale",
  "icon_url": "https://example.com/icon.png",
  "image_url": "https://example.com/banner.png",
  "buttons": [
    { "text": "Shop Now", "url": "https://example.com/sale" }
  ],
  "segment_ids": [1, 2],
  "settings": {
    "ttl": 86400,
    "urgency": "high"
  },
  "scheduled_at": "2026-02-01T09:00:00Z",
  "scheduled_timezone": "Europe/London"
}
```

#### Send Campaign

```http
POST /api/v1/notify/campaigns/{id}/send
```

**Scope required:** `notify:send`

Schedules campaign for immediate delivery.

#### Cancel Campaign

```http
POST /api/v1/notify/campaigns/{id}/cancel
```

**Scope required:** `notify:write`

Cancels a scheduled or sending campaign.

#### Delete Campaign

```http
DELETE /api/v1/notify/campaigns/{id}
```

**Scope required:** `notify:write`

### Instant Send

#### Send Notification

```http
POST /api/v1/notify/send
Content-Type: application/json
```

**Scope required:** `notify:send`

Creates and sends a campaign in one request.

**Request:**
```json
{
  "website_id": 1,
  "title": "New Message",
  "description": "You have a new message",
  "url": "https://example.com/messages",
  "icon_url": "https://example.com/icon.png",
  "segment_ids": [1]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Push notification queued for delivery.",
  "campaign_id": 123,
  "estimated_recipients": 500,
  "status": "scheduled"
}
```

#### Send to Specific Subscribers

```http
POST /api/v1/notify/send/targeted
Content-Type: application/json
```

**Scope required:** `notify:send`

**Request:**
```json
{
  "website_id": 1,
  "subscriber_ids": [1, 2, 3, 4, 5],
  "title": "Personal Alert",
  "description": "Just for you",
  "url": "https://example.com/personal"
}
```

### Subscribers

#### List Subscribers

```http
GET /api/v1/notify/websites/{websiteId}/subscribers
```

**Scope required:** `notify:read`

#### Get Subscriber Stats

```http
GET /api/v1/notify/websites/{websiteId}/subscribers/stats
```

**Scope required:** `notify:read`

**Response:**
```json
{
  "website_id": 1,
  "total_subscribers": 1000,
  "active_subscribers": 850,
  "by_country": { "GB": 400, "US": 300, ... },
  "by_device": { "desktop": 600, "mobile": 400 },
  "by_browser": { "Chrome": 500, "Firefox": 200, ... }
}
```

#### Get Subscriber

```http
GET /api/v1/notify/subscribers/{id}
```

**Scope required:** `notify:read`

#### Create Subscriber

```http
POST /api/v1/notify/websites/{websiteId}/subscribers
Content-Type: application/json
```

**Scope required:** `notify:write`

#### Update Subscriber

```http
PUT /api/v1/notify/subscribers/{id}
Content-Type: application/json
```

**Scope required:** `notify:write`

#### Delete Subscriber

```http
DELETE /api/v1/notify/subscribers/{id}
```

**Scope required:** `notify:write`

---

## Authenticated API

Base path: `/api/notify`

Requires session authentication (Laravel Sanctum or web session).

These endpoints are used by the Livewire UI and follow the same patterns as the programmatic API but with session-based auth instead of API keys.

### Websites

- `GET /api/notify/websites` - List user's websites
- `POST /api/notify/websites` - Create website
- `GET /api/notify/websites/{id}` - Show website
- `PUT /api/notify/websites/{id}` - Update website
- `DELETE /api/notify/websites/{id}` - Delete website
- `GET /api/notify/websites/{id}/subscribers` - Subscriber stats

### Campaigns

- `GET /api/notify/campaigns` - List campaigns
- `POST /api/notify/campaigns` - Create campaign
- `GET /api/notify/campaigns/{id}` - Show campaign
- `PUT /api/notify/campaigns/{id}` - Update campaign
- `DELETE /api/notify/campaigns/{id}` - Delete campaign
- `POST /api/notify/campaigns/{id}/send` - Schedule/send

### Segments

- `GET /api/notify/segments` - List segments
- `POST /api/notify/segments` - Create segment
- `GET /api/notify/segments/{id}` - Show segment
- `PUT /api/notify/segments/{id}` - Update segment
- `DELETE /api/notify/segments/{id}` - Delete segment
- `POST /api/notify/segments/{id}/refresh` - Refresh count

### Flows

- `GET /api/notify/flows` - List flows
- `POST /api/notify/flows` - Create flow
- `GET /api/notify/flows/{id}` - Show flow
- `PUT /api/notify/flows/{id}` - Update flow
- `DELETE /api/notify/flows/{id}` - Delete flow
- `GET /api/notify/flows/trigger-types` - Get trigger types

---

## Error Responses

### Validation Error (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."],
    "website_id": ["The selected website_id is invalid."]
  }
}
```

### Not Found (404)

```json
{
  "error": "not_found",
  "message": "Website not found or does not belong to this workspace."
}
```

### Unauthorized (401)

```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)

```json
{
  "message": "This action is unauthorized."
}
```

### Rate Limited (429)

```json
{
  "message": "Too Many Attempts."
}
```

With headers:
- `X-RateLimit-Limit: 5`
- `X-RateLimit-Remaining: 0`
- `Retry-After: 60`

---

## Webhooks

See [Webhooks documentation](./webhooks.md) for webhook event payloads and integration guide.
