# Creating the Core PHP Framework Template Repository

This guide explains how to create the `host-uk/core-template` GitHub template repository that `php artisan core:new` will use to scaffold new projects.

---

## Overview

The template repository is a minimal Laravel application pre-configured with Core PHP Framework packages. Users run:

```bash
php artisan core:new my-project
```

This clones the template, configures it, and installs dependencies automatically.

---

## Repository Structure

```
host-uk/core-template/
├── app/
│   ├── Console/
│   ├── Http/
│   ├── Models/
│   └── Providers/
├── bootstrap/
│   └── app.php              # Core packages registered here
├── config/
│   ├── app.php
│   ├── database.php
│   └── core.php             # Core framework config
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── views/
│   └── css/
├── routes/
│   ├── api.php
│   ├── console.php
│   └── web.php
├── storage/
├── tests/
├── .env.example
├── .gitignore
├── composer.json            # Pre-configured with Core packages
├── package.json
├── phpunit.xml
├── README.md
└── vite.config.js
```

---

## Step 1: Create Base Laravel App

```bash
# Create fresh Laravel 12 app
composer create-project laravel/laravel core-template
cd core-template
```

---

## Step 2: Configure composer.json

Update `composer.json` to require Core PHP packages:

```json
{
    "name": "host-uk/core-template",
    "type": "project",
    "description": "Core PHP Framework - Project Template",
    "keywords": ["laravel", "core-php", "modular", "framework", "template"],
    "license": "EUPL-1.2",
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10",
        "livewire/flux": "^2.0",
        "livewire/flux-pro": "^2.10",
        "livewire/livewire": "^3.0",
        "host-uk/core": "^1.0",
        "host-uk/core-admin": "^1.0",
        "host-uk/core-api": "^1.0",
        "host-uk/core-mcp": "^1.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pail": "^1.2",
        "laravel/pint": "^1.18",
        "laravel/sail": "^1.41",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.6",
        "phpunit/phpunit": "^11.5"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Website\\": "app/Website/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "repositories": [
        {
            "name": "flux-pro",
            "type": "composer",
            "url": "https://composer.fluxui.dev"
        }
    ],
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi",
            "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
            "@php artisan migrate --graceful --ansi"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "php-http/discovery": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

---

## Step 3: Update bootstrap/app.php

Register Core PHP packages:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        // Core PHP Framework Packages
        Core\CoreServiceProvider::class,
        Core\Mod\Admin\Boot::class,
        Core\Mod\Api\Boot::class,
        Core\Mod\Mcp\Boot::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## Step 4: Create config/core.php

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Core PHP Framework Configuration
    |--------------------------------------------------------------------------
    */

    'module_paths' => [
        base_path('packages/core-php/src/Mod'),
        base_path('packages/core-php/src/Core'),
        base_path('app/Mod'),
    ],

    'services' => [
        'cache_discovery' => env('CORE_CACHE_DISCOVERY', true),
    ],

    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'driver' => env('CDN_DRIVER', 'bunny'),
    ],
];
```

---

## Step 5: Update .env.example

Add Core PHP specific variables:

```env
APP_NAME="Core PHP App"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

APP_LOCALE=en_GB
APP_FALLBACK_LOCALE=en_GB
APP_FAKER_LOCALE=en_GB

DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=core
# DB_USERNAME=root
# DB_PASSWORD=

# Core PHP Framework
CORE_CACHE_DISCOVERY=true

# CDN Configuration
CDN_ENABLED=false
CDN_DRIVER=bunny
BUNNYCDN_API_KEY=
BUNNYCDN_STORAGE_ZONE=
BUNNYCDN_PULL_ZONE=

# Flux Pro (optional)
FLUX_LICENSE_KEY=
```

---

## Step 6: Create README.md

```markdown
# Core PHP Framework Project

A modular monolith Laravel application built with Core PHP Framework.

## Features

✅ **Core Framework** - Event-driven module system with lazy loading
✅ **Admin Panel** - Livewire-powered admin interface with Flux UI
✅ **REST API** - Scoped API keys, rate limiting, webhooks, OpenAPI docs
✅ **MCP Tools** - Model Context Protocol for AI agent integration

## Installation

### From Template (Recommended)

```bash
# Using the core:new command
php artisan core:new my-project

# Or manually clone
git clone https://github.com/host-uk/core-template.git my-project
cd my-project
composer install
php artisan core:install
```

### Requirements

- PHP 8.2+
- Composer 2.x
- SQLite (default) or MySQL/PostgreSQL
- Node.js 18+ (for frontend assets)

## Quick Start

```bash
# 1. Install dependencies
composer install
npm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Set up database
touch database/database.sqlite
php artisan migrate

# 4. Start development server
php artisan serve
```

Visit: http://localhost:8000

## Project Structure

```
app/
├── Mod/          # Your custom modules
├── Website/      # Multi-site website modules
└── Providers/    # Laravel service providers

config/
└── core.php      # Core framework configuration

routes/
├── web.php       # Public web routes
├── api.php       # REST API routes (via core-api)
└── console.php   # Artisan commands
```

## Creating Modules

```bash
# Create a new module with all features
php artisan make:mod Blog --all

