---
title: Security
description: Security considerations and audit notes for core-analytics
updated: 2026-01-29
---

# Security

This document outlines security considerations, implemented mitigations, and audit notes for the core-analytics package.

## Threat Model

### Actors

1. **Anonymous attackers** - Attempting to exploit public tracking endpoints
2. **Authenticated users** - Potentially abusing legitimate access
3. **Tracked visitors** - Privacy concerns, data exposure
4. **Malicious websites** - Cross-site attacks via tracking pixel

### Assets

1. Analytics data (pageviews, sessions, conversions)
2. Visitor PII (IP addresses, geolocation, device info)
3. Session replay recordings
4. Workspace/tenant data isolation

## Implemented Mitigations

### Input Validation

#### Tracking Endpoints

All tracking endpoints validate input:

```php
$validated = $request->validate([
    'key' => 'required|uuid',
    'type' => 'sometimes|string|in:pageview,click,scroll,form,goal,custom',
    'path' => 'required|string|max:512',
    'title' => 'sometimes|string|max:256',
    // ... more fields
]);
```

**Audit note:** Consider adding Form Request classes for better organisation.

#### Regex Pattern Safety

Goal patterns support regex matching with ReDoS protection:

```php
protected function safeRegexMatch(string $pattern, string $subject): bool
{
    // Limit subject length
    if (strlen($subject) > 2048) {
        $subject = substr($subject, 0, 2048);
    }

    // Validate pattern syntax
    if (!$this->isValidRegexPattern($pattern)) {
        return false;
    }

    // Set lower backtrack limit
    ini_set('pcre.backtrack_limit', '10000');
    // ...
}
```

**Audit note:** Consider pattern complexity analysis at storage time, not just execution.

### Rate Limiting

Public tracking endpoints are rate-limited:

```php
Route::middleware('throttle:10000,1')->prefix('analytics')->group(function () {
    // 10,000 requests per minute
});
```

**Audit note:** Rate limiting is per-IP, not per-pixel-key. Shared pool could be exhausted by targeting one key. Consider per-key quotas.

### Bot Detection

Multi-signal bot detection prevents analytics pollution:

| Signal | Detection Method |
|--------|------------------|
| User-Agent | Pattern matching for known bots, headless browsers |
| HTTP Headers | Missing Accept/Accept-Language headers |
| IP Reputation | Datacenter IP ranges, cached bot scores |
| Behaviour | Invalid screen dimensions, fast request timing |

**Audit note:** Bot detection relies on IP caching. NAT/VPN users could be incorrectly flagged. Consider composite scoring.

### Multi-Tenant Isolation

All models use `BelongsToWorkspace` trait:

```php
class AnalyticsWebsite extends Model
{
    use BelongsToWorkspace;
    // Automatically scoped to current workspace
}
```

This provides:
- Automatic `workspace_id` assignment on create
- Global scope filtering queries to current workspace
- Exception thrown if no workspace context

**Audit note:** Verify all API endpoints properly set workspace context before queries.

### IP Anonymisation

IP addresses are anonymised before storage by default:

```php
'ip' => PrivacyHelper::anonymiseIp($request->ip()),
```

Zeroes the last octet: `192.168.1.123` -> `192.168.1.0`

Controlled by `analytics.privacy.anonymise_ip` config option.

### Pixel Key Security

Pixel keys are UUIDs generated at website creation:

```php
protected static function booted(): void
{
    static::creating(function (AnalyticsWebsite $website) {
        if (empty($website->pixel_key)) {
            $website->pixel_key = Str::uuid();
        }
    });
}
```

Keys are:
- Unique across all websites
- Cannot be user-specified
- Cached for lookup efficiency

**Audit note:** Keys are not rotatable. Consider adding key rotation capability.

### CORS Configuration

Tracking endpoints allow cross-origin requests (required for tracking pixels):

```php
protected function corsHeaders(): array
{
    return [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
        'Access-Control-Max-Age' => '86400',
    ];
}
```

**Audit note:** `*` origin is acceptable for tracking endpoints. Authenticated API endpoints should have stricter CORS.

## GDPR Compliance

### Data Export

Full data export for GDPR requests:

```php
public function exportVisitorData(AnalyticsVisitor $visitor): array
{
    return [
        'visitor' => [...],
        'sessions' => $this->exportSessions($visitor),
        'events' => $this->exportEvents($visitor),
        'pageviews' => $this->exportPageviews($visitor),
        'conversions' => $this->exportConversions($visitor),
        'heatmap_events' => $this->exportHeatmapEvents($visitor),
        'session_replays' => $this->exportSessionReplays($visitor),
    ];
}
```

**Audit note:** Verify `AnalyticsFunnelConversion` and `AnalyticsExperimentVisitor` are included.

### Data Deletion

Complete data deletion:

```php
public function deleteVisitorData(AnalyticsVisitor $visitor): array
{
    DB::transaction(function () use ($visitor, &$counts) {
        $counts['events'] = AnalyticsEvent::where('visitor_id', $visitor->id)->delete();
        $counts['pageviews'] = Pageview::where('visitor_id', $visitor->visitor_uuid)->delete();
        // ... all related data
        $counts['visitor'] = $visitor->delete() ? 1 : 0;
    });
}
```

### Anonymisation

Alternative to deletion that preserves aggregates:

