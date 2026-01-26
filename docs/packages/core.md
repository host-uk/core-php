# Core Package

The Core package provides the foundation for the framework including the module system, lifecycle events, multi-tenancy, and shared utilities.

## Installation

```bash
composer require host-uk/core
```

## Features

### Module System

Auto-discover and lazy-load modules based on lifecycle events:

```php
<?php

namespace Mod\Example;

use Core\Events\WebRoutesRegistering;

class Boot
{
    public static array $listens = [
        WebRoutesRegistering::class => 'onWebRoutes',
    ];

    public function onWebRoutes(WebRoutesRegistering $event): void
    {
        $event->views('example', __DIR__.'/Views');
        $event->routes(fn () => require __DIR__.'/Routes/web.php');
    }
}
```

[Learn more about the Module System →](/architecture/module-system)

### Lifecycle Events

Event-driven extension points throughout the framework:

- `WebRoutesRegistering` - Public web routes
- `AdminPanelBooting` - Admin panel initialization
- `ApiRoutesRegistering` - REST API routes
- `ClientRoutesRegistering` - Authenticated client routes
- `ConsoleBooting` - Artisan commands
- `McpToolsRegistering` - MCP tools
- `FrameworkBooted` - Late-stage initialization

[Learn more about Lifecycle Events →](/architecture/lifecycle-events)

### Actions Pattern

Single-purpose business logic classes:

```php
class CreatePost
{
    use Action;

    public function handle(array $data): Post
    {
        $post = Post::create($data);

        event(new PostCreated($post));

        return $post;
    }
}

// Usage
$post = CreatePost::run($data);
```

[Learn more about Actions →](/patterns-guide/actions)

### Multi-Tenancy

Workspace-scoped data isolation:

```php
use Core\Mod\Tenant\Concerns\BelongsToWorkspace;

class Post extends Model
{
    use BelongsToWorkspace;
}

// Queries automatically scoped to current workspace
$posts = Post::all();
```

[Learn more about Multi-Tenancy →](/architecture/multi-tenancy)

### Activity Logging

Track changes to models with automatic workspace scoping:

```php
use Core\Activity\Concerns\LogsActivity;

class Post extends Model
{
    use LogsActivity;

    protected array $activityLogAttributes = ['title', 'status'];
}

// Changes logged automatically
$post->update(['title' => 'New Title']);

// Retrieve activity
$activity = Activity::forSubject($post)->get();
```

[Learn more about Activity Logging →](/patterns-guide/activity-logging)

### Seeder Discovery

Automatic seeder discovery with dependency ordering:

```php
use Core\Database\Seeders\Attributes\SeederPriority;
use Core\Database\Seeders\Attributes\SeederAfter;

#[SeederPriority(50)]
#[SeederAfter(WorkspaceSeeder::class)]
class PostSeeder extends Seeder
{
    public function run(): void
    {
        Post::factory()->count(20)->create();
    }
}
```

[Learn more about Seeders →](/patterns-guide/seeders)

### Configuration Management

Multi-profile configuration with versioning:

```php
use Core\Config\ConfigService;

$config = app(ConfigService::class);

// Set configuration
$config->set('api.rate_limit', 10000, $profile);

// Get configuration
$rateLimit = $config->get('api.rate_limit', $profile);

// Export configuration
php artisan config:export production

// Import configuration
php artisan config:import production.json
```

### CDN Integration

Unified CDN interface for BunnyCDN and Cloudflare:

```php
use Core\Cdn\Facades\Cdn;

// Generate CDN URL
$url = Cdn::url('images/photo.jpg');

// Store file to CDN
$path = Cdn::store($uploadedFile, 'media');

// Delete from CDN
Cdn::delete($path);

// Purge cache
Cdn::purge('images/*');
```

### Security Headers

Configurable security headers with CSP support:

```php
// config/core.php
'security_headers' => [
    'csp' => [
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
        ],
    ],
    'hsts' => [
        'enabled' => true,
        'max_age' => 31536000,
    ],
],
```

### Email Shield

Disposable email detection and validation:

```php
use Core\Mail\EmailShield;

$shield = app(EmailShield::class);

$result = $shield->validate('user@example.com');

if (! $result->isValid) {
    // Email is disposable, has syntax errors, etc.
    return back()->withErrors(['email' => $result->reason]);
}
```

### Media Processing

Image optimization and responsive images:

```php
use Core\Media\Image\ImageOptimizer;

$optimizer = app(ImageOptimizer::class);

// Optimize image
$optimized = $optimizer->optimize('path/to/image.jpg');

// Generate responsive variants
$variants = $optimizer->generateVariants($image, [
    'thumbnail' => ['width' => 150, 'height' => 150],
    'medium' => ['width' => 640],
    'large' => ['width' => 1024],
]);
```

### Search

Unified search interface across modules:

```php
use Core\Search\Unified;

$search = app(Unified::class);

$results = $search->search('query', [
    'types' => ['posts', 'pages'],
    'limit' => 10,
]);

foreach ($results as $result) {
    echo $result->title;
    echo $result->url;
}
```

### SEO Tools

SEO metadata generation and sitemap:

```php
use Core\Seo\SeoMetadata;

$seo = app(SeoMetadata::class);

$seo->setTitle('Page Title')
    ->setDescription('Page description')
    ->setCanonicalUrl('https://example.com/page')
    ->setOgImage('https://example.com/og-image.jpg');

// Generate in view
{!! $seo->render() !!}

// Sitemap generation
php artisan seo:generate-sitemap
```

