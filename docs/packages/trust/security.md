---
title: Security
description: Security considerations and audit notes for core-trust
updated: 2026-01-29
---

# Security

This document outlines security considerations, potential vulnerabilities, and recommended mitigations for the core-trust package.

## Threat Model

### Trust Boundaries

1. **Public Internet** → **Widget API** (unauthenticated, rate-limited)
2. **Authenticated User** → **Management API** (requires auth, user_id check)
3. **Admin/Platform** → **Admin Panel** (requires auth, workspace access)
4. **Backend Services** → **Webhook Targets** (outbound HTTP)

### Assets

- Customer website visitor data (hashed IPs, countries, pages)
- Conversion data (names, locations, products, values)
- Collected data (emails, messages, names)
- Campaign configuration (pixel keys, webhook URLs)
- Custom CSS code

### Attackers

1. **Malicious website visitor** - Abuse rate limits, inject data
2. **Competing campaign owner** - Enumerate campaigns, steal config
3. **Malicious admin** - XSS via custom CSS/HTML
4. **Network attacker** - Intercept webhooks, SSRF

## Current Security Controls

### Authentication & Authorisation

| Endpoint Group | Auth Required | Authorisation |
|----------------|---------------|---------------|
| `/api/trust/widgets` | No | By pixel_key (UUID) |
| `/api/trust/track` | No | By pixel_key + notification_id |
| `/api/trust/conversion` | No | By pixel_key |
| `/api/trust/collect` | No | By pixel_key + notification_id |
| `/api/trust/campaigns/*` | Yes | `user_id` ownership check |
| `/api/trust/notifications/*` | Yes | `user_id` ownership check |
| `/hub/trust/*` | Yes | `user_id` ownership check |

**Finding**: Authorisation uses direct `user_id` comparison rather than Laravel policies. This works but lacks the extensibility and audit trail of policy-based authorisation.

### Input Validation

| Field | Validation | Notes |
|-------|------------|-------|
| `pixel_key` | UUID format | Prevents enumeration |
| `notification_id` | Integer, exists in campaign | |
| `type` (event) | Enum: impression, hover, click, close, conversion | |
| `type` (widget) | Enum from config | |
| `email` | email, max:255 | |
| `name` | string, max:128 | |
| `message` | string, max:2000 | |
| `page_url` | string, max:512 | **No URL validation** |
| `custom_css` | string, max:10000 | Sanitised by CssSanitiser |
| `metadata` | array, SafeJsonPayload::small() | |

### Rate Limiting

| Endpoint | Limit | Scope |
|----------|-------|-------|
| `/api/trust/*` (general) | 300/min | Per IP |
| `/api/trust/conversion` | 30/min | Per IP + pixel_key |
| `/api/trust/conversion` | 100/min | Per pixel_key (global) |
| `/api/trust/collect` | 30/min | Per IP |

**Finding**: Rate limits are reasonable but configurable via env vars without validation. Extremely high limits could be set accidentally.

### Data Privacy

| Data | Protection | Retention |
|------|------------|-----------|
| Visitor IP | SHA-256 hash (via PrivacyHelper) | 90 days (events) |
| Collected emails | Stored raw | No auto-cleanup |
| Collected IP | Stored raw | No auto-cleanup |
| Conversion names | Anonymised for display | 365 days |

**Finding**: `CollectedData.ip_address` stores raw IPs, creating GDPR compliance risk.

## Known Vulnerabilities

### HIGH: SSRF via Webhook URL (SEC-001)

**Description**: Campaign owners can set arbitrary `webhook_url` values. The `DispatchConversionWebhook` job makes HTTP POST requests to this URL without validation.

**Impact**: An attacker could:
- Probe internal network services (169.254.x.x, 10.x.x.x, etc.)
- Attack internal APIs using the server's identity
- Exfiltrate data to external services

**Current mitigation**: None

**Recommended fix**:
```php
// In Campaign model or webhook job
protected function isValidWebhookUrl(string $url): bool
{
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['host'])) {
        return false;
    }

    $host = $parsed['host'];

    // Block private IP ranges
    $ip = gethostbyname($host);
    if (filter_var($ip, FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return false;
    }

    // Require HTTPS
    if (($parsed['scheme'] ?? '') !== 'https') {
        return false;
    }

    return true;
}
```

### MEDIUM: Stored XSS Risk (SEC-002)

