# JCF

JCF is a foundation for single-tenant Laravel applications using PostgreSQL. It follows a **source scaffold** distribution model: once a project is created, the application owns its code, repository, database, secrets, branding, and release cycle. `composer update` updates dependencies; it does not automatically adopt changes to JCF's application code.

Status: **first stable release `v1.0.0`**, published November 2, 2026 as a [GitHub Release](https://github.com/tomylana93/jcf/releases/tag/v1.0.0) and on Packagist as `tomylana93/jcf`. The public 1.0.0 package has passed clean installation through `composer create-project` and through the controlled Laravel Installer 5.32.0 adapter, followed by explicit setup and client/SSR builds on disposable PostgreSQL 18.6 databases. Direct named-directory Installer invocations remain rejected. Composer creation hooks perform preparation without accessing a database.

## Support contract

| Component         | Target                                                    | Baseline evidence as of October 26–November 2, 2026                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| ----------------- | --------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| PHP               | 8.5 (`^8.5`)                                              | Host CLI 8.5.12; the Lerd site selects 8.5.                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| Laravel           | 13                                                        | Installed dependency and lockfile version `v13.39.0`.                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| PostgreSQL        | One database per application; a separate testing database | Local connection verified on 16.15; CI uses the `postgres:18` image and has a successful historical run. Explicit initialization and rerun have passed on a disposable local PostgreSQL 16.15 database; candidate archives have also passed clean Composer installation and explicit setup/build on disposable PostgreSQL 18.6 databases, with default and core-only profiles. The public 1.0.0 package has passed the same setup on PostgreSQL 18.6. This does not establish support for every PostgreSQL version. |
| Node              | 26                                                        | Host 26.12.1; `.node-version` and CI select 26. Frozen install, client/SSR build, frontend checks and Vue/TypeScript checks passed on the host runtime. The complete candidate Composer test gate passed on PHP 8.5.12 with nested Node 26.12.1 and pnpm 12.16.1 through a disposable Alpine runtime. Public 1.0.0 package setup and client/SSR builds also passed with nested Node 26.12.1.                                                                                                                        |
| pnpm              | 12.16.1                                                   | `packageManager`, its lockfile and CI pin 12.16.1 (registry latest checked October 29, 2026). Fresh Installer-adapter installs, default/core-only setup and the complete candidate Composer test gate passed with 12.16.1.                                                                                                                                                                                                                                                                                          |
| Composer          | 2.10.3 minimum                                            | Clean candidate `create-project --prefer-dist` and explicit setup passed on 2.10.3, the minimum tested and supported version; earlier versions are not claimed.                                                                                                                                                                                                                                                                                                                                                     |
| Laravel Installer | 5.32.0 through the controlled adapter                     | Fresh default/core-only installations passed through `bootstrap/install-project.php` using Installer 5.32.0. Direct named-directory invocations remain rejected before database, Git, or frontend side effects; other Installer versions are not supported.                                                                                                                                                                                                                                                         |

These targets do not imply that every patch or minor version has been tested. PostgreSQL is the only supported database; SQLite and MySQL are outside the application support contract. Lerd is an optional development environment. Once the installation gates are met, derived applications must be installable without the JCF developer machine's registry or domains.

## Core and modules

Core includes authentication and accounts, users and permissions, the application shell, locale and timezone, basic settings, audit, notifications, and existing generic infrastructure. Chat, documentation, and backup are optional modules. Chat and documentation are enabled by default through hard flags; backup is opt-in. Website/CMS was removed before the first stable release.

Hard flags control module availability and migration registration. A module disabled through its hard flag must not access its settings or schema. Soft toggles control usage without deleting schema or data. When enabling a hard flag later, run migrations before allowing module traffic or workers. Core realtime notifications remain separate from the chat module. Derived applications own their business workflows, domain permissions, domain navigation, integrations, deployment, and instance identity. Jalara branding may remain as a fallback.

## Installation: current status and limitations

The validated public installation paths for 1.0.0 are:

```shell
composer create-project tomylana93/jcf app-name 1.0.0
```

or, from a JCF source checkout prepared as described in the Installer audit below:

```shell
php bootstrap/install-project.php ../app-name --using=tomylana93/jcf:1.0.0 --git
```

Both paths produce `.foundation-version` = `jcf@1.0.0`, reset the application version to `0.1.0`, and keep the released lockfiles unchanged. Then configure PostgreSQL and administrator credentials and run `composer setup`. Direct `laravel new app-name --using=tomylana93/jcf` is **not supported**; Installer 5.32.0 is supported only through the adapter. Community starter kit support through Packagist is described in the [Laravel 13 documentation](https://laravel.com/docs/13.x/starter-kits#community-maintained-starter-kits).

The Composer creation hook runs `init:prepare`, then database-free application release finalization. It generates an application key when missing, preserves an existing key, and prints the remaining setup steps. It does not create a SQLite file, run migrations, or provision accounts. Ordinary dependency installation and updates do not run this creation hook.

For a checkout or Composer-created application, complete setup explicitly:

1. Provide `.env` from `.env.example` if it does not exist. Set `APP_URL`, a unique `APP_INSTANCE`, database connection settings, and application-specific secrets. `APP_NAME` controls display branding and may retain the Jalara fallback.
2. Create separate PostgreSQL development and testing databases. Set `DB_DATABASE` and `DB_TEST_DATABASE`; when the latter is blank, it defaults to the effective development database name plus `_testing`. No database name is supplied by the example environment. Do not use the JCF foundation database for a derived application.
3. Configure `SUPERADMIN_NAME`, `SUPERADMIN_EMAIL`, and `SUPERADMIN_PASSWORD` for local initialization. No fallback administrator email or password is provided.
4. Run `composer setup` in local/testing. It installs dependencies, performs preparation, checks the PostgreSQL target, runs `init:app`, installs frontend dependencies with `pnpm install --frozen-lockfile`, and builds assets. Required failures stop setup; they are not hidden with `--graceful`.

`init:app` checks database configuration and connectivity before changing the key or running setup commands. It preserves an existing key, then runs migrations, permission initialization, superadmin initialization, and storage linking. Rerunning preserves the existing administrator and data. Cached configuration must be cleared before preparation or initialization. This command is restricted to local/testing and is not a production installer; production provisioning requires a separate, explicit deployment process.

Tests use `DB_TEST_DATABASE` rather than a hardcoded JCF name. Testing ignores the development `DB_URL` as a connection target and rejects a testing database name that matches the effective development name, including a name supplied through `DB_URL`. Configure `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD` for the test server even when development uses a URL. CI declares separate development and test names and migrates only its disposable testing target.

Backup names, session cookies, cache prefixes, and Redis prefixes default to `APP_INSTANCE` or the checkout directory name. Existing installations can retain their namespaces with `BACKUP_NAME`, `SESSION_COOKIE`, `CACHE_PREFIX`, and `REDIS_PREFIX`; changing cookie names requires users to sign in again. Configure a real mail sender and unique Reverb credentials before enabling those services. Backup remains opt-in.

The distribution archive excludes the foundation instance’s `.lerd.yaml`. For Lerd, discover the site, select its own database, set up the environment, then run framework setup. Configure a checkout-specific domain in `.lerd.local.yaml` or through Lerd. Do not copy the foundation instance's domain or secrets.

### Laravel Installer audit

Installer 5.32.0 runs starter kit hooks before database rewriting. For a community starter kit without an explicit database option, it selects SQLite; an explicit database option can trigger migrations before application-specific configuration is ready. Its pnpm command is `pnpm install --ignore-scripts`, without a frozen lockfile. It may also initialize Git or push a repository when requested through `--git` or `--github`.

The supported integration uses `bootstrap/install-project.php` from a JCF checkout or source archive, after `composer install` with development dependencies in that source checkout. The adapter uses its pinned **Laravel Installer 5.32.0** package, without changing the globally installed CLI:

```shell
php bootstrap/install-project.php ../new-app --git
```

Without `--using`, the adapter requests `tomylana93/jcf` without a version constraint; only the pinned 1.0.0 path has passed acceptance. `--using=vendor/package:version` pins a release, for example `tomylana93/jcf:1.0.0`, or a candidate served from a local Composer package repository. The adapter accepts only the target directory, `--using`, and optional `--git`; it never pushes a repository.

The adapter creates a new empty directory and uses a compatibility adapter for Installer 5.32.0 with the arguments `laravel new . --no-node --no-boost --no-interaction`. The stock 5.32.0 command incorrectly rejects `.` as an existing destination. The adapter permits only the fresh empty current directory created by the launcher. Current-directory installation skips the Installer's database rewriting/migration branch. Frontend installation is deferred so `composer setup` can use the pinned pnpm version and `--frozen-lockfile`. A private temporary context authorizes the creation hook only for that exact directory and invocation; the hook performs database-free preparation. The adapter then verifies that Installer preserved `.env`, dependency manifests/lockfiles and release identity. Preparation/Installer failures propagate as nonzero status; partial targets remain available for inspection and existing directories are never overwritten.

After scaffolding, configure the new application's PostgreSQL development/testing databases, identity and administrator credentials, then run `composer setup` inside it. This is a staged local/testing installation, not a production provisioner. Direct `laravel new new-app --using=...`, including attempts with `--database=pgsql`, `--pnpm` or `--no-node`, remains rejected without the adapter context. Other Installer versions require separate acceptance before extending the supported version list.

## Versioning and adoption contract

The distribution implements the following version contract:

- `version.txt` stores the repository version: JCF's first stable release is `1.0.0`, and the initial development baseline for a derived application is `0.1.0`. Release Please uses the `simple` strategy, one root package, `vX.Y.Z` tags, and a manifest and changelog owned by each repository.
- Only derived applications carry `.foundation-version`: one line containing `jcf@X.Y.Z`, taken from the source version before resetting the application version. JCF does not need to mark itself.
- Foundation and application versions are independent. Use patch releases for compatible fixes, minor releases for compatible capabilities, and major releases for breaking contract or support changes. Assess dependency updates by their compatibility impact.
- Derived application bootstrap must be idempotent, start its own history and changelog, and stay outside ordinary dependency installation or updates. Application releases do not change the foundation origin marker.
- Adopt foundation changes through focused PRs and testing. Advance the foundation baseline only after full adoption has been validated; record partial patches in PRs and the changelog. Changes to released migrations must use new, forward-only migrations.

The source `version.txt` contains the foundation release version, currently `1.0.0`. During Composer project creation, `bootstrap/finalize-project.php` reads that source version, writes `.foundation-version`, resets `version.txt` and the root Release Please manifest to `0.1.0`, and starts an application changelog. Rerunning with a valid origin marker preserves all application release files. A missing/invalid source version, an invalid origin marker, or an existing Git repository without provenance fails explicitly. Finalize an archive before `git init`; do not run the creation event on the foundation checkout. Ordinary `composer install`, `composer update`, and `composer setup` do not finalize release identity.

For a source archive installed with `--no-scripts`, run `php bootstrap/finalize-project.php` explicitly before initializing Git. It needs only PHP and local files, with no registry, discovery service, Git executable, or connection to JCF. Finalization replaces the foundation release config with a generic root package and keeps the generic, repository-scoped workflow. It removes the foundation bootstrap SHA, initial release override, component and other inherited config. The workflow must be present in the archive. No foundation owner/repository or credential is embedded. The application manifest starts at `0.1.0`; with no existing application tag, the first `fix:` release is `0.1.1`, the first `feat:` release is `0.2.0`, and a breaking change is `1.0.0`. The first public release is therefore not automatically `0.1.0`. Only the new application Git history is scanned; initialize a new repository after finalization, without importing foundation commits or tags.

Archives retain application code, regression tests, project rules, README, lockfiles, and CI. Root roadmap files and machine-specific Lerd/editor/agent state are excluded. The public 1.0.0 archive passed this content check. No frontend unit-test tooling is required.

## Validation and releases

The existing gate is `composer test`: audits, feature build acceptance, SSR build, linting, type checks, type coverage, backend coverage, and browser tests. CI runs the faster `composer test:ci` gate (coverage and browser smoke tests) in one Linux job for non-draft PRs targeting `main`; manual dispatch can run the full suite. `composer test` and `composer test:feature-build` remain pre-release gates. Historical hosted results do not prove that this documentation commit or starter kit installation has passed the full suite.

The realtime browser lifecycle tests replace WebSocket with a test implementation. Their frontend build needs the same non-secret fixture values supplied by CI:

```sh
VITE_BROADCAST_CONNECTION=reverb \
VITE_REVERB_APP_KEY=browser-key \
VITE_REVERB_HOST=127.0.0.1 \
VITE_REVERB_PORT=8080 \
VITE_REVERB_SCHEME=http \
composer test
```

For containerized Composer, pass these values into the container process environment. Keep the application's own Reverb configuration separate from this test fixture. The complete candidate gate has passed with PHP 8.5.12, nested Node 26.12.1 and pnpm 12.16.1 using a disposable Alpine runtime. The PHP container's default Node remains unchanged; nested processes must explicitly select the declared runtime. This local gate was not rerun on the public package installation.

To activate releases in each derived repository:

1. Initialize a new Git repository after finalization, set its own remote, and use `main`. Push application code through its own review process. Keep `.foundation-version` unchanged during application releases.
2. Install a GitHub App on that repository with Contents, Pull requests and Issues **read/write** permissions. Set repository variable `RELEASE_APP_ID` and secret `RELEASE_APP_PRIVATE_KEY` from credentials authorized for that application. Foundation repository secrets are not inherited. The workflow mints a token scoped to its current repository; it never falls back to `GITHUB_TOKEN`.
3. Configure branch protection or a ruleset for `main` requiring the `CI` check and review on the latest release PR head, with stale approvals dismissed and no App bypass/auto-merge. Confirm Actions can run and that an App-created PR triggers CI. The workflow orchestrates releases on `main` pushes or manual dispatch, and does not run the full suite a second time.
4. Validate the release workflow in an application sandbox, then set repository variable `RELEASE_ENABLED=true` when release publication is authorized. It is disabled while this variable is unset. Review the generated version/changelog/manifest diff, require successful checks, and merge explicitly. Verify the resulting `vX.Y.Z` tag points at the release merge commit and the GitHub Release is published. Manual dispatch on `main` is available for recovery; dispatch on another ref is ignored.

Releases require GitHub App credentials that can trigger release PR checks, required checks on the latest head, and review. Local config simulation does not prove hosted CI or tag/release publication. A tag or release does not authorize deployment. Consult the foundation repository for its internal implementation roadmap and outstanding publication gates; internal roadmap files are not distributed.
