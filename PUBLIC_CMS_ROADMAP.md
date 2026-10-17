# Public CMS roadmap

Status: planned. This document does not indicate implementation completion.

## Objective and agreed scope

Build a CMS-like company/organization website whose primary visitor action is contacting the organization. Provide Home, About, article listing/detail, and Contact. Administrators manage content and choose at least three complete presets: Corporate, Editorial, and Minimal. Every public page must support SEO and safe server-side rendering (SSR).

Reuse the existing Laravel/Inertia Vue application, public layout/navigation/footer, settings, permissions, editor, and media patterns. Inspect installed versions and applicable path rules before implementation. No dependency changes without explicit approval.

## Delivery rules

- Work on a short-lived local branch based on current main. Preserve unrelated changes.
- Implement scopes in dependency order below. Finish the scope's regression checks before committing it.
- Make one focused local implementation commit per scope, including its tests and this document's scope status/evidence update. If a scope needs multiple commits, keep them limited to that scope and record the reason.
- Do not push, open a PR, merge, deploy, or delete branches as part of this roadmap. Local commits are the requested delivery boundary.
- Use conventional English commit messages based on the staged diff; suggested messages below are examples, not preapproved descriptions of future work.
- Mark a scope complete only after its acceptance criteria pass. Record actual checks and unresolved gates, never inferred success.
- Keep the website feature switch effective throughout incremental implementation; do not expose incomplete public features.

## Content and template contract

All presets use the same canonical content and routes. Switching presets changes presentation and composition without deleting content, changing URLs, or resetting SEO. Store preset-specific presentation preferences separately and preserve them when switching away and back.

| Preset | Intent | Default home composition |
| --- | --- | --- |
| Corporate | Formal company profile | Hero, introduction, services, strengths, recent articles, contact CTA |
| Editorial | Organization stories and publications | Editorial hero, introduction, featured articles, activities/services, contact CTA |
| Minimal | Concise profile and direct contact | Simple hero, introduction, services, recent articles, contact CTA |

Each preset covers all public page types, desktop/mobile navigation, empty states, and long content. Use validated, structured section editing with supported ordering and visibility controls. Do not build an arbitrary HTML/CSS page builder. Hide empty optional sections. Do not publish fabricated statistics, clients, testimonials, or other organizational claims.

Content editing follows draft → authenticated preview → explicit publish. Editing a published page must not silently replace its public version. Public queries, props, caches, and sitemap must exclude drafts and unpublished content. Preview requires backend authorization; noindex alone is not access control.

## SSR safety contract — applies to every scope

- Render meaningful public content, links, and page-specific metadata in initial server HTML; do not depend on client fetches or JavaScript for the primary content.
- Do not access window, document, navigator, localStorage, browser observers, or browser-only libraries during server module evaluation or rendering. Isolate browser enhancements behind client lifecycle boundaries and SSR-safe fallbacks.
- Produce deterministic server/client output. Pass dates and locale deliberately; avoid render-time randomness, time-dependent branches, and viewport-based initial markup that causes hydration mismatches.
- Keep request-specific auth, locale, preview, and content state isolated. Never retain request/user state in shared server module variables or cache personalized public HTML across users.
- Initialize browser-only rich text editors only in admin/client contexts. Public rich text rendering uses a consistent sanitized representation that is safe on server and client. Escape SEO and JSON-LD output safely.
- Images, navigation, icons, and preset composition must render without browser-only initialization. Client enhancement may add interaction without removing the server-rendered content.
- Treat SSR service/build health as an explicit runtime requirement. Client rendering fallback does not prove SEO acceptance. Verify production-style SSR build and initial HTTP HTML, not just development rendering.
- Reject hydration warnings, SSR exceptions, private data leaks, and newly introduced N+1 queries as incomplete work.

## Scope 01 — public access and shared contracts

Status: pending. Dependencies: none.

1. Inspect existing public routes, auth props, feature switch, metadata, SSR entry/configuration, tests, branding, and shared components.
2. Define typed public page, SEO, section, preset, and navigation contracts using existing project conventions.
3. Hide Dashboard for guests in both home and public navigation; show it only for authenticated users with the applicable access. Public primary CTA targets contact/content.
4. Define feature-switch behavior: preserve current root redirects when disabled; other public content routes return 404 when disabled. Admin management remains permission-protected and available.
5. Add focused guest/authenticated and enabled/disabled regression coverage.
6. Check existing public rendering for SSR-sensitive dependencies before expanding it.

Acceptance: no guest Dashboard link; existing login/intended redirects remain correct; public route gating is explicit and tested; contracts support all three presets.

Suggested commit: `fix: gate public dashboard links by access`

