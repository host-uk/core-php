# Core PHP Framework

A modular monolith framework for Laravel with event-driven architecture and lazy module loading.

## Installation

```bash
composer require host-uk/core
```

The service provider will be auto-discovered.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=core-config
```

Configure your module paths in `config/core.php`:

```php
return [
    'module_paths' => [
        app_path('Core'),
        app_path('Mod'),
    ],
];
```

## Creating Modules

Use the artisan commands to scaffold modules:

```bash
# Create a full module
php artisan make:mod Commerce

# Create a website module (domain-scoped)
php artisan make:website Marketing

# Create a plugin
php artisan make:plug Stripe
```

## Module Structure

Modules are organised with a `Boot.php` entry point:

```
app/Mod/Commerce/
├── Boot.php
├── Routes/
│   ├── web.php
│   ├── admin.php
│   └── api.php
├── Views/
└── config.php
```

## Lifecycle Events

Modules declare interest in lifecycle events via a static `$listens` array:

```php
<?php

namespace Mod\Commerce;

use Core\Events\WebRoutesRegistering;
use Core\Events\AdminPanelBooting;

class Boot
{
    public static array $listens = [
        WebRoutesRegistering::class => 'onWebRoutes',
        AdminPanelBooting::class => 'onAdmin',
    ];

    public function onWebRoutes(WebRoutesRegistering $event): void
    {
        $event->views('commerce', __DIR__.'/Views');
        $event->routes(fn () => require __DIR__.'/Routes/web.php');
    }

    public function onAdmin(AdminPanelBooting $event): void
    {
        $event->routes(fn () => require __DIR__.'/Routes/admin.php');
    }
}
```

### Available Events

| Event | Purpose |
|-------|---------|
| `WebRoutesRegistering` | Public-facing web routes |
| `AdminPanelBooting` | Admin panel routes and navigation |
| `ApiRoutesRegistering` | REST API endpoints |
| `ClientRoutesRegistering` | Authenticated client/workspace routes |
| `ConsoleBooting` | Artisan commands |
| `McpToolsRegistering` | MCP tool handlers |
| `FrameworkBooted` | Late-stage initialisation |

### Event Methods

Events collect requests from modules:

```php
// Register routes
$event->routes(fn () => require __DIR__.'/routes.php');

// Register view namespace
$event->views('namespace', __DIR__.'/Views');

// Register Livewire component
$event->livewire('alias', ComponentClass::class);

// Register navigation item
$event->navigation(['label' => 'Products', 'icon' => 'box']);

// Register Artisan command (ConsoleBooting)
$event->command(MyCommand::class);

// Register middleware alias
$event->middleware('alias', MiddlewareClass::class);

// Register translations
$event->translations('namespace', __DIR__.'/lang');

// Register Blade component path
$event->bladeComponentPath(__DIR__.'/components', 'prefix');

// Register policy
$event->policy(Model::class, Policy::class);
```

## Firing Events

Create frontage service providers to fire events at appropriate times:

```php
use Core\LifecycleEventProvider;

class WebServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        LifecycleEventProvider::fireWebRoutes();
    }
}
```

## Lazy Loading

Modules are only instantiated when their subscribed events fire. A web request doesn't load admin-only modules. An API request doesn't load web modules. This keeps your application fast.

## Custom Namespace Mapping

For non-standard directory structures:

```php
$scanner = app(ModuleScanner::class);
$scanner->setNamespaceMap([
    'CustomMod' => 'App\\CustomMod',
]);
```

## Contracts

### AdminMenuProvider

Implement for admin navigation:

```php
use Core\Front\Admin\Contracts\AdminMenuProvider;

class Boot implements AdminMenuProvider
{
    public function adminMenuItems(): array
    {
        return [
            [
                'group' => 'services',
                'priority' => 20,
                'item' => fn () => [
                    'label' => 'Products',
                    'icon' => 'box',
                    'href' => route('admin.products.index'),
                ],
            ],
        ];
    }
}
```

### ServiceDefinition

For SaaS service registration:

```php
use Core\Service\Contracts\ServiceDefinition;

class Boot implements ServiceDefinition
{
    public static function definition(): array
    {
        return [
            'code' => 'commerce',
            'module' => 'Commerce',
            'name' => 'Commerce',
            'tagline' => 'E-commerce platform',
        ];
    }
}
```

## Testing

```bash
composer test
```

## License

EUPL-1.2. See [LICENSE](LICENSE) for details.
