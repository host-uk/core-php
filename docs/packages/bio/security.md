---
title: Security
description: Security considerations and audit notes for core-bio
updated: 2026-01-29
---

# Security Documentation

This document covers security considerations, threat mitigations, and audit notes for the `core-bio` package.

## Security Model Overview

The `core-bio` package handles user-generated content including HTML, CSS, JavaScript, URLs, and file uploads. Security is enforced at multiple layers:

1. **Input Validation** - Request validation classes
2. **Sanitisation** - Content sanitisers for XSS prevention
3. **Authorisation** - Policies and workspace isolation
4. **Rate Limiting** - Abuse prevention
5. **Output Encoding** - Blade template escaping

## Authentication and Authorisation

### Multi-Tenant Isolation

All data is scoped to workspaces using the `BelongsToWorkspace` trait:

```php
// Automatic query scoping
$biolinks = Page::all(); // Only returns workspace's biolinks

// Automatic workspace assignment on create
$biolink = Page::create([...]); // workspace_id auto-set
```

Without valid workspace context, `MissingWorkspaceContextException` is thrown.

### Policy Enforcement

The `BioPagePolicy` defines access rules:

| Action | Rule |
|--------|------|
| `viewAny` | Any authenticated user |
| `view` | Owner OR workspace member (read-only) |
| `create` | Has access to at least one workspace |
| `update` | Owner only |
| `delete` | Owner only |

**Design decision:** Workspace members can view all biolinks within the workspace but only owners can modify. This enables team visibility while protecting individual content.

### API Authentication

Two authentication methods:
1. **Session auth** - Standard Laravel session cookies
2. **API key auth** - `Authorization: Bearer hk_xxx` header

API routes use `api.auth` middleware with scope enforcement (`api.scope.enforce`):
- GET requests require `read` scope
- POST/PUT/PATCH require `write` scope
- DELETE requires `delete` scope

## XSS Prevention

### Static Page Sanitisation

The `StaticPageSanitiser` service handles user-provided HTML/CSS/JS for static pages:

#### HTML Sanitisation

Uses HTMLPurifier with strict allowlist:

**Allowed elements:**
- Structure: div, span, section, article, header, footer, main, nav, aside
- Text: h1-h6, p, br, hr, strong, em, b, i, u, small, mark, del, ins, sub, sup, code, pre, blockquote
- Lists: ul, ol, li, dl, dt, dd
- Links/Media: a, img, picture, source, video, audio, iframe (restricted)
- Tables: table, thead, tbody, tfoot, tr, th, td, caption
- Forms: form, input, textarea, button, label, select, option, fieldset, legend

**Allowed attributes:**
- Most elements: id, class, style
- Links: href, target, rel
- Images: src, alt, width, height
- iframes: src, width, height (SafeIframe for YouTube/Vimeo only)

**Blocked completely:**
- `<script>` tags
- Event handlers (onclick, onerror, onload, etc.)
- `javascript:` protocol
- `data:text/html` URLs
- `<object>`, `<embed>`, `<meta>` tags

#### CSS Sanitisation

Pattern-based blocking with Unicode decode protection:

```php
// Blocked patterns
'expression('     // IE CSS expressions
'javascript:'     // Protocol in url()
'vbscript:'       // Protocol in url()
'@import'         // External CSS loading
'behavior:'       // IE HTC files
'-moz-binding:'   // Firefox XBL (deprecated)
'data:text/html'  // Data URLs with scripts
```

**Unicode escape handling:**
The sanitiser decodes CSS unicode escapes (`\6A\61\76\61` = "java") before checking for dangerous patterns. Multiple passes handle:
- Hex escapes: `\XX` where XX is 1-6 hex digits
- Character escapes: `\j` for literal 'j'
- Whitespace obfuscation: `j a v a s c r i p t :`

#### JavaScript Sanitisation

**IMPORTANT LIMITATION:** The JS sanitiser uses blocklist (regex) approach which has inherent limitations.

The sanitiser blocks known dangerous patterns including:
- Dynamic code execution constructs (Function constructor, etc.)
- DOM manipulation methods that can inject HTML
- innerHTML/outerHTML assignments
- Dangerous protocol handlers
- Cookie access
- Location manipulation

**Known limitations (documented in code):**
1. Obfuscation bypass - Attackers may use encoding, Unicode, or string concatenation
2. New attack vectors - Future JS features may introduce new vectors
3. Context-dependent - Some attacks depend on page context

