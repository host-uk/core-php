---
title: Architecture
description: Technical architecture of the core-tools package
updated: 2026-01-29
---

# Architecture

This document describes the technical architecture of `host-uk/core-tools`, a Laravel package providing 40+ utility tools (JSON formatter, DNS lookup, password generator, etc.) for the Host UK platform.

## Overview

The package follows the Core PHP Framework's event-driven module system. Tools are registered as services and exposed through:
- **Web UI** - Livewire components at `/tools/{slug}`
- **REST API** - JSON endpoints at `/api/tools/`
- **MCP** - Model Context Protocol for AI agents

## Directory Structure

```
core-tools/
├── Boot.php                    # Service provider with event listeners
├── config.php                  # Configuration (caching, rate limits, timeouts)
├── Concerns/
│   └── PreventsSSRF.php       # SSRF protection trait for network tools
├── Controllers/
│   ├── Api/
│   │   ├── ToolsApiController.php   # REST API for tools
│   │   └── BatchToolController.php  # Batch operations API
│   └── ShortUrlRedirectController.php
├── Enums/
│   └── BatchToolStatus.php    # pending, processing, completed, failed, cancelled
├── Jobs/
│   └── ProcessBatchToolOperation.php
├── Lang/en_GB/                # Translations (UK English)
├── Mcp/Tools/
│   └── UtilityTools.php       # MCP tool handler
├── Migrations/                 # Database migrations
├── Models/
│   ├── ToolUsage.php          # Usage tracking and favourites
│   ├── ShortUrl.php           # URL shortener storage
│   └── BatchToolOperation.php # Batch operation records
├── Notifications/
│   └── BatchToolCompleted.php
├── routes/
│   ├── web.php                # Public routes (/tools/*)
│   ├── api.php                # API routes (/api/tools/*)
│   ├── admin.php              # Admin panel routes
│   └── console.php            # Artisan commands
├── Services/
│   ├── ToolService.php        # Abstract base class for all tools
│   ├── ToolManager.php        # Tool registry and discovery
│   ├── ToolHistoryService.php # User history and favourites
│   ├── BatchToolService.php   # Batch operation management
│   ├── GeoIpService.php       # IP geolocation provider chain
│   └── *Tool.php              # Individual tool implementations (40+)
├── View/
│   ├── Blade/                 # Blade templates
│   │   ├── web/               # Public tool pages
│   │   ├── admin/             # Admin analytics
│   │   └── components/        # Reusable components
│   └── Modal/                 # Livewire components
│       ├── Web/
│       │   ├── ToolsIndex.php # Tool listing page
│       │   └── ToolPage.php   # Individual tool page
│       └── Admin/
│           └── UsageAnalytics.php
└── tests/
    ├── Unit/                  # Tool-specific unit tests
    └── Feature/               # Integration and API tests
```

## Core Concepts

### ToolService (Abstract Base)

All tools extend `ToolService` and implement:

```php
abstract class ToolService
{
    // Required
    abstract public function getSlug(): string;
    abstract public function getName(): string;
    abstract public function getDescription(): string;
    abstract public function getIcon(): string;
    abstract public function execute(array $input): mixed;

    // Optional overrides
    public function validate(array $input): array { return []; }
    public function requiresAuth(): bool { return false; }
    public function getRequiredEntitlement(): ?string { return null; }
    public function requiresRateLimiting(): bool { return false; }
    public function supportsCaching(): bool { return false; }
    public function isAvailableViaMcp(): bool { return true; }

    // Helper methods
    protected function errorResponse(string $message, array $context = []): array;
    protected function successResponse(array $data): array;
}
```

### ToolManager

Central registry for all tools. Registered as a singleton:

```php
// Get a tool
$tool = app(ToolManager::class)->getTool('hash-generator');

// List all tools
$tools = app(ToolManager::class)->getAllTools();

// Group by category
$categories = app(ToolManager::class)->getToolsByCategory();
```

