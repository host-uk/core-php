# Security Changelog

This page documents all security-related changes, fixes, and improvements to Core PHP Framework.

## 2026

### January 2026

#### Core MCP Package

**SQL Query Validation Improvements**
- **Type:** Security Enhancement
- **Severity:** High
- **Impact:** Strengthened SQL injection prevention
- **Changes:**
  - Replaced permissive `.+` regex patterns with restrictive character class validation
  - Added explicit WHERE clause structure validation
  - Improved pattern detection for SQL injection attempts
- **Commit:** [View changes](/packages/core-mcp/changelog/2026/jan/security)

**Database Connection Validation**
- **Type:** Security Fix
- **Severity:** Critical
- **Impact:** Prevents silent fallback to default database connection
- **Changes:**
  - Added exception throwing for invalid database connections
  - Prevents accidental exposure of production data
  - Enforces explicit connection configuration
- **Commit:** [View changes](/packages/core-mcp/changelog/2026/jan/security)

#### Core API Package

**API Key Secure Hashing**
- **Type:** Security Feature
- **Severity:** High
- **Impact:** API keys now hashed with bcrypt, never stored in plaintext
- **Changes:**
  - Bcrypt hashing for all API keys
  - Secure key rotation with grace period
  - Plaintext key only shown once at creation
- **Commit:** [View changes](/packages/core-api/changelog/2026/jan/features)

**Webhook Signature Verification**
- **Type:** Security Feature
- **Severity:** High
- **Impact:** HMAC-SHA256 signatures prevent webhook tampering
- **Changes:**
  - Added HMAC-SHA256 signature generation
  - Timestamp-based replay attack prevention
  - Configurable signature verification
- **Commit:** [View changes](/packages/core-api/changelog/2026/jan/features)

**Scope-Based Authorization**
- **Type:** Security Feature
- **Severity:** Medium
- **Impact:** Fine-grained API permissions
- **Changes:**
  - Middleware-enforced scope checking
  - Per-endpoint scope requirements
  - Scope validation in requests
- **Commit:** [View changes](/packages/core-api/changelog/2026/jan/features)

#### Core PHP Package

**Security Headers Enhancement**
- **Type:** Security Feature
- **Severity:** Medium
- **Impact:** Comprehensive protection against common web attacks
- **Changes:**
  - Content Security Policy (CSP) with nonce support
  - HTTP Strict Transport Security (HSTS)
  - X-Frame-Options, X-Content-Type-Options
  - Referrer-Policy configuration
- **Commit:** [View changes](/packages/core-php/changelog/2026/jan/features)

**Action Gate System**
- **Type:** Security Feature
- **Severity:** Medium
- **Impact:** Request whitelisting for sensitive operations
- **Changes:**
  - Training mode for learning valid requests
  - Enforcement mode with blocking
  - Audit logging for all requests
- **Commit:** [View changes](/packages/core-php/changelog/2026/jan/features)

**IP Blocklist Service**
- **Type:** Security Feature
- **Severity:** Low
- **Impact:** Automatic blocking of malicious IPs
- **Changes:**
  - Temporary and permanent IP blocks
  - Reason tracking and audit trail
  - Automatic expiry support
- **Commit:** [View changes](/packages/core-php/changelog/2026/jan/features)

**GDPR-Compliant Activity Logging**
- **Type:** Privacy Enhancement
- **Severity:** Medium
- **Impact:** Activity logs respect privacy regulations
- **Changes:**
  - IP address logging disabled by default
  - Configurable retention periods
  - Automatic anonymization support
  - User data deletion on account closure
- **Commit:** [View changes](/packages/core-php/changelog/2026/jan/features)

**Referral Tracking IP Hashing**
- **Type:** Privacy Fix
- **Severity:** Medium
- **Impact:** IP addresses hashed in referral tracking
- **Changes:**
  - SHA-256 hashing of IP addresses
  - Cannot reverse to identify users
  - GDPR compliance
- **Commit:** c8dfc2a

---

## Reporting Security Issues

If you discover a security vulnerability, please follow our [Responsible Disclosure](/security/responsible-disclosure) policy.

**Contact:** dev@host.uk.com

## Security Update Policy

### Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

### Update Schedule

- **Critical vulnerabilities:** Patch within 24-48 hours
- **High severity:** Patch within 7 days
- **Medium severity:** Patch within 30 days
- **Low severity:** Patch in next minor release

### Notification Channels

Security updates are announced via:
- GitHub Security Advisories
- Release notes
- Email to registered users (critical only)

## Security Best Practices

### For Users

1. **Keep Updated** - Always use the latest stable release
2. **Review Configurations** - Audit security settings regularly
3. **Monitor Logs** - Check activity logs for suspicious behavior
4. **Use HTTPS** - Always enforce HTTPS in production
5. **Rotate Keys** - Regularly rotate API keys and secrets

### For Contributors

1. **Security-First** - Consider security implications of all changes
2. **Input Validation** - Validate and sanitize all user input
3. **Output Encoding** - Properly encode output to prevent XSS
4. **Parameterized Queries** - Always use Eloquent or parameterized queries
5. **Authorization Checks** - Verify permissions before actions

## Security Features Summary

### Authentication & Authorization
- Bcrypt password hashing with automatic rehashing
- Two-factor authentication (TOTP)
- Session security (secure cookies, HTTP-only)
- API key authentication with bcrypt hashing
- Scope-based API permissions
- Policy-based authorization

### Data Protection
- Multi-tenant workspace isolation
- Namespace-based resource boundaries
- Automatic query scoping
- Workspace context validation
- Cache isolation per workspace

### Input/Output Security
- Comprehensive input sanitization
- XSS prevention (Blade auto-escaping)
- SQL injection prevention (Eloquent ORM)
- CSRF protection (Laravel default)
- Mass assignment protection

### API Security
- Rate limiting per tier
- Webhook signature verification (HMAC-SHA256)
- Scope enforcement
- API key rotation
- Usage tracking and alerts

### Infrastructure Security
- Security headers (CSP, HSTS, etc.)
- IP blocklist
- Action gate (request whitelisting)
- SQL query validation
- Email validation (disposable detection)

### Compliance
- Activity logging with audit trails
- GDPR-compliant data handling
- Configurable data retention
- Automatic data anonymization
- Right to be forgotten support

## Historical Vulnerabilities

No vulnerabilities have been publicly disclosed for Core PHP Framework.

---

**Last Updated:** January 2026

For the latest security information, always refer to:
- [Security Overview](/security/overview)
- [GitHub Security Advisories](https://github.com/host-uk/core-php/security/advisories)
- [Responsible Disclosure Policy](/security/responsible-disclosure)
