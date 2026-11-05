---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Thin controllers delegate to Actions without private methods
Keep controllers focused on HTTP orchestration: validated input, authorization, session/auth handling, and responses. Delegate application operations and substantial query/data preparation to focused classes in app/Actions via dependency injection and call handle(). Controllers must not declare private methods; do not change helpers to public/protected merely to evade this rule.

## Resources for tabular HTTP output
Serialize Table-backed Inertia rows through the matching JsonResource resolved against the request. Reuse the resource collection for exports.

## Code follows domain ownership, not menus
Place code by owning domain: Identity (users, API clients), Account (own profile, sessions, tokens), Operations (backup and restore), plus Chat, Documentation, Settings, Audit, Notification and System. The master-data route names and pages are menu structure only. App\Jobs\MasterData and App\Notifications\MasterData keep their class names solely for already-serialized queue payloads; rename them to Identity in a release after queues are drained.

## One authorization location per route
Authorize every route in exactly one primary place: route ->can() for permission-only routes such as settings, Gate::authorize() at the top of the controller method for permission or policy checks, or FormRequest::authorize() for mutation requests that already carry validation. Module availability is enforced by the feature:<module> route middleware, not repeated in controllers.
