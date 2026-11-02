---
paths:
  - '.github/workflows/**'
---

# Workflows

## Conserve GitHub Actions minutes
Run CI in one Linux job for ready-for-review pull requests targeting main, with manual dispatch available. Avoid duplicate push/post-merge runs, detection-only runners, and unnecessary matrices; cancel superseded runs through concurrency. Use `composer test:ci` for pull requests: audit, one client/SSR build, lint, types, full backend coverage, and browser smoke. Keep full browser and type coverage in `composer test`; run clean-build acceptance separately with `composer test:feature-build`. Run both locally or manually before a release or after broad framework/runtime changes. Manual dispatch selects `ci`, `full`, or `acceptance`. Each entry point builds client and SSR once; never invoke standalone build/browser preparation again inside the same pipeline. Keep dependency caches, install only the browser required by the test suite, and collect backend coverage with PCOV instead of Xdebug.

## Documentation-only pull requests
Detect the changed files before installing dependencies. Skip the PHP, Node and browser setup and the test suite only when every changed file is documentation (`docs/**`, a Markdown file, or `LICENSE`), and still report success so the required `CI` check completes. Run the standard PR suite when the file list is empty or detection fails. Manual dispatch always runs its selected suite. Dependency and release metadata files, including Dependabot and Release Please changes, always run the standard PR suite; Dependabot commits use the `build` prefix so Release Please does not treat them as a feature or fix.

## Release orchestration exception

The lightweight `release-please` workflow may run on pushes to main and manual dispatch. It only reconciles release PRs, tags and GitHub Releases; it must not repeat the full test suite, auto-merge, or deploy. Release PRs use the existing ready-for-review PR CI on their latest head. Use a repository-scoped GitHub App token with contents, pull requests and issues write permissions so automation-created PRs trigger checks; never fall back to GITHUB_TOKEN. Keep RELEASE_ENABLED unset until sandbox acceptance and the public-release gate authorize activation.
