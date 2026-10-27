# JT SEO Control Center 1.0.70 test candidate

Tested on 2026-10-27. Not an official release; awaiting JED Checker and real-site testing.

## Change

Addresses Peter's menu-only sitemap request:
https://github.com/joomtheme/JT-SEO-Control-Center/issues/6#issuecomment-6437311695

Adds **Include menu items** / **Menü öğelerini dahil et**, default **No**.
Enable it and disable **Include Joomla articles** to collect menu destinations
without enumerating all articles. **Include homepage** remains independent.

Only published, guest-accessible site menu destinations are collected. Internal
component and URL links are supported; external/protocol-relative/non-page links,
aliases, headings, separators, home menu items and inaccessible/unpublished or
scheduled-out menu branches are omitted. Menu-linked articles reuse article
publication, category/guest access, canonical validity and noindex eligibility.
Joomla site routing selects menu Itemid and content language. Existing combined
URL deduplication, inclusion/exclusion priority, noindex path filters, multilingual
buckets, sitemap index and pagination remain in the shared pipeline.

## Verified results

- PHP **8.4.24**, MariaDB **11.8.6**, Joomla **6.0.0** and **6.2.0**.
- Clean package installation on Joomla 6.0.0: passed.
- Actual installed **1.0.69 → 1.0.70** upgrades on Joomla 6.0.0 and 6.2.0: passed;
  all four extension versions and component schema marker report 1.0.70.
- Existing installed-Joomla compatibility suite: **68 passed** on each version.
- Menu sitemap integration suite: **40 passed** on each version with local HTTP
  server; **38 passed** without the optional HTTP checks on the separate upgrade
  site. Checks exercise real Joomla tables, router, guest access, Form field
  loading and installed package code, not mocks.
- Menu-only versus linked/unlinked articles, simultaneous sources, published and
  public menu visibility, internal URL links, excluded external/structural menu
  types, publication windows, ancestor access/publication, noindex parent/child
  filtering, article noindex/access/state, explicit include precedence, homepage
  independence, EN/TR language buckets, shared-language menus and index: passed.
- HTTP component sitemap endpoint returned parseable `urlset` XML containing menu
  URLs and omitting unlinked articles with article enumeration disabled.
- MariaDB stop/restart followed by saved startup command and repeat invocation:
  both passed all 40 Joomla 6.2.0 sitemap checks.
- Python metadata/archive/source tests: **7 passed**. Candidate ZIP payload matches
  tracked extension source; repeat builds are byte-for-byte reproducible.
- PHP syntax: **28 files passed** across extensions, installer and Joomla tests.
- All manifest/XML references and version/asset/schema consistency checks: passed.

Commands (from repository root, PHP on PATH):

```sh
python3 -m unittest discover -s tests -v
find extensions package tests -name '*.php' -print0 | xargs -0 -n1 php -l
python3 tools/build.py
php "$JTSEO_JOOMLA_ROOT/cli/joomla.php" extension:install --path="$PWD/downloads/pkg_jtseo_v1.0.70.zip" --no-interaction
JTSEO_JOOMLA_ROOT=/path/to/disposable/installed/joomla php tests/joomla/compatibility.php
JTSEO_JOOMLA_ROOT=/path/to/disposable/installed/joomla JTSEO_TEST_ORIGIN=http://127.0.0.1:8082 php tests/joomla/sitemap_menu.php
```

The integration test creates temporary menus/articles and rebuilds the menu tree;
use a disposable test site only. Its fixtures and temporary settings are removed
or restored after each run. EN/TR bucket checks use published content-language
records; full production multilingual plugin/language-pack configurations remain
part of real-site acceptance testing. JED Checker and PHP 8.3/8.5 were not run in
this cloud instance (the existing GitHub Actions PHP matrix remains configured).

## Artifact and publication boundary

Test ZIP: `downloads/pkg_jtseo_v1.0.70.zip`

SHA-256:
`3d2442be79182924993fa599768662a918b212c15aeb007dfbe8edc2eea61391`

The exact ZIP above was installed and tested. A separate
`SHA256SUMS-1.0.70-test.txt` identifies it. Source/package versions are 1.0.70.

The official `downloads/pkg_jtseo_v1.0.69.zip`, `downloads/SHA256SUMS.txt`,
`updates/update.xml` and `updates/changelog.xml` remain unchanged. No GitHub
release or release tag was created. The official release remains **1.0.69**.
