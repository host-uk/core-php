---
title: Security
description: Security considerations and audit notes for core-social
updated: 2026-01-29
---

# Security

This document covers security considerations, implemented protections, and audit notes for the `core-social` package.

## Overview

`core-social` handles sensitive data including:
- OAuth tokens and refresh tokens
- API keys and app passwords
- User content and scheduling data
- Webhook secrets and delivery payloads
- Media uploads

## Implemented Protections

### Credential Storage

**Encryption at Rest:**

Account credentials are stored using Laravel's `AsEncryptedCollection` cast:

```php
// Models/Account.php
protected $casts = [
    'credentials' => AsEncryptedCollection::class,
];
```

This encrypts the entire credentials JSON blob using the application key.

**Hidden from Serialisation:**

Credentials are excluded from JSON/array serialisation:

```php
protected $hidden = [
    'credentials',
];
```

### OAuth Security

**CSRF Protection via State Parameter:**

OAuth flows use a cryptographically random state parameter:

```php
// AccountService::getAuthUrl()
$state = Str::random(40);
Session::put("oauth_state_{$provider}", $state);
```

State is validated on callback using timing-safe comparison:

```php
// AccountService::handleCallback()
if (!hash_equals($sessionState, $callbackState)) {
    return null; // CSRF attempt
}
```

**PKCE for Twitter/X:**

Twitter uses PKCE (Proof Key for Code Exchange) to prevent authorization code interception:

```php
// TwitterProvider
$codeVerifier = $this->generateCodeVerifier();  // 32 random bytes
$codeChallenge = $this->generateCodeChallenge($codeVerifier);  // SHA256 hash
```

The code verifier is cached with the state and retrieved during token exchange.

### Multi-Tenant Isolation

**Automatic Workspace Scoping:**

All models use the `BelongsToWorkspace` trait which:
- Auto-applies global scope filtering by `workspace_id`
- Auto-assigns `workspace_id` on model creation
- Throws exception if workspace context is missing

**Controller-Level Verification:**

Controllers explicitly verify workspace ownership:

```php
// SocialPostController::show()
if ($post->workspace_id !== $workspace->id) {
    return $this->notFoundResponse('Post');
}
```

### Input Validation

**Request Validation:**

All API inputs are validated via Form Request classes:

```php
$request->validate([
    'account_ids' => 'required|array|min:1',
    'account_ids.*' => 'exists:social_accounts,uuid',
    'content' => 'required|array',
    'content.body' => 'required|string',
    // ...
]);
```

**SQL Injection Prevention:**

Sort columns are whitelisted to prevent injection:

```php
$allowedSortColumns = ['scheduled_at', 'created_at', 'updated_at', 'status'];
$sortBy = in_array($request->input('sort_by'), $allowedSortColumns, true)
    ? $request->input('sort_by')
    : 'scheduled_at';
```

### Rate Limiting

**Endpoint-Specific Limits:**

Critical endpoints have rate limits:

| Endpoint | Limit | Key |
|----------|-------|-----|
| Post scheduling | 30/min | User ID or IP |
| Queue operations | 20/min | User ID or IP |
| Post creation | 60/min | User ID or IP |
| OAuth callback | 10/min | IP |

### Webhook Security

**HMAC Signature:**

Custom webhooks can be signed with a secret:

```php
// TriggerWebhook action
if ($webhook->isCustom() && $webhook->secret) {
    $headers['X-Signature'] = hash_hmac(
        'sha256',
        json_encode($payload),
        $webhook->secret
    );
}
```

Recipients should verify the signature before processing.

### Media Upload Security

**File Type Validation:**

Uploads are restricted to allowed MIME types:

```php
protected array $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
protected array $allowedVideoTypes = ['video/mp4', 'video/quicktime', 'video/webm'];
```

**Size Limits:**

- Images: 10MB maximum
- Videos: 100MB maximum

**Path Generation:**

File paths use UUIDs to prevent enumeration:

```
social/{workspace_id}/{YYYY}/{MM}/{uuid}.{extension}
```

## Known Vulnerabilities & Mitigations Needed

### HIGH: SSRF via Webhook URLs

