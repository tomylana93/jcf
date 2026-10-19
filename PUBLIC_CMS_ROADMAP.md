# Public CMS roadmap

Status: Scopes 01–05 complete locally; Scopes 06–08 remain planned. No deployment is implied.

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

| Preset    | Intent                                | Default home composition                                                          |
| --------- | ------------------------------------- | --------------------------------------------------------------------------------- |
| Corporate | Formal company profile                | Hero, introduction, services, strengths, recent articles, contact CTA             |
| Editorial | Organization stories and publications | Editorial hero, introduction, featured articles, activities/services, contact CTA |
| Minimal   | Concise profile and direct contact    | Simple hero, introduction, services, recent articles, contact CTA                 |

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

Status: complete locally (2026-10-17). Dependencies: none.

1. Inspect existing public routes, auth props, feature switch, metadata, SSR entry/configuration, tests, branding, and shared components.
2. Define typed public page, SEO, section, preset, and navigation contracts using existing project conventions.
3. Hide Dashboard for guests in both home and public navigation; show it only for authenticated users with the applicable access. Public primary CTA targets contact/content.
4. Define feature-switch behavior: preserve current root redirects when disabled; other public content routes return 404 when disabled. Admin management remains permission-protected and available.
5. Add focused guest/authenticated and enabled/disabled regression coverage.
6. Check existing public rendering for SSR-sensitive dependencies before expanding it.

Acceptance: no guest Dashboard link; existing login/intended redirects remain correct; public route gating is explicit and tested; contracts support all three presets.

Suggested commit: `fix: gate public dashboard links by access`

## Scope 02 — CMS storage, authorization, and publishing

Status: complete locally (2026-10-17). Dependencies: 01.

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

Status: complete locally (2026-10-17). Dependencies: 02.

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

Status: complete locally. Dependencies: 03.

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

Status: complete locally (2026-10-19). Dependencies: 04.

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

## Scope 01 implementation notes

- Public Dashboard navigation now follows authenticated access and the configurable email verification requirement. Dashboard has no separate permission in the current route contract. Home's primary CTA targets existing public content instead of Dashboard. Root redirects and intended destinations are preserved.
- `resources/js/types/public.ts` defines shared page, SEO, structured section, navigation, image, preset, and per-preset preference contracts for Corporate, Editorial, and Minimal. Storage, input validation, publishing, and full preset rendering remain in their later scopes.
- `EnsurePublicPagesEnabled` is the non-root content boundary: disabled public content returns 404 without consuming an intended destination. Scope 02 and later must attach this middleware to new public content routes; Home retains its special redirect behavior. About, Contact, and articles remain unavailable until implemented. Settings remain permission-protected and accessible when public pages are disabled.
- SSR inspection covered the automatic Inertia Vite entry, per-request translation initialization, public layout/navigation/logo/footer, and browser timezone/theme/toast setup. Theme and toast initialization now run only in the client `withApp` branch; footer year comes from the server instead of render-time machine dates. The production-style client and SSR builds pass. This is not the complete runtime, hydration, metadata, or three-preset SSR matrix required by Scope 07.
- Additional local commits were needed after the requested RAM investigation: `9479e41` introduced validation resource limits and restricted Serena to PHP/Vue; `abfd462` moved the resource limits out of the repo again. Only the PHP/Vue Serena configuration remains in the final repository state. Local PHP 8.5 service limits are CPU quota 200%, CPUs 0–1, MemoryHigh 2 GiB, MemoryMax 3 GiB, and MemorySwapMax 512 MiB. No global Node/editor/desktop limits were applied after narrowing the investigation to PHP. Existing Serena processes still require an MCP restart to reload their reduced language-server list.
- A monitored coverage run with the initial repo limits passed 583 tests with 4 skips and 100% coverage; sampled PHP test RSS peaked at 410 MiB with 2 workers. Those limits were subsequently removed from the repo. The final environment-only gate evidence is recorded below.

## Scope 02 implementation notes

