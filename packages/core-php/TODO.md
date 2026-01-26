# Core-PHP TODO

## Testing & Quality Assurance

### High Priority

- [ ] **Test Coverage: CDN Services** - Achieve 80%+ coverage for CDN integration
  - [ ] Test BunnyCdnService upload/purge operations
  - [ ] Test FluxCdnService URL generation and purging
  - [ ] Test StorageOffload for S3/BunnyCDN switching
  - [ ] Test AssetPipeline with versioning and minification
  - [ ] Test CdnUrlBuilder with signed URLs
  - **Estimated effort:** 4-6 hours

- [ ] **Test Coverage: Activity Logging** - Add comprehensive activity tests
  - [ ] Test LogsActivity trait with all CRUD operations
  - [ ] Test IP hashing for GDPR compliance
  - [ ] Test activity pruning command
  - [ ] Test workspace scoping in activity logs
  - **Estimated effort:** 3-4 hours

- [ ] **Test Coverage: Media Processing** - Test image optimization pipeline
  - [ ] Test ImageOptimizer with various formats (JPG, PNG, WebP, AVIF)
  - [ ] Test ImageResizer with responsive sizes
  - [ ] Test ExifStripper for privacy
  - [ ] Test lazy thumbnail generation
  - [ ] Test MediaConversion queuing and progress tracking
  - **Estimated effort:** 5-7 hours

- [ ] **Test Coverage: Search System** - Test unified search
  - [ ] Test SearchAnalytics recording and queries
  - [ ] Test SearchSuggestions with partial queries
  - [ ] Test SearchHighlighter with various patterns
  - [ ] Test cross-model unified search
  - **Estimated effort:** 4-5 hours

### Medium Priority

- [ ] **Test Coverage: SEO Tools** - Test SEO metadata and generation
  - [ ] Test SeoMetadata rendering (title, description, OG, Twitter)
  - [ ] Test dynamic OG image generation job
  - [ ] Test sitemap generation and indexing
  - [ ] Test structured data (JSON-LD) generation
  - [ ] Test canonical URL validation
  - **Estimated effort:** 4-5 hours

- [ ] **Test Coverage: Configuration System** - Test config profiles and versioning
  - [ ] Test ConfigService with profiles
  - [ ] Test ConfigVersioning and rollback
  - [ ] Test ConfigExporter import/export
  - [ ] Test sensitive config encryption
  - [ ] Test config cache invalidation
  - **Estimated effort:** 3-4 hours

- [ ] **Test Coverage: Security Headers** - Test header middleware
  - [ ] Test CSP header generation with nonces
  - [ ] Test HSTS enforcement
  - [ ] Test X-Frame-Options and security headers
  - [ ] Test CspNonceService in views
  - **Estimated effort:** 2-3 hours

- [ ] **Test Coverage: Email Shield** - Test email validation
  - [ ] Test disposable domain detection
  - [ ] Test role-based email detection
  - [ ] Test DNS MX record validation
  - [ ] Test blocklist/allowlist functionality
  - **Estimated effort:** 2-3 hours

### Low Priority

- [ ] **Test Coverage: Lang/Translation** - Test translation memory
  - [ ] Test TranslationMemory fuzzy matching
  - [ ] Test TMX import/export
  - [ ] Test ICU message formatting
  - [ ] Test translation coverage reporting
  - **Estimated effort:** 3-4 hours

- [ ] **Performance: Config Caching** - Optimize config queries
  - [ ] Profile ConfigService query performance
  - [ ] Implement query result caching beyond remember()
  - [ ] Add Redis cache driver support
  - **Estimated effort:** 2-3 hours

## Features & Enhancements

### High Priority

