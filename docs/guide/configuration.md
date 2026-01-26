# Configuration

Core PHP Framework provides extensive configuration options for all packages. This guide covers the configuration system and available options.

## Configuration System

Core PHP uses Laravel's configuration system with multi-profile support for environment-specific settings.

### Publishing Configuration

Publish configuration files for the packages you need:

```bash
# Publish all core configurations
php artisan vendor:publish --tag=core-config

# Publish specific package configs
php artisan vendor:publish --tag=core-admin-config
php artisan vendor:publish --tag=core-api-config
php artisan vendor:publish --tag=core-mcp-config
```

This creates configuration files in your `config/` directory:

- `config/core.php` - Core framework settings
- `config/core-admin.php` - Admin panel configuration
- `config/core-api.php` - API configuration
- `config/core-mcp.php` - MCP tools configuration

## Core Configuration

Location: `config/core.php`

### Module Paths

Define where the framework scans for modules:

```php
'module_paths' => [
    app_path('Core'),
    app_path('Mod'),
    app_path('Plug'),
    base_path('packages'),
],
```

### Module Discovery

Control module auto-discovery behavior:

```php
'modules' => [
    'auto_discover' => env('MODULES_AUTO_DISCOVER', true),
    'cache_enabled' => env('MODULES_CACHE_ENABLED', true),
    'cache_key' => 'core:modules:discovered',
],
```

### Seeder Configuration

Configure automatic seeder discovery and ordering:

```php
'seeders' => [
    'auto_discover' => env('SEEDERS_AUTO_DISCOVER', true),
    'paths' => [
        'Mod/*/Database/Seeders',
        'Core/*/Database/Seeders',
    ],
    'exclude' => [
        'DatabaseSeeder',
        'CoreDatabaseSeeder',
    ],
],
```

### Activity Logging

Configure activity log retention and behavior:

```php
'activity' => [
    'enabled' => env('ACTIVITY_LOG_ENABLED', true),
    'retention_days' => env('ACTIVITY_RETENTION_DAYS', 90),
    'cleanup_enabled' => true,
    'log_ip_address' => false, // GDPR compliance
],
```

### Workspace Cache

Configure team-scoped caching:

```php
'workspace_cache' => [
    'enabled' => env('WORKSPACE_CACHE_ENABLED', true),
    'ttl' => env('WORKSPACE_CACHE_TTL', 3600),
    'use_tags' => env('WORKSPACE_CACHE_USE_TAGS', true),
    'prefix' => 'workspace',
],
```

### Action Gate System

Configure request whitelisting for sensitive operations:

```php
'bouncer' => [
    'enabled' => env('ACTION_GATE_ENABLED', true),
    'training_mode' => env('ACTION_GATE_TRAINING', false),
    'block_unauthorized' => true,
    'log_all_requests' => true,
],
```

### CDN Configuration

Configure CDN and storage offloading:

```php
'cdn' => [
    'enabled' => env('CDN_ENABLED', false),
    'provider' => env('CDN_PROVIDER', 'bunny'), // bunny, cloudflare
    'url' => env('CDN_URL'),
    'storage_url' => env('CDN_STORAGE_URL'),
    'apex_domain' => env('CDN_APEX_DOMAIN'),
    'zones' => [
        'public' => env('CDN_ZONE_PUBLIC'),
        'private' => env('CDN_ZONE_PRIVATE'),
    ],
],
```

### Security Headers

Configure security header policies:

```php
'security_headers' => [
    'enabled' => env('SECURITY_HEADERS_ENABLED', true),
    'csp' => [
        'enabled' => true,
        'report_only' => env('CSP_REPORT_ONLY', false),
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'https:'],
        ],
    ],
    'hsts' => [
        'enabled' => true,
        'max_age' => 31536000,
        'include_subdomains' => true,
    ],
],
```

## Admin Configuration

Location: `config/core-admin.php`

### Admin Menu

Configure admin panel navigation:

```php
'menu' => [
    'cache_enabled' => env('ADMIN_MENU_CACHE', true),
    'cache_ttl' => 3600,
    'show_icons' => true,
    'collapsible_groups' => true,
],
```

### Global Search

Configure admin global search:

```php
'search' => [
    'enabled' => env('ADMIN_SEARCH_ENABLED', true),
    'providers' => [
        \Core\Admin\Search\Providers\AdminPageSearchProvider::class,
        // Add custom providers here
    ],
    'max_results' => 10,
    'highlight' => true,
],
```

### Livewire Configuration

Configure Livewire modal behavior:

```php
'livewire' => [
    'modal_max_width' => '7xl',
    'modal_close_on_escape' => true,
    'modal_close_on_backdrop_click' => true,
],
```

## API Configuration

Location: `config/core-api.php`

### Rate Limiting

Configure API rate limits by tier:

```php
'rate_limits' => [
    'tiers' => [
        'free' => [
            'requests' => 1000,
            'window' => 60, // minutes
            'burst' => 1.2, // 20% over limit
        ],
        'starter' => [
            'requests' => 10000,
            'window' => 60,
            'burst' => 1.2,
        ],
        'pro' => [
            'requests' => 50000,
            'window' => 60,
            'burst' => 1.5,
        ],
        'enterprise' => [
            'requests' => null, // unlimited
            'window' => 60,
            'burst' => 2.0,
        ],
    ],
    'headers_enabled' => true,
],
```

### API Keys

Configure API key security:

```php
'api_keys' => [
    'hash_algorithm' => 'bcrypt', // bcrypt or sha256
    'rotation_grace_period' => 86400, // 24 hours
    'prefix' => 'sk_', // secret key prefix
    'length' => 32,
],
```

### Webhook Configuration

Configure outbound webhook behavior:

