# MCP Package

The MCP (Model Context Protocol) package provides AI-powered tools for integrating with Large Language Models. Build custom tools with workspace context security, SQL query validation, usage quotas, and analytics.

## Installation

```bash
composer require host-uk/core-mcp
```

## Features

### Tool Registry

Automatically discover and register MCP tools:

```php
<?php

namespace Mod\Blog\Mcp\Tools;

use Core\Mcp\Tool;
use Core\Mcp\Request;
use Core\Mcp\Response;
use Mod\Blog\Models\Post;

class GetPostTool extends Tool
{
    public function name(): string
    {
        return 'blog_get_post';
    }

    public function description(): string
    {
        return 'Retrieve a blog post by ID or slug';
    }

    public function parameters(): array
    {
        return [
            'post_id' => [
                'type' => 'number',
                'description' => 'The post ID',
                'required' => false,
            ],
            'slug' => [
                'type' => 'string',
                'description' => 'The post slug',
                'required' => false,
            ],
        ];
    }

    public function handle(Request $request): Response
    {
        $post = $request->input('post_id')
            ? Post::findOrFail($request->input('post_id'))
            : Post::where('slug', $request->input('slug'))->firstOrFail();

        return Response::success([
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'published_at' => $post->published_at,
        ]);
    }
}
```

Register tools in your module:

```php
public function onMcpTools(McpToolsRegistering $event): void
{
    $event->tools([
        GetPostTool::class,
        CreatePostTool::class,
        UpdatePostTool::class,
    ]);
}
```

### Workspace Context Security

Enforce workspace context for multi-tenant safety:

```php
<?php

namespace Mod\Blog\Mcp\Tools;

use Core\Mcp\Tool;
use Core\Mcp\Concerns\RequiresWorkspaceContext;

class ListPostsTool extends Tool
{
    use RequiresWorkspaceContext;

    public function handle(Request $request): Response
    {
        // Workspace context automatically validated
        $workspace = $this->workspace();

        // Queries automatically scoped to workspace
        $posts = Post::latest()->limit(10)->get();

        return Response::success([
            'posts' => $posts->map(fn ($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'published_at' => $post->published_at,
            ]),
        ]);
    }
}
```

If workspace context is missing or invalid, the tool automatically throws `MissingWorkspaceContextException`.

### SQL Query Validation

Secure database querying with multi-layer validation:

```php
<?php

namespace Core\Mcp\Tools;

use Core\Mcp\Tool;
use Core\Mcp\Request;
use Core\Mcp\Response;
use Core\Mcp\Services\SqlQueryValidator;

class QueryDatabaseTool extends Tool
{
    use RequiresWorkspaceContext;

    public function __construct(
        private SqlQueryValidator $validator,
    ) {}

    public function handle(Request $request): Response
    {
        $query = $request->input('query');

        // Validate query against:
        // - Blocked keywords (INSERT, UPDATE, DELETE, etc.)
        // - Blocked tables (users, api_keys, etc.)
        // - SQL injection patterns
        // - Whitelist (if enabled)
        $this->validator->validate($query);

        $results = DB::connection('mcp_readonly')
            ->select($query);

        return Response::success([
            'rows' => $results,
            'count' => count($results),
        ]);
    }
}
```

Configuration:

```php
// config/core-mcp.php
'database' => [
    'validation' => [
        'enabled' => true,
        'blocked_keywords' => ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE'],
        'blocked_tables' => ['users', 'api_keys', 'password_resets'],
        'whitelist_enabled' => false,
    ],
],
```

### EXPLAIN Query Analysis

Analyze query performance:

```php
$tool = new QueryDatabaseTool();

$response = $tool->handle(new Request([
    'query' => 'SELECT * FROM posts WHERE category_id = 1',
    'explain' => true,
]));

// Returns:
[
    'explain' => [
        'type' => 'ref',
        'possible_keys' => 'category_id_index',
        'key' => 'category_id_index',
        'rows' => 42,
    ],
    'analysis' => [
        'efficient' => true,
        'warnings' => [],
        'recommendations' => [
            'Consider adding LIMIT clause for large result sets',
        ],
    ],
]
```

### Tool Dependencies

Declare tool dependencies:

