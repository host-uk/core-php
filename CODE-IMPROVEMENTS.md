# Code Improvements Analysis

**Generated:** 2026-01-26
**Scope:** core-php and core-admin packages
**Focus:** Production-ready improvements for v1.0.0

---

## Summary

Found **12 high-impact improvements** across core-php and core-admin packages. These improvements focus on:

1. **Completing partial implementations** (ServiceDiscovery, SeederRegistry)
2. **Removing TODO comments** for clean v1.0.0 release
3. **Type safety improvements** (ConfigService)
4. **Test coverage gaps** (Services, Seeders)
5. **Performance optimizations** (Config caching)

**Total estimated effort:** 18-24 hours

---

## High Priority Improvements

### 1. Complete ServiceDiscovery Implementation ⭐⭐⭐

**File:** `packages/core-php/src/Core/Service/ServiceDiscovery.php`

**Issue:** ServiceDiscovery is fully documented (752 lines!) but appears to be unused in the codebase. No services are actually implementing `ServiceDefinition`.

**Impact:** High - Core infrastructure for service registration and dependency resolution

**Actions:**
- [ ] Create example service implementing `ServiceDefinition`
- [ ] Wire ServiceDiscovery into Boot/lifecycle
- [ ] Add test coverage for discovery process
- [ ] Document how modules register as services
- [ ] OR: Mark as experimental/future feature in docs

**Estimated effort:** 4-5 hours

**Code snippet:**
```php
// File shows comprehensive implementation but no usage:
class ServiceDiscovery
{
    public function discover(): Collection { /* 752 lines */ }
    public function validateDependencies(): array { /* ... */ }
    public function getResolutionOrder(): Collection { /* ... */ }
}

// But grep shows no ServiceDefinition implementations in codebase
```

---

### 2. Complete SeederRegistry Integration ⭐⭐⭐

**File:** `packages/core-php/src/Core/Database/Seeders/SeederRegistry.php`

**Issue:** SeederRegistry + SeederDiscovery exist but aren't integrated with Laravel's seeder system. The `CoreDatabaseSeeder` class exists but may not use these.

**Impact:** High - Critical for database setup

**Actions:**
- [ ] Integrate SeederRegistry with `CoreDatabaseSeeder`
- [ ] Test seeder dependency resolution
- [ ] Add circular dependency detection tests
- [ ] Document seeder ordering in README
- [ ] Add `php artisan db:seed --class=CoreDatabaseSeeder` docs

**Estimated effort:** 3-4 hours

**Code snippet:**
```php
// SeederRegistry has full topological sort implementation
public function getOrdered(): array
{
    $discovery = new class extends SeederDiscovery {
        public function setSeeders(array $seeders): void { /* ... */ }
    };

    return $discovery->discover();
}

// But TODO indicates this is incomplete
```

---

### 3. Remove UserStatsService TODO Comments ⭐⭐

**File:** `packages/core-php/src/Mod/Tenant/Services/UserStatsService.php`

**Issue:** 6 TODO comments for features that won't exist in v1.0.0:
- Social accounts (line 83)
- Scheduled posts (line 87)
- Storage tracking (line 92)
- Social account checks (line 165)
- Bio page checks (line 170)
- Activity logging (line 218)

**Impact:** Medium - Confusing for contributors, looks unfinished

**Actions:**
- [ ] Remove TODOs and replace with `// Future: ...` comments
- [ ] Add docblock explaining these are planned v1.1+ features
- [ ] Update service stats methods to return placeholder data cleanly
- [ ] Document feature roadmap in separate file

**Estimated effort:** 1 hour

**Code snippet:**
```php
// Current:
// TODO: Implement when social accounts are linked
// $socialAccountCount = ...

// Improved:
// Future (v1.1+): Track social accounts across workspaces
// Will be implemented when Mod\Social integration is complete
$limits['social_accounts']['used'] = 0; // Placeholder until v1.1
```

---

### 4. Remove 2FA TODO Comments from Settings Modal ⭐⭐

**File:** `packages/core-admin/src/Website/Hub/View/Modal/Admin/Settings.php`

