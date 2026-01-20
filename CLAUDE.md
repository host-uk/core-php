# Core PHP Framework

A modular monolith framework for Laravel. This is the open-source foundation extracted from Host Hub.

## Quick Reference

```bash
composer test                 # Run tests
composer install              # Install dependencies
```

## Architecture

### Event-Driven Module Loading

1. **ModuleScanner** scans directories for `Boot.php` files with `$listens` arrays
2. **ModuleRegistry** wires lazy listeners for each event-module pair
3. **LazyModuleListener** defers module instantiation until events fire
4. **LifecycleEventProvider** fires events and processes collected requests

### Lifecycle Events

Located in `src/Core/Events/`:

- `WebRoutesRegistering` - public web routes
- `AdminPanelBooting` - admin panel
- `ApiRoutesRegistering` - REST API
- `ClientRoutesRegistering` - authenticated client routes
- `ConsoleBooting` - artisan commands
- `McpToolsRegistering` - MCP tools
- `FrameworkBooted` - late initialisation

### Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `CoreServiceProvider` | `src/Core/` | Package entry point |
| `LifecycleEventProvider` | `src/Core/` | Fires events, processes requests |
| `ModuleScanner` | `src/Core/Module/` | Scans for `$listens` declarations |
| `ModuleRegistry` | `src/Core/Module/` | Wires lazy listeners |
| `LazyModuleListener` | `src/Core/Module/` | Deferred module instantiation |

### Contracts

- `AdminMenuProvider` - admin navigation interface
- `ServiceDefinition` - SaaS service registration

## File Structure

```
src/Core/
├── CoreServiceProvider.php      # Package entry
├── LifecycleEventProvider.php   # Event firing
├── Events/                      # Lifecycle events
│   ├── LifecycleEvent.php       # Base class
│   ├── WebRoutesRegistering.php
│   ├── AdminPanelBooting.php
│   └── ...
├── Module/                      # Module system
│   ├── ModuleScanner.php
│   ├── ModuleRegistry.php
│   └── LazyModuleListener.php
├── Front/                       # Frontage contracts
│   └── Admin/Contracts/
│       └── AdminMenuProvider.php
├── Service/Contracts/
│   └── ServiceDefinition.php
└── Console/Commands/            # Artisan commands
    ├── MakeModCommand.php
    ├── MakeWebsiteCommand.php
    └── MakePlugCommand.php
```

## Module Pattern

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

## Namespacing

Default namespace detection:
- `/Core` paths → `Core\` namespace
- `/Mod` paths → `Mod\` namespace
- `/Website` paths → `Website\` namespace
- `/Plug` paths → `Plug\` namespace

Custom mapping via `ModuleScanner::setNamespaceMap()`.

## Testing

Tests use Orchestra Testbench. Fixtures in `tests/Fixtures/`.

## License

EUPL-1.2 (copyleft, GPL-compatible).