- `WebsiteContent` stores reusable page/article records with UUIDs, an independently versioned working draft, and a published snapshot. `page_key` identifies the singleton Home/About/Contact/article-index pages; a null key identifies an article. No existing pages, users, settings, or documentation records are rewritten or seeded with fabricated content.
- Save, publish, unpublish, settings configuration, and image upload are callable backend Actions. Policies separate editing/preview from publishing; separate permissions cover template/settings configuration and future contact inbox access. All protected Actions enforce authorization themselves. Input validation runs independently of HTTP; future admin Form Requests can reuse the domain validation boundary. No CMS HTTP routes or admin screens are introduced in this scope.
- Structured content uses the existing bounded rich-text renderer. Draft input accepts `body_json`; public snapshots expose sanitized `body_html` and section `body`, with no editor JSON or private user identifiers. Images are immutable references to media owned by the content record, accompanied by required alt text. Scope 03/05 consumers will resolve these references and complete the frontend presentation contract.
- Publication locks the content row, checks the expected draft version, and updates the snapshot, date, slug reservation/history, and audit atomically. Draft edits preserve the live snapshot and URL. Slug reservations prevent reuse by another article; old published slugs resolve only while the owning article is currently visible. Future dates and disabled public access return no public content. HTTP redirects and article pagination belong to Scope 05.
- Website Settings store preset, identity, default SEO, contact details, navigation, footer, and separate preferences for all three presets. Partial preset changes preserve other presets and content. Settings updates lock and refresh persisted values before saving.
- Public content cache keys include a publication UUID and public settings keys include their own revision. Draft writes do not populate public caches. Publication, unpublication, and settings changes invalidate old entries and the reserved sitemap key after commit; rolled-back publication does not invalidate caches. Public reads check current database publication visibility before consulting the snapshot cache.
- Image uploads accept bounded JPEG/PNG/WebP files on the private local disk. Replacing a draft image does not delete the previously published image. `GetWebsiteImage` permits authorized preview or references in a currently published snapshot; unused, draft-only, future, and unpublished images remain private. HTTP image delivery and admin media controls remain later integration work.
- Local additive migrations and `init:permission` completed successfully. New permissions are provisioned through the existing enum-based initializer; affected permission fixtures and English/Indonesian labels are updated. The scope branch continues the locally completed Scope 01 commits because those prerequisite commits have not been merged into local main.

## Scope 03 implementation notes

- The three presets are registered centrally in `resources/js/components/public/presets.ts` as bounded variant tokens rather than full templates: hero `split|editorial|simple`, article grid `grid|lead|list`, list composition `columns|ruled|compact`, container width, and a canonical section order per preset. `composePublicSections` applies per-preset preferences (`sectionOrder`, `hiddenSections`) over the canonical content order without mutating published sections, and components receive a resolved preset definition instead of naming a preset.
- Shared components under `resources/js/components/public/` cover container, section heading, breadcrumb, CTA, article card/list, pagination, sanitized rich text, and contact information. `PublicPageContent` composes hero/introduction/services/strengths/articles/contact sections for all five page kinds without preset-name conditions, hides empty optional sections, and falls back to a plain page header with a single h1 when no hero section leads the page.
- `PublicLayout`, `PublicNavigation`, and `PublicFooter` were extended in place: the shell reads the preset width from the same registry, falls back to the pre-website behavior when no `website` prop exists (default Home item in navigation, existing `AppFooter`), and keeps the Dashboard button gated by `publicContext`.
- `HomeController` now passes the published home presentation built by `BuildPublicHome`: sections/body/cover/SEO resolved through the public read Actions, with a settings-derived fallback hero when home content is not published. Only published snapshot fields compose the response. Section and page images resolve to relative URLs served by the new `website.image` route behind `EnsurePublicPagesEnabled` with `Cache-Control: no-store, private` and `X-Content-Type-Options: nosniff`, keeping draft, unused, future, and unpublished images private.
- Section headings carry an id matching their `aria-labelledby` (`{id}-title`) through `PublicHeading`; the preset fixture asserts every `aria-labelledby` target exists on both the SSR markup and the hydrated document.
- The hydration fixture (`tests/Browser/Fixtures/public-preset.ts`) mounts the real components under `createSSRApp` with vue-i18n, driven by `tests/Browser/public-presets.mjs`, which renders through `@vue/server-renderer` into an in-process Vite dev server (`charset=utf-8`). It exercises 3 presets × 5 page kinds × 4 content states (empty, typical, long, missing-image) in server rendering and hydration, asserting hidden/empty sections stay out, exactly one h1, no dangling `aria-labelledby`, no horizontal overflow at 390/1440, zero console errors or hydration warnings, reduced motion, section preferences, and anchor CTA interaction, in both locales. It uses only existing dependencies, no production fixture route, and runs standalone with `node tests/Browser/public-presets.mjs`; it is a component-level complement to the real-page browser tests, not a replacement for the full `lerd composer test` gate. The fixture imports the public components through the `@/` alias and is type-checked via the tsconfig include.
- New `lang/{en,id}/public.php` translations (breadcrumb/pagination labels, location button, empty-articles status) use neutral wording and identical key order; the existing Vite translation exporter produces `resources/js/lang/messages.json`.

