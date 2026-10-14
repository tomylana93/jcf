---
paths:
  - '**/*'
---

# General

## Conventional commit messages for all agents
When generating a commit message, follow .github/instructions/commit-message.instructions.md regardless of the agent used. Base exactly one message strictly on the staged diff: <type>(<scope>): <description>, omitting scope when none is meaningful; return only that single line when asked to generate a message.
Choose the primary intent from feat, fix, refactor, perf, docs, test, style, build, ci, chore, revert; use chore only when no specific type applies. Prefer one lowercase business-domain scope (auth, user, customer, shipment, pickup, tracking, invoice, payment, report, notification); technical scopes (api, database, ui, config, deps, vite, actions) are for cross-cutting changes. Choose scope by logical purpose, not file location.
Use English imperative wording starting with a lowercase verb, describing the specific dominant logical change concisely (ideally under 50 characters), without a trailing period, repeated type, vague phrases, unrelated generic summaries, or claims unsupported by the staged diff. Prioritize business behavior over supporting implementation details.

## Short-lived branches and squash merges
Never develop, edit project files, or create commits directly on main. Before starting changes, switch to a short-lived branch based on up-to-date main; preserve any existing uncommitted work when moving it to the branch. Make focused logical commits and open a pull request targeting main. Squash merge only after checks pass on the latest PR head and review feedback is resolved. After verifying the PR state is MERGED, synchronize local main and delete both the remote and local short-lived branch; preserve any unmerged commits or unrelated work before deletion, and do not force-delete them. Do not push directly to main. Respect explicit instructions to keep changes local; recording this workflow does not authorize pushing, merging, or deleting branches now.

## Complete local database reset and re-initialization
When a developer explicitly requests a local database reset and re-initialization, treat cleanup of that checkout's generated runtime artifacts as part of the authorized task. Do not stop after `migrate:fresh` and `init:app`, or ask again for permission to clean these artifacts.

- Confirm the target is the intended local development database. Keep development and testing databases separate; never reset another checkout's database or production data.
- Inspect the configured storage disks and temporary directories, including custom paths. Before dropping database references, identify application uploads that will become orphaned by the reset. Use the existing cleanup command's dry-run mode where available.
- Remove temporary avatars, orphaned media files and directories, orphaned brand images, and conversion temporary directories belonging to this checkout. Use the existing `avatars:clean` command where applicable and verify any remaining uploads against the new database/settings references.
- Clean generated test artifacts in `storage/framework/testing`, including fake disks and test caches; compiled views, including test-specific view directories; generated application cache/session files; and application log files. Include configured conversion temp paths outside `storage/app` when they belong to this checkout.
- Preserve tracked files, `.gitignore` placeholders, required storage directories, `.env`, application keys, valid storage symlinks, and unrelated files. Preserve backup archives unless their deletion is explicitly requested. Do not follow symlinks into unrelated directories or clean shared/external storage indiscriminately. If a target cannot be safely attributed to this checkout, report it and request clarification for that target only.
- Run the reset and `init:app` through Lerd, then verify migrations, permissions, superadmin creation, and the storage link. If tests are run afterward, clean their generated artifacts again before reporting completion.
- Finish with a read-only check: report remaining test artifacts, conversion temp directories, orphaned media/brand files, and log/cache/view files. Initialization may recreate valid runtime files; distinguish those from stale artifacts. Report any cleanup failure or remaining stale artifact rather than claiming storage is empty.