- [ ] **EPIC: Core DOM Component System** - Extend `<core:*>` helpers for HLCRF layouts
  - [ ] **Phase 1: Architecture & Planning** (2-3 hours)
    - [ ] Create `src/Core/Front/Dom/` namespace structure
    - [ ] Design Blade component API (slot-based vs named components)
    - [ ] Document component naming conventions
    - [ ] Plan backwards compatibility with existing HLCRF Layout class

  - [ ] **Phase 2: Core DOM Components** (4-6 hours)
    - [ ] Create `<core:header>` component → maps to HLCRF H slot
    - [ ] Create `<core:left>` component → maps to HLCRF L slot
    - [ ] Create `<core:content>` component → maps to HLCRF C slot
    - [ ] Create `<core:right>` component → maps to HLCRF R slot
    - [ ] Create `<core:footer>` component → maps to HLCRF F slot
    - [ ] Create `<core:dom :slot="H|L|C|R|F">` generic slot component
    - [ ] Add automatic path tracking (H-0, L-C-2, etc.)
    - [ ] Support nested layouts with path inheritance

  - [ ] **Phase 3: Layout Container Components** (3-4 hours)
    - [ ] Create `<core:layout variant="HLCRF">` wrapper component
    - [ ] Create `<core:page>` component (alias for HCF layout)
    - [ ] Create `<core:dashboard>` component (alias for HLCRF layout)
    - [ ] Create `<core:widget>` component (alias for C-only layout)
    - [ ] Support inline nesting syntax: `<core:layout variant="H[LC]CF">`

  - [ ] **Phase 4: Semantic HTML Components** (2-3 hours)
    - [ ] Create `<core:section>` with automatic semantic tags
    - [ ] Create `<core:aside>` for sidebars
    - [ ] Create `<core:article>` for content blocks
    - [ ] Create `<core:nav>` for navigation areas
    - [ ] Add ARIA landmark support automatically

  - [ ] **Phase 5: Component Composition** (3-4 hours)
    - [ ] Support `<core:block>` for data-block attributes
    - [ ] Add `<core:slot name="xyz">` for custom named slots
    - [ ] Create `<core:grid cols="3">` for layout grids
    - [ ] Create `<core:stack direction="vertical|horizontal">`
    - [ ] Support responsive breakpoints in components

  - [ ] **Phase 6: Integration & Testing** (4-5 hours)
    - [ ] Register all components in CoreTagCompiler
    - [ ] Test component nesting and path generation
    - [ ] Test with Livewire components inside slots
    - [ ] Test responsive layout switching
    - [ ] Create comprehensive test suite (80%+ coverage)
    - [ ] Add Pest snapshots for HTML output

  - [ ] **Phase 7: Documentation & Examples** (3-4 hours)
    - [ ] Create `docs/packages/core/dom-components.md`
    - [ ] Document all component props and slots
    - [ ] Add migration guide from PHP Layout class to Blade components
    - [ ] Create example layouts (blog, dashboard, landing page)
    - [ ] Add Storybook-style component gallery

  - [ ] **Phase 8: Developer Experience** (2-3 hours)
    - [ ] Add IDE autocomplete hints for component props
    - [ ] Create `php artisan make:layout` command
    - [ ] Add validation for invalid slot combinations
    - [ ] Create debug mode with visual slot boundaries
    - [ ] Add performance profiling for nested layouts

  **Total Estimated Effort:** 23-32 hours
  **Priority:** High - Core framework feature
  **Impact:** Dramatically improves DX for building HLCRF layouts
  **Dependencies:** Existing CoreTagCompiler, Layout class

  **Example Usage:**
  ```blade
  <core:layout variant="HLCRF">
      <core:header>
          <nav>Navigation here</nav>
      </core:header>

      <core:left>
          <core:widget>
              <h3>Sidebar Widget</h3>
              <p>Content</p>
          </core:widget>
      </core:left>

      <core:content>
          <core:article>
              <h1>Main Content</h1>
              <p>Article text...</p>
          </core:article>
      </core:content>

      <core:right>
          @livewire('recent-activity')
      </core:right>

      <core:footer>
          <p>&copy; 2026</p>
      </core:footer>
  </core:layout>
  ```

  **Alternative Slot-Based Syntax:**
  ```blade
  <core:page>
      <core:dom :slot="H">
          <nav>Header</nav>
      </core:dom>

      <core:dom :slot="C">
          <article>Content</article>
      </core:dom>

      <core:dom :slot="F">
          <footer>Footer</footer>
      </core:dom>
  </core:page>
  ```

- [ ] **Feature: Seeder Dependency Resolution** - Complete seeder system
  - [ ] Implement SeederRegistry with dependency graph
  - [ ] Add circular dependency detection
  - [ ] Support #[SeederPriority], #[SeederBefore], #[SeederAfter]
  - [ ] Test with complex dependency chains
  - **Estimated effort:** 4-6 hours
  - **Files:** `src/Core/Database/Seeders/`

- [ ] **Feature: Service Discovery** - Complete service registration system
  - [ ] Implement ServiceDiscovery class
  - [ ] Add service dependency validation
  - [ ] Support version compatibility checking
  - [ ] Test service resolution with dependencies
  - **Estimated effort:** 3-4 hours
  - **Files:** `src/Core/Service/`