```php
'webhooks' => [
    'signature_algorithm' => 'sha256',
    'max_retries' => 3,
    'retry_delay' => 60, // seconds
    'timeout' => 10, // seconds
    'verify_ssl' => true,
    'replay_tolerance' => 300, // 5 minutes
],
```

### OpenAPI Documentation

Configure API documentation:

```php
'documentation' => [
    'enabled' => env('API_DOCS_ENABLED', true),
    'require_auth' => env('API_DOCS_REQUIRE_AUTH', false),
    'title' => env('API_DOCS_TITLE', 'API Documentation'),
    'version' => '1.0.0',
    'default_ui' => 'scalar', // scalar, swagger, redoc
    'servers' => [
        [
            'url' => env('APP_URL').'/api',
            'description' => 'Production',
        ],
    ],
],
```

### Scope Enforcement

Configure API scope requirements:

```php
'scopes' => [
    'enforce' => env('API_SCOPES_ENFORCE', true),
    'available' => [
        'bio:read',
        'bio:write',
        'bio:delete',
        'analytics:read',
        'webhooks:manage',
        'keys:manage',
    ],
],
```

## MCP Configuration

Location: `config/core-mcp.php`

### Tool Registry

Configure MCP tool discovery:

```php
'tools' => [
    'auto_discover' => env('MCP_TOOLS_AUTO_DISCOVER', true),
    'paths' => [
        'Mod/*/Mcp/Tools',
        'Core/Mcp/Tools',
    ],
    'cache_enabled' => true,
],
```

### Database Access

Configure SQL query validation and database access:

```php
'database' => [
    'connection' => env('MCP_DB_CONNECTION', 'mcp_readonly'),
    'validation' => [
        'enabled' => true,
        'blocked_keywords' => ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE'],
        'allowed_tables' => '*', // or array of specific tables
        'blocked_tables' => ['users', 'api_keys', 'password_resets'],
        'whitelist_enabled' => env('MCP_QUERY_WHITELIST', false),
        'whitelist_path' => storage_path('mcp/query-whitelist.json'),
    ],
    'explain' => [
        'enabled' => true,
        'performance_thresholds' => [
            'slow_query_rows' => 10000,
            'full_table_scan_warning' => true,
        ],
    ],
],
```

### Workspace Context

Configure workspace context security:

```php
'workspace_context' => [
    'required' => env('MCP_WORKSPACE_REQUIRED', true),
    'validation' => [
        'verify_existence' => true,
        'check_suspension' => true,
    ],
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
],
```

### Tool Analytics

Configure tool usage tracking:

```php
'analytics' => [
    'enabled' => env('MCP_ANALYTICS_ENABLED', true),
    'retention_days' => 90,
    'track_performance' => true,
    'track_errors' => true,
],
```

### Usage Quotas

Configure per-workspace usage limits:

```php
'quotas' => [
    'enabled' => env('MCP_QUOTAS_ENABLED', true),
    'tiers' => [
        'free' => [
            'daily_calls' => 100,
            'monthly_calls' => 2000,
        ],
        'pro' => [
            'daily_calls' => 1000,
            'monthly_calls' => 25000,
        ],
        'enterprise' => [
            'daily_calls' => null, // unlimited
            'monthly_calls' => null,
        ],
    ],
],
```

## Environment Variables

Key environment variables for configuration:

```bash
# Core
MODULES_AUTO_DISCOVER=true
MODULES_CACHE_ENABLED=true
SEEDERS_AUTO_DISCOVER=true

# Activity Logging
ACTIVITY_LOG_ENABLED=true
ACTIVITY_RETENTION_DAYS=90

# Workspace Cache
WORKSPACE_CACHE_ENABLED=true
WORKSPACE_CACHE_TTL=3600
WORKSPACE_CACHE_USE_TAGS=true

# Action Gate
ACTION_GATE_ENABLED=true
ACTION_GATE_TRAINING=false

# CDN
CDN_ENABLED=false
CDN_PROVIDER=bunny
CDN_URL=https://cdn.example.com
CDN_STORAGE_URL=https://storage.example.com

# Security Headers
SECURITY_HEADERS_ENABLED=true
CSP_REPORT_ONLY=false

# API
API_DOCS_ENABLED=true
API_DOCS_REQUIRE_AUTH=false
API_SCOPES_ENFORCE=true

# MCP
MCP_TOOLS_AUTO_DISCOVER=true
MCP_DB_CONNECTION=mcp_readonly
MCP_QUERY_WHITELIST=false
MCP_WORKSPACE_REQUIRED=true
MCP_ANALYTICS_ENABLED=true
MCP_QUOTAS_ENABLED=true
```

## Configuration Profiles

Core PHP supports multi-profile configuration for different environments:

### Creating Profiles

```php
use Core\Config\Models\ConfigProfile;

$profile = ConfigProfile::create([
    'name' => 'production',
    'workspace_id' => $workspace->id,
    'is_active' => true,
]);
```

### Setting Configuration Values

```php
use Core\Config\ConfigService;

$config = app(ConfigService::class);

$config->set('api.rate_limit', 10000, $profile);
$config->set('cdn.enabled', true, $profile);
```

### Retrieving Configuration

```php
$rateLimit = $config->get('api.rate_limit', $profile);
```

## Configuration Versioning

Track configuration changes over time:

```bash
# Export current configuration
php artisan config:export production

# Import configuration from file
php artisan config:import production.json --profile=production

# Show configuration version history
php artisan config:version --profile=production
```

## Next Steps

- [Quick Start Guide](/guide/quick-start) - Create your first module
- [Architecture Overview](/architecture/lifecycle-events) - Understand the event system
- [Security Configuration](/security/overview) - Security best practices
