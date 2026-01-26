# API Package

The API package provides secure REST API functionality with OpenAPI documentation, rate limiting, webhook delivery, and scope-based authorization.

## Installation

```bash
composer require host-uk/core-api
```

## Features

### OpenAPI Documentation

Automatically generated API documentation with Swagger/Scalar/ReDoc interfaces:

```php
<?php

namespace Mod\Blog\Controllers\Api;

use Mod\Blog\Models\Post;
use Core\Api\Documentation\Attributes\ApiTag;
use Core\Api\Documentation\Attributes\ApiParameter;
use Core\Api\Documentation\Attributes\ApiResponse;

#[ApiTag('Posts', 'Blog post management')]
class PostController
{
    #[ApiResponse(200, 'Success', Post::class)]
    #[ApiResponse(404, 'Post not found')]
    public function show(Post $post)
    {
        return response()->json($post);
    }

    #[ApiParameter('title', 'string', 'Post title', required: true)]
    #[ApiParameter('content', 'string', 'Post content', required: true)]
    #[ApiResponse(201, 'Post created', Post::class)]
    public function store(Request $request)
    {
        $post = Post::create($request->validated());

        return response()->json($post, 201);
    }
}
```

Access documentation:
- Scalar UI: `https://your-app.test/api/docs`
- Swagger UI: `https://your-app.test/api/docs/swagger`
- ReDoc: `https://your-app.test/api/docs/redoc`
- OpenAPI JSON: `https://your-app.test/api/docs/openapi.json`

### Secure API Keys

Bcrypt-hashed API keys with rotation support:

```php
use Mod\Api\Models\ApiKey;

// Create API key
$apiKey = ApiKey::create([
    'name' => 'Mobile App',
    'workspace_id' => $workspace->id,
    'scopes' => ['posts:read', 'posts:write'],
    'rate_limit_tier' => 'pro',
]);

// Get plaintext key (only shown once!)
$plaintext = $apiKey->plaintext_key; // sk_live_...

// Verify key
if ($apiKey->verify($plaintext)) {
    // Valid key
}

// Rotate key
$newKey = $apiKey->rotate();
```

### Rate Limiting

Tier-based rate limiting with workspace isolation:

```php
// config/core-api.php
'rate_limits' => [
    'tiers' => [
        'free' => [
            'requests' => 1000,
            'window' => 60, // minutes
        ],
        'pro' => [
            'requests' => 10000,
            'window' => 60,
        ],
        'enterprise' => [
            'requests' => null, // unlimited
        ],
    ],
],
```

Rate limit headers are automatically added:

```
X-RateLimit-Limit: 10000
X-RateLimit-Remaining: 9995
X-RateLimit-Reset: 1640995200
```

### Scope Enforcement

Fine-grained API access control:

```php
// Define scopes in API key
$apiKey = ApiKey::create([
    'scopes' => ['posts:read', 'posts:write', 'categories:read'],
]);

// Protect routes with scopes
Route::middleware(['api', 'auth:sanctum', 'scope:posts:write'])
    ->post('/posts', [PostController::class, 'store']);

// Check scopes in controller
if (! $request->user()->tokenCan('posts:delete')) {
    abort(403, 'Insufficient permissions');
}
```

Available scopes:

```php
// config/core-api.php
'scopes' => [
    'available' => [
        'posts:read',
        'posts:write',
        'posts:delete',
        'categories:read',
        'categories:write',
        'analytics:read',
        'webhooks:manage',
    ],
],
```

### Webhook Delivery

Reliable webhook delivery with retry logic and signature verification:

```php
use Mod\Api\Models\WebhookEndpoint;
use Mod\Api\Services\WebhookService;

// Register webhook endpoint
$endpoint = WebhookEndpoint::create([
    'url' => 'https://customer.com/webhooks',
    'events' => ['post.created', 'post.updated'],
    'secret' => Str::random(32),
]);

// Dispatch webhook
$webhook = app(WebhookService::class);

$webhook->dispatch('post.created', [
    'id' => $post->id,
    'title' => $post->title,
    'published_at' => $post->published_at,
], $endpoint);
```

### Webhook Signature Verification

Webhooks are signed with HMAC-SHA256:

```php
// Receiving webhooks (customer side)
$signature = $request->header('X-Webhook-Signature');
$timestamp = $request->header('X-Webhook-Timestamp');
$payload = $request->getContent();

$expected = hash_hmac(
    'sha256',
    $timestamp . '.' . $payload,
    $webhookSecret
);

if (! hash_equals($expected, $signature)) {
    abort(401, 'Invalid signature');
}

// Check timestamp to prevent replay attacks
if (abs(time() - $timestamp) > 300) {
    abort(401, 'Request too old');
}
```

Core PHP provides a helper service:

```php
use Mod\Api\Services\WebhookSignature;

$verifier = app(WebhookSignature::class);

if (! $verifier->verify($request, $webhookSecret)) {
    abort(401, 'Invalid signature');
}
```

### Usage Alerts

Monitor API usage and alert on high usage:

```php
// config/core-api.php
'usage_alerts' => [
    'enabled' => true,
    'thresholds' => [
        'warning' => 80, // % of limit
        'critical' => 95,
    ],
],
```

Check usage alerts:

```bash
php artisan api:check-usage-alerts
```

Notifications sent when usage exceeds thresholds:

```php
use Mod\Api\Notifications\HighApiUsageNotification;

// Sent automatically to workspace owners
Mail::to($workspace->owner)
    ->send(new HighApiUsageNotification($workspace, $usage));
```

## API Routes

Define API routes in your module:

