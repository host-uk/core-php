# Core PHP Framework - TODO

## Code Cleanup

- [ ] **ApiExplorer** - Update biolinks endpoint examples

---

## Completed (January 2026)

### Security Fixes

- [x] **MCP: Database Connection Fallback** - Fixed to throw exception instead of silently falling back to default connection
  - See: `packages/core-mcp/changelog/2026/jan/security.md`

- [x] **MCP: SQL Validator Regex** - Strengthened WHERE clause patterns to prevent SQL injection vectors
  - See: `packages/core-mcp/changelog/2026/jan/security.md`

### Features

- [x] **MCP: EXPLAIN Plan** - Added query optimization analysis with human-readable performance insights
  - See: `packages/core-mcp/changelog/2026/jan/features.md`

- [x] **CDN: Integration Tests** - Comprehensive test suite for CDN operations and asset pipeline
  - See: `packages/core-php/changelog/2026/jan/features.md`

### Documentation & Code Quality

- [x] **API docs** - Genericized vendor-specific content (removed Host UK branding, lt.hn references)
  - See: `packages/core-api/changelog/2026/jan/features.md`

- [x] **Admin: Route Audit** - Verified admin routes use Livewire modals instead of traditional controllers; #[Action] attributes not applicable

- [x] **ServicesAdmin** - Reviewed stubbed bio service methods; intentionally stubbed pending module extraction (documented with TODO comments)

---

## Package Changelogs

For complete feature lists and implementation details:
- `packages/core-php/changelog/2026/jan/features.md`
- `packages/core-admin/changelog/2026/jan/features.md`
- `packages/core-api/changelog/2026/jan/features.md`
- `packages/core-mcp/changelog/2026/jan/features.md`
- `packages/core-mcp/changelog/2026/jan/security.md` ⚠️ Security fixes
