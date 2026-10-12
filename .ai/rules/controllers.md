---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Thin controllers delegate to Actions without private methods
Keep controllers focused on HTTP orchestration: validated input, authorization, session/auth handling, and responses. Delegate application operations and substantial query/data preparation to focused classes in app/Actions via dependency injection and call handle(). Controllers must not declare private methods; do not change helpers to public/protected merely to evade this rule.

## Resources for tabular HTTP output
Serialize Table-backed Inertia rows through the matching JsonResource resolved against the request. Reuse the resource collection for exports.
