# Contributing to Core PHP Framework

Thank you for considering contributing to Core PHP Framework! This guide will help you get started.

## Code of Conduct

Be respectful, professional, and constructive. We're building open-source software together.

## How to Contribute

### Reporting Bugs

Before creating a bug report:
- Check existing issues to avoid duplicates
- Verify the bug exists in the latest version
- Collect relevant information (PHP version, Laravel version, error messages)

**Good Bug Report:**

```markdown
**Description:** API key validation fails with bcrypt-hashed keys

**Steps to Reproduce:**
1. Create API key: `$key = ApiKey::create(['name' => 'Test'])`
2. Attempt authentication: `GET /api/v1/posts` with key
3. Receive 401 Unauthorized

**Expected:** Authentication succeeds
**Actual:** Authentication fails
**Version:** v1.0.0
**PHP:** 8.2.0
**Laravel:** 11.x
```

### Suggesting Features

Feature requests should include:
- Clear use case
- Example implementation (if possible)
- Impact on existing functionality
- Alternative approaches considered

### Pull Requests

1. **Fork the repository**
2. **Create a feature branch:** `git checkout -b feature/my-feature`
3. **Make your changes** (see coding standards below)
4. **Write tests** for your changes
5. **Run test suite:** `composer test`
6. **Commit with clear message:** `feat: add API key rotation`
7. **Push to your fork**
8. **Open pull request** against `main` branch

## Development Setup

### Prerequisites

- PHP 8.2+
- Composer
- MySQL/PostgreSQL
- Redis (optional, for cache testing)

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/core-php.git
cd core-php

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Run tests
composer test
```

### Running Tests

```bash
# All tests
composer test

# Specific test file
./vendor/bin/phpunit packages/core-php/tests/Feature/ActivityLogServiceTest.php

# With coverage
./vendor/bin/phpunit --coverage-html coverage
```

## Coding Standards

### PSR-12 Compliance

Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards:

```php
<?php

namespace Mod\Blog\Actions;

use Core\Actions\Action;

class CreatePost
{
    use Action;

    public function handle(array $data): Post
    {
        return Post::create($data);
    }
}
```

### Type Hints

Always use type hints:

```php
// ✅ Good
public function store(Request $request): JsonResponse
{
    $post = CreatePost::run($request->validated());
    return response()->json($post, 201);
}

// ❌ Bad
public function store($request)
{
    $post = CreatePost::run($request->validated());
    return response()->json($post, 201);
}
```

### Docblocks

Use docblocks for complex methods:

```php
/**
 * Generate OG image for blog post.
 *
 * @param Post $post The blog post
 * @param array $options Image generation options
 * @return string Path to generated image
 * @throws ImageGenerationException
 */
public function generateOgImage(Post $post, array $options = []): string
{
    // Implementation
}
```

### Naming Conventions

**Classes:**
- PascalCase
- Descriptive names
- Singular nouns for models

```php
class Post extends Model {}
class CreatePost extends Action {}
class PostController extends Controller {}
```

**Methods:**
- camelCase
- Verb-based names
- Descriptive intent

```php
public function createPost() {}
public function publishPost() {}
public function getPublishedPosts() {}
```

**Variables:**
- camelCase
- Descriptive names
- No abbreviations

```php
// ✅ Good
$publishedPosts = Post::published()->get();
$userWorkspace = $user->workspace;

// ❌ Bad
$p = Post::published()->get();
$ws = $user->workspace;
```

## Module Structure

Follow the established module pattern:

```
src/Mod/MyModule/
├── Boot.php                    # Module entry point
├── Controllers/
│   ├── Web/
│   └── Api/
├── Models/
├── Actions/
├── Migrations/
├── Routes/
│   ├── web.php
│   └── api.php
├── Views/
│   └── Blade/
├── Lang/
│   └── en_GB/
└── Tests/
    ├── Feature/
    └── Unit/
```

**Boot.php Example:**

```php
<?php

namespace Mod\MyModule;

use Core\Events\WebRoutesRegistering;
use Core\Events\AdminPanelBooting;

class Boot
{
    public static array $listens = [
        WebRoutesRegistering::class => 'onWebRoutes',
        AdminPanelBooting::class => 'onAdminPanel',
    ];