- [ ] **Feature: Tiered Cache** - Complete tiered caching implementation
  - [ ] Implement TieredCacheStore with memory → Redis → file
  - [ ] Add CacheWarmer for pre-population
  - [ ] Add StorageMetrics for monitoring
  - [ ] Test cache tier fallback behavior
  - **Estimated effort:** 5-6 hours
  - **Files:** `src/Core/Storage/`

### Medium Priority

- [ ] **Feature: Action Gate Enforcement** - Complete action gate system
  - [ ] Add ActionGateMiddleware enforcement mode
  - [ ] Implement training mode for learning patterns
  - [ ] Add audit logging for all requests
  - [ ] Test with dangerous actions
  - **Estimated effort:** 4-5 hours
  - **Files:** `src/Core/Bouncer/Gate/`

- [ ] **Enhancement: Media Progress Tracking** - Real-time conversion progress
  - [ ] Fire ConversionProgress events
  - [ ] Add WebSocket broadcasting support
  - [ ] Create Livewire progress component
  - [ ] Test with large video files
  - **Estimated effort:** 3-4 hours
  - **Files:** `src/Core/Media/`

- [ ] **Enhancement: SEO Score Tracking** - Complete SEO analytics
  - [ ] Implement SeoScoreTrend recording
  - [ ] Add SEO score calculation logic
  - [ ] Create admin dashboard for SEO metrics
  - [ ] Add automated SEO audit command
  - **Estimated effort:** 4-5 hours
  - **Files:** `src/Core/Seo/Analytics/`

### Low Priority

- [ ] **Enhancement: Search Analytics Dashboard** - Visual search insights
  - [ ] Create Livewire component for search analytics
  - [ ] Add charts for popular searches and CTR
  - [ ] Show zero-result searches for improvement
  - [ ] Export search analytics to CSV
  - **Estimated effort:** 3-4 hours

- [ ] **Enhancement: Email Shield Stats** - Email validation metrics
  - [ ] Track disposable email blocks
  - [ ] Track validation failures by reason
  - [ ] Add admin dashboard for email stats
  - [ ] Implement automatic pruning
  - **Estimated effort:** 2-3 hours

## Documentation

- [ ] **API Docs: Service Contracts** - Document service pattern
  - [ ] Add examples for ServiceDefinition
  - [ ] Document service versioning
  - [ ] Add dependency resolution examples
  - **Estimated effort:** 2-3 hours

- [ ] **API Docs: Seeder System** - Document seeder attributes
  - [ ] Document dependency resolution
  - [ ] Add complex ordering examples
  - [ ] Document circular dependency errors
  - **Estimated effort:** 2-3 hours

## Code Quality

- [ ] **Refactor: Extract BlocklistService Tests** - Separate test concerns
  - [ ] Create BlocklistServiceTest.php
  - [ ] Move tests from inline to dedicated file
  - [ ] Add edge case coverage
  - **Estimated effort:** 1-2 hours

- [ ] **Refactor: Consolidate Privacy Helpers** - Single source of truth
  - [ ] Move IP hashing to dedicated service
  - [ ] Consolidate anonymization logic
  - [ ] Add comprehensive tests
  - **Estimated effort:** 2-3 hours

- [ ] **PHPStan: Fix Level 5 Errors** - Improve type safety
  - [ ] Fix union type issues in config system
  - [ ] Add missing return types
  - [ ] Fix property type declarations
  - **Estimated effort:** 3-4 hours

## Infrastructure

- [x] **GitHub Template Repository** - Created host-uk/core-template
  - [x] Set up base Laravel 12 app
  - [x] Configure composer.json with Core packages
  - [x] Update bootstrap/app.php to register providers
  - [x] Create config/core.php
  - [x] Update .env.example with Core variables
  - [x] Write comprehensive README.md
  - [x] Test `php artisan core:new` command
  - **Completed:** January 2026
  - **Command:** `php artisan core:new my-project`

- [ ] **CI/CD: Add PHP 8.3 Testing** - Future compatibility
  - [ ] Test on PHP 8.3
  - [ ] Fix any deprecations
  - [ ] Update composer.json PHP constraint
  - **Estimated effort:** 1-2 hours

- [ ] **CI/CD: Add Performance Benchmarks** - Track performance
  - [ ] Benchmark critical paths (config load, search, etc.)
  - [ ] Set performance budgets
  - [ ] Fail CI on regressions
  - **Estimated effort:** 3-4 hours

---

## Completed (January 2026)

- [x] **CDN integration tests** - Comprehensive test suite added
- [x] **Security: IP Hashing** - GDPR-compliant IP hashing in referral tracking
- [x] **Documentation** - Complete package documentation created

*See `changelog/2026/jan/` for completed features.*
