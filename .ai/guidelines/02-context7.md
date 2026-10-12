# Context7 Library Documentation

Use Context7 to fetch current documentation whenever the task involves a library, framework, SDK, API, CLI tool, or cloud service — even well-known ones (React, Tailwind, Express, Prisma). Training data may be outdated; prefer Context7 over web search for library docs.

## Workflow

- Call `resolve-library-id` first to get the exact Context7 ID (`/org/project`), unless the user already provides one. Never guess the ID.
- Then call `query-docs` with that ID and one specific concept per query (e.g. `How to set up authentication with JWT in Express.js`). For multiple concepts, make separate calls.
- Do not call `resolve-library-id` more than 3 times per question; if unresolved, proceed with the best match.
- Do not include secrets or proprietary code in queries.

## Use for

- API syntax, configuration, setup instructions, CLI usage, version migration, library-specific debugging.

## Do not use for

- Refactoring, writing scripts from scratch, debugging business logic, code review, or general programming concepts — use Serena and local tools instead.