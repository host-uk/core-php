---
title: Security
description: Security considerations and audit notes for core-tools
updated: 2026-01-29
---

# Security

This document covers security considerations, implemented protections, and audit notes for the `core-tools` package.

## Threat Model

The tools package exposes both public and authenticated endpoints that:
- Accept user input and process it
- Make external network requests (DNS, HTTP, WHOIS, SSL)
- Store user data (short URLs, usage history, batch operations)
- Execute on behalf of authenticated users

Primary threats:
1. **SSRF** - Server-Side Request Forgery via network tools
2. **Injection** - Command/protocol injection via input
3. **DoS** - Resource exhaustion via large inputs or rate abuse
4. **Open Redirect** - URL shortener as phishing vector
5. **Information Disclosure** - Leaking internal network topology
6. **Privilege Escalation** - Bypassing entitlement checks

## Implemented Protections

### SSRF Protection (PreventsSSRF Trait)

Network tools use the `PreventsSSRF` trait which:

**IP Address Blocking**
- 127.0.0.0/8 (localhost range)
- ::1 (IPv6 localhost)
- 0.0.0.0/8 (current network)
- 10.0.0.0/8 (private)
- 172.16.0.0/12 (private)
- 192.168.0.0/16 (private)
- 169.254.0.0/16 (link-local)
- fe80::/10 (IPv6 link-local)

**Non-Standard IP Normalisation**
Attackers may encode IPs to bypass filters:
```
2130706433       -> 127.0.0.1 (decimal)
0177.0.0.1       -> 127.0.0.1 (octal)
0x7f.0.0.1       -> 127.0.0.1 (hex)
0x7f000001       -> 127.0.0.1 (hex single)
127.1            -> 127.0.0.1 (compressed)
::ffff:127.0.0.1 -> 127.0.0.1 (IPv4-mapped IPv6)
```

The trait normalises all formats before validation.

**Hostname Blocking**
- `localhost`
- `*.local` (mDNS)
- `*.localhost` (RFC 6761)
- `*.internal` (convention)

**DNS Rebinding Protection**
1. Resolve hostname to all IPs (A + AAAA records)
2. Validate ALL resolved IPs are public
3. Connect using resolved IP, not hostname
4. Use original hostname for SNI/Host header

**Implementation Status**
| Tool | Uses PreventsSSRF | DNS Rebinding Protected |
|------|-------------------|-------------------------|
| HttpHeadersTool | Yes | Yes |
| SslLookupTool | Yes | Yes |
| DnsLookupTool | No* | N/A |
| WhoisLookupTool | No** | N/A |
| IpLookupTool | N/A*** | N/A |

\* DNS lookup itself doesn't make HTTP requests but should validate input hostnames
\** WHOIS uses hardcoded server list, not user-controlled
\*** IP lookup receives IP, not hostname

### Rate Limiting

**Per-Tool Limits** (requests per minute per IP/user)
| Tool | Default Limit |
|------|---------------|
| dns-lookup | 30 |
| whois-lookup | 10 |
| ssl-lookup | 20 |
| http-headers | 20 |
| ip-lookup | 30 |
| (other tools) | 30 |

Rate limiter uses Laravel's `RateLimiter` with per-tool keys:
```
tool:{slug}:{user_id|ip}
```

**API-Level Limits**
- Public endpoints: 60 requests/minute
- Batch endpoint: 10 requests/minute

### Input Validation

**Size Limits**
- Image converter: 10MB max input, 8192px max dimension
- Batch operations: 100 inputs max
- Short URL slug: 3-20 characters

**Format Validation**
- URLs: `FILTER_VALIDATE_URL` + scheme check
- IPs: `FILTER_VALIDATE_IP`
- Hostnames: Regex validation
- JSON: `json_decode` with error checking

**Protocol Injection Prevention**
- WHOIS: Strips control characters (`\x00-\x1F\x7F`)
- HTTP: Uses `stream_context_create` with controlled parameters

### Authentication & Authorisation

**Three-Tier Access**
1. **Public** - No auth required (most tools)
2. **Authenticated** - Requires login (`requiresAuth(): true`)
3. **Entitled** - Requires subscription (`getRequiredEntitlement(): string`)

**Entitlement Codes**
- `tool.dns_lookup`
- `tool.whois_lookup`
- `tool.ssl_lookup`
- `tool.http_headers`
- `tool.ip_lookup`
- `tool.url_shortener`