**Issue:** 5 identical TODO comments: `// TODO: Implement native 2FA - currently disabled`

**Impact:** Medium - Duplicate comments, confusing state

**Actions:**
- [ ] Remove duplicate TODO comments
- [ ] Add single docblock at class level explaining 2FA status
- [ ] Update feature flag logic with clear comment
- [ ] Document 2FA roadmap in ROADMAP.md (already exists)

**Estimated effort:** 30 minutes

**Code snippet:**
```php
// Current: 5x duplicate TODO comments

// Improved:
/**
 * Settings Modal
 *
 * Two-Factor Authentication:
 * Native 2FA is planned for v1.2 (see ROADMAP.md).
 * Currently checks config('social.features.two_factor_auth') flag.
 * When enabled, integrates with Laravel Fortify.
 */
class Settings extends Component
{
    // Feature flags - 2FA via config flag
    public bool $isTwoFactorEnabled = false;
```

---

### 5. ConfigService Type Safety Improvements ⭐⭐

**File:** `packages/core-php/src/Core/Config/ConfigService.php`

**Issue:** 25+ public methods with complex signatures, some using `mixed` types. Could benefit from stricter typing and return type hints.

**Impact:** Medium - Better IDE support and type safety

**Actions:**
- [ ] Add stricter return types where possible
- [ ] Use union types (e.g., `string|int|bool|array`)
- [ ] Add @template PHPDoc for generic methods
- [ ] Add PHPStan level 5 annotations
- [ ] Test with PHPStan --level=5

**Estimated effort:** 2-3 hours

**Code snippet:**
```php
// Current:
public function get(string $key, mixed $default = null): mixed

// Improved with generics:
/**
 * @template T
 * @param T $default
 * @return T
 */
public function get(string $key, mixed $default = null): mixed
```

---

### 6. Add Missing Service Tests ⭐⭐

**Issue:** Several services lack dedicated test files:
- `ActivityLogService` - no test file
- `BlocklistService` - has test but inline (should be in Tests/)
- `CspNonceService` - no tests
- `SchemaBuilderService` - no tests

**Impact:** Medium - Test coverage gaps

**Actions:**
- [ ] Create `ActivityLogServiceTest.php`
- [ ] Move `BlocklistServiceTest` to proper location
- [ ] Create `CspNonceServiceTest.php`
- [ ] Create `SchemaBuilderServiceTest.php`
- [ ] Add integration tests for service lifecycle

**Estimated effort:** 4-5 hours

**Files to create:**
```
packages/core-php/src/Core/Activity/Tests/Unit/ActivityLogServiceTest.php
packages/core-php/src/Core/Headers/Tests/Unit/CspNonceServiceTest.php
packages/core-php/src/Core/Seo/Tests/Unit/SchemaBuilderServiceTest.php
```

---

## Medium Priority Improvements

### 7. Optimize Config Caching ⭐

**File:** `packages/core-php/src/Core/Config/ConfigService.php`

**Issue:** Config resolution hits database frequently. Could use tiered caching (memory → Redis → DB).

**Actions:**
- [ ] Profile config query performance
- [ ] Implement request-level memoization cache
- [ ] Add Redis cache layer with TTL
- [ ] Add config warmup artisan command
- [ ] Document cache strategy

**Estimated effort:** 3-4 hours

---

### 8. Add ServiceDiscovery Artisan Commands ⭐

**Issue:** No CLI tooling for service management

**Actions:**
- [ ] Create `php artisan services:list` command
- [ ] Create `php artisan services:validate` command
- [ ] Create `php artisan services:cache` command
- [ ] Show dependency tree visualization
- [ ] Add JSON export option

**Estimated effort:** 2-3 hours

---

### 9. Extract Locale/Timezone Lists to Config ⭐

**File:** `packages/core-php/src/Mod/Tenant/Services/UserStatsService.php`

**Issue:** Hardcoded locale/timezone lists in service methods

**Actions:**
- [ ] Move to `config/locales.php`
- [ ] Move to `config/timezones.php`
- [ ] Make extensible via config
- [ ] Add `php artisan locales:update` command
- [ ] Support custom locale additions

