import { defineConfig } from 'vitepress'
import { fileURLToPath } from 'url'
import path from 'path'
import { getPackagesSidebar, getPackagesNav } from './sidebar.js'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const docsDir = path.resolve(__dirname, '..')

// Auto-discover packages
const packagesSidebar = getPackagesSidebar(docsDir)
const packagesNav = getPackagesNav(docsDir)

// Separate PHP/Go from ecosystem packages for nav
const phpNav = packagesNav.find(p => p.link === '/packages/php/')
const goNav = packagesNav.find(p => p.link === '/packages/go/')
const ecosystemNav = packagesNav.filter(p =>
  p.link !== '/packages/php/' && p.link !== '/packages/go/'
)

export default defineConfig({
  title: 'Host UK',
  description: 'Native application frameworks for PHP and Go',
  base: '/',

  ignoreDeadLinks: [
    // Ignore localhost links
    /^https?:\/\/localhost/,
    // Old paths during migration
    /\/packages\/core/,
    /\/core\//,
    /\/architecture\//,
    /\/patterns-guide\//,
    // Security pages not yet created
    /\/security\/(api-authentication|rate-limiting|workspace-isolation|sql-validation|gdpr)/,
    // Package pages not yet created
    /\/packages\/admin\/(tables|security|hlcrf|activity)/,
    /\/packages\/api\/(openapi|analytics|alerts|logging)/,
    /\/packages\/mcp\/commerce/,
    /\/packages\/php\/(services|seeders|security|email-shield|action-gate|i18n)/,
    // Other pages not yet created
    /\/testing\//,
    /\/contributing/,
    /\/guide\/testing/,
    // Go docs - relative paths
    /\.\.\/configuration/,
    /\.\.\/examples/,
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      {
        text: 'PHP',
        link: '/packages/php/',
        activeMatch: '/packages/php/'
      },
      {
        text: 'Go',
        link: '/packages/go/',
        activeMatch: '/packages/go/'
      },
      {
        text: 'Packages',
        items: ecosystemNav
      },
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

      // Packages index
      '/packages/': [
        {
          text: 'Packages',
          items: packagesNav.map(p => ({ text: p.text, link: p.link }))
        }
      ],

      // Auto-discovered package sidebars (php, go, admin, api, mcp, etc.)
      ...packagesSidebar,

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
      { icon: 'github', link: 'https://github.com/host-uk' }
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
