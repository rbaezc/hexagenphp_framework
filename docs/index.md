---
layout: home

hero:
  name: "HexaGen PHP"
  text: "High-performance PHP framework"
  tagline: Built for FrankenPHP. Vertical Slices. Zero cold start.
  actions:
    - theme: brand
      text: Get Started
      link: /guide/installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/rbaezc/hexagenphp_framework

features:
  - icon: ⚡
    title: FrankenPHP Worker Mode
    details: Boot once, serve thousands of requests per process. Zero PHP cold start overhead.

  - icon: 🏗️
    title: Vertical Slice Architecture
    details: Each feature lives in its own slice — controller, model, routes, and services all together.

  - icon: 🛡️
    title: Security First
    details: CSRF, JWT, bcrypt with auto-rehash, SQL injection protection, security headers out of the box.

  - icon: 🗄️
    title: HexaORM
    details: Active Record ORM on native PDO. QueryBuilder, eager loading (no N+1), relations, migrations with Schema Builder.

  - icon: 🧰
    title: Full-Stack Ready
    details: Mail, Queue, Cache, Storage (Local/S3), Events, Notifications, Broadcasting, i18n, OpenAPI generation.

  - icon: 🧪
    title: Testing Built In
    details: HttpTestClient, TestResponse assertions, ModelFactory with Faker. No extra setup required.
---
