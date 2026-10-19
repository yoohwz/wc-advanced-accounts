"""Focused release and exact-candidate failure regressions; no live publication."""
import copy
import importlib.util
import io
from pathlib import Path
import tempfile
import subprocess
import sys
import unittest
import zipfile


def load(name, path):
    spec = importlib.util.spec_from_file_location(name, Path(__file__).resolve().parents[1] / path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


release = load('release', 'scripts/prepare-release.py')
ci = load('ci', 'scripts/check-ci-pr.py')


class ReleaseTests(unittest.TestCase):
    def files(self):
        return {release.SLUG + '.php': b' * Version: 1.5.0\n',
                'readme.txt': b'Stable tag: 1.5.0\n== Changelog ==\n= 1.5.0 (Oct 19, 2026) =\nFix: Example\n',
                'changelog.txt': b'= 1.5.0 (Oct 19, 2026) =\nFix: Example\n', 'license.txt': b'GPL'}

    def test_package_is_reproducible_and_installable(self):
        files = self.files()
        files['inc/.hidden.php'] = b'fixture'
        release.validate_version(files, '1.5.0')
        zipped = release.package(files)
        self.assertEqual(zipped, release.package(dict(reversed(list(files.items())))))
        with zipfile.ZipFile(io.BytesIO(zipped)) as archive:
            self.assertEqual(set(archive.namelist()), {release.SLUG + '/' + name for name in files})
            for name, data in files.items():
                self.assertEqual(data, archive.read(release.SLUG + '/' + name))

    def test_distribution_excludes_development_and_backups(self):
        for path in ['AGENTS.md', '.github/workflows/membership.yml', 'tests/role-ownership.php',
                     'scripts/prepare-release.py', 'docs/workflow.md', 'inc/.DS_Store',
                     'inc/debug.log', 'js/node_modules/x.js', 'inc/archive.zip', 'inc/file.php.bak']:
            self.assertFalse(release.product_path(path), path)
        for path in ['inc/cores/helper/role-ownership.php', 'font/fontawesome/metadata/icons-solid-list.json',
                     'inc/backend/actions/data/phone_country_codes.conf', 'readme.txt']:
            self.assertTrue(release.product_path(path), path)

    def test_rejects_mismatched_or_empty_release_metadata(self):
        for path, value in [('readme.txt', b'Stable tag: 1.4.5\n'),
                            ('changelog.txt', b'= 1.4.5 =\nOld\n'),
                            ('changelog.txt', b'= 1.5.0 =\n\n= 1.4.5 =\nOld\n')]:
            files = self.files()
            files[path] = value
            with self.assertRaises(ValueError):
                release.validate_version(files, '1.5.0')
        with self.assertRaises(ValueError):
            release.validate_version(self.files(), '../1.5')

    def test_payload_detects_extra_bytes_and_symlinks(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            (root / 'a.php').write_bytes(b'a')
            expected = {'a.php': b'a'}
            self.assertEqual(release.payload_files(root), expected)
            (root / 'extra.php').write_bytes(b'extra')
            self.assertNotEqual(release.payload_files(root), expected)
            (root / 'link').symlink_to(root / 'a.php')
            with self.assertRaises(ValueError):
                release.payload_files(root)


class PreparationCommandTests(unittest.TestCase):
    def test_prepare_verify_and_failure_guards_in_disposable_git(self):
        helper = Path(__file__).resolve().parents[1] / 'scripts/prepare-release.py'
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp) / 'source'
            root.mkdir()
            def git(*args):
                return subprocess.check_output(['git', '-C', str(root), *args], stderr=subprocess.STDOUT).decode().strip()
            git('init', '-q')
            for name, data in ReleaseTests().files().items():
                (root / name).write_bytes(data)
            (root / 'tests').mkdir()
            (root / 'tests/dev.php').write_text('development only')
            git('add', '.')
            git('-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'commit', '-qm', 'fixture')
            sha = git('rev-parse', 'HEAD')
            git('update-ref', 'refs/remotes/origin/main', sha)
            rc = Path(tmp) / 'rc'
            def run(*args):
                return subprocess.run([sys.executable, str(helper), '--candidate', sha,
                                       '--version', '1.5.0', *map(str, args)], cwd=root, capture_output=True)
            self.assertEqual(run('--output', rc).returncode, 0)
            self.assertEqual(run('--verify', rc).returncode, 0)
            self.assertFalse((rc / 'payload' / release.SLUG / 'tests').exists())
            self.assertNotEqual(run('--output', rc).returncode, 0)
            payload = rc / 'payload' / release.SLUG / 'license.txt'
            payload.write_bytes(b'tampered')
            self.assertNotEqual(run('--verify', rc).returncode, 0)
            payload.write_bytes(b'GPL')
            archive = rc / (release.SLUG + '-1.5.0.zip')
            original = archive.read_bytes()
            archive.write_bytes(original + b'extra')
            self.assertNotEqual(run('--verify', rc).returncode, 0)
            archive.write_bytes(original)
            self.assertEqual(run('--verify', rc).returncode, 0)
            (root / 'untracked.txt').write_text('dirty')
            self.assertNotEqual(run('--verify', rc).returncode, 0)
            (root / 'untracked.txt').unlink()
            self.assertNotEqual(run('--output', root / 'rc').returncode, 0)
            (root / 'inc').mkdir()
            (root / 'inc/link.php').symlink_to('../license.txt')
            git('add', '.')
            git('-c', 'user.name=Fixture', '-c', 'user.email=fixture@example.invalid', 'commit', '-qm', 'symlink')
            next_sha = git('rev-parse', 'HEAD')
            git('update-ref', 'refs/remotes/origin/main', next_sha)
            result = subprocess.run([sys.executable, str(helper), '--candidate', next_sha,
                                     '--version', '1.5.0', '--output', str(Path(tmp) / 'other')], cwd=root, capture_output=True)
            self.assertNotEqual(result.returncode, 0)
            self.assertIn(b'Non-regular product file', result.stderr)


class CandidateTests(unittest.TestCase):
    def candidate(self):
        return {'number': 4, 'state': 'open', 'draft': False,
                'head': {'sha': 'a' * 40, 'repo': {'full_name': 'yoohwz/wc-advanced-accounts'}},
                'base': {'sha': 'b' * 40, 'ref': 'main', 'repo': {'full_name': 'yoohwz/wc-advanced-accounts'}}}

    def check(self, event, current):
        ci.verify(event, current, 'yoohwz/wc-advanced-accounts', 'a' * 40, 'b' * 40)

    def test_current_final_candidate(self):
        current = self.candidate()
        self.check({'action': 'ready_for_review', 'pull_request': current}, current)

    def test_rejects_moved_head_base_pr_state_or_wrong_event(self):
        original = self.candidate()
        event = {'action': 'ready_for_review', 'pull_request': original}
        for path, value in [(('head', 'sha'), 'c' * 40), (('base', 'sha'), 'c' * 40),
                            (('base', 'ref'), 'other'), (('state',), 'closed'),
                            (('draft',), True), (('number',), 5),
                            (('base', 'repo', 'full_name'), 'other/repo')]:
            current = copy.deepcopy(original)
            target = current
            for key in path[:-1]:
                target = target[key]
            target[path[-1]] = value
            with self.assertRaises(AssertionError):
                self.check(event, current)
        with self.assertRaises(AssertionError):
            self.check({'action': 'synchronize', 'pull_request': original}, original)

    def test_draft_checks_are_allowed(self):
        current = self.candidate()
        current['draft'] = True
        self.check({'action': 'synchronize', 'pull_request': current}, current)


if __name__ == '__main__':
    unittest.main()
