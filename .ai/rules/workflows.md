---
paths:
  - '.github/workflows/**'
---

# Workflows

## Conserve GitHub Actions minutes
Run CI in one Linux job for ready-for-review pull requests targeting main, with manual dispatch available. Avoid duplicate push/post-merge runs, detection-only runners, and unnecessary matrices; cancel superseded runs through concurrency. Reuse the current Composer test entry point, including its frontend build, rather than duplicating checks or referencing removed scripts. Keep dependency caches and install only the browser required by the test suite.
