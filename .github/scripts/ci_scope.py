"""Only skip application checks for a complete, documentation-only PR diff."""

import json
import sys


def is_documentation(path: object) -> bool:
    return isinstance(path, str) and bool(path) and (
        path.startswith("docs/") or path == "LICENSE" or path.endswith(".md")
    )


def classify_changes(pages: object, expected_count: int) -> str:
    if expected_count <= 0 or not isinstance(pages, list):
        return "ci"

    if not all(isinstance(page, list) for page in pages):
        return "ci"

    files = [file for page in pages for file in page]
    if len(files) != expected_count:
        return "ci"

    for file in files:
        if not isinstance(file, dict) or not is_documentation(file.get("filename")):
            return "ci"
        if file.get("status") not in {"added", "modified", "removed", "renamed", "copied"}:
            return "ci"
        if file["status"] == "renamed" and not is_documentation(file.get("previous_filename")):
            return "ci"
        if "previous_filename" in file and not is_documentation(file["previous_filename"]):
            return "ci"

    return "docs"


def main() -> None:
    try:
        mode = classify_changes(json.load(sys.stdin), int(sys.argv[1]))
    except (ValueError, IndexError):
        mode = "ci"
    print(mode)


if __name__ == "__main__":
    main()