## Scope 04 implementation notes

- The Website admin area lives under `routes/website.php` (12 routes): an authenticated overview hub (`website/Index`, `GetWebsiteArea` reports the active preset, per-page status, and preset options), page editors, settings, preview, and image serving. Authorization reuses the existing permission system: `website.content.edit` / `website.content.publish` / `website.configure`, enforced with `->can()` middleware on every mutation route and explicit `Gate`/`abort_unless` guards on read endpoints, so direct requests (not only UI affordances) are denied.
- Structure: enums `WebsitePage` (home/about/contact/articles-index) and `WebsitePreset`; actions `GetWebsiteArea`, `GetWebsitePageEditor`, `SaveWebsiteDraft`, `PublishWebsiteContent` (extended), `UnpublishWebsiteContent`, `UploadWebsiteImage`, `ListWebsiteImages`, `GetWebsiteImage`, `BuildWebsitePreview`; controllers under `app/Http/Controllers/Website/`; requests under `app/Http/Requests/Website/`.
- Structured editors (`website/Content.vue` + `WebsiteSectionEditor`) cover hero/introduction/services/strengths/articles/contact sections with add/remove, reordering, visibility, CTA configuration, and image/alt controls backed by the `website-images` media collection. Images can only be uploaded after the first draft save (media belongs to the WebsiteContent row); validation rejects duplicate section ids, non-`http(s)` CTA URLs, oversized fields, and unknown section types. Rich-body render failures surface on one global `content_json` key; every other error reports on its input path.
- Shared `WebsiteSeoFields` (title/description/robots/image) is used by page editors and settings; settings carry the SEO defaults plus identity, navigation/footer, contact channels/links/social links, and preferences. `ValidateWebsiteSettings` reports errors on flat input paths (e.g. `contact.social_links.0.href`) via an unknown-field rejecting wrapper. Validation/save feedback comes through Inertia flash toasts and per-field error banners; all strings are localized in `lang/{en,id}/website.php`.
- Draft lifecycle: Save draft (version guarded, stale submissions rejected with `website.error.stale_draft`), Preview (separate `public/Preview` page, `noindex`, banner with the preview mode; preset query override only affects the preview request), Publish (snapshot strips editor-only JSON so public payloads never carry it), and Unpublish; the preview image route stays private (`no-store`) and only streams media referenced by a draft the actor may preview or by a live publication. Home keeps a stable `/` route with a meaningful unpublished state (existing fallback hero), and the audit log records draft/publish/unpublish/settings events.
- Preset switching (`website/preset`) changes only the settings row; published snapshots, URLs, and content survive switches, and the picker cards show server-localized descriptions, the current selection, an authenticated preview link per preset, and an explicit Apply with a confirm dialog. Public pages keep rendering from settings + published content only, so preview state can never leak into the live site.

## Evidence register

Update with each scope's local commit. Record commands/results, SSR matrix evidence, manual review results, and any unresolved gate. Do not mark work complete based only on the presence of a test or configuration flag.