```php
public function anonymiseVisitor(AnalyticsVisitor $visitor): AnalyticsVisitor
{
    $visitor->update([
        'ip' => null,
        'country_code' => null,
        'city_name' => null,
        'region' => null,
        'browser_language' => null,
        'custom_parameters' => null,
        'is_anonymised' => true,
        'anonymised_at' => now(),
    ]);
    // Also anonymises related records
}
```

### Consent Tracking

Per-visitor consent tracking:

```php
public function recordConsent(AnalyticsVisitor $visitor, ?string $ip = null): AnalyticsVisitor
{
    $visitor->update([
        'consent_given' => true,
        'consent_given_at' => now(),
        'consent_ip' => $ip,
    ]);
}
```

Tracking can be configured to require consent:

```php
if ($website->require_consent && !$visitor->hasConsent()) {
    return null; // Don't track
}
```

## Session Replay Security

### Storage

Replays are stored compressed on configured disk (S3 recommended for production):

```php
$compressedData = gzencode($jsonData, 9);
Storage::disk($this->disk)->put($storagePath, $compressedData);
```

### Sensitive Data

**Warning:** Session replays may capture sensitive form data.

Mitigations:
1. Client-side SDK should mask password fields, credit cards
2. Max size limit (10MB default) prevents large data exfiltration
3. Expiry (90 days default) limits data retention

**Audit note:** Consider server-side PII detection and redaction.

### Access Control

Replay playback requires authentication and workspace ownership:

```php
Route::middleware(['auth', 'api'])->group(function () {
    Route::get('/websites/{website}/replays/{replay}/playback', ...);
});
```

## A/B Testing Security

### Variant Assignment

Deterministic assignment prevents manipulation:

```php
$hash = abs(crc32($visitorId . $experimentId)) % 100;
```

Users cannot choose their variant.

### Statistical Integrity

- Minimum sample sizes enforced before declaring winners
- Statistical significance uses standard z-test
- Results are read-only once experiment is complete

## API Security

### Authentication

All management endpoints require authentication:

```php
Route::middleware(['auth', 'api'])->prefix('analytics')->group(function () {
    // Websites, goals, experiments, etc.
});
```

### Authorisation

Workspace scoping provides implicit authorisation. Users can only access resources in their workspace.

**Audit note:** Consider explicit policy classes for finer-grained control.

## Data Retention

### Automatic Cleanup

```bash
php artisan analytics:cleanup
```

Tier-based retention:

| Tier | Days |
|------|------|
| Free | 30 |
| Pro | 90 |
| Business | 365 |
| Enterprise | 3650 |

### Aggregation Before Deletion

Data is aggregated into `analytics_daily_stats` before raw data deletion, preserving historical trends.

## Known Vulnerabilities / Limitations

### Rate Limit Bypass

**Issue:** Rate limiting uses shared pool across all pixel keys.

**Impact:** Medium - Attacker could exhaust quota for legitimate users.

**Mitigation:** Consider per-pixel-key rate limiting.

### Bot IP Cache Poisoning

**Issue:** IP scores are cached for 24 hours. NAT/VPN users share IPs.

**Impact:** Low - False positives for legitimate users behind bot-flagged IPs.

**Mitigation:** Consider composite scoring (IP + fingerprint) and score decay.

### Session Replay PII

**Issue:** Replays may contain sensitive data if client SDK doesn't mask inputs.

**Impact:** Medium - PII exposure in replays.

**Mitigation:** Document client-side requirements; consider server-side sanitisation.

## Security Headers

The package doesn't set security headers (handled by framework/proxy). Recommended:

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Content-Security-Policy: default-src 'self'
```

## Logging

Security-relevant events are logged:

```php
Log::info('GDPR: Visitor data deleted', [...]);
Log::info('GDPR: Consent recorded', [...]);
Log::warning('ProcessTrackingEvent: Missing website_id', [...]);
```

**Audit note:** Consider structured security event logging for SIEM integration.

## Dependency Security

### Direct Dependencies

- `host-uk/core` - Internal, audited
- `laravel/*` - Well-maintained, security updates

### GeoIP Database

MaxMind GeoLite2 database should be updated regularly:

```bash
# Recommended: weekly cron job
wget -O storage/app/geoip/GeoLite2-City.mmdb https://...
```

## Recommendations

### High Priority

1. Implement per-pixel-key rate limiting
2. Add Form Request classes for input validation
3. Server-side session replay PII detection
4. Complete GDPR export (funnel/experiment data)

### Medium Priority

1. Add policy classes for explicit authorisation
2. Pixel key rotation capability
3. Composite bot scoring (IP + fingerprint)
4. Structured security event logging

### Low Priority

1. IP reputation decay
2. Anomaly detection for abuse patterns
3. CSP header configuration guide

## Audit Checklist

- [ ] All models use BelongsToWorkspace trait
- [ ] API endpoints set workspace context
- [ ] Input validation on all endpoints
- [ ] Rate limiting active
- [ ] GDPR export complete
- [ ] Session replay PII handling documented
- [ ] Bot detection thresholds tuned
- [ ] Cleanup job scheduled
- [ ] GeoIP database current
- [ ] Dependencies updated

## Incident Response

### Data Breach

1. Identify affected workspaces
2. Export affected visitor data
3. Notify workspace owners
4. Delete or anonymise exposed data
5. Rotate affected pixel keys

### Bot Attack

1. Review `analytics_bot_detections` for patterns
2. Add custom blacklist rules
3. Adjust thresholds if needed
4. Consider IP-based blocking at CDN level

### Quota Abuse

1. Identify abusive pixel key
2. Check workspace entitlements
3. Disable tracking for affected website
4. Contact workspace owner
