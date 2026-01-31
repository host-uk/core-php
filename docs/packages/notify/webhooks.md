---
title: Webhooks
description: Webhook integration guide for core-notify
updated: 2026-01-29
---

# Webhooks

Webhooks allow external systems to receive real-time notifications about push notification events.

## Overview

When events occur in the Notify system (new subscriber, campaign sent, notification clicked, etc.), HTTP POST requests are sent to configured webhook URLs with event data.

## Configuration

Webhooks are configured per-website via:
- Admin UI: Settings > Webhooks
- Livewire component: `notify.web.settings`
- API: Create via authenticated API (coming)

### Webhook Settings

| Field | Description |
|-------|-------------|
| `name` | Human-readable identifier |
| `url` | HTTPS endpoint to receive events |
| `secret` | Shared secret for signature verification |
| `events` | Array of event types to subscribe to |
| `is_active` | Enable/disable webhook |
| `max_attempts` | Max delivery attempts (default: 5) |

## Available Events

### subscriber.created

Triggered when a new subscriber opts in.

```json
{
  "event": "subscriber.created",
  "data": {
    "subscriber_id": 123,
    "website_id": 1,
    "device_type": "desktop",
    "os_name": "Windows",
    "browser_name": "Chrome",
    "browser_language": "en-GB",
    "country_code": "GB",
    "city_name": "London",
    "subscribed_at": "2026-01-29T10:30:00+00:00"
  },
  "timestamp": "2026-01-29T10:30:01+00:00"
}
```

### subscriber.updated

Triggered when subscriber data changes.

```json
{
  "event": "subscriber.updated",
  "data": {
    "subscriber_id": 123,
    "website_id": 1,
    "changes": {
      "custom_parameters": { "plan": "premium" }
    },
    "updated_at": "2026-01-29T11:00:00+00:00"
  },
  "timestamp": "2026-01-29T11:00:01+00:00"
}
```

### subscriber.deleted

Triggered when a subscriber unsubscribes or subscription expires.

```json
{
  "event": "subscriber.deleted",
  "data": {
    "subscriber_id": 123,
    "website_id": 1,
    "reason": "unsubscribed|expired",
    "unsubscribed_at": "2026-01-29T12:00:00+00:00"
  },
  "timestamp": "2026-01-29T12:00:01+00:00"
}
```

### campaign.sent

Triggered when a campaign starts sending.

```json
{
  "event": "campaign.sent",
  "data": {
    "campaign_id": 456,
    "website_id": 1,
    "name": "Winter Sale",
    "title": "50% Off Everything!",
    "total_subscribers": 1000,
    "started_at": "2026-01-29T09:00:00+00:00"
  },
  "timestamp": "2026-01-29T09:00:01+00:00"
}
```

### campaign.completed

Triggered when a campaign finishes sending.

```json
{
  "event": "campaign.completed",
  "data": {
    "campaign_id": 456,
    "website_id": 1,
    "name": "Winter Sale",
    "status": "sent",
    "total_subscribers": 1000,
    "sent_count": 998,
    "delivered_count": 950,
    "failed_count": 48,
    "delivery_rate": 95.19,
    "completed_at": "2026-01-29T09:05:00+00:00"
  },
  "timestamp": "2026-01-29T09:05:01+00:00"
}
```

### notification.delivered

Triggered when a notification is successfully delivered.

```json
{
  "event": "notification.delivered",
  "data": {
    "subscriber_id": 123,
    "website_id": 1,
    "campaign_id": 456,
    "delivered_at": "2026-01-29T09:01:00+00:00"
  },
  "timestamp": "2026-01-29T09:01:01+00:00"
}
```

### notification.clicked

Triggered when a subscriber clicks a notification.

```json
{
  "event": "notification.clicked",
  "data": {
    "subscriber_id": 123,
    "website_id": 1,
    "type": "campaign",
    "campaign_id": 456,
    "clicked_at": "2026-01-29T09:10:00+00:00"
  },
  "timestamp": "2026-01-29T09:10:01+00:00"
}
```

### notification.failed

Triggered when notification delivery fails.

```json
{
  "event": "notification.failed",
  "data": {
    "subscriber_id": 123,
    "website_id": 1,
    "campaign_id": 456,
    "error_code": "410",
    "error_message": "Subscription expired",
    "source": "campaign",
    "failed_at": "2026-01-29T09:01:00+00:00"
  },
  "timestamp": "2026-01-29T09:01:01+00:00"
}
```

## Delivery

### Request Format

```http
POST https://your-endpoint.com/webhook
Content-Type: application/json
X-Signature: sha256=abc123...
X-Request-Source: Host UK
User-Agent: Host UK Notify Webhook
```