Categories: Marketing, Development, Design, Security, Network, Text, Converters, Generators, Link Generators, Miscellaneous.

### Event-Driven Registration

The package uses Core PHP Framework's lazy-loading event system:

```php
// Boot.php
public static array $listens = [
    AdminPanelBooting::class => 'onAdminPanel',
    WebRoutesRegistering::class => 'onWebRoutes',
    ApiRoutesRegistering::class => 'onApiRoutes',
    ConsoleBooting::class => 'onConsole',
];
```

Handlers are only invoked when the corresponding event fires, reducing boot overhead.

## Tool Categories

### Public Tools (No Auth Required)
- Hash Generator, UUID Generator, Password Generator
- Base64 Encoder, URL Encoder, JSON Formatter
- Lorem Ipsum, Colour Picker, Case Converter
- And more...

### Premium Tools (Auth + Entitlement Required)
- DNS Lookup (`tool.dns_lookup`)
- WHOIS Lookup (`tool.whois_lookup`)
- SSL Certificate Lookup (`tool.ssl_lookup`)
- HTTP Headers Lookup (`tool.http_headers`)
- IP Lookup (`tool.ip_lookup`)
- URL Shortener (`tool.url_shortener`)

### Network Tools Architecture

Network tools that make external requests share common patterns:

1. **SSRF Protection** - Use `PreventsSSRF` trait
2. **Rate Limiting** - Override `requiresRateLimiting(): bool`
3. **Caching** - Override `supportsCaching(): bool`
4. **Timeouts** - Configured via `config.php`

```php
class HttpHeadersTool extends ToolService
{
    use PreventsSSRF;

    public function requiresRateLimiting(): bool { return true; }
    public function supportsCaching(): bool { return true; }
    public function requiresAuth(): bool { return true; }
    public function getRequiredEntitlement(): ?string { return 'tool.http_headers'; }
}
```

## API Architecture

### Public API (Rate Limited)

```
GET  /api/tools                    - List all tools
GET  /api/tools/categories         - List tools by category
GET  /api/tools/{slug}             - Get tool info
POST /api/tools/{slug}/execute     - Execute tool (public tools only)
POST /api/tools/batch              - Batch execute (legacy)
```

### Authenticated API

```
POST /api/tools/{slug}/execute     - Execute any tool (with auth)
GET  /api/tools/batch              - List user's batches
POST /api/tools/batch              - Create batch operation
GET  /api/tools/batch/{uuid}       - Get batch status/results
POST /api/tools/batch/{uuid}/cancel
```

### API Key API (Bearer Token)

Same endpoints as authenticated API, using `Authorization: Bearer hk_xxx` header.

## Batch Operations

For processing multiple inputs through a single tool:

1. **Synchronous** - Up to 10 inputs processed immediately
2. **Asynchronous** - >10 inputs queued via `ProcessBatchToolOperation` job

Supported tools: `url-shortener`, `qr-code-generator`, `hash-generator`, `uuid-generator`, `slug-generator`, `base64-encoder`, `url-encoder`, `case-converter`.

```php
// Create batch
$result = BatchToolService::createBatch($user, 'hash-generator', [
    ['text' => 'input1'],
    ['text' => 'input2'],
    // ...
]);

// Check progress
$progress = BatchToolService::getProgress($batch);
```

## Security Measures

### SSRF Protection (`PreventsSSRF` trait)

Prevents Server-Side Request Forgery in network tools:

- Blocks localhost and loopback addresses (127.0.0.0/8, ::1)
- Blocks private networks (10.x, 172.16.x, 192.168.x)
- Blocks link-local addresses (169.254.x, fe80::)
- Normalises non-standard IP encodings (decimal, octal, hex)
- Validates all DNS-resolved IPs before connecting
- Blocks .local, .localhost, .internal hostnames

### Rate Limiting

Per-tool rate limits configured in `config.php`:

