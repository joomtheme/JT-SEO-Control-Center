"""Validate release metadata, source fidelity and the Joomla installation archive."""

import hashlib
import io
import json
from pathlib import Path
import re
import sys
import tempfile
import unittest
import xml.etree.ElementTree as ET
from zipfile import ZipFile

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'tools'))
from build import build_package


def zip_contents(data):
    with ZipFile(io.BytesIO(data)) as archive:
        if archive.testzip() is not None:
            raise AssertionError('Corrupt ZIP entry')
        return {name: archive.read(name) for name in archive.namelist() if not name.endswith('/')}


class ReleaseTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.manifest = ET.parse(ROOT / 'package/pkg_jtseo.xml').getroot()
        cls.version = cls.manifest.findtext('version')
        cls.filename = f'pkg_jtseo_v{cls.version}.zip'
        cls.release = (ROOT / 'downloads' / cls.filename).read_bytes()
        cls.update = ET.parse(ROOT / 'updates/update.xml').getroot().find('update')
        cls.published_version = cls.update.findtext('version')
        cls.published_filename = f'pkg_jtseo_v{cls.published_version}.zip'
        cls.published_release = (ROOT / 'downloads' / cls.published_filename).read_bytes()

    def test_update_metadata_matches_published_artifact(self):
        expected = hashlib.sha256(self.published_release).hexdigest()
        self.assertEqual(self.update.findtext('sha256'), expected)
        self.assertEqual(self.update.findtext('version'), self.published_version)
        self.assertEqual(self.update.findtext('element'), 'pkg_jtseo')
        self.assertEqual(self.update.findtext('type'), 'package')
        self.assertEqual(self.update.find('targetplatform').get('version'), '6.*')
        self.assertEqual(self.update.findtext('php_minimum'), '8.3.0')
        self.assertEqual(self.update.findtext('./downloads/downloadurl'),
                         f'https://github.com/joomtheme/JT-SEO-Control-Center/releases/download/v{self.published_version}/{self.published_filename}')
        checksum, filename = (ROOT / 'downloads/SHA256SUMS.txt').read_text().split()
        self.assertEqual((checksum, filename), (expected, self.published_filename))

    def test_approved_artifact_and_previous_release_are_preserved(self):
        previous = (ROOT / 'downloads/pkg_jtseo_v1.0.69.zip').read_bytes()
        previous_checksum = hashlib.sha256(previous).hexdigest()
        self.assertEqual(previous_checksum,
                         '3c83151265975c92d960713031d1c0cd68720e254c2534f50f0df513d687e60d')
        self.assertEqual((ROOT / 'downloads/SHA256SUMS-1.0.69.txt').read_text().split(),
                         [previous_checksum, 'pkg_jtseo_v1.0.69.zip'])
        self.assertEqual(hashlib.sha256(self.release).hexdigest(),
                         '3d2442be79182924993fa599768662a918b212c15aeb007dfbe8edc2eea61391')
        self.assertEqual(self.version, self.published_version)
        self.assertEqual(self.update.findtext('./tags/tag'), 'stable')

    def test_current_changelog_is_first_and_unique(self):
        changelogs = ET.parse(ROOT / 'updates/changelog.xml').getroot().findall('changelog')
        self.assertEqual(changelogs[0].findtext('version'), self.published_version)
        self.assertEqual(sum(c.findtext('version') == self.published_version for c in changelogs), 1)
        self.assertEqual(changelogs[0].findtext('element'), 'pkg_jtseo')
        self.assertTrue(changelogs[0].findall('./fix/item'))

    def test_extension_asset_health_and_schema_versions_agree(self):
        for source in (ROOT / 'extensions').iterdir():
            self.assertEqual(ET.parse(source / 'jtseo.xml').findtext('version'), self.version)
        assets = json.loads((ROOT / 'extensions/com_jtseo/media/joomla.asset.json').read_text())
        self.assertEqual(assets['version'], self.version)
        for asset in assets['assets']:
            self.assertEqual(asset['version'], self.version)
        health = (ROOT / 'extensions/com_jtseo/admin/src/Helper/HealthHelper.php').read_text()
        self.assertEqual(re.search(r"const VERSION = '([^']+)'", health).group(1), self.version)
        self.assertTrue((ROOT / f'extensions/com_jtseo/admin/sql/updates/mysql/{self.version}.sql').is_file())

    def test_xml_and_manifest_file_references_are_valid(self):
        manifests = [ROOT / 'package/pkg_jtseo.xml', *(ROOT / 'extensions').glob('*/jtseo.xml')]
        for folder in (ROOT / 'extensions', ROOT / 'package', ROOT / 'updates'):
            for path in folder.rglob('*.xml'):
                ET.parse(path)
        for path in manifests:
            manifest = ET.parse(path).getroot()
            is_package = manifest.get('type') == 'package'
            self.assertTrue((path.parent / manifest.findtext('scriptfile')).is_file())
            for group in manifest.iter('files'):
                for item in group:
                    if is_package:
                        self.assertTrue((ROOT / 'extensions' / Path(item.text).stem).is_dir())
                    else:
                        target = path.parent / group.get('folder', '') / item.text.strip()
                        self.assertTrue(target.exists(), str(target))
            for group in manifest.iter('languages'):
                for item in group:
                    target = path.parent / group.get('folder', '') / item.text.strip()
                    self.assertTrue(target.is_file(), str(target))

    def test_committed_source_matches_current_tested_runtime_payload(self):
        published = zip_contents(self.release)
        for name, data in published.items():
            if name.startswith('packages/'):
                source = ROOT / 'extensions' / Path(name).stem
                child = zip_contents(data)
                paths = {p.relative_to(source).as_posix() for p in source.rglob('*') if p.is_file()}
                self.assertEqual(paths, set(child))
                for entry, content in child.items():
                    self.assertEqual((source / entry).read_bytes(), content, name + '/' + entry)
            elif name != 'README.md':
                self.assertEqual((ROOT / 'package' / name).read_bytes(), data, name)

    def test_source_build_preserves_tested_code_and_is_reproducible(self):
        with tempfile.TemporaryDirectory() as tmp:
            first = build_package(ROOT, Path(tmp) / 'first').read_bytes()
            second = build_package(ROOT, Path(tmp) / 'second').read_bytes()
        self.assertEqual(first, second)
        built = zip_contents(first)
        published = zip_contents(self.release)
        self.assertEqual(set(built), set(published))
        for name, data in published.items():
            if name.startswith('packages/'):
                self.assertEqual(zip_contents(built[name]), zip_contents(data), name)
            elif name != 'README.md':
                self.assertEqual(built[name], data, name)
        self.assertEqual(built['README.md'], (ROOT / 'package/README.md').read_bytes())


if __name__ == '__main__':
    unittest.main()
