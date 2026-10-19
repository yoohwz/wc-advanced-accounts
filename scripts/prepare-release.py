#!/usr/bin/env python3
"""Prepare or verify a Core WordPress.org payload. Never publishes anything."""
import argparse
import hashlib
import io
import json
from pathlib import Path, PurePosixPath
import re
import subprocess
import zipfile

SLUG = 'wc-advanced-accounts'
ROOT_FILES = {SLUG + '.php', 'readme.txt', 'changelog.txt', 'license.txt'}
PRODUCT_DIRS = {'inc', 'css', 'js', 'font', 'img', 'languages'}


def git(*args):
    return subprocess.check_output(['git', *args])


def digest(data):
    return hashlib.sha256(data).hexdigest()


def product_path(name):
    path = PurePosixPath(name)
    return (name in ROOT_FILES or path.parts[0] in PRODUCT_DIRS) and not any(
        part.startswith('.') or part in {'node_modules', '__pycache__'} for part in path.parts
    ) and path.suffix.lower() not in {'.zip', '.log', '.bak', '.tmp', '.pyc'}


def source_files(sha):
    files = {}
    for record in git('ls-tree', '-rz', sha).split(b'\0'):
        if not record:
            continue
        metadata, raw_name = record.split(b'\t', 1)
        name = raw_name.decode('utf-8')
        if not product_path(name):
            continue
        mode, kind, oid = metadata.split()
        if kind != b'blob' or mode not in {b'100644', b'100755'}:
            raise ValueError('Non-regular product file: ' + name)
        files[name] = git('cat-file', 'blob', oid.decode())
    if not ROOT_FILES <= files.keys():
        raise ValueError('Missing required product files')
    return dict(sorted(files.items()))


def validate_version(files, version):
    if not re.fullmatch(r'\d+(?:\.\d+){1,3}', version):
        raise ValueError('Expected numeric release version')
    header = files[SLUG + '.php'].decode()
    readme = files['readme.txt'].decode()
    match = re.search(r'^\s*\* Version:\s*(\S+)', header, re.M)
    stable = re.search(r'^Stable tag:\s*(\S+)', readme, re.M)
    if not match or not stable or match[1] != version or stable[1] != version:
        raise ValueError('Version/Stable tag mismatch')
    for name, content in [('readme.txt', readme), ('changelog.txt', files['changelog.txt'].decode())]:
        section = content.split('== Changelog ==', 1)[-1] if name == 'readme.txt' else content
        entries = re.split(r'^= ([^\n]+) =\s*$', section, flags=re.M)
        if len(entries) < 3 or not re.fullmatch(re.escape(version) + r'(?: \([^\n]+\))?', entries[1]):
            raise ValueError('Wrong newest changelog: ' + name)
        if not entries[2].strip():
            raise ValueError('Empty newest changelog: ' + name)


def package(files):
    stream = io.BytesIO()
    with zipfile.ZipFile(stream, 'w', compression=zipfile.ZIP_STORED) as archive:
        for name, data in sorted(files.items()):
            info = zipfile.ZipInfo(SLUG + '/' + name, (1980, 1, 1, 0, 0, 0))
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            archive.writestr(info, data)
    return stream.getvalue()


def payload_files(directory):
    if directory.is_symlink() or not directory.is_dir():
        raise ValueError("Payload root must be a real directory")
    files = {}
    for path in directory.rglob('*'):
        if path.is_symlink():
            raise ValueError('Symlink in payload')
        if path.is_file():
            files[path.relative_to(directory).as_posix()] = path.read_bytes()
    return files


def manifest(sha, version, files, zipped):
    return {'slug': SLUG, 'candidate_sha': sha, 'source_tree': git('rev-parse', sha + '^{tree}').decode().strip(),
            'version': version, 'zip_sha256': digest(zipped),
            'files': {name: digest(data) for name, data in files.items()}}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--candidate', required=True, help='Exact accepted merged source SHA')
    parser.add_argument('--version', required=True)
    operation = parser.add_mutually_exclusive_group(required=True)
    operation.add_argument('--output', type=Path, help='New directory outside this checkout')
    operation.add_argument('--verify', type=Path, help='Existing preparation directory; read only')
    args = parser.parse_args()
    if not re.fullmatch('[0-9a-f]{40}', args.candidate):
        raise ValueError('Candidate must be a full SHA')
    root = Path(git('rev-parse', '--show-toplevel').decode().strip()).resolve()
    if git('rev-parse', 'HEAD').decode().strip() != args.candidate:
        raise ValueError('Checkout must equal the accepted candidate')
    if git('status', '--porcelain', '--untracked-files=normal').strip():
        raise ValueError('Source checkout must be clean')
    if git('rev-parse', 'origin/main').decode().strip() != args.candidate:
        raise ValueError('Candidate must equal freshly fetched origin/main')
    files = source_files(args.candidate)
    validate_version(files, args.version)
    zipped = package(files)
    if zipped != package(files):
        raise ValueError('Non-deterministic ZIP')
    expected = manifest(args.candidate, args.version, files, zipped)
    archive_name = SLUG + '-' + args.version + '.zip'
    if args.verify:
        directory = args.verify.resolve()
        if json.loads((directory / 'release-manifest.json').read_text()) != expected:
            raise ValueError('Manifest does not match source')
        if (directory / archive_name).read_bytes() != zipped or payload_files(directory / 'payload' / SLUG) != files:
            raise ValueError('Prepared ZIP/payload does not match source')
        print('VERIFIED', args.candidate, args.version, expected['zip_sha256'])
        return
    directory = args.output.resolve()
    if directory == root or root in directory.parents:
        raise ValueError('Output must be outside the source checkout')
    directory.mkdir(parents=True, exist_ok=False)
    for name, data in files.items():
        target = directory / 'payload' / SLUG / name
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(data)
    (directory / archive_name).write_bytes(zipped)
    (directory / 'release-manifest.json').write_text(json.dumps(expected, indent=2) + '\n')
    print('PREPARED', args.candidate, args.version, expected['zip_sha256'])


if __name__ == '__main__':
    main()
