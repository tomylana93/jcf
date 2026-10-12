# Serena Semantic Navigation and Refactoring

Use Serena's semantic tools instead of reading whole files when exploring or editing PHP and TypeScript code. Active language servers: `typescript`, `php_phpantom`. Line numbers from Serena tools are 0-based.

## Navigation (prefer over full-file reads)

- Start new files with `get_symbols_overview` for a symbol outline.
- Use `find_symbol` with `depth > 0` to list children (e.g. class methods), then re-query with `include_body=True` only for the symbols you need.
- Use `find_declaration` to jump from a usage to its declaration.
- Use `find_referencing_symbols` to map callers before changing a symbol's signature or behavior.
- Use `find_implementations` for interface/abstract method implementations.
- Use `search_for_pattern` only when the symbol name or location is unknown; then continue with symbolic tools.
- Use `get_diagnostics_for_file` to check errors for a touched file.
- Once a full file is read, do not re-analyze it with symbolic tools.

## Refactoring (prefer over hand-edits)

- Prefer `rename_symbol` and `safe_delete_symbol`: they are reference-aware across declarations, references, overrides, and imports. On success the refactor is complete; do not re-read files just to confirm propagation.
- Replace a whole symbol via `replace_symbol_body`; insert code via `insert_after_symbol` / `insert_before_symbol`.
- For partial edits inside a symbol, use `replace_content` (regex mode with tight wildcards like `start.*?end` for long spans).
- For one edit across many files, use `replace_in_files` with `dry_run=True` first, then apply by `occurrence_ids` (or `expected_count` guard).
- Batch independent Serena calls in parallel; chain sequentially only on real dependencies.

## Boundaries

- Keep edits backward-compatible or update all references found via `find_referencing_symbols`.
- Do not use Serena memory tools in this project.