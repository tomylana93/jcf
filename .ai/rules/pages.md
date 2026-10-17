---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Application page containers and document heads
Place Head directly before PageContainer with a blank line between them and no wrapper solely to enforce one root. Application pages use PageContainer directly or inherit it through the account layout; retain the dedicated auth, public and error layouts.

## Page-local table column factories
Define datatable columns in a page-local columns.ts exporting createColumns; inject translation, date formatting, permissions and action callbacks from the page, and recompute columns when that context changes.

## Reusable operational status presentation
Use the shared StatusBadge with a translated label and semantic tone for operational statuses; keep domain-specific status-to-tone mappings in the consuming page.
