import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'HexaGen PHP',
  description: 'High-performance PHP 8.3+ framework optimized for FrankenPHP',
  lang: 'en-US',

  ignoreDeadLinks: [
    /^http:\/\/localhost/,
    /^http:\/\/127\.0\.0\.1/,
  ],

  head: [
    ['link', { rel: 'icon', href: '/favicon.ico' }],
  ],

  themeConfig: {
    logo: '/logo.svg',
    siteTitle: 'HexaGen PHP',

    nav: [
      { text: 'Guide', link: '/guide/installation' },
      { text: 'ORM', link: '/orm/models' },
      { text: 'Features', link: '/features/routing' },
      { text: 'CLI', link: '/cli/' },
      {
        text: 'v2.0.1',
        items: [
          { text: 'Changelog', link: 'https://github.com/rbaezc/hexagenphp_framework/releases' },
          { text: 'Packagist', link: 'https://packagist.org/packages/rbaezc/hexagenphp-framework' },
        ],
      },
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Architecture', link: '/guide/architecture' },
            { text: 'Configuration', link: '/guide/configuration' },
            { text: 'Worker Mode (FrankenPHP)', link: '/guide/worker-mode' },
          ],
        },
        {
          text: 'Security',
          items: [
            { text: 'Security Overview', link: '/guide/security' },
          ],
        },
      ],
      '/orm/': [
        {
          text: 'HexaORM',
          items: [
            { text: 'Models', link: '/orm/models' },
            { text: 'Query Builder', link: '/orm/query-builder' },
            { text: 'Relations', link: '/orm/relations' },
            { text: 'Migrations', link: '/orm/migrations' },
            { text: 'Seeders & Factories', link: '/orm/seeders' },
          ],
        },
      ],
      '/features/': [
        {
          text: 'HTTP',
          items: [
            { text: 'Routing', link: '/features/routing' },
            { text: 'Middleware', link: '/features/middleware' },
            { text: 'Validation', link: '/features/validation' },
            { text: 'HTTP Client', link: '/features/http-client' },
          ],
        },
        {
          text: 'Auth',
          items: [
            { text: 'Authentication', link: '/features/auth' },
            { text: 'Authorization', link: '/features/authorization' },
          ],
        },
        {
          text: 'Services',
          items: [
            { text: 'Mail', link: '/features/mail' },
            { text: 'Queue', link: '/features/queue' },
            { text: 'Cache', link: '/features/cache' },
            { text: 'Storage', link: '/features/storage' },
            { text: 'Events', link: '/features/events' },
            { text: 'Notifications', link: '/features/notifications' },
            { text: 'Broadcasting', link: '/features/broadcasting' },
          ],
        },
        {
          text: 'Views & i18n',
          items: [
            { text: 'Templates (Twig)', link: '/features/templates' },
            { text: 'Internationalization', link: '/features/i18n' },
            { text: 'Live Slices', link: '/features/live-slices' },
          ],
        },
        {
          text: 'Developer Tools',
          items: [
            { text: 'Testing', link: '/features/testing' },
            { text: 'Scheduler', link: '/features/scheduler' },
            { text: 'OpenAPI', link: '/features/openapi' },
          ],
        },
      ],
      '/cli/': [
        {
          text: 'CLI Reference',
          items: [
            { text: 'All Commands', link: '/cli/' },
          ],
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/rbaezc/hexagenphp_framework' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2024-present Raul Baez',
    },

    search: {
      provider: 'local',
    },

    editLink: {
      pattern: 'https://github.com/rbaezc/hexagenphp_framework/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },
  },
})
