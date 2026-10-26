---
paths:
  - '.github/workflows/**'
---

# Workflows

## Conserve GitHub Actions minutes
Run CI in one Linux job for ready-for-review pull requests targeting main, with manual dispatch available. Avoid duplicate push/post-merge runs, detection-only runners, and unnecessary matrices; cancel superseded runs through concurrency. Reuse the current Composer test entry point, including its frontend build, rather than duplicating checks or referencing removed scripts. Keep dependency caches and install only the browser required by the test suite.

## Release orchestration exception

The lightweight `release-please` workflow may run on pushes to main and manual dispatch. It only reconciles release PRs, tags and GitHub Releases; it must not repeat the full test suite, auto-merge, or deploy. Release PRs use the existing ready-for-review PR CI on their latest head. Use a repository-scoped GitHub App token with contents, pull requests and issues write permissions so automation-created PRs trigger checks; never fall back to GITHUB_TOKEN. Keep RELEASE_ENABLED unset until sandbox acceptance and the public-release gate authorize activation.