    public function onWebRoutes(WebRoutesRegistering $event): void
    {
        $event->views('mymodule', __DIR__.'/Views');
        $event->routes(fn () => require __DIR__.'/Routes/web.php');
    }

    public function onAdminPanel(AdminPanelBooting $event): void
    {
        $event->menu(new MyModuleMenuProvider());
    }
}
```

## Testing Guidelines

### Write Tests First

Follow TDD when possible:

```php
// 1. Write test
public function test_creates_post(): void
{
    $post = CreatePost::run([
        'title' => 'Test Post',
        'content' => 'Content',
    ]);

    $this->assertDatabaseHas('posts', [
        'title' => 'Test Post',
    ]);
}

// 2. Implement feature
class CreatePost
{
    use Action;

    public function handle(array $data): Post
    {
        return Post::create($data);
    }
}
```

### Test Coverage

Aim for 80%+ coverage on new code:

```bash
./vendor/bin/phpunit --coverage-text
```

### Test Organization

```php
class PostTest extends TestCase
{
    // Feature tests - test complete workflows
    public function test_user_can_create_post(): void {}
    public function test_user_cannot_create_post_without_permission(): void {}

    // Unit tests - test isolated components
    public function test_post_is_published(): void {}
    public function test_post_has_slug(): void {}
}
```

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation only
- `refactor:` Code refactoring
- `test:` Adding tests
- `chore:` Maintenance tasks

**Examples:**

```
feat(api): add API key rotation endpoint

Implements automatic API key rotation with configurable expiry.
Keys are hashed with bcrypt for security.

Closes #123
```

```
fix(mcp): validate workspace context in query tool

Previously, queries could bypass workspace scoping if context
was not explicitly validated.

BREAKING CHANGE: Query tool now requires workspace context
```

## Documentation

### Code Comments

Comment why, not what:

```php
// ✅ Good
// Hash IP for GDPR compliance
$properties['ip_address'] = LthnHash::make(request()->ip());

// ❌ Bad
// Hash the IP address
$properties['ip_address'] = LthnHash::make(request()->ip());
```

### README Updates

Update relevant README files when:
- Adding new features
- Changing configuration
- Modifying installation steps

### VitePress Documentation

Add documentation for new features:

```bash
# Create new doc page
docs/packages/my-package/my-feature.md

# Update config
docs/.vitepress/config.js
```

## Security

### Reporting Vulnerabilities

**Do not** open public issues for security vulnerabilities.

Email: [security@host.uk](mailto:security@host.uk)

Include:
- Description of vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### Security Best Practices

```php
// ✅ Good - parameterized query
$posts = DB::select('SELECT * FROM posts WHERE id = ?', [$id]);

// ❌ Bad - SQL injection risk
$posts = DB::select("SELECT * FROM posts WHERE id = {$id}");

// ✅ Good - workspace scoping
$post = Post::where('workspace_id', $workspace->id)->find($id);

// ❌ Bad - potential data leak
$post = Post::find($id);
```

## Performance

### Database Queries

```php
// ✅ Good - eager loading
$posts = Post::with(['author', 'category'])->get();

// ❌ Bad - N+1 queries
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name; // Query per post
}
```

### Caching

```php
// ✅ Good - cache expensive operations
$stats = Cache::remember('workspace.stats', 3600, function () {
    return $this->calculateStats();
});

// ❌ Bad - no caching
$stats = $this->calculateStats(); // Slow query
```

## Review Process

### What We Look For

- Code follows PSR-12 standards
- Tests are included and passing
- Documentation is updated
- No security vulnerabilities
- Performance is acceptable
- Backward compatibility maintained

### CI Checks

Pull requests must pass:
- PHPUnit tests
- PHPStan static analysis (level 5)
- PHP CS Fixer
- Security audit

## License

By contributing, you agree that your contributions will be licensed under the EUPL-1.2 License.

## Questions?

- Open a [Discussion](https://github.com/host-uk/core-php/discussions)
- Join our [Discord](https://discord.gg/host-uk)
- Read the [Documentation](https://core.help/)

Thank you for contributing! 🎉