# Create module with specific features
php artisan make:mod Shop --web --api --admin
```

Modules follow the event-driven pattern:

```php
<?php
namespace Mod\Blog;

class Boot
{
    public static array $listens = [
        WebRoutesRegistering::class => 'onWebRoutes',
        ApiRoutesRegistering::class => 'onApiRoutes',
        AdminPanelBooting::class => 'onAdminPanel',
    ];

    public function onWebRoutes(WebRoutesRegistering $event): void
    {
        $event->routes(fn() => require __DIR__.'/Routes/web.php');
    }
}
```

## Core Packages

- **host-uk/core** - Core framework components
- **host-uk/core-admin** - Admin panel with Livewire modals
- **host-uk/core-api** - REST API with scopes & webhooks
- **host-uk/core-mcp** - Model Context Protocol tools for AI

## Documentation

- [Core PHP Framework](https://github.com/host-uk/core-php)
- [Admin Package](https://github.com/host-uk/core-admin)
- [API Package](https://github.com/host-uk/core-api)
- [MCP Package](https://github.com/host-uk/core-mcp)

## License

EUPL-1.2 (European Union Public Licence)
```

---

## Step 7: Add .gitattributes

```gitattributes
* text=auto

*.blade.php diff=html
*.css diff=css
*.html diff=html
*.md diff=markdown
*.php diff=php

/.github export-ignore
CHANGELOG.md export-ignore
```

---

## Step 8: Create GitHub Repository

### On GitHub:

1. **Create new repository**
   - Name: `core-template`
   - Description: "Core PHP Framework - Project Template"
   - Public repository
   - ✅ Check "Template repository"

2. **Push your code**

```bash
git init
git add .
git commit -m "Initial Core PHP Framework template"
git branch -M main
git remote add origin https://github.com/host-uk/core-template.git
git push -u origin main
```

3. **Configure template settings**
   - Go to Settings → General
   - Under "Template repository", enable checkbox
   - Add topics: `laravel`, `core-php`, `modular-monolith`, `template`

4. **Create releases**
   - Tag: `v1.0.0`
   - Title: "Core PHP Framework Template v1.0.0"
   - Include changelog

---

## Step 9: Test Template Creation

```bash
# Test the template works
php artisan core:new test-project

# Should create:
# - test-project/ directory
# - Run composer install
# - Run core:install
# - Initialize git repo

cd test-project
php artisan serve
```

---

## Additional Template Variants

You can create specialized templates:

### API-Only Template
**Repository:** `host-uk/core-api-template`
**Usage:** `php artisan core:new my-api --template=host-uk/core-api-template`

Includes only:
- core
- core-api
- Minimal routes (API only)

### Admin-Only Template
**Repository:** `host-uk/core-admin-template`
**Usage:** `php artisan core:new my-admin --template=host-uk/core-admin-template`

Includes only:
- core
- core-admin
- Auth scaffolding

### SaaS Template
**Repository:** `host-uk/core-saas-template`
**Usage:** `php artisan core:new my-saas --template=host-uk/core-saas-template`

Includes:
- All core packages
- Multi-tenancy pre-configured
- Billing integration stubs
- Feature flags

---

## Updating the Template

When you release new core package versions:

1. Update `composer.json` with new version constraints
2. Update `.env.example` with new config options
3. Update `README.md` with new features
4. Tag a new release: `v1.1.0`, `v1.2.0`, etc.

Users can specify template versions:

```bash
php artisan core:new my-project --template=host-uk/core-template --branch=v1.0.0
```

---

## GitHub Actions (Optional)

Add `.github/workflows/test-template.yml` to test template on every commit:

```yaml
name: Test Template

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: sqlite3

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Copy .env
        run: cp .env.example .env

      - name: Generate Key
        run: php artisan key:generate

      - name: Create Database
        run: touch database/database.sqlite

      - name: Run Migrations
        run: php artisan migrate --force

      - name: Run Tests
        run: php artisan test
```

---

## Maintenance

### Regular Updates

- **Monthly:** Update Laravel & core package versions
- **Security:** Apply security patches immediately
- **Testing:** Test template creation works after updates

### Community Templates

Encourage community to create their own templates:

```bash
# Community members can create templates like:
php artisan core:new my-blog --template=johndoe/core-blog-template
php artisan core:new my-shop --template=acme/core-ecommerce
```

---

## Support

For issues with the template:
- **GitHub Issues:** https://github.com/host-uk/core-template/issues
- **Discussions:** https://github.com/host-uk/core-php/discussions

---

## Checklist

Before publishing the template:

- [ ] All core packages install without errors
- [ ] `php artisan core:install` runs successfully
- [ ] Database migrations work
- [ ] `php artisan serve` starts server
- [ ] Admin panel accessible at `/admin`
- [ ] API routes respond correctly
- [ ] MCP tools registered
- [ ] README.md is clear and helpful
- [ ] .env.example has all required variables
- [ ] Repository is marked as "Template repository"
- [ ] v1.0.0 release is tagged
- [ ] License file is included (EUPL-1.2)

---

**Template Ready!** 🚀

Users can now run:

```bash
php artisan core:new my-awesome-project
```

And get a fully configured Core PHP Framework application in seconds.
