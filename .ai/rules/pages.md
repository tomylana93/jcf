---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Application page containers and document heads
Place Head directly before PageContainer with a blank line between them and no wrapper solely to enforce one root. Application pages use PageContainer directly or inherit it through the account layout; account pages render one section wrapper whose first child is Head, followed by a blank line, and use Heading for section titles because the layout already owns the page h1. Retain the dedicated auth, public and error layouts.

## Page-local table column factories
Define datatable columns in a page-local columns.ts exporting createColumns; inject translation, date formatting (formatDateTime from useDateTime), permissions and action callbacks from the page as one context object extending DataTableColumnContext, and recompute columns when that context changes. Check permissions with can() from usePermissions instead of reading auth.permissions directly.

## Reusable operational status presentation
Use the shared StatusBadge with a translated label and semantic tone for operational statuses; keep domain-specific status-to-tone mappings in the consuming page.

## Shared pagination and bodiless mutations
Paginate non-datatable lists with the shared SimplePagination component and the table.pagination translations. Submit bodiless button actions through useForm({}).submit() with a Wayfinder route so processing state is available; reserve useHttp for JSON endpoints that do not perform an Inertia visit.

## Empty and loading states
Render empty collections with the Empty primitives (EmptyMedia icon, EmptyTitle, optional EmptyDescription) and loading placeholders with Skeleton instead of hand-written text, divs or animate-pulse utilities.

## Settings form feedback
Settings forms with text or number inputs use useForm().withPrecognition() with field validation on blur, a visually hidden (sr-only) role="status" validating message, and a backend route with HandlePrecognitiveRequests; file-upload forms submit normally. Submit buttons show Spinner while processing, replacing the leading icon when one exists.

## Copy placement follows the lang wording rules
Pages and components follow the wording rules in .ai/rules/lang.md: use each translation key only in its own position (list card.description not in sheets, dialogs or detail pages; button.* not as titles; navigation.* not as page titles), render labels instead of raw codes, field names, enum values or JSON, show statuses only when informative, and gate local-only hints behind an environment prop from the controller.
