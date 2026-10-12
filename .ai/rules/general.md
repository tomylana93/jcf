---
paths:
  - '**/*'
---

# General

## Conventional commit messages for all agents
When generating a commit message, follow .github/instructions/commit-message.instructions.md regardless of the agent used. Base exactly one message strictly on the staged diff: <type>(<scope>): <description>, omitting scope when none is meaningful; return only that single line when asked to generate a message.
Choose the primary intent from feat, fix, refactor, perf, docs, test, style, build, ci, chore, revert; use chore only when no specific type applies. Prefer one lowercase business-domain scope (auth, user, customer, shipment, pickup, tracking, invoice, payment, report, notification); technical scopes (api, database, ui, config, deps, vite, actions) are for cross-cutting changes. Choose scope by logical purpose, not file location.
Use English imperative wording starting with a lowercase verb, describing the specific dominant logical change concisely (ideally under 50 characters), without a trailing period, repeated type, vague phrases, unrelated generic summaries, or claims unsupported by the staged diff. Prioritize business behavior over supporting implementation details.
