# Using `php artisan core:new`

The `core:new` command scaffolds a new Core PHP Framework project, similar to `laravel new`.

---

## Quick Start

```bash
# Create a new project
php artisan core:new my-project

# With custom template
php artisan core:new my-api --template=host-uk/core-api-template

# Skip installation (manual setup)
php artisan core:new my-project --no-install
```

---

## Command Reference

### Basic Usage

```bash
php artisan core:new {name}
```

**Arguments:**
- `name` - Project directory name (required)

**Options:**
- `--template=` - GitHub template repository (default: `host-uk/core-template`)
- `--branch=` - Template branch to use (default: `main`)
- `--no-install` - Skip `composer install` and `core:install`
- `--dev` - Install with `--prefer-source` for development
- `--force` - Overwrite existing directory

---

## Examples

### 1. Standard Project

Creates a full-stack application with all Core packages:

```bash
php artisan core:new my-app
cd my-app
php artisan serve
```

**Includes:**
- Core framework
- Admin panel (Livewire + Flux)
- REST API (scopes, webhooks, OpenAPI)
- MCP tools for AI agents

---

### 2. API-Only Project

```bash
php artisan core:new my-api \
  --template=host-uk/core-api-template
```

**Includes:**
- Core framework
- core-api package
- Minimal routes (API only)
- No frontend dependencies

---

### 3. Admin Panel Only

```bash
php artisan core:new my-admin \
  --template=host-uk/core-admin-template
```

**Includes:**
- Core framework
- core-admin package
- Livewire + Flux UI
- Auth scaffolding

---

### 4. Custom Template

Use your own or community templates:

```bash
# Your own template
php artisan core:new my-project \
  --template=my-company/core-custom

# Community template
php artisan core:new my-blog \
  --template=johndoe/core-blog-starter
```

---

### 5. Specific Version

Lock to a specific template version:

```bash
php artisan core:new my-project \
  --template=host-uk/core-template \
  --branch=v1.0.0
```

---

### 6. Manual Setup

Create project but skip automated setup:

```bash
php artisan core:new my-project --no-install

cd my-project
composer install
cp .env.example .env
php artisan key:generate
php artisan core:install
```

Useful when you want to:
- Review dependencies before installing
- Customize composer.json first
- Set up .env manually

---

### 7. Development Mode

Install packages with `--prefer-source` for contributing:

```bash
php artisan core:new my-project --dev
```

Clones packages as git repos instead of downloading archives.

---

## What It Does

When you run `php artisan core:new my-project`, it:

1. **Clones template** from GitHub
2. **Removes .git** to make it a fresh repo
3. **Updates composer.json** with your project name
4. **Installs dependencies** via Composer
5. **Runs core:install** to configure the app
6. **Initializes git** with initial commit

---

## Project Structure

After creation, your project will have:

```
my-project/
├── app/
│   ├── Console/
│   ├── Http/
│   ├── Models/
│   └── Mod/              # Your modules go here
├── bootstrap/
│   └── app.php           # Core packages registered
├── config/
│   └── core.php          # Core framework config
├── database/
│   ├── migrations/       # Core + your migrations
│   └── seeders/
├── routes/
│   ├── api.php           # API routes (via core-api)
│   ├── console.php       # Artisan commands
│   └── web.php           # Web routes
├── .env
├── composer.json         # Core packages required
└── README.md
```

---

## Next Steps After Creation

### 1. Start Development Server

```bash
cd my-project
php artisan serve
```

Visit: http://localhost:8000

### 2. Access Admin Panel

```bash
# Create an admin user
php artisan make:user admin@example.com --admin

# Visit admin panel
open http://localhost:8000/admin
```

### 3. Create a Module

```bash
# Full-featured module
php artisan make:mod Blog --all

# Specific features
php artisan make:mod Shop --web --api --admin
```

### 4. Configure API

```bash
# Generate API key
php artisan api:key-create "My App" --scopes=posts:read,posts:write

# View OpenAPI docs
open http://localhost:8000/api/docs
```

### 5. Enable MCP Tools

```bash
# List available tools
php artisan mcp:list

# Test a tool
php artisan mcp:test query_database
```

---

## Troubleshooting

### Template Not Found

```
Error: Failed to clone template
```

**Solution:** Verify template exists on GitHub:
```bash
# Check if template is public
curl -I https://github.com/host-uk/core-template

# Use HTTPS URL explicitly
php artisan core:new my-project \
  --template=https://github.com/host-uk/core-template.git
```

---

### Composer Install Fails

```
Error: Composer install failed
```

**Solution:** Install manually:
```bash
cd my-project
composer install --no-interaction
php artisan core:install
```

---

### Directory Already Exists

```
Error: Directory [my-project] already exists!
```

**Solution:** Use `--force` or choose different name:
```bash
php artisan core:new my-project --force
# or
php artisan core:new my-project-v2
```

---

### Git Not Found

```
Error: git command not found
```

**Solution:** Install Git:
```bash
# macOS
brew install git

# Ubuntu/Debian
sudo apt-get install git

# Windows
# Download from https://git-scm.com
```

---

## Template Repositories

### Official Templates

| Template | Purpose | Command |
|----------|---------|---------|
| `host-uk/core-template` | Full-stack (default) | `php artisan core:new app` |
| `host-uk/core-api-template` | API-only | `--template=host-uk/core-api-template` |
| `host-uk/core-admin-template` | Admin panel only | `--template=host-uk/core-admin-template` |
| `host-uk/core-saas-template` | SaaS starter | `--template=host-uk/core-saas-template` |

### Community Templates

Browse templates: https://github.com/topics/core-php-template

Create your own: See `CREATING-TEMPLATE-REPO.md`

---

## Environment Configuration

After creation, update `.env`:

```env
# App Settings
APP_NAME="My Project"
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Core Framework
CORE_CACHE_DISCOVERY=true

# Optional: CDN
CDN_ENABLED=false
CDN_DRIVER=bunny
```

---

## Comparison to Other Tools

### vs `laravel new`

**Laravel New:**
```bash
laravel new my-project
# Creates: Basic Laravel app
```

**Core New:**
```bash
php artisan core:new my-project
# Creates: Laravel + Core packages pre-configured
#          Admin panel, API, MCP tools ready to use
```

### vs `composer create-project`

**Composer:**
```bash
composer create-project laravel/laravel my-project
composer require host-uk/core host-uk/core-admin ...
# Manual: Update bootstrap/app.php, config files, etc.
```

**Core New:**
```bash
php artisan core:new my-project
# Everything configured automatically
```

---

## Contributing

### Create Your Own Template

1. Fork `host-uk/core-template`
2. Customize for your use case
3. Enable "Template repository" on GitHub
4. Share with the community!

See: `CREATING-TEMPLATE-REPO.md` for full guide

---

## FAQ

**Q: Can I use this in production?**
Yes! The template creates production-ready applications.

**Q: How do I update Core packages?**
```bash
composer update host-uk/core-*
```

**Q: Can I create a template without GitHub?**
Currently requires GitHub, but you can specify any git URL:
```bash
--template=https://gitlab.com/my-org/core-template.git
```

**Q: Does it work with Laravel Sail?**
Yes! After creation, add Sail:
```bash
cd my-project
php artisan sail:install
./vendor/bin/sail up
```

**Q: Can I customize the generated project?**
Absolutely! After creation, it's your project. Modify anything.

---

## Support

- **Documentation:** https://github.com/host-uk/core-php
- **Issues:** https://github.com/host-uk/core-template/issues
- **Discussions:** https://github.com/host-uk/core-php/discussions

---

**Happy coding with Core PHP Framework!** 🚀
