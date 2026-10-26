#!/usr/bin/env python3
"""Build an installable Joomla package from the checked-in source (stdlib only)."""

import argparse
import hashlib
import io
from datetime import date
from pathlib import Path
import xml.etree.ElementTree as ET
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo


def source_files(directory):
    files = {}
    for path in sorted(directory.rglob('*')):
        if path.is_symlink():
            raise ValueError(f'Symlinks are not package source files: {path}')
        if path.is_file():
            files[path.relative_to(directory).as_posix()] = path.read_bytes()
    return files


def archive_bytes(files, timestamp):
    output = io.BytesIO()
    with ZipFile(output, 'w', ZIP_DEFLATED) as archive:
        for name, data in sorted(files.items()):
            entry = ZipInfo(name, timestamp)
            entry.compress_type = ZIP_DEFLATED
            entry.create_system = 3
            entry.external_attr = 0o100644 << 16
            archive.writestr(entry, data)
    return output.getvalue()


def build_package(root, output_dir):
    manifest = ET.parse(root / 'package/pkg_jtseo.xml').getroot()
    version = manifest.findtext('version')
    if not version or not all(c.isdigit() or c == '.' for c in version):
        raise ValueError('Expected a numeric package version')
    release_date = date.fromisoformat(manifest.findtext('creationDate'))
    timestamp = (release_date.year, release_date.month, release_date.day, 0, 0, 0)
    files = source_files(root / 'package')
    for item in manifest.findall('./files/file'):
        name = item.text.strip()
        if Path(name).name != name or not name.endswith('.zip'):
            raise ValueError(f'Invalid child package filename: {name}')
        directory = root / 'extensions' / name.removesuffix('.zip')
        if not directory.is_dir():
            raise ValueError(f'Missing extension source: {directory}')
        files['packages/' + name] = archive_bytes(source_files(directory), timestamp)
    data = archive_bytes(files, timestamp)
    output_dir.mkdir(parents=True, exist_ok=True)
    target = output_dir / f'pkg_jtseo_v{version}.zip'
    target.write_bytes(data)
    return target


def main():
    root = Path(__file__).resolve().parents[1]
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output-dir', type=Path, default=root / 'dist')
    args = parser.parse_args()
    target = build_package(root, args.output_dir.resolve())
    print(target)
    print(f'SHA-256: {hashlib.sha256(target.read_bytes()).hexdigest()}')


if __name__ == '__main__':
    main()
