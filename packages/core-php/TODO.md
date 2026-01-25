# Core-PHP TODO

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
