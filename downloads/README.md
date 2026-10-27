# JT SEO Control Center 1.0.69

[Download the released installation ZIP](https://github.com/joomtheme/JT-SEO-Control-Center/releases/download/v1.0.69/pkg_jtseo_v1.0.69.zip)

This package fixes the missing article SEO fields
reported in [issue #6](https://github.com/joomtheme/JT-SEO-Control-Center/issues/6).
It also corrects dashboard/live-score asset paths and enables the required
plugins on fresh installations while preserving selected states on upgrades.

Install through Joomla **System → Install → Extensions**. An existing
installation can be upgraded without uninstalling the extension.

The package was tested on Joomla 6.2.0 with PHP 8.4.27. See
[the test report](TEST-REPORT.md) and [SHA-256 checksum](SHA256SUMS.txt).

## 1.0.70 test candidate (unreleased)

[Download the test ZIP](https://raw.githubusercontent.com/joomtheme/JT-SEO-Control-Center/main/downloads/pkg_jtseo_v1.0.70.zip)
— [test report](TEST-REPORT-1.0.70.md), [SHA-256](SHA256SUMS-1.0.70-test.txt).

Adds **Include menu items** (default off), independently of **Include Joomla
articles**. Install over 1.0.69, enable the new option, disable articles and
regenerate the sitemap. The 1.0.69 artifact, official release and update feed
remain unchanged. Awaiting JED Checker and real-site testing before release.
