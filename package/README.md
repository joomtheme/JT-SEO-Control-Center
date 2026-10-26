# JT SEO Control Center 1.0.69

Joomla 6 article SEO fields and live score compatibility release.

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