## Artisan Commands

### Module Management

```bash
# Create new module
php artisan make:mod Blog

# Create website module
php artisan make:website Marketing

# Create plugin module
php artisan make:plug Stripe
```

### Configuration

```bash
# Export configuration
php artisan config:export production

# Import configuration
php artisan config:import production.json --profile=production

# Show configuration versions
php artisan config:version --profile=production
```

### Activity Logs

```bash
# Prune old activity logs
php artisan activity:prune --days=90
```

### Email Shield

```bash
# Prune email shield statistics
php artisan email-shield:prune --days=30
```

### SEO

```bash
# Generate sitemap
php artisan seo:generate-sitemap

# Audit canonical URLs
php artisan seo:audit-canonical

# Test structured data
php artisan seo:test-structured-data --url=/blog/post-slug
```

### Storage

```bash
# Warm cache
php artisan cache:warm

# Offload files to CDN
php artisan storage:offload --disk=public
```

## Configuration

### Core Configuration

```php
// config/core.php
return [
    'module_paths' => [
        app_path('Core'),
        app_path('Mod'),
        app_path('Plug'),
    ],

    'modules' => [
        'auto_discover' => true,
        'cache_enabled' => true,
    ],

    'seeders' => [
        'auto_discover' => true,
        'paths' => [
            'Mod/*/Database/Seeders',
            'Core/*/Database/Seeders',
        ],
    ],

    'activity' => [
        'enabled' => true,
        'retention_days' => 90,
        'log_ip_address' => false,
    ],

    'workspace_cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'use_tags' => true,
    ],
];
```

[View full configuration options →](/guide/configuration#core-configuration)

## Testing

### Feature Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Mod\Blog\Actions\CreatePost;

class CreatePostTest extends TestCase
{
    public function test_creates_post(): void
    {
        $post = CreatePost::run([
            'title' => 'Test Post',
            'content' => 'Test content',
        ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
        ]);
    }
}
```

### Unit Tests

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use Core\Module\ModuleScanner;

class ModuleScannerTest extends TestCase
{
    public function test_discovers_modules(): void
    {
        $scanner = new ModuleScanner();

        $modules = $scanner->scan([app_path('Mod')]);

        $this->assertNotEmpty($modules);
        $this->assertArrayHasKey('listens', $modules[0]);
    }
}
```

## Database

### Migrations

Core package includes migrations for:

- `activity_log` - Activity logging
- `config_keys` - Configuration keys
- `config_values` - Configuration values
- `config_profiles` - Configuration profiles
- `config_versions` - Configuration versioning
- `email_shield_stats` - Email validation statistics
- `workspaces` - Multi-tenant workspaces
- `workspace_users` - User-workspace relationships

Run migrations:

```bash
php artisan migrate
```

## Events

Core package dispatches these events:

### Lifecycle Events

- `Core\Events\WebRoutesRegistering`
- `Core\Events\AdminPanelBooting`
- `Core\Events\ApiRoutesRegistering`
- `Core\Events\ClientRoutesRegistering`
- `Core\Events\ConsoleBooting`
- `Core\Events\McpToolsRegistering`
- `Core\Events\FrameworkBooted`

### Configuration Events

- `Core\Config\Events\ConfigChanged`
- `Core\Config\Events\ConfigInvalidated`

### Activity Events

- `Core\Activity\Events\ActivityLogged`

## Middleware

### Multi-Tenancy

- `Core\Mod\Tenant\Middleware\RequireWorkspaceContext` - Ensure workspace is set

### Security

- `Core\Headers\SecurityHeaders` - Apply security headers
- `Core\Bouncer\BlocklistService` - IP blocklist
- `Core\Bouncer\Gate\ActionGateMiddleware` - Action authorization

## Service Providers

Register Core package in `config/app.php`:

```php
'providers' => [
    // ...
    Core\CoreServiceProvider::class,
],
```

Or use auto-discovery (Laravel 11+).

## Helpers

### Global Helpers

```php
// Get current workspace
$workspace = workspace();

// Create activity log
activity()
    ->performedOn($model)
    ->log('action');

// Generate CDN URL
$url = cdn_url('path/to/asset.jpg');

// Get CSP nonce
$nonce = csp_nonce();
```

## Best Practices

### 1. Use Actions for Business Logic

```php
// ✅ Good
$post = CreatePost::run($data);

// ❌ Bad
$post = Post::create($data);
event(new PostCreated($post));
Cache::forget('posts');
```

### 2. Log Activity for Audit Trail

```php
class Post extends Model
{
    use LogsActivity;

    protected array $activityLogAttributes = ['title', 'status', 'published_at'];
}
```

### 3. Use Workspace Scoping

```php
class Post extends Model
{
    use BelongsToWorkspace;
}
```

### 4. Leverage Module System

```php
// Create focused modules with clear boundaries
Mod/Blog/
Mod/Commerce/
Mod/Analytics/
```

## Changelog

See [CHANGELOG.md](https://github.com/host-uk/core-php/blob/main/packages/core-php/changelog/2026/jan/features.md)

## License

EUPL-1.2

## Learn More

- [Module System →](/architecture/module-system)
- [Lifecycle Events →](/architecture/lifecycle-events)
- [Actions Pattern →](/patterns-guide/actions)
- [Multi-Tenancy →](/architecture/multi-tenancy)
- [Activity Logging →](/patterns-guide/activity-logging)