**Check Flow (ToolPage.php)**
```php
protected function checkEntitlements($tool): void
{
    // Public tool = entitled
    if ($tool->isPublic()) {
        $this->isEntitled = true;
        return;
    }

    // Auth required but not logged in
    if ($tool->requiresAuth() && !$this->isAuthenticated) {
        $this->isEntitled = false;
        return;
    }

    // Check specific entitlement
    $entitlementCode = $tool->getRequiredEntitlement();
    if ($entitlementCode && $this->isAuthenticated) {
        $result = $entitlements->can($workspace, $entitlementCode);
        $this->isEntitled = $result->isAllowed();
    }
}
```

**Re-validation on Execute**
Entitlements are re-checked on every `execute()` call to prevent bypass via direct Livewire method calls.

### Privacy

**IP Address Hashing**
User IPs in `tool_usages` are hashed via `PrivacyHelper::hashIp()` - consistent for analytics but not reversible.

**No PII in Logs**
Tool inputs/outputs may contain sensitive data; they're stored in database but not logged.

### Caching Security

**Cache Key Structure**
```
tools:cache:{slug}:{md5(json(input))}
```

- Keys are namespaced to prevent collision
- Input is hashed, not stored in key directly
- TTL prevents stale data accumulation

**Cache Poisoning Prevention**
- Only successful results are cached
- Error responses are never cached
- `cached: true` flag indicates cached response

## Known Vulnerabilities & Mitigations

### URL Shortener Open Redirect (TODO: SEC-002)

**Issue**: The URL shortener accepts any URL and redirects without validation.

**Risk**: Could be used for phishing (attacker creates short link to malicious site appearing to be from trusted domain).

**Current Mitigations**:
- Requires authentication to create short URLs
- Requires entitlement (`tool.url_shortener`)
- Short URLs are tied to creator for accountability

**Recommended Actions**:
- Block javascript:, data:, file: schemes
- Block internal network URLs
- Consider redirect interstitial page

### DnsLookupTool Input Validation (TODO: SEC-001)

**Issue**: Accepts any hostname without validating it's not internal.

**Risk**: Low - DNS lookup doesn't make HTTP requests, but could leak internal DNS names.

**Recommended Actions**:
- Add `.local`, `.internal` hostname blocking
- Consider logging suspicious queries

### ImageConverterTool Decompression Bomb (TODO: SEC-004)

**Issue**: Size validation is on base64 string, not decoded image dimensions.

**Risk**: A small base64 string could decode to a massive image consuming server memory.

**Current Mitigations**:
- 10MB base64 size limit
- 8192px max dimension (checked after decode)

**Recommended Actions**:
- Validate dimensions immediately after base64 decode
- Add memory limit guards
- Consider sandboxed processing

## Audit Checklist

### Authentication & Authorisation
- [x] Public tools accessible without auth
- [x] Premium tools require auth
- [x] Premium tools require entitlement
- [x] Entitlements re-checked on execute
- [x] Batch operations require auth
- [x] Batch ownership validated on access

### Input Validation
- [x] All tools have validate() method
- [x] URL validation on URL-accepting tools
- [x] IP validation on IP-accepting tools
- [x] Size limits on file uploads
- [x] JSON parsing with error handling
- [ ] Hostname validation on DnsLookupTool
- [ ] URL scheme validation on UrlShortenerTool

### SSRF Protection
- [x] HttpHeadersTool uses PreventsSSRF
- [x] SslLookupTool uses PreventsSSRF
- [x] DNS rebinding protection via IP resolution
- [x] Non-standard IP format normalisation
- [x] Private IP range blocking
- [x] Local hostname blocking
- [ ] DnsLookupTool hostname validation

### Rate Limiting
- [x] Network tools have rate limits
- [x] Batch endpoint has stricter limits
- [x] Rate limit headers in responses
- [x] Per-user/IP rate tracking

### Protocol Security
- [x] WHOIS input sanitisation (control chars)
- [x] HTTP tool uses stream context
- [x] SSL tool uses SNI correctly
- [x] No command injection vectors

### Data Protection
- [x] IP addresses hashed in storage
- [x] No PII in application logs
- [x] Cache keys don't expose input
- [x] Batch results scoped to owner

## Security Contacts

Report security issues to: security@host.uk.com

For responsible disclosure, please allow 90 days before public disclosure.