### Headers

| Header | Description |
|--------|-------------|
| `Content-Type` | Always `application/json` |
| `X-Signature` | HMAC-SHA256 signature |
| `X-Request-Source` | Application name |
| `User-Agent` | `Host UK Notify Webhook` |
| `X-Retry-Attempt` | Attempt number (for retries) |
| `X-Test-Webhook` | `true` for test deliveries |

### Response Handling

- **2xx responses** (200, 201, 202, 204) = Success
- **Other status codes** = Failure (will retry)
- **Timeout** (> 10 seconds) = Failure (will retry)
- **Network error** = Failure (will retry)

## Signature Verification

All webhook deliveries are signed with HMAC-SHA256 using your webhook secret.

### Verification (PHP)

```php
function verifyWebhookSignature(
    string $payload,
    string $signature,
    string $secret
): bool {
    $expected = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}

// Usage
$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

if (!verifyWebhookSignature($rawBody, $signature, $webhookSecret)) {
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($rawBody, true);
// Process event...
```

### Verification (Node.js)

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
  const expected = crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');

  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expected)
  );
}

// Usage in Express
app.post('/webhook', express.raw({type: 'application/json'}), (req, res) => {
  const signature = req.headers['x-signature'];

  if (!verifyWebhookSignature(req.body, signature, process.env.WEBHOOK_SECRET)) {
    return res.status(401).send('Invalid signature');
  }

  const event = JSON.parse(req.body);
  // Process event...

  res.status(200).send('OK');
});
```

## Retry Behaviour

Failed deliveries are retried with exponential backoff:

| Attempt | Delay |
|---------|-------|
| 1 | Immediate |
| 2 | 5 minutes |
| 3 | 15 minutes |
| 4 | 45 minutes |
| 5 | 2 hours 15 minutes |

After all attempts fail, the delivery is marked as failed and the webhook's failure count is incremented.

## Circuit Breaker

To protect your endpoint from being overwhelmed:

- **5 consecutive failures** automatically disables the webhook
- Webhook must be manually re-enabled after fixing the issue
- Use `NotifyWebhookService::resetCircuitBreaker()` or admin UI

## Testing

### Test Webhook Delivery

Send a test event to verify your endpoint:

1. Go to Settings > Webhooks
2. Click "Test" on the webhook
3. A test event is sent with `X-Test-Webhook: true` header

### Test Event Payload

```json
{
  "event": "test",
  "data": {
    "webhook_id": "uuid",
    "webhook_name": "My Webhook",
    "message": "This is a test webhook delivery from Example Site",
    "subscribed_events": ["subscriber.created", "campaign.completed"]
  },
  "timestamp": "2026-01-29T10:00:00+00:00"
}
```

## Best Practices

### For Your Endpoint

1. **Respond quickly** - Return 2xx within 10 seconds
2. **Process asynchronously** - Queue heavy processing, don't block
3. **Handle idempotency** - Deliveries may retry, use event IDs
4. **Validate signatures** - Always verify before processing
5. **Use HTTPS** - Plaintext HTTP is blocked in production

### For Reliability

1. **Log all events** - Keep audit trail for debugging
2. **Handle duplicates** - Same event may arrive multiple times
3. **Graceful degradation** - Your app should work if webhook fails
4. **Monitor delivery stats** - Watch for failures in Host UK dashboard

### For Security

1. **Keep secrets secure** - Never log or expose webhook secrets
2. **Validate event types** - Check `event` field before processing
3. **Validate data** - Don't trust webhook data blindly
4. **Use firewall rules** - Whitelist Host UK IP ranges if needed

## Troubleshooting

### Webhook Not Receiving Events

1. Check webhook is enabled (`is_active: true`)
2. Check subscribed events include the event type
3. Verify URL is accessible from internet
4. Check for circuit breaker activation

### Signature Verification Failing

1. Ensure using raw request body (not parsed JSON)
2. Check secret matches exactly (no whitespace)
3. Verify encoding matches (UTF-8)

### Deliveries Always Failing

1. Check endpoint returns 2xx status code
2. Ensure response within 10-second timeout
3. Verify SSL certificate is valid
4. Check firewall isn't blocking requests

### Manual Retry

To retry a failed delivery:

1. Find the delivery in admin panel
2. Click "Retry" to resend
3. Or use `NotifyWebhookService::retryDelivery($delivery)`

## Delivery History

View delivery history in:
- Admin UI: Webhooks > View Deliveries
- Model: `NotifyWebhookDelivery`

Delivery record includes:
- Event type
- Status (success/failed)
- HTTP status code
- Request payload
- Response body
- Attempt count
- Timestamps
