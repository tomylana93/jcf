# JCF

JCF is a foundation for single-tenant Laravel applications using PostgreSQL. It follows a **source scaffold** distribution model: once a project is created, the application owns its code, repository, database, secrets, branding, and release cycle. `composer update` updates dependencies; it does not automatically adopt changes to JCF's application code.

Status: starter kit preparation, **no stable release yet**. The `tomylana93/jcf` package was not found on Packagist during the check on October 26, 2026. Public installation through Laravel Installer has not passed acceptance testing. The current project creation hooks should not be treated as a validated, safe installation path for derived applications.

## Support contract

| Component | Target | Baseline evidence as of October 26, 2026 |
| --- | --- | --- |
| PHP | 8.5 (`^8.5`) | Host CLI 8.5.12; the Lerd site selects 8.5. |
| Laravel | 13 | Installed dependency and lockfile version `v13.38.0`. |
| PostgreSQL | One database per application; a separate testing database | Local connection verified on 16.15; CI uses the `postgres:18` image and has a successful historical run. Clean installation has not been tested on either version. |
| Node | 26 | Host 26.12.1; `.node-version` and CI select 26. |
| pnpm | 12.10.1 | Host, `packageManager`, and CI agree; use the frozen lockfile. |
| Composer | Test candidate 2.10.3 | CLI and manifest validation passed; the minimum supported installation version has not been established through acceptance testing. |
| Laravel Installer | Test candidate 5.32.0 | `--version` and `new --help` passed; `--using`, `--database`, `--pnpm`, and `--no-node` are available. The minimum version for installing JCF has not been verified. |

These targets do not imply that every patch or minor version has been tested. PostgreSQL is the only supported database; SQLite and MySQL are outside the application support contract. Lerd is an optional development environment. Once the installation gates are met, derived applications must be installable without the JCF developer machine's registry or domains.

## Core and modules

Core includes authentication and accounts, users and permissions, the application shell, locale and timezone, basic settings, audit, notifications, and existing generic infrastructure. Website/CMS, chat, documentation, and backup are optional modules. Website, chat, and documentation are enabled by default through hard flags; backup is opt-in.

Hard flags control module availability and migration registration. A module disabled through its hard flag must not access its settings or schema. Soft toggles control usage without deleting schema or data. When enabling a hard flag later, run migrations before allowing module traffic or workers. Core realtime notifications remain separate from the chat module. Derived applications own their business workflows, domain permissions, domain navigation, integrations, deployment, and instance identity. Jalara branding may remain as a fallback.

## Installation: current status and limitations

After publication and acceptance testing, the target command is `laravel new app-name --using=tomylana93/jcf`; the fixed-version path is `composer create-project tomylana93/jcf app-name 1.0.0`. Neither is a **validated public installation instruction yet**. Community starter kit support through Packagist is described in the [Laravel 13 documentation](https://laravel.com/docs/13.x/starter-kits#community-maintained-starter-kits).

The current `post-create-project-cmd` hook still creates a SQLite file and runs `migrate --graceful`; `.env.example` still uses the database name `jcf`. Fixing these belongs to phase 1. Configure the application identity, separate PostgreSQL development and testing databases, and application-specific secrets before running migrations. Do not point a new application at the foundation's database.

`init:app` may only run in local or testing environments. It preserves an existing APP_KEY, then runs migrations, permission initialization, superadmin initialization, and storage linking, checking for failures. It is not a production installer. Frontend setup uses `pnpm install --frozen-lockfile` and `pnpm run build`. For Lerd, discover the site, select the application's database, set up the environment, then run framework setup. Do not copy the JCF instance's domain.

## Versioning and adoption contract

The following contract is established before automation is implemented:

- `version.txt` stores the repository version: JCF's first stable release target is `1.0.0`, and the initial development baseline for a derived application is `0.1.0`. Release Please uses the `simple` strategy, one root package, `vX.Y.Z` tags, and a manifest and changelog owned by each repository.
- Only derived applications carry `.foundation-version`: one line containing `jcf@X.Y.Z`, taken from the source version before resetting the application version. JCF does not need to mark itself.
- Foundation and application versions are independent. Use patch releases for compatible fixes, minor releases for compatible capabilities, and major releases for breaking contract or support changes. Assess dependency updates by their compatibility impact.
- Derived application bootstrap must be idempotent, start its own history and changelog, and stay outside ordinary dependency installation or updates. Application releases do not change the foundation origin marker.
- Adopt foundation changes through focused PRs and testing. Advance the foundation baseline only after full adoption has been validated; record partial patches in PRs and the changelog. Changes to released migrations must use new, forward-only migrations.

Version files, Release Please, derived application finalization, archive policy, and public acceptance testing have not been implemented in phase 0. No foundation registry or discovery service, or additional frontend unit-test tooling, is required.

## Validation and releases

The existing gate is `composer test`: audits, feature build acceptance, SSR build, linting, type checks, type coverage, backend coverage, and browser tests. CI runs it in one Linux job for non-draft PRs targeting `main`, with manual dispatch available. Historical hosted results do not prove that this documentation commit or starter kit installation has passed the full suite.

Releases require GitHub App credentials that can trigger release PR checks, required checks on the latest head, and review. A tag or release does not authorize deployment. Baseline details, external access checks, and outstanding gates are recorded in the [implementation roadmap](REUSABLE_STARTERKIT_ROADMAP.md).