**Estimated effort:** 1-2 hours

---

### 10. Add MakePlugCommand Template Validation ⭐

**File:** `packages/core-php/src/Core/Console/Commands/MakePlugCommand.php`

**Issue:** TODO comments are intentional templates but could be validated

**Actions:**
- [ ] Add `--validate` flag to check generated code
- [ ] Warn if TODOs remain after generation
- [ ] Add completion checklist after generation
- [ ] Create interactive setup wizard option
- [ ] Add `php artisan make:plug --example` with filled example

**Estimated effort:** 2-3 hours

---

## Low Priority Improvements

### 11. Document RELEASE-BLOCKERS Status ⭐

**File:** `packages/core-php/src/Core/RELEASE-BLOCKERS.md`

**Issue:** File references TODOs as blockers but most are resolved

**Actions:**
- [ ] Review and update blocker status
- [ ] Move resolved items to completed section
- [ ] Archive or delete if no longer relevant
- [ ] Link to TODO.md for tracking

**Estimated effort:** 30 minutes

---

### 12. Standardize Service Naming ⭐

**Issue:** Inconsistent service class naming:
- `ActivityLogService` ✓
- `UserStatsService` ✓
- `CspNonceService` ✓
- `RedirectService` ✓
- BUT: `ServiceOgImageService` ❌ (should be `OgImageService`)

**Actions:**
- [ ] Rename `ServiceOgImageService` → `OgImageService`
- [ ] Update imports and references
- [ ] Add naming convention to CONTRIBUTING.md
- [ ] Check for other naming inconsistencies

**Estimated effort:** 1 hour

---

## Code Quality Metrics

**Current State:**
- ✅ Services: 33 service classes found
- ✅ Documentation: Excellent (752-line ServiceDiscovery doc!)
- ⚠️ Test Coverage: Gaps in service tests
- ⚠️ TODO Comments: 10+ production TODOs
- ⚠️ Type Safety: Good but could be stricter

**After Improvements:**
- ✅ Zero production TODO comments
- ✅ All services have tests (80%+ coverage)
- ✅ ServiceDiscovery fully integrated OR documented as future
- ✅ SeederRegistry integrated with database setup
- ✅ Stricter type hints with generics

---

## Implementation Priority

### For v1.0.0 Release (Next 48 hours):
1. Remove TODO comments (#3, #4) - 1.5 hours
2. Document ServiceDiscovery status (#1) - 1 hour
3. Add critical service tests (#6) - 2 hours
4. Review RELEASE-BLOCKERS (#11) - 30 minutes

**Total: 5 hours for clean v1.0.0**

### For v1.1 (Post-release):
1. Complete ServiceDiscovery integration (#1) - 4 hours
2. Complete SeederRegistry integration (#2) - 3 hours
3. Config caching optimization (#7) - 3 hours
4. Type safety improvements (#5) - 2 hours

**Total: 12 hours for v1.1 features**

---

## Recommendations

### Immediate (Before v1.0.0 release):
✅ **Remove all TODO comments** - Replace with "Future:" or remove entirely
✅ **Add service test coverage** - At least smoke tests for critical services
✅ **Document incomplete features** - Clear roadmap for ServiceDiscovery/SeederRegistry

### Short-term (v1.1):
🔨 **Complete ServiceDiscovery** - Integrate or document as experimental
🔨 **Seeder dependency resolution** - Wire into CoreDatabaseSeeder
🔨 **Config caching** - Significant performance win

### Long-term (v1.2+):
📚 **Service CLI tools** - Better DX for service management
📚 **Type safety audit** - PHPStan level 8
📚 **Performance profiling** - Benchmark all services

---

## Notes

- **ServiceDiscovery**: Incredibly well-documented but appears unused. Needs integration OR documentation as future feature.
- **SeederRegistry**: Has topological sort implemented but not wired up. High value once integrated.
- **UserStatsService**: TODOs are for v1.1+ features - should document this clearly.
- **Config System**: Very comprehensive - caching would be high-value optimization.

**Overall Assessment:** Code quality is high. Main improvements are completing integrations and removing TODOs for clean v1.0.0 release.