```php
'rate_limiting' => [
    'enabled' => true,
    'default_limit' => 30, // requests per minute
    'tools' => [
        'dns-lookup' => 30,
        'whois-lookup' => 10,
        'ssl-lookup' => 20,
        'http-headers' => 20,
        'ip-lookup' => 30,
    ],
],
```

### Input Validation

Tools implement `validate(array $input): array` returning errors:

```php
public function validate(array $input): array
{
    $errors = [];
    if (empty($input['url'])) {
        $errors['url'] = 'URL is required';
    }
    return $errors;
}
```

### Entitlement Checks

Premium tools require both authentication and entitlement:

```php
$entitlements = app(EntitlementService::class);
$result = $entitlements->can($workspace, 'tool.dns_lookup');
if (!$result->isAllowed()) {
    // Block access
}
```

## Caching Strategy

Network tools cache results to reduce external requests:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 300, // 5 minutes default
    'tools' => [
        'dns-lookup' => 300,
        'whois-lookup' => 3600, // 1 hour
        'ssl-lookup' => 3600,
        'http-headers' => 60,
        'ip-lookup' => 3600,
    ],
    'prefix' => 'tools:cache:',
],
```

Cache keys include tool slug and input hash for uniqueness.

## GeoIP Service

IP Lookup uses a provider chain with fallback:

1. **MaxMind Database** - Local GeoLite2/GeoIP2 database (fastest)
2. **MaxMind API** - Web service (paid, most accurate)
3. **ip-api.com** - Free, 45 req/min limit
4. **ipinfo.io** - 50k/month free tier

Configure in `config.php` under `geoip`.

## Database Schema

### tool_usages
```sql
- id, workspace_id, user_id, tool, action
- input (JSON), output (JSON), input_hash
- is_favourite, usage_count
- ip_address (hashed), user_agent, duration_ms
- created_at, updated_at
```

### tool_short_urls
```sql
- id, code (unique), original_url
- clicks, created_by, expires_at
- created_at, updated_at
```

### tool_batch_operations
```sql
- id, uuid (unique), user_id, workspace_id
- tool_name, inputs (JSON), results (JSON)
- status, total_count, completed_count, failed_count
- started_at, completed_at, created_at, updated_at
```

## Adding a New Tool

1. Create `Services/{ToolName}Tool.php` extending `ToolService`
2. Implement required methods: `getSlug()`, `getName()`, `getDescription()`, `getIcon()`, `execute()`
3. Add to `ToolManager::registerTools()` under appropriate category
4. Create Blade partial at `View/Blade/web/partials/{slug}.blade.php` (optional, uses generic-tool.blade.php by default)
5. Add tests in `tests/Unit/`
6. Run `composer lint && composer test`

```php
class MyNewTool extends ToolService
{
    public function getSlug(): string { return 'my-new-tool'; }
    public function getName(): string { return 'My New Tool'; }
    public function getDescription(): string { return 'Does something useful.'; }
    public function getIcon(): string { return 'wrench'; }

    public function execute(array $input): array
    {
        $result = // ... process input
        return $this->successResponse(['output' => $result]);
    }

    public function validate(array $input): array
    {
        $errors = [];
        if (empty($input['required_field'])) {
            $errors['required_field'] = 'This field is required';
        }
        return $errors;
    }
}
```

## Testing

```bash
composer test                      # Run all tests
composer test -- --filter=Hash     # Run specific tests
composer test -- --group=slow      # Run slow (network) tests
```

Test organisation:
- `Unit/HashToolsTest.php` - Crypto/hash tools
- `Unit/NetworkToolsTest.php` - Network tools (DNS, SSL, etc.)
- `Unit/EncodingToolsTest.php` - Encoding tools
- `Unit/ConverterToolsTest.php` - Converter tools
- `Feature/ToolsTest.php` - Livewire component tests
- `Feature/Api/ToolsApiTest.php` - API endpoint tests
