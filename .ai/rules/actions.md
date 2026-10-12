---
paths:
  - 'app/Actions/**'
---

# Actions

## Application Actions use handle
Application Actions expose a public handle() method with explicit parameter and return types, represent a focused operation, and follow the existing domain directory structure. Keep HTTP requests, redirects, Inertia responses, and session handling in controllers. Framework-required contract methods (for example Fortify create/reset) retain their required names and delegate to handle-based Actions when extraction is needed.