## Scope 02 — CMS storage, authorization, and publishing

Status: pending. Dependencies: 01.

1. Inspect database schema, UUID conventions, permission provisioning, settings migrations, audit patterns, and media lifecycle.
2. Define domain settings for active preset, public identity, default SEO, contact details, navigation, and footer. Keep reusable page/article records separate from global settings.
3. Add Home/About/Contact/article-index page content with separate working draft and published snapshot, plus validated structured sections and per-page SEO.
4. Add article storage for UUID, unique slug, title, excerpt, body, cover/alt, author, publication state/date, and SEO; retain prior published slugs for redirects.
5. Add backend permissions/policies for content editing, publishing, template configuration, and contact inbox access. Update permission fixtures affected by new permissions.
6. Implement focused Actions for save/publish/unpublish and public reads; validate section types, order, URLs, uploads, and rich text. Use atomic publication updates.
7. Add cache invalidation on publication, unpublication, preset changes, and SEO changes. Ensure drafts never populate public caches.
8. Test access boundaries, draft isolation, publication consistency, validation, and non-destructive migrations/defaults.

Acceptance: backend publishing works independently of the UI; unauthorized writes fail; public serialization contains only approved published fields; no existing data is overwritten.

Suggested commit: `feat: add public content publishing foundation`

## Scope 03 — reusable public components and three presets

Status: pending. Dependencies: 02.

1. Establish visual compositions for Corporate, Editorial, and Minimal using existing brand assets and tokens; review real content lengths before coding.
2. Extend existing PublicLayout, PublicNavigation, and PublicFooter rather than duplicating the shell.
3. Build shared container, section heading, breadcrumb, CTA, article card/list, pagination, sanitized rich text, and contact information components.
4. Build reusable hero, introduction, services, strengths, and featured article sections with bounded variants.
5. Register complete presets centrally; compose all page types without scattering preset-name conditions across components.
6. Use shared content/SEO contracts, Wayfinder links, responsive CSS, accessible navigation, and reduced-motion behavior.
7. Exercise each preset with empty, typical, long, and missing-image content in server rendering and hydration.

Acceptance: three distinct presets cover all page types; shared behavior is DRY; no overflow or broken empty sections; no browser globals during SSR or hydration mismatch.

Suggested commit: `feat: add three reusable public presets`

## Scope 04 — admin website management and preset selection

Status: pending. Dependencies: 03.

1. Add a permission-aware Website admin area using existing form/table/navigation patterns.
2. Add a preset picker with descriptive thumbnails/summaries, current selection, authenticated preview, and explicit Apply action.
3. Add structured Home/About/Contact/article-index editors, supported section ordering/visibility, CTA configuration, and media/alt controls.
4. Add shared SEO field groups and defaults, navigation/footer/contact settings, and clear validation/save feedback.
5. Expose draft/publication status and explicit Save draft, Preview, Publish, and Unpublish actions where supported. Home remains a stable route with a meaningful unpublished state.
6. Preserve published content while editing and all content/preferences when switching presets. Keep preview state separate from the active public preset.
7. Test permissions through direct requests, invalid submissions, preview isolation, draft save/publish, and preset switching/back.

Acceptance: an administrator configures the website without editing code; preview is private; only Apply/Publish updates public output; preset switches preserve content, URLs, and SEO.

Suggested commit: `feat: add website content and preset management`

## Scope 05 — article administration and public reading

Status: pending. Dependencies: 04.

1. Add permission-aware article list/create/edit using existing rich text, media, and table components.
2. Implement draft, publish, unpublish, cover/alt, excerpt, author, slug validation, and per-article SEO.
3. Add public paginated listing and detail routes, featured/recent articles on Home, breadcrumbs, and empty states across all presets.
4. Restrict public reads to currently published articles; future publication dates stay private. Automatic scheduled publishing is outside this minimum scope.
5. Resolve old published slugs to the current published URL with 301; reject collisions and redirect loops. Unpublished content must remain inaccessible through old slugs.
6. Render sanitized article content and publication metadata during SSR; eager-load required related data and bound pagination.
7. Test public visibility, preview authorization, pagination, slug history, rich text safety, and query behavior.

Acceptance: complete article editing and reading flow; no draft/future content leaks; slug redirects work; all three presets render article content on the server.

Suggested commit: `feat: add public article publishing and reading`

## Scope 06 — contact form and admin inbox

Status: pending. Dependencies: 04; execute after 05 for a linear commit history.

