---
paths:
  - '.github/workflows/**'
---

# Workflows

## Conserve GitHub Actions minutes

Hosted branch protection is specific to the foundation repository `tomylana93/jcf`, which already has it enabled. Derived private repositories on GitHub Free run CI without branch protection or rulesets; verify CI manually before merging. Keep repository provisioning independent of those hosted controls and preserve the derived project's free-plan compatibility.

Run CI in one Linux job for ready-for-review pull requests targeting main, with manual dispatch available. Also run the standard PR suite once on pushes to main so the README `tests` badge reflects main; filter that trigger with `paths-ignore` for documentation and Release Please metadata (`docs/**`, Markdown, `LICENSE`, `version.txt`, `.release-please-manifest.json`) so no runner starts for them. Avoid any other duplicate post-merge runs, detection-only runners, and unnecessary matrices; cancel superseded runs through concurrency. Use `composer test:ci` for pull requests: audit, one client/SSR build, lint, types, full backend coverage, and browser smoke. Keep full browser and type coverage in `composer test`; run clean-build acceptance separately with `composer test:feature-build`. Run both locally or manually before a release or after broad framework/runtime changes. Manual dispatch selects `ci`, `full`, or `acceptance`. Each entry point builds client and SSR once; never invoke standalone build/browser preparation again inside the same pipeline. Keep dependency caches, install only the browser required by the test suite, and collect backend coverage with PCOV instead of Xdebug.

## Documentation-only pull requests
Detect the changed files before installing dependencies. Skip the PHP, Node and browser setup and the test suite only when every changed file is documentation (`docs/**`, a Markdown file, or `LICENSE`), and still report success so the required `CI` check completes. Run the standard PR suite when the file list is empty or detection fails. Manual dispatch always runs its selected suite. Dependency and release metadata files, including Dependabot and Release Please changes, always run the standard PR suite; Dependabot commits use the `build` prefix so Release Please does not treat them as a feature or fix.

Check both original and final paths for renames and compare the paginated API file count with the event's changed-file count. Run the scope classifier's Python regression tests before detection. Retargeting a ready PR to main runs CI; title/body-only edits use a separate concurrency group and a non-required skipped job name so they neither cancel running tests nor replace the required CI check. Pin external actions to full commit SHAs and let Dependabot propose updates.

## Release orchestration exception

The lightweight `release-please` workflow may run on pushes to main and manual dispatch. It only reconciles release PRs, tags and GitHub Releases; it must not repeat the full test suite, auto-merge, or deploy. Release PRs use the existing ready-for-review PR CI on their latest head. Use a repository-scoped GitHub App token with contents, pull requests and issues write permissions so automation-created PRs trigger checks; never fall back to GITHUB_TOKEN. Keep RELEASE_ENABLED unset until sandbox acceptance and the public-release gate authorize activation.

Configure the token action with `RELEASE_APP_CLIENT_ID` and `RELEASE_APP_PRIVATE_KEY`. Keep standard Conventional Commit types visible in the explicit changelog sections, including internal maintenance. Non-feature, non-breaking visible commits can produce patch releases; Dependabot's `build` prefix preserves that distinction. Keep the default changelog generator's issue URLs, which GitHub redirects to PRs when the number identifies a PR.
