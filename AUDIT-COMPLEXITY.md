# Code Complexity & Maintainability Audit

This audit analyzes the code quality of the Core PHP Framework, identifies maintainability issues, and provides recommendations for improvement. The findings are prioritized by their impact on the maintenance burden.

## High Impact Findings

None. The codebase is generally well-structured and follows modern best practices.

## Medium Impact Findings

### 1. Rigid Namespace Detection in `ModuleScanner`

**File:** `src/Core/ModuleScanner.php`
**Method:** `classFromFile()`

**Issue:**
The `classFromFile()` method uses a series of `if (str_contains(...))` checks to determine the root namespace for a module based on its file path. This approach is rigid and violates the Open/Closed Principle. Adding a new module type (e.g., `app/Addon/`) would require modifying this core file, which increases the risk of regressions.

**Recommendation:**
Refactor the `if` chain into a more flexible, data-driven approach. A mapping array can be used to associate base paths with their corresponding root namespaces. This would allow new module types to be registered through configuration rather than code changes, improving extensibility and maintainability.

**Code Example of Improvement:**

```php
// Before
private function classFromFile(string $file, string $basePath): ?string
{
    // ... namespace derivation logic ...

    if (str_contains($basePath, '/Core')) {
        return "Core\\{$namespace}";
    }
    if (str_contains($basePath, '/Mod')) {
        return "Mod\\{$namespace}";
    }
    // ... more if statements ...
}

// After
private function classFromFile(string $file, string $basePath): ?string
{
    // A configurable mapping of base paths to namespaces
    $namespaceMapping = [
        '/Core' => 'Core\\',
        '/Mod' => 'Mod\\',
        '/Website' => 'Website\\',
        '/Plug' => 'Plug\\',
    ];

    // ... namespace derivation logic ...

    foreach ($namespaceMapping as $pathFragment => $rootNamespace) {
        if (str_contains($basePath, $pathFragment)) {
            return "{$rootNamespace}{$namespace}";
        }
    }

    // Fallback or error handling
    return null;
}
```

## Low Impact Findings

### 2. Minor Code Duplication in `ModuleRegistry`

**File:** `src/Core/ModuleRegistry.php`
**Methods:** `register()`, `addPaths()`

**Issue:**
The `register()` and `addPaths()` methods share similar logic for iterating through newly discovered mappings, sorting them by priority, and registering them with Laravel's event listener. This represents a minor case of code duplication.

**Recommendation:**
Extract the common logic into a private helper method to reduce duplication and improve clarity. This would centralize the listener registration process, making the code more DRY (Don't Repeat Yourself).

**Code Example of Improvement:**

```php
// Before
public function register(array $paths): void
{
    // ...
    foreach ($this->mappings as $event => $listeners) {
        $sorted = $this->sortByPriority($listeners);
        foreach ($sorted as $moduleClass => $config) {
            Event::listen($event, new LazyModuleListener($moduleClass, $config['method']));
        }
    }
    // ...
}

public function addPaths(array $paths): void
{
    // ...
    foreach ($newMappings as $event => $listeners) {
        $sorted = $this->sortByPriority($listeners);
        foreach ($sorted as $moduleClass => $config) {
            // ... logic to skip if already registered ...
            $this->mappings[$event][$moduleClass] = $config;
            Event::listen($event, new LazyModuleListener($moduleClass, $config['method']));
        }
    }
}

// After
public function register(array $paths): void
{
    // ...
    $this->registerListenersFromMappings($this->mappings);
    // ...
}

public function addPaths(array $paths): void
{
    // ...
    $this->registerListenersFromMappings($newMappings, true); // true to check for existing
    // ...
}

private function registerListenersFromMappings(array $mappings, bool $checkExisting = false): void
{
    foreach ($mappings as $event => $listeners) {
        $sorted = $this->sortByPriority($listeners);
        foreach ($sorted as $moduleClass => $config) {
            if ($checkExisting && isset($this->mappings[$event][$moduleClass])) {
                continue;
            }
            if ($checkExisting) {
                $this->mappings[$event][$moduleClass] = $config;
            }
            Event::listen($event, new LazyModuleListener($moduleClass, $config['method']));
        }
    }
}
```
