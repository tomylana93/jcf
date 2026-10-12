---
paths:
  - 'tests/**'
---

# Tests

## Pest describe, it, and functional helpers
Group tests by behavior using describe() and declare test cases with it(), using descriptive verb phrases. Import and use Pest\Laravel functional helpers for requests, authentication, assertions, mocking, and time instead of $this-> calls. Put shared project test helpers in tests/Pest.php.

## One-off verification tests are temporary
Use temporary tests for one-off verification, such as comparing a legacy implementation with its replacement or confirming obsolete artifacts were removed. Run them and report the results, then remove the tests and any helpers, datasets, imports, or assertions used solely for that verification before completing the task. Preserve regression tests that protect current application behavior, including tests introduced during refactoring or behavior changes; a test's origin alone is not a reason to delete it.
