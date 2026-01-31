---
title: Security
description: Security considerations and audit notes for core-notify
updated: 2026-01-29
---

# Security

This document outlines security considerations, implemented protections, and areas requiring attention for the core-notify package.

## Security Model

### Public Endpoints

Public endpoints (subscribe, unsubscribe, click tracking) are identified by `pixel_key`:
- UUID-based, not predictable
- Per-website isolation
- No authentication required (by design for JS SDK)

### Authenticated Endpoints

Management endpoints require:
- Session authentication (web UI)
- API key authentication (programmatic API)
- Workspace ownership validation

### Admin Endpoints

Platform admin endpoints require:
- Authenticated user
- `isHades()` role check

## Implemented Protections

### Rate Limiting

All public endpoints have rate limiting:

| Endpoint | Limit | Rationale |
|----------|-------|-----------|
| Subscribe | 5/min/IP | Prevent subscription spam |
| Unsubscribe | 10/min/IP | Ensure availability |
| VAPID key | 30/min/IP | Read-only, moderate |
| Click tracking | 60/min/IP | Higher for UX |
| Event tracking | 30/min/IP | Moderate |
| GDPR export | 3/min/IP | Sensitive data |
| Programmatic API | Per API key | Configurable |

### Data Encryption

- **VAPID private keys**: Encrypted at rest using Laravel's `encrypted` cast
- **Webhook secrets**: Encrypted at rest using Laravel's `encrypted` cast
- **Subscriber endpoints**: Stored in plaintext (required for delivery)

### Input Validation

- `SafeJsonPayload` rule limits custom parameter size
- `NotificationPayloadSize` rule prevents oversized payloads (4KB Web Push limit)
- UUID validation on pixel_key
- URL validation on endpoints and click URLs

### Webhook Security

- HMAC-SHA256 signature on all webhook deliveries
- Secret stored encrypted
- Signature in `X-Signature` header
- Verification method provided for receivers

### Multi-Tenant Isolation

- `BelongsToNamespace` trait ensures workspace scoping
- Ownership validated in controllers before operations
- Segment ownership validated before campaign/flow creation

### Privacy Protections

- **IP hashing**: Daily-rotating hash (no long-term tracking)
- **Endpoint hashing**: SHA256 for quick lookups without exposing full URL
- **VAPID private keys hidden**: Not returned in API responses
- **Webhook secrets hidden**: Not returned in API responses

## GDPR Compliance

### Data Portability (Article 20)

- `POST /api/notify/gdpr/export` endpoint
- Returns all subscriber data in JSON format
- Includes: device info, location, preferences, engagement history
- Rate limited to prevent abuse

### Right to Erasure (Article 17)

- Unsubscribe endpoint marks subscriber as unsubscribed
- Cleanup command deletes old unsubscribed records (configurable retention)
- Campaign logs cleaned after retention period

### Lawful Basis

- Consent: Explicit opt-in via browser permission prompt
- Record: `subscribed_at` timestamp stored
- Withdrawal: Unsubscribe endpoint and browser revocation

## Known Security Considerations

### Medium Priority

#### 1. Cross-Site Subscription Possible

**Issue**: No verification that the push endpoint belongs to the website's origin.

**Risk**: Malicious site could subscribe users to another site's notifications.

**Mitigation**:
- Browser permission prompt is site-specific
- Push endpoints are opaque URLs from browser vendors
- Consider adding origin validation header check

#### 2. Full Endpoint Storage

**Issue**: Both hashed and full endpoint stored in database.

**Risk**: Correlation across services if database compromised.

**Mitigation**:
- Endpoint is required for delivery (cannot hash-only)
- Database encryption at rest recommended
- Consider purging after subscription expires

#### 3. Custom Parameters Without Schema

**Issue**: Arbitrary JSON accepted in custom_parameters field.

**Risk**:
- XSS if rendered unsafely in admin UI
- Storage bloat with large values

**Mitigation**:
- `SafeJsonPayload::small()` limits size
- Ensure HTML escaping in Livewire templates
- Consider allow-list of parameter keys

#### 4. No Origin Validation on Subscribe

**Issue**: Subscribe endpoint doesn't validate request origin.

**Risk**: CSRF-style attacks to inflate subscriber counts.

**Mitigation**:
- Rate limiting by IP
- Browser permission required anyway
- Consider adding referer/origin header validation

### Low Priority

#### 1. Admin Permission Granularity

**Issue**: Admin panels only check `isHades()` method.

**Risk**: All admins have full access to all features.

**Mitigation**: Consider role-based permissions for admin operations.

#### 2. Bulk Operation Safety

**Issue**: No confirmation step for bulk subscriber operations.

**Risk**: Accidental mass deletion.

**Mitigation**: Add modal confirmation with count display.

### Addressed Issues

- [x] VAPID private key encryption at rest
- [x] Rate limiting on all public endpoints
- [x] Segment ownership validation in controllers
- [x] Payload size validation
- [x] Webhook signature verification
- [x] Database transactions for campaign sending

## Webhook Receiver Guidelines

For clients implementing webhook receivers:

### Signature Verification

```php
function verifyWebhook(array $payload, string $signature, string $secret): bool
{
    $expected = hash_hmac('sha256', json_encode($payload), $secret);
    return hash_equals($expected, $signature);
}
```

### Best Practices

1. **Always verify signature** before processing
2. **Respond quickly** (< 10 seconds) to avoid timeouts
3. **Process asynchronously** - queue heavy work
4. **Idempotency** - deliveries may retry, handle duplicates
5. **HTTPS only** - webhook URLs must be HTTPS in production

## Audit Logging

Using `spatie/laravel-activitylog`, the following are logged:

- Website: name, host, is_enabled changes
- Campaign: name, status, scheduled_at, title changes
- Segment: name, is_active changes
- Flow: name, trigger_type, is_enabled changes
- Webhook: name, url, events, is_active changes

Logs include:
- User who made the change
- Previous and new values
- Timestamp

## Security Recommendations

### For Production

1. **Enable database encryption at rest**
2. **Use HTTPS exclusively** for all endpoints
3. **Rotate webhook secrets** periodically
4. **Monitor rate limit hits** for abuse detection
5. **Set up alerting** on circuit breaker activations
6. **Regular security reviews** of webhook URLs

### For Development

1. **Never commit** real VAPID keys or webhook secrets
2. **Use test pixel keys** in development
3. **Review payload contents** before logging

## Incident Response

### Compromised VAPID Keys

1. Generate new keys via `PushWebsite::generateVapidKeys()`
2. Update website record
3. All subscribers will need to re-subscribe (browser key mismatch)

### Compromised Webhook Secret

1. Generate new secret
2. Update in both sender (Host UK) and receiver
3. Previous deliveries cannot be verified retroactively

### Subscriber Data Breach

1. Notify affected users per GDPR requirements
2. Consider forced unsubscribe of affected endpoints
3. Rotate VAPID keys as precaution