1. Add configurable contact details, hours, social links, location link, and accessible form in each preset.
2. Store contact messages with explicit allowed fields; do not expose inbox records publicly or serialize unnecessary personal data.
3. Implement server validation, request protection, rate limiting, and an accessible spam deterrent. Do not add a third-party CAPTCHA dependency by default.
4. Add permission-protected inbox list/detail and handled/unhandled status.
5. Persist messages before optional mail notification using existing mail infrastructure; notification failure must not discard accepted messages.
6. Handle success, field errors, network/server errors, and rate-limit feedback without losing useful form input. Avoid logging message bodies or sensitive request data.
7. Test validation, throttling, inbox isolation, persistence, notification failure, and form interaction.

Acceptance: visitor can send a message; authorized administrators can manage it; mail failures do not lose messages; contact content/form render safely through SSR.

Suggested commit: `feat: add public contact form and inbox`

## Scope 07 — complete public SEO and SSR verification

Status: pending. Dependencies: 05, 06.

1. Implement one backend metadata resolver and shared PublicSeo component; replace duplicated home metadata.
2. Resolve title, description, canonical, robots, Open Graph, Twitter Card, social image/alt, and locale with page/article → content-derived → website-default fallback.
3. Generate canonical URLs from trusted application configuration/named routes; normalize tracking parameters and retain page identity for paginated article lists. Search/filter URLs do not create duplicate indexed pages.
4. Add escaped, content-appropriate JSON-LD: Organization/WebSite, BreadcrumbList, and Article. Do not fabricate schema properties or ratings.
5. Generate sitemap from published indexable pages/articles; invalidate it on content changes. Exclude drafts, previews, noindex content, redirects, and disabled public routes.
6. Align robots.txt with indexing policy; protect previews/admin with authorization independently of robots directives.
7. Build production-style SSR assets and verify initial HTTP HTML for every public page type under each preset: main content, heading, metadata, canonical, structured data, and links.
8. Verify guest/auth and locale request isolation, hydration, repeated head navigation, SSR errors, and service health. Record runtime requirements and unresolved failures in this roadmap.

Acceptance: page-specific SEO is present before JavaScript runs; no duplicate/stale head tags; sitemap and robots match publication rules; complete three-preset SSR matrix passes.

Suggested commit: `feat: complete public seo and ssr support`

## Scope 08 — integrated acceptance and release readiness

Status: pending. Dependencies: 07.

1. Run an end-to-end admin draft → preview → publish → guest visit flow and preset-switch round trip.
2. Verify desktop/mobile layouts, keyboard navigation, menu focus, form labels/errors, heading hierarchy, contrast, image alt, and reduced motion. Distinguish manual evidence from automated checks.
3. Check direct navigation and Inertia navigation across all page types/presets, disabled website behavior, draft/unpublished 404s, old-slug redirects, contact failure modes, and content cache refresh.
4. Capture affected-route query evidence through Lerd; resolve new N+1 or slow-route regressions. SSR tests must use a correctly configured runtime, not silent CSR fallback.
5. Run focused checks first, then the authoritative `lerd composer test` gate; execute browser/backend gates sequentially where they share runtime state. Record final exit results.
6. Fix scope-specific defects and add only meaningful missing regression coverage. Update status/evidence and summarize local commits and pending gates.

Acceptance: integrated flow and complete local gate pass; manual acceptance evidence is accurately labeled; all commits remain local. No claim of deployment or production readiness beyond verified evidence.

Suggested commit when fixes/tests change: `test: verify public cms acceptance flows`

If this scope only records evidence, use a documentation commit instead of an empty test commit.

## Evidence register

Update with each scope's local commit. Record commands/results, SSR matrix evidence, manual review results, and any unresolved gate. Do not mark work complete based only on the presence of a test or configuration flag.

| Scope | State | Validation evidence | Remaining gate |
| --- | --- | --- | --- |
| 01 | Pending | — | Implementation and focused tests |
| 02 | Pending | — | Storage, authorization, publishing tests |
| 03 | Pending | — | Three-preset rendering and responsive review |
| 04 | Pending | — | Admin editing, preview, preset-switch tests |
| 05 | Pending | — | Publication, slug, pagination, SSR tests |
| 06 | Pending | — | Form/inbox security and failure-mode tests |
| 07 | Pending | — | SEO and production-style SSR matrix |
| 08 | Pending | — | Integrated review and complete local gate |

## Explicit minimum-scope boundaries

No arbitrary page builder, custom CSS editor, multi-template marketplace, article comments, newsletter, advanced revision history, automated scheduling, or translated content workflow. Existing interface localization remains supported; multilingual content and hreflang require a separate scope. Use existing dependencies unless an additional dependency is explicitly approved.

Only the roadmap is delivered in the documentation scope. Implementation scopes above remain pending until requested and verified.
