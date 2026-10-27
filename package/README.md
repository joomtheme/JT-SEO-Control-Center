# JT SEO Control Center 1.0.70 — test package

Unreleased test package for Joomla 6. The official release remains 1.0.69.

To generate only menu destinations, open component Options → Sitemap, enable
**Include menu items**, disable **Include Joomla articles**, then regenerate
the sitemap. **Include homepage** stays independent. The new menu option is
off by default; published public internal links follow Joomla routing and
existing noindex, URL exclusion/include, language and deduplication rules.
Aliases, headings, separators, external links and restricted/unpublished menu
branches are omitted. Published menu-linked articles retain article visibility
and noindex checks. Existing 1.0.69 fixes remain included:

- Restores the JT SEO fields in Joomla article editing using the typed form event API.
- Supports generic events with subject, form or positional arguments.
- Loads the plugin's own form XML by absolute filename.
- Corrects dashboard and live-score JavaScript/CSS asset paths.
- Enables both JT SEO plugins on fresh installations and preserves selected plugin states during upgrades.
- Synchronizes extension, asset, health-check and database schema-marker versions.

Install the package through Joomla **System → Install → Extensions**.
Check both plugins and the component's System Status after installation.

Chrome may show an XSLT deprecation notice when displaying a sitemap.
This concerns browser presentation; sitemap XML generation is unchanged.

Documentation and support: https://github.com/joomtheme/JT-SEO-Control-Center

License: GNU General Public License version 2 or later; see LICENSE.txt.