**Recommendations:**
- Custom JS is permitted for Pro/Ultimate users at their own risk
- Users warned that custom JS has full page privileges
- For higher security, consider sandboxing (iframe sandbox, Web Workers)
- Monitor for abuse patterns in production

### Test Coverage

`StaticPageSecurityTest.php` covers 28+ attack vectors including:
- Script tag injection
- Event handler injection (onerror, onload, onfocus)
- Protocol handler attacks (javascript:, data:)
- Inline event handlers
- CSS expression attacks
- CSS import attacks
- Various DOM manipulation attempts

## CSRF Protection

All web routes use Laravel's `web` middleware which includes CSRF protection via `@csrf` tokens.

API routes use `api` middleware (no CSRF) but require authentication.

## SQL Injection Prevention

All database queries use Eloquent ORM or Query Builder with parameter binding:

```php
// Safe - parameterised query
Page::where('url', $userInput)->first();

// Safe - escaped LIKE wildcards
$search = str_replace(['%', '_'], ['\\%', '\\_'], $userInput);
$query->where('url', 'like', '%'.$search.'%');
```

No raw SQL queries with string concatenation exist in the codebase.

## Rate Limiting

### Public Endpoints

```php
// General click tracking
Route::middleware('throttle:120,1')  // 120 requests per minute

// Form submissions (stricter)
Route::middleware('throttle:10,1')   // 10 requests per minute
```

### Password Protection

The `BioPasswordRateLimiter` implements exponential backoff:

| Attempt | Lockout Duration |
|---------|------------------|
| 1-5 | 60 seconds |
| 6-10 | 120 seconds |
| 11-15 | 240 seconds |
| 16-20 | 480 seconds |
| 21-25 | 960 seconds |
| 26-30 | 1920 seconds |
| 31+ | 3600 seconds (max) |

Key features:
- Rate limit is per-biolink + per-IP
- Backoff level persists for 24 hours
- Successful login clears rate limit but NOT backoff level
- Password change resets everything

## File Upload Security

File links and other components accept user uploads. The `ValidatesFileUploads` trait provides comprehensive security validation:

### Security Measures

1. **MIME Type Validation (Magic Bytes)** - File content is validated against expected magic byte signatures, not just the extension. This prevents attackers from uploading executable files disguised as images.

2. **File Size Limits** - Configurable per-upload limits enforced at validation time.

3. **Image Dimension Limits** - Prevents decompression bombs:
   - Maximum dimensions: 8192 x 8192 pixels
   - Maximum total pixels: 50 megapixels
   - Uses `getimagesize()` which reads only headers (memory-efficient)

