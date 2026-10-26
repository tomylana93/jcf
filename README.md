# JCF

JCF is a foundation for single-tenant Laravel applications using PostgreSQL. It follows a **source scaffold** distribution model: once a project is created, the application owns its code, repository, database, secrets, branding, and release cycle. `composer update` updates dependencies; it does not automatically adopt changes to JCF's application code.

Status: starter kit preparation, **no stable release yet**. The `tomylana93/jcf` package was not found on Packagist during the check on October 26, 2026. Public installation through Laravel Installer has not passed acceptance testing and is explicitly rejected by the installer hook until its database and package-manager behavior is supported. Composer creation hooks perform preparation without accessing a database; public package installation still awaits acceptance testing.

## Support contract

| Component | Target | Baseline evidence as of October 26, 2026 |
| --- | --- | --- |
| PHP | 8.5 (`^8.5`) | Host CLI 8.5.12; the Lerd site selects 8.5. |
| Laravel | 13 | Installed dependency and lockfile version `v13.38.0`. |
| PostgreSQL | One database per application; a separate testing database | Local connection verified on 16.15; CI uses the `postgres:18` image and has a successful historical run. Explicit initialization and rerun have passed on a disposable local PostgreSQL 16.15 database; clean package installation and PostgreSQL 18 acceptance remain pending. |
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

The JCF installer hook therefore returns a nonzero status before those database, Git, and package-manager steps, with instructions to use Composer and explicit setup. This applies even with `--database=pgsql`, `--pnpm`, or `--no-node`: those flags do not establish a validated installation path. Composer setup remains the implementation path for phase 1. Enabling public `--using` support requires a later, tested integration; it is not claimed by this phase.

## Versioning and adoption contract

The distribution implements the following version contract:

- `version.txt` stores the repository version: JCF's first stable release target is `1.0.0`, and the initial development baseline for a derived application is `0.1.0`. Release Please uses the `simple` strategy, one root package, `vX.Y.Z` tags, and a manifest and changelog owned by each repository.
- Only derived applications carry `.foundation-version`: one line containing `jcf@X.Y.Z`, taken from the source version before resetting the application version. JCF does not need to mark itself.
- Foundation and application versions are independent. Use patch releases for compatible fixes, minor releases for compatible capabilities, and major releases for breaking contract or support changes. Assess dependency updates by their compatibility impact.
- Derived application bootstrap must be idempotent, start its own history and changelog, and stay outside ordinary dependency installation or updates. Application releases do not change the foundation origin marker.
- Adopt foundation changes through focused PRs and testing. Advance the foundation baseline only after full adoption has been validated; record partial patches in PRs and the changelog. Changes to released migrations must use new, forward-only migrations.

The source `version.txt` currently contains the development baseline `0.1.0`; this is not a published stable release. During Composer project creation, `bootstrap/finalize-project.php` reads that source version, writes `.foundation-version`, resets `version.txt` and the root Release Please manifest to `0.1.0`, and starts an application changelog. Rerunning with a valid origin marker preserves all application release files. A missing/invalid source version, an invalid origin marker, or an existing Git repository without provenance fails explicitly. Finalize an archive before `git init`; do not run the creation event on the foundation checkout. Ordinary `composer install`, `composer update`, and `composer setup` do not finalize release identity.

For a source archive installed with `--no-scripts`, run `php bootstrap/finalize-project.php` explicitly before initializing Git. It needs only PHP and local files, with no registry, discovery service, Git executable, or connection to JCF. Finalization replaces the foundation release config with a generic root package and keeps the generic, repository-scoped workflow. It removes the foundation bootstrap SHA, initial release override, component and other inherited config. The workflow must be present in the archive. No foundation owner/repository or credential is embedded. The application manifest starts at `0.1.0`; with no existing application tag, the first `fix:` release is `0.1.1`, the first `feat:` release is `0.2.0`, and a breaking change is `1.0.0`. The first public release is therefore not automatically `0.1.0`. Only the new application Git history is scanned; initialize a new repository after finalization, without importing foundation commits or tags.

Archives retain application code, regression tests, project rules, README, lockfiles, and CI. Root roadmap files and machine-specific Lerd/editor/agent state are excluded. Public package acceptance remains pending. No frontend unit-test tooling is required.

## Validation and releases

The existing gate is `composer test`: audits, feature build acceptance, SSR build, linting, type checks, type coverage, backend coverage, and browser tests. CI runs it in one Linux job for non-draft PRs targeting `main`, with manual dispatch available. Historical hosted results do not prove that this documentation commit or starter kit installation has passed the full suite.

To activate releases in each derived repository:

1. Initialize a new Git repository after finalization, set its own remote, and use `main`. Push application code through its own review process. Keep `.foundation-version` unchanged during application releases.
2. Install a GitHub App on that repository with Contents, Pull requests and Issues **read/write** permissions. Set repository variable `RELEASE_APP_ID` and secret `RELEASE_APP_PRIVATE_KEY` from credentials authorized for that application. Foundation repository secrets are not inherited. The workflow mints a token scoped to its current repository; it never falls back to `GITHUB_TOKEN`.
3. Configure branch protection or a ruleset for `main` requiring the `CI` check and review on the latest release PR head, with stale approvals dismissed and no App bypass/auto-merge. Confirm Actions can run and that an App-created PR triggers CI. The workflow orchestrates releases on `main` pushes or manual dispatch, and does not run the full suite a second time.
4. Validate the release workflow in an application sandbox, then set repository variable `RELEASE_ENABLED=true` when release publication is authorized. It is disabled while this variable is unset. Review the generated version/changelog/manifest diff, require successful checks, and merge explicitly. Verify the resulting `vX.Y.Z` tag points at the release merge commit and the GitHub Release is published. Manual dispatch on `main` is available for recovery; dispatch on another ref is ignored.

Releases require GitHub App credentials that can trigger release PR checks, required checks on the latest head, and review. Local config simulation does not prove hosted CI or tag/release publication. A tag or release does not authorize deployment. Consult the foundation repository for its internal implementation roadmap and outstanding publication gates; internal roadmap files are not distributed.
