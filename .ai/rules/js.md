---
paths:
  - 'resources/js/**'
---

# Js

## Square Tailwind utilities
Use size-* instead of matching h-* and w-* utilities for square dimensions, including responsive variants. Preserve separate width and height utilities for rectangular elements and leave CLI-managed UI primitives unchanged.

## Frontend module boundaries and typing
Put pure functions and one-time global initializers in resources/js/lib and functions that use Vue reactivity or usePage in resources/js/composables. Keep entity, row and form types in resources/js/types/<domain>.ts, mirror backend enums as named unions covered by FrontendEnumMirrorTest, and import Wayfinder actions and routes with named imports only. Declare page props inline with defineProps and write handlers as function declarations with explicit return types.