```php
<?php

namespace Mod\Blog\Mcp\Tools;

use Core\Mcp\Tool;
use Core\Mcp\Dependencies\HasDependencies;
use Core\Mcp\Dependencies\ToolDependency;

class PublishPostTool extends Tool
{
    use HasDependencies;

    public function dependencies(): array
    {
        return [
            ToolDependency::make('blog_get_post')
                ->description('Required to fetch post before publishing'),

            ToolDependency::make('notifications_send')
                ->optional()
                ->description('Send notifications when post is published'),
        ];
    }

    public function handle(Request $request): Response
    {
        // Dependencies validated before execution
        $post = $this->callTool('blog_get_post', [
            'post_id' => $request->input('post_id'),
        ]);

        $post->update(['published_at' => now()]);

        // Optional dependency
        if ($this->hasTool('notifications_send')) {
            $this->callTool('notifications_send', [
                'type' => 'post_published',
                'post_id' => $post->id,
            ]);
        }

        return Response::success($post);
    }
}
```

### Usage Quotas

Per-workspace usage limits:

```php
// config/core-mcp.php
'quotas' => [
    'enabled' => true,
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

Quota enforcement is automatic via middleware:

```php
// Applied automatically to MCP routes
Route::middleware(CheckMcpQuota::class)->group(/*...*/);
```

Check quota status:

```php
use Core\Mcp\Services\McpQuotaService;

$quota = app(McpQuotaService::class);

$usage = $quota->getUsage($workspace);
// ['daily' => 42, 'monthly' => 1250]

$remaining = $quota->getRemaining($workspace);
// ['daily' => 58, 'monthly' => 750]

$isExceeded = $quota->isExceeded($workspace);
// false
```

### Tool Analytics

Track tool usage and performance:

```php
use Core\Mcp\Services\ToolAnalyticsService;

$analytics = app(ToolAnalyticsService::class);

// Get tool statistics
$stats = $analytics->getToolStats('blog_get_post', $workspace);
// ToolStats {
//     total_calls: 1234,
//     success_rate: 98.5,
//     avg_duration_ms: 45.2,
//     error_count: 19,
// }

// Get top tools
$topTools = $analytics->getTopTools($workspace, limit: 10);

// Get recent errors
$errors = $analytics->getRecentErrors($workspace, limit: 20);
```

View analytics in admin panel:

```
/admin/mcp/analytics
/admin/mcp/analytics/{tool}
```

### MCP Playground

Interactive tool testing interface:

```
/admin/mcp/playground
```

Features:
- Tool browser with search
- Parameter editor with validation
- Real-time response preview
- Workspace context switcher
- Request history

## Tool Patterns

### Read-Only Tools

```php
class GetPostsTool extends Tool
{
    use RequiresWorkspaceContext;

    public function handle(Request $request): Response
    {
        $posts = Post::query()
            ->when($request->input('category_id'), fn ($q, $id) =>
                $q->where('category_id', $id)
            )
            ->latest()
            ->limit($request->input('limit', 10))
            ->get();

        return Response::success(['posts' => $posts]);
    }
}
```

### Mutation Tools

```php
class CreatePostTool extends Tool
{
    use RequiresWorkspaceContext;

    public function parameters(): array
    {
        return [
            'title' => ['type' => 'string', 'required' => true],
            'content' => ['type' => 'string', 'required' => true],
            'category_id' => ['type' => 'number', 'required' => false],
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $post = Post::create($validated);

        return Response::success($post);
    }
}
```

### Async Tools

```php
class GeneratePostContentTool extends Tool
{
    public function handle(Request $request): Response
    {
        // Queue long-running task
        $job = GenerateContentJob::dispatch(
            $request->input('topic'),
            $request->input('style')
        );

        return Response::accepted([
            'job_id' => $job->id,
            'status_url' => route('api.jobs.status', $job->id),
        ]);
    }
}
```

## Error Handling

```php
class GetPostTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            $post = Post::findOrFail($request->input('post_id'));

