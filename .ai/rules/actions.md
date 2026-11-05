---
paths:
  - 'app/Actions/**'
---

# Actions

## Application Actions use handle
Application Actions expose a public handle() method with explicit parameter and return types, represent a focused operation, and follow the existing domain directory structure. Keep HTTP requests, redirects, Inertia responses, and session handling in controllers. Framework-required contract methods (for example Fortify create/reset) retain their required names and delegate to handle-based Actions when extraction is needed.

## Actions stay transport-neutral
Actions run from controllers, jobs, console commands and the API, so they never call abort(), abort_if() or abort_unless() or throw Symfony HTTP exceptions. Throw domain or framework exceptions that the handler already renders: AuthorizationException (or Gate::authorize) for 403, RecordsNotFoundException or ModelNotFoundException for 404, ValidationException for 422. Gate module operations with FeatureManager::ensureEnabled(Module::X), which throws ModuleUnavailableException (rendered as 404). Fortify contract actions, FormRequests and controllers remain HTTP code and may abort.