**Description**: The `custom_html` widget type exists in config but handling is unclear. If HTML content is rendered without sanitisation, it creates XSS risk.

**Impact**: Attackers could inject JavaScript that runs on all visitors' browsers.

**Current mitigation**: CssSanitiser exists for CSS but HTML handling not visible.

**Recommended fix**:
- Audit custom_html widget rendering
- Implement HTML sanitisation (HTMLPurifier or similar)
- Or remove custom_html widget type entirely

### MEDIUM: Raw IP Storage (SEC-004)

**Description**: `CollectedData` model stores raw IP addresses in `ip_address` column.

**Impact**: GDPR compliance violation - IP addresses are PII.

**Current mitigation**: None

**Recommended fix**:
- Option A: Hash IPs like visitor_hash
- Option B: Implement data retention and document legal basis
- Option C: Remove IP storage entirely

### LOW: Missing Webhook Signature

**Description**: Outgoing webhooks have no signature for verification.

**Impact**: Webhook receivers cannot verify requests came from Trust.

**Recommended fix**: Add HMAC-SHA256 signature header:
```php
$signature = hash_hmac('sha256', json_encode($payload), $campaign->webhook_secret);
$response = Http::withHeaders([
    'X-Trust-Signature' => $signature,
])->post($webhookUrl, $payload);
```

## CSS Sanitisation Analysis

The `CssSanitiser` class blocks:

| Pattern | Protection Against |
|---------|-------------------|
| `javascript:` | XSS via CSS expressions |
| `expression()` | IE CSS expressions |
| `behavior:` | IE HTC behaviors |
| `binding:` | Mozilla XBL |
| `@import` | External CSS loading |
| `<script>` etc. | HTML injection |
| Unicode escapes | Filter bypass |
| `data:` URLs | Script injection |
| `vbscript:` | IE VBScript |
| HTML entities | Encoded attacks |

**Additional checks**:
- URL schemes limited to `https://`, `/`, `#`
- Maximum length: 10KB
- Selectors scoped to widget ID prefix

**Potential gaps**:
1. No protection against CSS-based data exfiltration (attribute selectors + background-url)
2. No Content-Security-Policy enforcement recommended

**Recommendation**: Document that custom CSS should be used with CSP headers on the widget host.

## Authentication Flow Security

### API Authentication
- Uses Laravel `auth` middleware
- Bearer token or session-based
- No API-specific rate limiting per user

### Livewire Components
- Session-based authentication
- User ownership verified in `mount()` and before actions
- No CSRF issues (Livewire handles)

## Recommendations Summary

### Immediate (Before Production)

1. **Implement webhook URL validation** to prevent SSRF
2. **Audit custom_html widget** for XSS vulnerabilities
3. **Hash or remove raw IP storage** in CollectedData

### Short Term

4. **Add webhook signature verification** for authenticity
5. **Create authorisation policies** for cleaner access control
6. **Add security headers** to widget responses (CSP, X-Content-Type-Options)

### Medium Term

7. **Implement audit logging** for security-sensitive operations
8. **Add rate limiting per authenticated user** for management APIs
9. **Create security test suite** for XSS, SSRF, injection

### Long Term

10. **Consider WAF integration** for widget endpoints
11. **Implement anomaly detection** for conversion abuse
12. **Add webhook delivery logging** with retry UI

## Security Testing Checklist

- [ ] SSRF: Test webhook with internal URLs (localhost, 169.254.x.x, 10.x.x.x)
- [ ] XSS: Test custom_css with various payloads
- [ ] XSS: Test custom_html widget if enabled
- [ ] Injection: Test conversion data fields for SQL/NoSQL injection
- [ ] Enumeration: Test pixel_key brute force detection
- [ ] Rate limiting: Verify limits work under load
- [ ] Authorisation: Test cross-user access attempts
- [ ] IDOR: Test notification/campaign ID manipulation

## Compliance Notes

### GDPR

- IP hashing in Events is good
- Raw IP in CollectedData needs addressing
- Email collection requires consent (widget owner responsibility)
- Conversion data may contain PII (names)

### PCI-DSS

- No payment data stored (conversion `value` is amount only)
- Webhook could transmit to payment systems - ensure HTTPS

### SOC 2

- Audit logging not implemented
- Access control via user_id (documented)
- Data retention configurable

## Contact

Security issues should be reported to the Host UK security team.
