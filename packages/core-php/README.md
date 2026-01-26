# Core PHP Framework

The core framework package providing event-driven architecture, module system, and foundational features for building modular Laravel applications.

## Installation

```bash
composer require host-uk/core
```

## Key Features

### Event-Driven Module System
Modules declare lifecycle events they're interested in and are only loaded when needed:

```php
class Boot
{
    public static array $listens = [
        WebRoutesRegistering::class => 'onWebRoutes',
        AdminPanelBooting::class => 'onAdmin',
    ];
}
```

### Multi-Tenant Data Isolation
Automatic workspace scoping for Eloquent models:

```php
use Core\Mod\Tenant\Concerns\BelongsToWorkspace;

class Product extends Model
{
    use BelongsToWorkspace;
}

// Automatically scoped to current workspace
$products = Product::all();
```

### Actions Pattern
Single-purpose business logic classes with dependency injection:

```php
use Core\Actions\Action;

class CreateOrder
{
    use Action;

    public function handle(User $user, array $data): Order
    {
        return Order::create($data);
    }
}

$order = CreateOrder::run($user, $validated);
```

### Activity Logging
Built-in audit trails for model changes:

```php
use Core\Activity\Concerns\LogsActivity;

class Order extends Model
{
    use LogsActivity;

    protected array $activityLogAttributes = ['status', 'total'];
}
```

### Seeder Auto-Discovery
Automatic seeder ordering via attributes:

```php
#[SeederPriority(10)]
#[SeederAfter(FeatureSeeder::class)]
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        // ...
    }
}
```

### HLCRF Layout System
Data-driven composable layouts:

```php
use Core\Front\Components\Layout;

$page = Layout::make('HCF')
    ->h('<nav>Navigation</nav>')
    ->c('<article>Content</article>')
    ->f('<footer>Footer</footer>');
```

## Lifecycle Events

| Event | Purpose |
|-------|---------|
| `WebRoutesRegistering` | Public web routes |
| `AdminPanelBooting` | Admin panel routes |
| `ApiRoutesRegistering` | REST API endpoints |
| `ClientRoutesRegistering` | Authenticated client routes |
| `ConsoleBooting` | Artisan commands |
| `McpToolsRegistering` | MCP tool handlers |
| `FrameworkBooted` | Late-stage initialization |

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=core-config
```

Configure in `config/core.php`:

```php
return [
    'module_paths' => [
        app_path('Core'),
        app_path('Mod'),
    ],
    'workspace_cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
];
```

## Artisan Commands

```bash
php artisan make:mod Commerce      # Create module
php artisan make:website Marketing # Create website
php artisan make:plug Stripe       # Create plugin
```

## Requirements

- PHP 8.2+
- Laravel 11+ or 12+

## Documentation

- [Main Documentation](../../README.md)
- [Patterns Guide](../../docs/patterns.md)
- [HLCRF Layout System](src/Core/Front/HLCRF.md)

## Changelog

See [changelog/2026/jan/features.md](changelog/2026/jan/features.md) for recent changes.

## License

EUPL-1.2 - See [LICENSE](../../LICENSE) for details.