4. **Path Traversal Prevention** - Filenames are sanitised to:
   - Remove path components (`../`, `..\`)
   - Strip null bytes
   - Remove dangerous characters
   - Collapse multiple dots
   - Enforce maximum length (255 chars)

5. **SVG XSS Protection** - SVG files are scanned for dangerous patterns:
   - `<script>` tags
   - Event handlers (`onload=`, `onerror=`, `onclick=`, etc.)
   - `javascript:` and `vbscript:` protocols
   - `data:text/html` URLs
   - `<foreignObject>`, `<iframe>`, `<embed>`, `<object>` elements
   - CSS expressions (`expression()`, `behavior:`, `-moz-binding:`)
   - XML entity definitions (`<!ENTITY`)

6. **Storage Isolation** - Files stored outside web root in local filesystem.

### Usage in Components

The trait is used in all Livewire components that handle file uploads:

```php
use Core\Mod\Bio\Concerns\ValidatesFileUploads;

class CreateFileLink extends Component
{
    use ValidatesFileUploads;
    use WithFileUploads;

    public function updatedFile(): void
    {
        $result = $this->validateUploadedFile(
            $this->file,
            'general',    // Category: 'images', 'documents', 'audio', 'video', 'archives', 'general'
            50 * 1024,    // Max size in KB
            4096,         // Max width (optional, for images)
            4096          // Max height (optional, for images)
        );

        if (!$result['valid']) {
            $this->addError('file', $result['error']);
            $this->file = null;
            return;
        }

        $sanitisedFilename = $result['sanitised_filename'];
    }
}
```

### Allowed Extensions by Category

```php
'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf', 'odt', 'ods', 'odp'],
'audio' => ['mp3', 'wav', 'ogg', 'm4a'],
'video' => ['mp4', 'webm', 'mov', 'avi'],
'archives' => ['zip', 'rar', '7z', 'tar', 'gz'],
```

### Test Coverage

Comprehensive tests in `tests/Unit/ValidatesFileUploadsTest.php` cover:
- File size validation
- Extension validation
- MIME type magic byte verification
- Filename sanitisation (path traversal, null bytes, dangerous chars)
- Image dimension validation
- SVG XSS attack detection (script tags, event handlers, javascript: protocol, foreignObject, XML entities)

## Domain Verification

Custom domains require DNS verification to prevent domain hijacking:

### Verification Methods

1. **TXT Record** (preferred)
   - User adds `TXT` record at `_biohost-verify.domain.com`
   - Value: `host-uk-verify={64-char-token}`
   - Proves ownership without pointing domain

2. **CNAME Record** (fallback)
   - Domain CNAMEs to `bio.host.uk.com`
   - Requires full DNS configuration

### Token Security

- Tokens generated with `bin2hex(random_bytes(32))` - 64 hex chars
- Token comparison uses `hash_equals()` for timing attack resistance
- Tokens can be regenerated if compromised

### Reserved Domains

Blocked domains that cannot be claimed:
- `host.uk.com` and subdomains
- `lnktr.fyi`
- Any subdomain of platform domains

## Webhook Security (Notification Handlers)

**Current state:** Notification handlers can send data to arbitrary URLs.

**Recommended improvements:**
1. Validate against private IP ranges (SSRF prevention)
2. Implement webhook signing for payload verification
3. Consider URL allowlist for enterprise customers
4. Log all webhook deliveries for audit trail

## Privacy Considerations

### Analytics Data

- IP addresses are hashed daily with `sha256(date + ip + secret)`
- Hashing prevents individual tracking across days
- Raw IPs never stored in click records
- Country/city derived from IP at collection time only

### Cookie Usage

- Session cookies for authentication
- Password-protected page access stored in session
- No third-party cookies set by the module

### Data Retention

Analytics data retention is controlled by workspace entitlements:
- Default: 30 days
- Configurable per plan via `bio.analytics_days` entitlement

## Security Headers

Password-protected pages set no-cache headers:

```php
return response()->view('bio.password', [...], 200, [
    'Cache-Control' => 'no-store, private',
]);
```

Additional recommended headers (application-level):
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY` (for admin pages)
- `Content-Security-Policy` (application-specific)

## Audit Log

Changes to biolinks are tracked via Spatie Activity Log:

```php
class Page extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['url', 'type', 'settings', 'is_enabled', 'domain_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

## Resolved Security Items (2026-01-29)

The following P1 security items have been resolved:

1. **MCP Tools workspace validation** - FIXED
   - All MCP tools now require `workspace_id` in context
   - Queries scoped by workspace, preventing cross-tenant access
   - Tests in `tests/Feature/Mcp/WorkspaceIsolationTest.php`

2. **Block type validation in MCP** - FIXED
   - Block types validated against `config('webpage.block_types')`
   - Tier entitlements checked for pro/ultimate/payment blocks
   - Tests in `tests/Feature/Mcp/BioToolsTest.php`

3. **Webhook URL validation** - FIXED
   - SSRF protection via `PreventsSSRF` trait
   - Blocks private IPs, local domains, encoded addresses
   - DNS rebinding protection
   - Tests in `tests/Unit/PreventsSSRFTest.php`

4. **File upload validation** - FIXED
   - MIME type validation via magic bytes
   - Image dimension limits (decompression bomb protection)
   - Path traversal prevention
   - SVG XSS protection
   - Tests in `tests/Unit/ValidatesFileUploadsTest.php`

## Security Testing

Run security-focused tests:

```bash
# Run all security tests
./vendor/bin/pest tests/Feature/StaticPageSecurityTest.php

# Run with verbose output
./vendor/bin/pest tests/Feature/StaticPageSecurityTest.php -v
```

## Reporting Vulnerabilities

Security vulnerabilities should be reported via:
- Email: security@host.uk.com
- Do not open public GitHub issues for security bugs

## Changelog

### 2026-01-21

- Added exponential backoff to password rate limiter
- Added job error logging for abuse monitoring
- Escaped LIKE wildcards in API search
- Documented JS sanitiser limitations

### 2026-01-03

- Verified all 61 acceptance criteria for security compliance
- Documented policy permissions as intentional design
- Fixed workspace_id assignment on sub-page creation
