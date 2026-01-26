# Changelog

All notable changes to Core PHP Framework will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Comprehensive documentation for all core packages
- Usage alert system for workspace quota monitoring
- Tool analytics and performance tracking for MCP

### Changed
- Improved workspace context validation
- Enhanced security headers configuration

## [1.0.0] - 2026-01-26

Initial public release of Core PHP Framework.

### Added

#### Core Package
- Event-driven module system with lazy loading
- Multi-tenancy with Workspaces and Namespaces
- CDN integration (BunnyCDN, FluxCDN support)
- Actions pattern for business logic
- Configuration management with profiles and versioning
- Activity logging with GDPR compliance
- Media processing with image optimization
- Unified search with analytics
- SEO tools (metadata, sitemaps, structured data)
- Security headers middleware
- Email validation with disposable domain detection
- Privacy helpers (IP hashing, data anonymization)

#### Admin Package
- HLCRF layout system (Hierarchical Layout Component Rendering Framework)
- Form components with authorization props
- Full-page Livewire modals with file uploads
- Global search with providers and analytics
- Admin menu registry with badges and authorization
- UI components (cards, stats, tables, badges, alerts)
- Authorization integration with Gates and Policies

#### API Package
- RESTful API with OpenAPI documentation
- API key management with bcrypt hashing
- Scope-based permissions system
- Webhook delivery with HMAC signatures
- Rate limiting with tier-based quotas
- Automatic retry logic with exponential backoff
- OpenAPI 3.0 spec generation
- Multiple documentation viewers (Swagger, Scalar, ReDoc)

#### MCP Package
- Query Database tool with SQL validation
- Workspace context isolation
- Tool analytics and usage tracking
- Tier-based usage quotas
- SQL injection prevention
- Workspace boundary enforcement
- Performance metrics (P95, P99 latency)
- Error tracking and alerting

#### Multi-Tenancy
- Workspace isolation with automatic scoping
- Namespace support for agencies/white-label
- Workspace invitations system
- Entitlements and feature gating
- Usage tracking per workspace
- Member management

### Security

#### Initial Security Measures
- SQL injection prevention in MCP tools
- Workspace context validation
- API key hashing with bcrypt
- Webhook signature verification (HMAC-SHA256)
- IP address hashing for GDPR
- Security headers (CSP, HSTS, X-Frame-Options)
- Rate limiting per workspace tier
- Scope-based API permissions
- Action Gate for route-level authorization

## Version History

### Versioning Scheme

Core PHP Framework follows [Semantic Versioning](https://semver.org/):

- **MAJOR** version for incompatible API changes
- **MINOR** version for backwards-compatible functionality
- **PATCH** version for backwards-compatible bug fixes

### Upgrade Guides

When upgrading between major versions, refer to the upgrade guide:

- [Upgrading to 2.0](#) (coming soon)

### Package Changelogs

Detailed changelogs for individual packages:

- [Core Package](/packages/core-php/changelog/)
- [Admin Package](/packages/core-admin/changelog/)
- [API Package](/packages/core-api/changelog/)
- [MCP Package](/packages/core-mcp/changelog/)

## Release Schedule

- **Major releases:** Annually
- **Minor releases:** Quarterly
- **Patch releases:** As needed for bug fixes and security

## Support Policy

| Version | PHP Version | Laravel Version | Support Until |
|---------|-------------|-----------------|---------------|
| 1.x     | 8.2+        | 11.x            | 2027-01-26    |

### Security Updates

Security updates are provided for:
- Current major version: Full support
- Previous major version: Security fixes only (12 months)

## Notable Changes by Category

### Breaking Changes

None yet! This is the initial release.

### Deprecations

None yet! This is the initial release.

### New Features

See [1.0.0](#100---2026-01-26) release notes above.

### Bug Fixes

This is the initial release, so no bug fixes yet.

## Migration Guides

### From Host Hub Internal

If you're migrating from the internal Host Hub codebase:

1. **Namespace changes:**
   - `App\` → `Core\`, `Mod\`, `Website\`
   - Update imports throughout

2. **Module registration:**
   - Remove manual service provider registration
   - Modules auto-discovered via `Boot.php`

3. **Event names:**
   - `RouteRegistering` → `WebRoutesRegistering`
   - `AdminBooting` → `AdminPanelBooting`

4. **Configuration:**
   - Move config to database with ConfigService
   - Use profiles for environment-specific values

5. **Multi-tenancy:**
   - Add `BelongsToWorkspace` trait to models
   - Update queries to respect workspace scope

## Contributing

See [Contributing Guide](/contributing) for how to contribute to Core PHP Framework.

## License

Core PHP Framework is open-source software licensed under the [EUPL-1.2](https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12).

## Credits

### Core Team

- [Host UK](https://host.uk) - Original development

### Contributors

Thank you to all contributors who have helped shape Core PHP Framework!

See [Contributors](https://github.com/host-uk/core-php/graphs/contributors) on GitHub.

### Acknowledgments

Built with:
- [Laravel](https://laravel.com) - The PHP framework
- [Livewire](https://livewire.laravel.com) - Full-stack framework for Laravel
- [Alpine.js](https://alpinejs.dev) - Lightweight JavaScript framework
- [Tailwind CSS](https://tailwindcss.com) - Utility-first CSS framework

Special thanks to the open-source community!

---

For more information, visit:
- [Documentation](https://host-uk.github.io/core-php/)
- [GitHub Repository](https://github.com/host-uk/core-php)
- [Issue Tracker](https://github.com/host-uk/core-php/issues)
