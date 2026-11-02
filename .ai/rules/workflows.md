---
paths:
  - '.github/workflows/**'
---

# Workflows

## Conserve GitHub Actions minutes
Run CI in one Linux job for ready-for-review pull requests targeting main, with manual dispatch available. Avoid duplicate push/post-merge runs, detection-only runners, and unnecessary matrices; cancel superseded runs through concurrency. Reuse the current Composer test entry point, including its frontend build, rather than duplicating checks or referencing removed scripts. Keep dependency caches, install only the browser required by the test suite, and collect backend coverage with PCOV instead of Xdebug.

## Documentation-only pull requests
Detect the changed files before installing dependencies. Skip the PHP, Node and browser setup and the test suite only when every changed file is documentation (`docs/**`, a Markdown file, or `LICENSE`), and still report success so the required `CI` check completes. Run the full suite when the file list is empty, detection fails, or the workflow is dispatched manually. Dependency and release metadata files, including Dependabot and Release Please changes, always run the full suite; Dependabot commits use the `build` prefix so Release Please does not treat them as a feature or fix.

## Release orchestration exception

The lightweight `release-please` workflow may run on pushes to main and manual dispatch. It only reconciles release PRs, tags and GitHub Releases; it must not repeat the full test suite, auto-merge, or deploy. Release PRs use the existing ready-for-review PR CI on their latest head. Use a repository-scoped GitHub App token with contents, pull requests and issues write permissions so automation-created PRs trigger checks; never fall back to GITHUB_TOKEN. Keep RELEASE_ENABLED unset until sandbox acceptance and the public-release gate authorize activation.
