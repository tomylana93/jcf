import subprocess
import sys
import unittest
from pathlib import Path

from ci_scope import classify_changes


class ChangeScopeTest(unittest.TestCase):
    def test_documentation_only_changes_can_skip_application_checks(self):
        files = [
            {"filename": "docs/guide.md", "status": "modified"},
            {"filename": "README.md", "status": "added"},
            {"filename": "LICENSE", "status": "removed"},
        ]
        self.assertEqual(classify_changes([files], 3), "docs")

    def test_rename_from_code_to_documentation_runs_ci(self):
        for original in ["app/Actions/DeleteUser.php", ".github/workflows/tests.yml"]:
            with self.subTest(original=original):
                files = [{"filename": "docs/archive.md", "previous_filename": original, "status": "renamed"}]
                self.assertEqual(classify_changes([files], 1), "ci")

    def test_rename_from_documentation_to_code_runs_ci(self):
        files = [{"filename": "app/Action.php", "previous_filename": "docs/action.md", "status": "renamed"}]
        self.assertEqual(classify_changes([files], 1), "ci")

    def test_rename_between_documentation_paths_can_skip(self):
        files = [{"filename": "docs/new.md", "previous_filename": "README.md", "status": "renamed"}]
        self.assertEqual(classify_changes([files], 1), "docs")

    def test_missing_rename_origin_runs_ci(self):
        self.assertEqual(classify_changes([[{"filename": "docs/new.md", "status": "renamed"}]], 1), "ci")

    def test_code_removal_and_release_or_dependency_metadata_run_ci(self):
        for path in ["app/Action.php", "composer.lock", "version.txt", ".release-please-manifest.json", "release-please-config.json"]:
            with self.subTest(path=path):
                files = [{"filename": "README.md", "status": "modified"}, {"filename": path, "status": "removed"}]
                self.assertEqual(classify_changes([files], 2), "ci")

    def test_later_pages_are_checked(self):
        pages = [[{"filename": "README.md", "status": "modified"}], [{"filename": "app/Action.php", "status": "added"}]]
        self.assertEqual(classify_changes(pages, 2), "ci")

    def test_incomplete_or_changed_api_result_runs_ci(self):
        pages = [[{"filename": "README.md", "status": "modified"}]]
        for expected in [0, 2, 3001]:
            with self.subTest(expected=expected):
                self.assertEqual(classify_changes(pages, expected), "ci")

    def test_empty_or_malformed_data_runs_ci(self):
        for pages in [[], {}, [None], [[None]], [[{"filename": None}]], [[{"filename": "README.md", "status": "unknown"}]]]:
            with self.subTest(pages=pages):
                self.assertEqual(classify_changes(pages, 1), "ci")

    def test_cli_falls_back_to_ci_for_invalid_input(self):
        script = Path(__file__).with_name("ci_scope.py")
        for data, count in [("not json", "1"), ("[]", "invalid"), ("[]", "0")]:
            with self.subTest(data=data, count=count):
                result = subprocess.run([sys.executable, "-B", str(script), count], input=data, text=True, capture_output=True, check=True)
                self.assertEqual(result.stdout.strip(), "ci")


if __name__ == "__main__":
    unittest.main()
