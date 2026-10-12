# Conventional Commit Instructions

Generate **exactly one Conventional Commit message** based strictly on the staged diff.

## Output Format

`<type>(<scope>): <description>`

Omit `(scope)` when no meaningful scope applies.

**Output only the commit message.** No explanations, Markdown, quotes, alternatives, or extra lines.

## Type Selection

Choose exactly one type based on the **primary intent**:

- `feat` — New functionality
- `fix` — Bug or incorrect behavior
- `refactor` — Structural changes without behavior changes
- `perf` — Performance optimization
- `docs` — Documentation only
- `test` — Tests only
- `style` — Formatting only
- `build` — Build system or packaging
- `ci` — CI/CD workflows
- `chore` — Maintenance not covered above
- `revert` — Revert previous changes

**Never use `chore` when a more specific type applies.**

## Scope Selection

Prefer the affected business domain:

`auth`, `user`, `customer`, `shipment`, `pickup`, `tracking`, `invoice`, `payment`, `report`, `notification`

Use technical scopes only for cross-cutting changes:

`api`, `database`, `ui`, `config`, `deps`, `vite`, `actions`

Rules:
- Use exactly one lowercase scope.
- Choose by logical purpose, **not file location**.
- Prefer business scope over technical scope.
- Omit scope if none is meaningful.

## Description Rules

- Use English and imperative mood.
- Start with a lowercase verb.
- Describe the **specific behavior or change**.
- Keep it concise, ideally under 50 characters.
- Never end with a period.
- Never repeat the commit type.
- Never use vague phrases such as `update files`, `fix issues`, `improve code`, or `various changes`.

## Multiple Changes

- Identify the **dominant logical change** in the staged diff.
- Summarize its intent, not individual file modifications.
- Prioritize business behavior over supporting implementation changes.
- Never combine unrelated changes into a generic description.
- Never invent changes unsupported by the diff.

## Examples

✅ `feat(shipment): add shipment cancellation`

✅ `fix(payment): prevent duplicate payments`

✅ `refactor(customer): extract address validation`

✅ `ci(actions): cache composer dependencies`

❌ `fix: fix issues`

❌ `chore: update files`

❌ `refactor: improve code structure`

**Final rule: Return exactly one valid commit message and nothing else.**