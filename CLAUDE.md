# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
composer test                    # Run all tests
composer test -- --filter=Name   # Run single test by name
composer pint                    # Format code with Laravel Pint
```

## Architecture

### Event-Driven Module Loading

Modules declare interest in lifecycle events via static `$listens` arrays and are only instantiated when those events fire:

```
LifecycleEventProvider::register()
  └── ModuleScanner::scan()        # Finds Boot.php files with $listens
  └── ModuleRegistry::register()   # Wires LazyModuleListener for each event
```

### Frontages

Frontages are ServiceProviders in `src/Core/Front/` that fire context-specific lifecycle events:

| Frontage | Event | Middleware | Fires When |
|----------|-------|------------|------------|
| Web | `WebRoutesRegistering` | `web` | Public routes |
| Admin | `AdminPanelBooting` | `admin` | Admin panel |
| Api | `ApiRoutesRegistering` | `api` | REST endpoints |
| Client | `ClientRoutesRegistering` | `client` | Authenticated SaaS |
| Cli | `ConsoleBooting` | - | Artisan commands |
| Mcp | `McpToolsRegistering` | - | MCP tool handlers |

### L1 Packages

Subdirectories under `src/Core/` are self-contained "L1 packages" with their own Boot.php, migrations, tests, and views:

```
src/Core/Activity/        # Activity logging
src/Core/Bouncer/         # Security blocking/redirects
src/Core/Cdn/             # CDN integration
src/Core/Config/          # Dynamic configuration
src/Core/Lang/            # Translation system
src/Core/Media/           # Media handling
src/Core/Search/          # Search functionality
```

### Module Pattern

```php
class Boot
{
    public static array $listens = [
        WebRoutesRegistering::class => 'onWebRoutes',
        AdminPanelBooting::class => ['onAdmin', 10],  // With priority
    ];

    public function onWebRoutes(WebRoutesRegistering $event): void
    {
        $event->views('example', __DIR__.'/Views');
        $event->routes(fn () => require __DIR__.'/Routes/web.php');
    }
}
```

### Namespace Mapping

| Path | Namespace |
|------|-----------|
| `src/Core/` | `Core\` |
| `src/Mod/` | `Core\Mod\` |
| `src/Plug/` | `Core\Plug\` |
| `src/Website/` | `Core\Website\` |
| `app/Mod/` | `Mod\` |

### Actions Pattern

Single-purpose business logic classes with static `run()` helper:

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

// Usage: CreateOrder::run($user, $validated);
```

## Testing

Uses Orchestra Testbench. Tests can live:
- `tests/Feature/` and `tests/Unit/` - main test suites
- `src/Core/{Package}/Tests/` - L1 package co-located tests
- `src/Mod/{Module}/Tests/` - module co-located tests

Test fixtures are in `tests/Fixtures/`.