```php
// Mod/Blog/Routes/api.php
<?php

use Illuminate\Support\Facades\Route;
use Mod\Blog\Controllers\Api\PostController;

Route::prefix('v1')->group(function () {
    // Public endpoints
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{post}', [PostController::class, 'show']);

    // Protected endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('posts', [PostController::class, 'store'])
            ->middleware('scope:posts:write');

        Route::put('posts/{post}', [PostController::class, 'update'])
            ->middleware('scope:posts:write');

        Route::delete('posts/{post}', [PostController::class, 'destroy'])
            ->middleware('scope:posts:delete');
    });
});
```

Register in Boot.php:

```php
public function onApiRoutes(ApiRoutesRegistering $event): void
{
    $event->routes(fn () => require __DIR__.'/Routes/api.php');
}
```

## API Resources

Transform models for API responses:

```php
<?php

namespace Mod\Blog\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when(
                $request->user()?->tokenCan('posts:read:full'),
                $this->content
            ),
            'published_at' => $this->published_at?->toIso8601String(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'author' => new UserResource($this->whenLoaded('author')),
            'links' => [
                'self' => route('api.posts.show', $this),
                'category' => route('api.categories.show', $this->category_id),
            ],
        ];
    }
}
```

Use in controllers:

```php
public function index()
{
    $posts = Post::with('category', 'author')->paginate(20);

    return PostResource::collection($posts);
}

public function show(Post $post)
{
    return new PostResource($post->load('category', 'author'));
}
```

## Error Handling

Standardized error responses:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."],
    "content": ["The content field is required."]
  }
}
```

Custom error responses:

```php
return response()->json([
    'message' => 'Post not found',
    'error_code' => 'POST_NOT_FOUND',
], 404);
```

## Pagination

Laravel's pagination is automatically formatted:

```json
{
  "data": [
    { "id": 1, "title": "Post 1" },
    { "id": 2, "title": "Post 2" }
  ],
  "links": {
    "first": "https://api.example.com/posts?page=1",
    "last": "https://api.example.com/posts?page=10",
    "prev": null,
    "next": "https://api.example.com/posts?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 20,
    "to": 20,
    "total": 200
  }
}
```

## Testing

### Feature Tests

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Mod\Blog\Models\Post;
use Mod\Api\Models\ApiKey;

class PostApiTest extends TestCase
{
    public function test_can_list_posts(): void
    {
        Post::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_requires_authentication_to_create_post(): void
    {
        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Test Post',
            'content' => 'Test content',
        ]);

        $response->assertStatus(401);
    }

    public function test_can_create_post_with_valid_api_key(): void
    {
        $apiKey = ApiKey::factory()
            ->withScopes(['posts:write'])
            ->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey->plaintext_key,
        ])->postJson('/api/v1/posts', [
            'title' => 'Test Post',
            'content' => 'Test content',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'title']]);
    }

    public function test_enforces_rate_limits(): void
    {
        $apiKey = ApiKey::factory()
            ->tier('free')
            ->create();

        // Make requests up to limit
        for ($i = 0; $i < 1001; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey->plaintext_key,
            ])->getJson('/api/v1/posts');
        }

        $response->assertStatus(429); // Too Many Requests
    }
}
```

## Configuration

```php
// config/core-api.php
return [
    'rate_limits' => [
        'tiers' => [
            'free' => ['requests' => 1000, 'window' => 60],
            'pro' => ['requests' => 10000, 'window' => 60],
            'enterprise' => ['requests' => null],
        ],
        'headers_enabled' => true,
    ],

    'api_keys' => [
        'hash_algorithm' => 'bcrypt',
        'rotation_grace_period' => 86400, // 24 hours
        'prefix' => 'sk_',
    ],

    'webhooks' => [
        'signature_algorithm' => 'sha256',
        'max_retries' => 3,
        'retry_delay' => 60,
        'timeout' => 10,
        'verify_ssl' => true,
    ],

    'documentation' => [
        'enabled' => true,
        'require_auth' => false,
        'title' => 'API Documentation',
        'default_ui' => 'scalar',
    ],

    'scopes' => [
        'enforce' => true,
        'available' => [
            'posts:read',
            'posts:write',
            'posts:delete',
        ],
    ],
];
```

## Artisan Commands

```bash
# Check usage alerts
php artisan api:check-usage-alerts

# Rotate API key
php artisan api:rotate-key {key-id}

# Generate API documentation
php artisan api:generate-docs

# Test webhook delivery
php artisan api:test-webhook {endpoint-id}
```

## Best Practices

### 1. Use API Resources

```php
// ✅ Good - consistent formatting
return PostResource::collection($posts);

// ❌ Bad - raw data
return response()->json($posts);
```

### 2. Version Your API

```php
// ✅ Good - versioned routes
Route::prefix('v1')->group(/*...*/);
Route::prefix('v2')->group(/*...*/);

// ❌ Bad - no versioning
Route::prefix('api')->group(/*...*/);
```

### 3. Use Scopes for Authorization

```php
// ✅ Good - granular scopes
Route::middleware('scope:posts:write')->post('/posts', /*...*/);

// ❌ Bad - no scope checking
Route::middleware('auth:sanctum')->post('/posts', /*...*/);
```

### 4. Validate Webhook Signatures

```php
// ✅ Good - verify signatures
if (! WebhookSignature::verify($request, $secret)) {
    abort(401);
}

// ❌ Bad - no verification
// Process webhook without checking signature
```

## Changelog

See [CHANGELOG.md](https://github.com/host-uk/core-php/blob/main/packages/core-api/changelog/2026/jan/features.md)

## License

EUPL-1.2

## Learn More

- [API Authentication →](/security/api-authentication)
- [Rate Limiting →](/security/rate-limiting)
- [Webhook Delivery →](/patterns-guide/webhooks)
- [OpenAPI Documentation](https://swagger.io/specification/)