            return Response::success($post);
        } catch (ModelNotFoundException $e) {
            return Response::error(
                'Post not found',
                code: 'POST_NOT_FOUND',
                status: 404
            );
        } catch (\Exception $e) {
            return Response::error(
                'Failed to fetch post',
                code: 'INTERNAL_ERROR',
                status: 500,
                details: app()->environment('local') ? $e->getMessage() : null
            );
        }
    }
}
```

## Testing

### Tool Tests

```php
<?php

namespace Tests\Feature\Mcp;

use Tests\TestCase;
use Mod\Blog\Models\Post;
use Mod\Blog\Mcp\Tools\GetPostTool;
use Core\Mcp\Request;

class GetPostToolTest extends TestCase
{
    public function test_retrieves_post_by_id(): void
    {
        $post = Post::factory()->create();

        $tool = new GetPostTool();
        $response = $tool->handle(new Request([
            'post_id' => $post->id,
        ]));

        $this->assertTrue($response->isSuccess());
        $this->assertEquals($post->id, $response->data['id']);
    }

    public function test_requires_workspace_context(): void
    {
        $this->expectException(MissingWorkspaceContextException::class);

        // No workspace context set
        app()->forgetInstance('current.workspace');

        $tool = new GetPostTool();
        $tool->handle(new Request(['post_id' => 1]));
    }

    public function test_respects_workspace_isolation(): void
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        $post = Post::factory()->for($workspace1)->create();

        // Set context to workspace2
        app()->instance('current.workspace', $workspace2);

        $tool = new GetPostTool();
        $response = $tool->handle(new Request([
            'post_id' => $post->id,
        ]));

        $this->assertTrue($response->isError());
        $this->assertEquals(404, $response->status);
    }
}
```

## Configuration

```php
// config/core-mcp.php
return [
    'tools' => [
        'auto_discover' => true,
        'paths' => [
            'Mod/*/Mcp/Tools',
            'Core/Mcp/Tools',
        ],
    ],

    'database' => [
        'connection' => 'mcp_readonly',
        'validation' => [
            'enabled' => true,
            'blocked_keywords' => ['INSERT', 'UPDATE', 'DELETE'],
            'blocked_tables' => ['users', 'api_keys'],
        ],
    ],

    'workspace_context' => [
        'required' => true,
        'validation' => [
            'verify_existence' => true,
            'check_suspension' => true,
        ],
    ],

    'analytics' => [
        'enabled' => true,
        'retention_days' => 90,
    ],

    'quotas' => [
        'enabled' => true,
        'tiers' => [/*...*/],
    ],
];
```

## Artisan Commands

```bash
# List registered tools
php artisan mcp:tools

# Test tool execution
php artisan mcp:test blog_get_post --post_id=1

# Prune old metrics
php artisan mcp:prune-metrics --days=90

# Check quota usage
php artisan mcp:quota-status {workspace-id}

# Export tool definitions
php artisan mcp:export-tools --format=json
```

## Best Practices

### 1. Use Workspace Context

```php
// ✅ Good - workspace security
class ListPostsTool extends Tool
{
    use RequiresWorkspaceContext;
}

// ❌ Bad - no workspace isolation
class ListPostsTool extends Tool { }
```

### 2. Validate SQL Queries

```php
// ✅ Good - validated queries
$this->validator->validate($query);
DB::select($query);

// ❌ Bad - raw queries
DB::select($userInput); // SQL injection risk!
```

### 3. Use Read-Only Connections

```php
// ✅ Good - read-only connection
DB::connection('mcp_readonly')->select($query);

// ❌ Bad - default connection with write access
DB::select($query);
```

### 4. Track Analytics

```php
// ✅ Good - analytics tracked automatically
// Just implement the tool, framework handles tracking

// ❌ Bad - manual tracking (not needed)
```

### 5. Declare Dependencies

```php
// ✅ Good - explicit dependencies
public function dependencies(): array
{
    return [
        ToolDependency::make('prerequisite_tool'),
    ];
}
```

## Changelog

See [CHANGELOG.md](https://github.com/host-uk/core-php/blob/main/packages/core-mcp/changelog/2026/jan/features.md)

## License

EUPL-1.2

## Learn More

- [Workspace Security →](/security/workspace-isolation)
- [SQL Injection Prevention →](/security/sql-validation)
- [Model Context Protocol Specification](https://modelcontextprotocol.io)
