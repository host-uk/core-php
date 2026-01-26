import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Core PHP Framework',
  description: 'Modular monolith framework for Laravel',
  base: '/core-php/',

  ignoreDeadLinks: [
    // Ignore localhost links
    /^https?:\/\/localhost/,
    // Ignore internal doc links that haven't been created yet
    /\/packages\/admin\/(tables|security)/,
    /\/packages\/core\/(services|seeders|security|email-shield|action-gate|i18n)/,
    /\/architecture\/(custom-events|performance)/,
    /\/patterns-guide\/(multi-tenancy|workspace-caching|search|admin-menus|services|repositories|responsive-design|factories|webhooks)/,
    /\/testing\//,
    /\/contributing/,
    /\/guide\/testing/,
    // Ignore changelog relative paths
    /\.\/packages\//,
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'Patterns', link: '/patterns-guide/actions' },
      {
        text: 'Packages',
        items: [
          { text: 'Core', link: '/packages/core/' },
          { text: 'Admin', link: '/packages/admin/' },
          { text: 'API', link: '/packages/api/' },
          { text: 'MCP', link: '/packages/mcp/' }
        ]
      },
      { text: 'API', link: '/api/authentication' },
      { text: 'Security', link: '/security/overview' },
      {
        text: 'v1.0',
        items: [
          { text: 'Changelog', link: '/changelog' },
          { text: 'Contributing', link: '/contributing' }
        ]
      }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'Getting Started', link: '/guide/getting-started' },
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Configuration', link: '/guide/configuration' },
            { text: 'Quick Start', link: '/guide/quick-start' },
            { text: 'Testing', link: '/guide/testing' }
          ]
        }
      ],

      '/architecture/': [
        {
          text: 'Architecture',
          items: [
            { text: 'Lifecycle Events', link: '/architecture/lifecycle-events' },
            { text: 'Module System', link: '/architecture/module-system' },
            { text: 'Lazy Loading', link: '/architecture/lazy-loading' },
            { text: 'Multi-Tenancy', link: '/architecture/multi-tenancy' },
            { text: 'Custom Events', link: '/architecture/custom-events' },
            { text: 'Performance', link: '/architecture/performance' }
          ]
        }
      ],

      '/patterns-guide/': [
        {
          text: 'Patterns',
          items: [
            { text: 'Actions', link: '/patterns-guide/actions' },
            { text: 'Activity Logging', link: '/patterns-guide/activity-logging' },
            { text: 'Services', link: '/patterns-guide/services' },
            { text: 'Repositories', link: '/patterns-guide/repositories' },
            { text: 'Seeders', link: '/patterns-guide/seeders' },
            { text: 'HLCRF Layouts', link: '/patterns-guide/hlcrf' }
          ]
        }
      ],

      '/packages/core/': [
        {
          text: 'Core Package',
          items: [
            { text: 'Overview', link: '/packages/core/' },
            { text: 'Module System', link: '/packages/core/modules' },
            { text: 'Multi-Tenancy', link: '/packages/core/tenancy' },
            { text: 'CDN Integration', link: '/packages/core/cdn' },
            { text: 'Actions', link: '/packages/core/actions' },
            { text: 'Lifecycle Events', link: '/packages/core/events' },
            { text: 'Configuration', link: '/packages/core/configuration' },
            { text: 'Activity Logging', link: '/packages/core/activity' },
            { text: 'Media Processing', link: '/packages/core/media' },
            { text: 'Search', link: '/packages/core/search' },
            { text: 'SEO Tools', link: '/packages/core/seo' }
          ]
        }
      ],

      '/packages/admin/': [
        {
          text: 'Admin Package',
          items: [
            { text: 'Overview', link: '/packages/admin/' },
            { text: 'Form Components', link: '/packages/admin/forms' },
            { text: 'Livewire Modals', link: '/packages/admin/modals' },
            { text: 'Global Search', link: '/packages/admin/search' },
            { text: 'Admin Menus', link: '/packages/admin/menus' },
            { text: 'Authorization', link: '/packages/admin/authorization' },
            { text: 'UI Components', link: '/packages/admin/components' }
          ]
        }
      ],

      '/packages/api/': [
        {
          text: 'API Package',
          items: [
            { text: 'Overview', link: '/packages/api/' },
            { text: 'Authentication', link: '/packages/api/authentication' },
            { text: 'Webhooks', link: '/packages/api/webhooks' },
            { text: 'Rate Limiting', link: '/packages/api/rate-limiting' },
            { text: 'Scopes', link: '/packages/api/scopes' },
            { text: 'Documentation', link: '/packages/api/documentation' }
          ]
        }
      ],

      '/packages/mcp/': [
        {
          text: 'MCP Package',
          items: [
            { text: 'Overview', link: '/packages/mcp/' },
            { text: 'Query Database', link: '/packages/mcp/query-database' },
            { text: 'Creating Tools', link: '/packages/mcp/tools' },
            { text: 'Security', link: '/packages/mcp/security' },
            { text: 'Workspace Context', link: '/packages/mcp/workspace' },
            { text: 'Analytics', link: '/packages/mcp/analytics' },
            { text: 'Usage Quotas', link: '/packages/mcp/quotas' }
          ]
        }
      ],

      '/security/': [
        {
          text: 'Security',
          items: [
            { text: 'Overview', link: '/security/overview' },
            { text: 'Namespaces & Entitlements', link: '/security/namespaces' },
            { text: 'Changelog', link: '/security/changelog' },
            { text: 'Responsible Disclosure', link: '/security/responsible-disclosure' }
          ]
        }
      ],

      '/api/': [
        {
          text: 'API Reference',
          items: [
            { text: 'Authentication', link: '/api/authentication' },
            { text: 'Endpoints', link: '/api/endpoints' },
            { text: 'Errors', link: '/api/errors' }
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/host-uk/core-php' }
    ],

    footer: {
      message: 'Released under the EUPL-1.2 License.',
      copyright: 'Copyright © 2024-present Host UK'
    },

    search: {
      provider: 'local'
    },

    editLink: {
      pattern: 'https://github.com/host-uk/core-php/edit/main/docs/:path',
      text: 'Edit this page on GitHub'
    }
  }
})
