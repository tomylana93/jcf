<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="public/logo-wordmark-dark.svg">
    <source media="(prefers-color-scheme: light)" srcset="public/logo-wordmark.svg">
    <img src="public/logo-wordmark.svg" alt="Jalara" width="280">
  </picture>
</p>

<h1 align="center">Jalara Core Foundation (JCF)</h1>

<p align="center">
  A modular foundation for single-tenant Laravel applications on PostgreSQL.
</p>

<p align="center">
  <a href="https://github.com/tomylana93/jcf/releases/latest"><img src="https://img.shields.io/github/v/release/tomylana93/jcf?label=release" alt="Latest release"></a>
  <a href="https://packagist.org/packages/tomylana93/jcf"><img src="https://img.shields.io/packagist/v/tomylana93/jcf?label=packagist" alt="Packagist version"></a>
  <a href="https://github.com/tomylana93/jcf/actions/workflows/tests.yml"><img src="https://github.com/tomylana93/jcf/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <img src="https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white" alt="PHP 8.5">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PostgreSQL-only-4169E1?logo=postgresql&logoColor=white" alt="PostgreSQL">
  <a href="https://packagist.org/packages/tomylana93/jcf"><img src="https://img.shields.io/packagist/l/tomylana93/jcf" alt="License"></a>
</p>

---

JCF is a **source scaffold**: once a project is created, the application owns its code, repository, database, secrets, branding, and release cycle. `composer update` updates dependencies only. It never pulls in JCF application code.

## Features

- **Core:** authentication and accounts, users and permissions, application shell, locale and timezone, settings, audit, and notifications.
- **Optional modules:** chat, documentation, and backup. All are off by default; enable them with `FEATURE_CHAT`, `FEATURE_DOCUMENTATION`, and `FEATURE_BACKUP`, then run migrations before sending module traffic.
- **Stack:** Inertia v3 + Vue with SSR, Wayfinder, Fortify, Reverb, and Spatie permission, settings, media library, and backup.

## Requirements

| PHP | Laravel | PostgreSQL | Node | pnpm | Composer |
| --- | --- | --- | --- | --- | --- |
| 8.5 | 13 | one database per app, plus a separate testing database | 26 | 12.16.1 | ≥ 2.10.3 |

PostgreSQL is the only supported database. SQLite and MySQL are not supported.

## Quick start

```shell
composer create-project tomylana93/jcf app-name
cd app-name
```

Then:

1. Edit `.env`: set `APP_URL`, a unique `APP_INSTANCE`, and database credentials.
2. Create **separate** development and testing databases (`DB_DATABASE`, `DB_TEST_DATABASE`). If `DB_TEST_DATABASE` is blank, it defaults to `<DB_DATABASE>_testing`.
3. Set `SUPERADMIN_NAME`, `SUPERADMIN_EMAIL`, and `SUPERADMIN_PASSWORD`.
4. Run `composer setup`. It installs dependencies, migrates, initializes permissions and the superadmin, and builds assets.

> [!IMPORTANT]
> `composer setup` and `init:app` work only in local and testing environments. Production provisioning needs its own deployment process.

> [!NOTE]
> `laravel new --using=tomylana93/jcf` is **not supported** directly. To use Laravel Installer 5.32.0, go through the adapter from a JCF checkout:
> `php bootstrap/install-project.php ../app-name --using=tomylana93/jcf:1.0.1 --git`

## Versioning

- `version.txt` holds the repository version. A new application starts at `0.1.0` and is released by its own Release Please workflow.
- `.foundation-version` (for example `jcf@1.0.1`) records which JCF release the application is based on.
- **Upgrading:** in a JCF checkout, run `git diff vOLD vNEW` and apply the relevant changes to your application in one focused PR. Never copy `version.txt`, `CHANGELOG.md`, or the Release Please files, and never import foundation history. Bump `.foundation-version` only after the whole release has been adopted. Back up the database before deploying migrations.

## Development

```shell
composer test      # full gate: audits, builds, lint, types, coverage, browser tests
composer test:ci   # faster gate used by CI
```

To publish releases from a derived repository, configure a GitHub App (`RELEASE_APP_ID`, `RELEASE_APP_PRIVATE_KEY`) and set `RELEASE_ENABLED=true`.

## License

[MIT](https://opensource.org/licenses/MIT)