**Issue:** Webhook URLs can point to internal services (localhost, private IPs, cloud metadata endpoints).

**Risk:** Server-Side Request Forgery allowing internal network scanning or credential theft.

**Mitigation Required:**
```php
// Add to TriggerWebhook or webhook validation
$host = parse_url($webhook->url, PHP_URL_HOST);
$ip = gethostbyname($host);

// Block private ranges
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
    throw new InvalidWebhookUrlException('Invalid webhook URL');
}

// Block metadata endpoints
$blockedHosts = ['169.254.169.254', 'metadata.google.internal'];
if (in_array($host, $blockedHosts)) {
    throw new InvalidWebhookUrlException('Invalid webhook URL');
}
```

### MEDIUM: Template Injection

**Issue:** Mustache-style templates use regex parsing which could be vulnerable to ReDoS or unexpected behaviour.

**Risk:** Denial of service or information disclosure.

**Mitigation Required:**
- Add nesting depth limits
- Add execution timeout
- Sanitise interpolated values

### MEDIUM: Media Upload Path Traversal

**Issue:** While UUIDs are used, original filenames are stored in metadata.

**Risk:** Potential path manipulation if metadata is used in file operations.

**Mitigation Required:**
- Sanitise original filename before storing
- Never use original filename in file paths
- Add explicit path traversal check

### LOW: OAuth State Logging

**Issue:** Failed OAuth attempts could be CSRF attacks but aren't logged.

**Risk:** Unable to detect attack patterns.

**Mitigation Required:**
```php
if (!$sessionState || !$callbackState || !hash_equals($sessionState, $callbackState)) {
    Log::warning('OAuth state mismatch - potential CSRF', [
        'provider' => $provider,
        'ip' => request()->ip(),
        'session_exists' => (bool)$sessionState,
        'callback_state' => $callbackState ? 'provided' : 'missing',
    ]);
    return null;
}
```

## Security Checklist

### Before Production

- [ ] Ensure `APP_KEY` is set and not committed
- [ ] Enable HTTPS for all OAuth redirect URLs
- [ ] Set appropriate `SESSION_SECURE_COOKIE=true`
- [ ] Configure proper CORS headers for API
- [ ] Review rate limit values for your traffic
- [ ] Implement SSRF protection for webhooks
- [ ] Add template execution limits

### Ongoing

- [ ] Rotate OAuth client secrets annually
- [ ] Monitor for expired/revoked tokens
- [ ] Review activity logs for anomalies
- [ ] Update provider SDKs for security patches
- [ ] Audit webhook delivery destinations

## Credential Rotation

### OAuth Client Secrets

When rotating OAuth credentials:

1. Generate new credentials in provider dashboard
2. Update environment variables
3. Re-authorise affected accounts (tokens are provider-specific)

### Webhook Secrets

When rotating webhook secrets:

1. Generate new secret
2. Update in database via admin UI
3. Notify webhook recipients of new secret
4. Consider brief overlap period for migration

### Database Encryption Key

If `APP_KEY` is rotated:

1. All encrypted credentials become unreadable
2. Users must re-authorise all accounts
3. Plan for user communication and support load

## Audit Log

| Date | Auditor | Scope | Findings |
|------|---------|-------|----------|
| 2026-01-29 | Initial Review | Full codebase | See "Known Vulnerabilities" section |

## Reporting Security Issues

If you discover a security vulnerability:

1. **Do not** open a public GitHub issue
2. Email security@host.uk.com with details
3. Include reproduction steps if possible
4. Allow 90 days for remediation before disclosure

## Compliance Considerations

### GDPR

- User data is workspace-scoped
- Soft delete preserves data for legal hold
- Account disconnection removes credentials
- Export functionality for data portability

### Data Retention

- Posts: Soft deleted, permanent delete after 30 days
- Media: Deleted when explicitly removed
- Analytics: Retained indefinitely (aggregate data)
- Webhook deliveries: Pruned after 30 days

### Third-Party Data

Social platforms have their own data retention policies. Review:
- Twitter/X API Terms
- Meta Platform Terms
- LinkedIn API Terms
- TikTok Developer Terms

Data fetched from providers (post content, analytics) should be handled according to their terms.