| Scope | State            | Validation evidence                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         | Remaining gate                                                                                                                                                                                           |
| ----- | ---------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 01    | Complete locally | Focused feature tests: 52 passed; focused browser tests: 5 passed. Production-style `lerd composer build:ssr`: exit 0. Final environment-only `lerd composer test`: exit 0; backend 583 passed / 4 skipped, coverage and type coverage 100%; browser 44 passed / 3 warnings (no details emitted). Sampled PHP container peak 2047 MiB; minimum host available 3617 MiB; cgroup max/oom/oom_kill events 0.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   | New content routes must use EnsurePublicPagesEnabled; full runtime/preset SSR matrix belongs to 07; manual accessibility belongs to 08.                                                                  |
| 02    | Complete locally | Website publishing/settings/media regression: 49 passed. Full backend feature/unit regression (`lerd composer test:feature`): 644 passed / 4 skipped. PHPStan: 0 errors; Rector dry run: 0 changes; Pint passed; type coverage 100%. Additive local migrations and permission initialization: exit 0.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       | Admin/public HTTP integration belongs to 03–06; runtime query capture begins when CMS routes consume these Actions. Full coverage/browser/SSR acceptance gate remains 07–08.                             |
| 03    | Complete locally | Focused feature tests (`tests/Feature/PublicPresetTest.php`): 6 passed / 80 assertions, exit 0. Focused browser regression (`tests/Browser/ErrorPublicHomeBrowserTest.php`): 5 passed / 25 assertions, 3 warnings without details, matching the baseline. Standalone preset SSR/hydration fixture (`node tests/Browser/public-presets.mjs`): 60 SSR + hydration cases (3 presets × 5 page kinds × 4 content states), 120 viewport overflow checks at 390/1440, both locales, reduced motion, section preferences, anchor interaction, zero console errors/warnings. Full backend regression (`pest --compact --parallel --exclude-testsuite=Browser`): 650 passed / 4 skipped of 654 tests. Production-style `pnpm run build:ssr` (client + SSR bundle): exit 0. `pnpm run check` and `pnpm run types:check`: pass. Pint pass; Rector dry run 0 changes; PHPStan 0 errors.                                  | Real public routes beyond Home belong to 04–06; complete runtime SEO/SSR matrix belongs to 07; `lerd composer test` gate and manual accessibility belong to 08.                                          |
| 04    | Complete locally | Focused admin-area feature tests (`tests/Feature/WebsiteAdminAreaTest.php`): 45 passed / 231 assertions, exit 0 (permissions via direct requests, invalid submissions on clean error keys, stale version conflicts, draft save/publish/unpublish lifecycle, preview isolation per role, image gating, preset apply/switch/back, settings CRUD + audit). Focused publishing/preset regression (`WebsitePublishingTest.php` + `PublicPresetTest.php`): 55 passed / 264 assertions. Full backend regression (`pest --compact --parallel --exclude-testsuite=Browser`): 695 passed / 4 skipped of 699 tests. PHPStan: 0 errors. Pint pass. Rector applied (const typing, unused import) then clean. `pnpm run check` (lint + types over 186 files) and `pnpm run types:check` (strict vue-tsc): pass. Production-style `pnpm run build:ssr` (client + SSR bundle, lang export, Wayfinder regeneration): exit 0. | Real article routes/slug behavior belongs to 05; runtime SEO/SSR matrix + browser coverage belong to 07; `lerd composer test` gate and manual accessibility belong to 08.                                |
| 05    | Complete locally | Article HTTP regression: 19 passed / 170 assertions. Full backend regression (`lerd composer test:feature`): 714 passed / 4 skipped. Article browser flows: 4 passed / 59 assertions, including desktop/mobile reading in all three presets and production SSR rendering of actual HTTP page props. Existing preset matrix: 60 SSR/hydration cases and 120 viewport checks, both locales, no console warnings/errors. `pnpm run check`, strict `types:check`, production client + SSR build: pass. PHPStan 0 errors; Rector dry run 0 changes; Pint pass; type coverage 100%. Impeccable detector: no findings; scoped visual review: ship. Live initial `/articles` HTML includes heading/metadata after restarting stale Vite worker; query capture reports no slow-route findings (small local sample), and list/card query count stays at 2.                                                            | Complete SEO/runtime matrix remains scope 07; complete `lerd composer test` gate and manual accessibility remain scope 08. No automatic scheduling; future publication dates are rejected by admin HTTP. |
| 06    | Pending          | —                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | Form/inbox security and failure-mode tests                                                                                                                                                               |
| 07    | Pending          | —                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | SEO and production-style SSR matrix                                                                                                                                                                      |
| 08    | Pending          | —                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           | Integrated review and complete local gate                                                                                                                                                                |

## Explicit minimum-scope boundaries

No arbitrary page builder, custom CSS editor, multi-template marketplace, article comments, newsletter, advanced revision history, automated scheduling, or translated content workflow. Existing interface localization remains supported; multilingual content and hreflang require a separate scope. Use existing dependencies unless an additional dependency is explicitly approved.

Only the roadmap is delivered in the documentation scope. Implementation scopes above remain pending until requested and verified.
