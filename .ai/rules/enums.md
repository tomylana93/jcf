---
paths:
  - 'app/Enums/**'
---

# Enums

## Shared translated enum options
Define UI option enums as string-backed enums with PascalCase cases and the HasLabel and HasOptions concerns. Place labels under enums.<snake_case_enum_name>.<CaseName> and expose choices through options().
Represent persisted domain states and audit events as string-backed enums, such as AuditEvent, DocumentationStatus and OperationStatus, instead of string literals; record new audit events as AuditEvent cases with labels under enums.audit_event.
