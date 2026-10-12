---
paths:
  - 'app/Tables/**'
---

# Tables

## Shared Table query contracts
Implement tabular Eloquent queries as domain-specific subclasses of App\Tables\Table, defining the base query, allowed filters, allowed sorts, and default sort. Reuse the inherited pagination and export methods so both consume the same filtered query.
