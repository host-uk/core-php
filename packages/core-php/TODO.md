# Core-PHP TODO

## Implemented

### Actions Pattern ✓

`Core\Actions\Action` trait for single-purpose business logic classes.

```php
use Core\Actions\Action;

class CreateThing
{
    use Action;

    public function handle(User $user, array $data): Thing
    {
        // Complex business logic here
    }
}

// Usage
$thing = CreateThing::run($user, $data);
```

**Location:** `src/Core/Actions/Action.php`, `src/Core/Actions/Actionable.php`

---

## Seeder Auto-Discovery

**Priority:** Medium
**Context:** Currently apps need a `database/seeders/DatabaseSeeder.php` that manually lists module seeders in order. This is boilerplate that core-php could handle.

### Requirements

- Auto-discover seeders from registered modules (`*/Database/Seeders/*Seeder.php`)
- Support priority ordering via property or attribute (e.g., `public int $priority = 50`)
- Support dependency ordering via `$after` or `$before` arrays
- Provide base `DatabaseSeeder` class that apps can extend or use directly
- Allow apps to override/exclude specific seeders if needed

### Example

```php
// In a module seeder
class FeatureSeeder extends Seeder
{
    public int $priority = 10; // Run early

    public function run(): void { ... }
}

class PackageSeeder extends Seeder
{
    public array $after = [FeatureSeeder::class]; // Run after features

    public function run(): void { ... }
}
```

### Notes

- Current Host Hub DatabaseSeeder has ~20 seeders with implicit ordering
- Key dependencies: features → packages → workspaces → system user → content
- Could use Laravel's service container to resolve seeder graph

---

## Team-Scoped Caching

**Priority:** Medium
**Context:** Repeated queries for workspace-scoped resources. Cache workspace-scoped queries with auto-invalidation.

### Implementation

Extend `BelongsToWorkspace` trait:

```php
trait BelongsToWorkspace
{
    public static function ownedByCurrentWorkspaceCached(int $ttl = 300)
    {
        $workspace = currentWorkspace();
        if (!$workspace) return collect();

        return Cache::remember(
            static::workspaceCacheKey($workspace->id),
            $ttl,
            fn() => static::ownedByCurrentWorkspace()->get()
        );
    }

    protected static function bootBelongsToWorkspace(): void
    {
        static::saved(fn($m) => static::clearWorkspaceCache($m->workspace_id));
        static::deleted(fn($m) => static::clearWorkspaceCache($m->workspace_id));
    }
}
```

### Usage

```php
// Cached for 5 minutes, auto-clears on changes
$biolinks = Biolink::ownedByCurrentWorkspaceCached();
```

---

## Activity Logging

**Priority:** Low
**Context:** No audit trail of user actions across modules.

### Implementation

Add `spatie/laravel-activitylog` integration:

```php
// Core trait for models
trait LogsActivity
{
    use \Spatie\Activitylog\Traits\LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

### Requirements

- Base trait modules can use
- Activity viewer Livewire component for admin
- Workspace-scoped activity queries

---

## Multi-Tenant Data Isolation

**Priority:** High (Security)
**Context:** Multiple modules have workspace isolation issues.

### Issues

1. **Fallback workspace_id** - Some code falls back to `workspace_id = 1` when no context
2. **Global queries** - Some commands query globally without workspace scope
3. **Session trust** - Using `session('workspace_id', 1)` with hardcoded fallback

### Solution

- `BelongsToWorkspace` trait should throw exception when workspace context is missing (not fallback)
- Add `WorkspaceScope` global scope that throws on missing context
- Audit all models for proper scoping
- Add middleware that ensures workspace context before any workspace-scoped operation

---

## Bouncer Request Whitelisting

**Priority:** Medium
**Context:** Every controller action must be explicitly permitted. Unknown actions are blocked (production) or prompt for approval (training mode).

**Philosophy:** If it wasn't trained, it doesn't exist.

### Concept

```
Training Mode (Development):
1. Developer hits /admin/products
2. Clicks "Create Product"
3. System: "BLOCKED - No permission defined for:"
   - Role: admin
   - Action: product.create
   - Route: POST /admin/products
4. Developer clicks [Allow for admin]
5. Permission recorded
6. Continue working

Production Mode:
If permission not in whitelist → 403 Forbidden
No exceptions. No fallbacks. No "default allow".
```

### Database Schema

```php
// core_action_permissions
Schema::create('core_action_permissions', function (Blueprint $table) {
    $table->id();
    $table->string('action');                     // product.create, order.refund
    $table->string('scope')->nullable();          // Resource type or specific ID
    $table->string('guard')->default('web');      // web, api, admin
    $table->string('role')->nullable();           // admin, editor, or null for any auth
    $table->boolean('allowed')->default(false);
    $table->string('source');                     // 'trained', 'seeded', 'manual'
    $table->string('trained_route')->nullable();
    $table->foreignId('trained_by')->nullable();
    $table->timestamp('trained_at')->nullable();
    $table->timestamps();

    $table->unique(['action', 'scope', 'guard', 'role']);
});

// core_action_requests (audit log)
Schema::create('core_action_requests', function (Blueprint $table) {
    $table->id();
    $table->string('method');
    $table->string('route');
    $table->string('action');
    $table->string('scope')->nullable();
    $table->string('guard');
    $table->string('role')->nullable();
    $table->foreignId('user_id')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('status');                     // allowed, denied, pending
    $table->boolean('was_trained')->default(false);
    $table->timestamps();

    $table->index(['action', 'status']);
});
```

### Action Resolution

```php
// Explicit via route attribute
Route::post('/products', [ProductController::class, 'store'])
    ->action('product.create');

// Or via controller attribute
#[Action('product.create')]
public function store(Request $request) { ... }

// Or auto-resolved from controller@method
// ProductController@store → product.store
```

### Integration with Existing Auth

```
Request
    │
    ▼
BouncerGate (action whitelisting)
    │ "Is this action permitted at all?"
    ▼
Laravel Gate/Policy (authorisation)
    │ "Can THIS USER do this to THIS RESOURCE?"
    ▼
Controller
```

### Implementation Phases

**Phase 1: Core**
- [ ] Database migrations
- [ ] `ActionPermission` model
- [ ] `BouncerService` with `check()` method
- [ ] `BouncerGate` middleware

**Phase 2: Training Mode**
- [ ] Training UI (modal prompt)
- [ ] Training controller/routes
- [ ] Request logging

**Phase 3: Tooling**
- [ ] `bouncer:export` command
- [ ] `bouncer:list` command
- [ ] Admin UI for viewing/editing permissions

**Phase 4: Integration**
- [ ] Apply to admin routes
- [ ] Apply to API routes
- [ ] Documentation

### Artisan Commands

```bash
php artisan bouncer:export   # Export trained permissions to seeder
php artisan bouncer:seed     # Import from seeder
php artisan bouncer:list     # List all defined actions
php artisan bouncer:reset    # Clear training data
```

### Benefits

1. **Complete audit trail** - Know exactly what actions exist in your app
2. **No forgotten routes** - If it's not trained, it doesn't work
3. **Role-based by default** - Actions scoped to guards and roles
4. **Deployment safety** - Export/import permissions between environments
5. **Discovery tool** - Training mode maps your entire app's surface area
