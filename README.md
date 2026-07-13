# JT SEO Control Center

**JT SEO Control Center** is a Joomla SEO management package for Joomla
6. It helps site owners, administrators, and developers audit SEO
issues, manage article metadata, generate XML sitemaps, manage
multilingual sitemap output, track 404 errors, create redirects, preview
structured data mappings, and improve Joomla SEO from one clean
administrator dashboard.

A clean SEO control center for Joomla: audit articles, manage metadata,
generate XML sitemaps, support multilingual sitemap output, track 404
errors, and create redirects from one dashboard.

Instead of managing common SEO tasks across multiple screens or
extensions, JT SEO Control Center brings essential Joomla SEO workflows
into a single control center.

The package includes:

-   `com_jtseo` --- JT SEO Control Center component
-   `plg_system_jtseo` --- frontend metadata, canonical, sitemap,
    redirect and 404 handling
-   `plg_content_jtseo` --- article edit form SEO fields and live SEO
    score

------------------------------------------------------------------------

## Key Features

-   SEO dashboard for Joomla administrators
-   Article SEO audit and scoring
-   Bulk metadata editing with pagination and filters
-   CSV import and export for SEO fields
-   Homepage metadata management
-   Article SEO title and description overrides
-   Canonical URL management
-   Open Graph and Twitter Card metadata
-   XML sitemap generation
-   Multilingual XML sitemap support
-   Sitemap index support for multilingual websites
-   Paginated sitemap files for large multilingual websites
-   Article and category image sitemap support
-   Article content image detection for sitemap images
-   Sitemap image count display in XML browser stylesheet
-   Global sitemap include and exclude URL rules
-   Wildcard URL filtering support for sitemap exclusions
-   Noindex menu path exclusion for sitemap output
-   Optional physical `sitemap.xml` creation
-   404 tracking with privacy-friendly logging options
-   Redirect manager with 301, 302, 307, 308 and 410 support
-   Structured Data preview for common JSON-LD mappings
-   English and Turkish language support

------------------------------------------------------------------------

## Requirements

-   Joomla 6.x
-   PHP version supported by the installed Joomla 6 environment
-   MySQL/MariaDB database supported by Joomla
-   Joomla administrator access
-   Writable Joomla root folder if physical `sitemap.xml` generation is
    enabled

------------------------------------------------------------------------

## Package Contents

JT SEO Control Center is distributed as a Joomla package extension.

Install this file through Joomla:

``` text
pkg_jtseo_v1.0.65.2.zip
```

The package installs the following child extensions:

``` text
Component:
- JT SEO Control Center

Plugins:
- System - JT SEO Control Center
- Content - JT SEO Control Center
```

The package also includes GPL license files and Joomla language files
for English and Turkish.

------------------------------------------------------------------------

## Main Features

### SEO Dashboard

JT SEO Control Center provides a central dashboard inside the Joomla
administrator area.

The dashboard includes:

-   Global SEO settings overview
-   Article metadata status
-   XML sitemap status
-   Physical `sitemap.xml` status
-   Multilingual sitemap status
-   SEO audit summary
-   Article SEO scores
-   Bulk metadata editor
-   Structured Data preview
-   Redirect and 404 manager
-   System health checks

The dashboard is designed to help site owners quickly understand what
needs attention and where to take action.

------------------------------------------------------------------------

### Homepage Metadata

The component can apply custom SEO metadata to the default Joomla
homepage menu item.

Supported homepage fields:

-   Homepage SEO title
-   Homepage meta description
-   Default Open Graph image
-   Twitter Card type

This allows the homepage to have clean and controlled SEO/social
metadata without editing template files.

------------------------------------------------------------------------

### Article SEO Fields

The Content plugin adds a **JT SEO** fieldset to Joomla article edit
forms.

Supported article override fields:

-   SEO title override
-   SEO description override
-   Canonical URL override
-   Open Graph image override
-   Robots index directive
-   Robots follow directive

These fields are stored inside the Joomla article attributes and do not
modify the article body content.

------------------------------------------------------------------------

### Live Article SEO Score

The Content plugin includes a live SEO score panel inside the Joomla
article edit screen.

The score helps editors review:

-   Title length
-   Meta description presence
-   Meta description length
-   Article image / Open Graph image availability
-   Robots noindex status
-   Content word count
-   Canonical override usage

The score is a practical editorial guide and is not a search engine
ranking guarantee.

------------------------------------------------------------------------

### Article SEO Scores

The dashboard includes an article scoring view that lists published
articles by SEO priority.

It checks for:

-   Missing SEO descriptions
-   Short or long descriptions
-   Missing images
-   Noindex articles
-   Duplicate aliases
-   Low word count
-   Canonical overrides

The weakest articles are shown first, making it easier to prioritize SEO
improvements.

------------------------------------------------------------------------

### SEO Audit

The dashboard audit checks published Joomla articles for common SEO
issues.

Audit checks include:

-   Missing explicit descriptions
-   Short descriptions
-   Long descriptions
-   Missing article or Open Graph images
-   Noindex directives
-   Duplicate aliases in the same category
-   Canonical override usage

The audit table includes severity, issue type and fix suggestions.

------------------------------------------------------------------------

### Bulk Meta Manager

The Bulk Meta Manager allows administrators to edit SEO override fields
for multiple Joomla articles from one dashboard section.

The default view focuses on articles that need attention, so healthy
articles do not clutter the screen.

Bulk Meta Manager features include:

-   Default **Needs attention** filter
-   Pagination options for 10, 25 or 50 articles
-   Search by article title, alias or metadata
-   Score/status filters
-   Compact default editing layout
-   Expandable advanced fields
-   CSV Import / Export tools in a collapsible panel

Main editable fields:

-   SEO title override
-   SEO description override

Advanced editable fields:

-   Robots index directive
-   Robots follow directive
-   Canonical URL override
-   Open Graph image override

Bulk saves only update JT SEO article override fields. Joomla article
titles and article body content are not changed.

Empty fields do not automatically rewrite Joomla article content. Only
supported JT SEO override fields are saved.

------------------------------------------------------------------------

### CSV Import / Export

JT SEO Control Center can export article SEO data to CSV and import
edited SEO override fields back into Joomla.

Export data may include:

-   Article ID
-   Article title
-   Category
-   Alias
-   SEO score
-   SEO issue summary
-   SEO title override
-   SEO description override
-   Canonical URL override
-   Robots index directive
-   Robots follow directive
-   Open Graph image override

Writable import columns:

``` text
seo_title
seo_description
canonical
robots_index
robots_follow
og_image
```

Important CSV rules:

-   Keep `article_id` unchanged.
-   Do not edit article IDs manually.
-   Article body content is not changed by CSV import.
-   Use preview mode before applying large CSV updates.

------------------------------------------------------------------------

### Canonical URL Manager

The System plugin can output canonical URLs for frontend HTML pages.

Canonical support includes:

-   Clean canonical URL output
-   Query-string tracking parameter removal
-   Article-level canonical override support
-   Global enable/disable option

Use canonical overrides only when a page has a preferred URL different
from the current Joomla route.

------------------------------------------------------------------------

### Open Graph and Twitter Card Metadata

JT SEO Control Center can output social metadata for Joomla pages and
articles.

Supported metadata includes:

-   `og:title`
-   `og:description`
-   `og:type`
-   `og:image`
-   `og:site_name`
-   `twitter:title`
-   `twitter:description`
-   `twitter:image`
-   `twitter:card`
-   Article publishing metadata when available

Article images are selected in this order:

1.  JT SEO article Open Graph image override
2.  Joomla article intro image
3.  Joomla article full image
4.  Global default Open Graph image

------------------------------------------------------------------------

### XML Sitemap Generator

JT SEO Control Center can generate XML sitemaps for Joomla sites.

Sitemap features:

-   Include homepage
-   Include published Joomla articles
-   Exclude articles marked as noindex
-   Exclude Joomla menu paths marked as noindex, including child URLs
-   Exclude fallback component URLs
-   Configurable article limit
-   Dynamic XML endpoint
-   Optional physical `sitemap.xml` generation in the Joomla root folder
-   Multilingual sitemap support for Joomla websites using multiple
    content languages
-   Sitemap index output for multilingual websites
-   Dedicated sitemap output for each published content language
-   Paginated sitemap files with a configurable maximum links limit

For standard single-language websites, the sitemap can be accessed as:

``` text
https://example.com/sitemap.xml
```

For multilingual websites, `sitemap.xml` can work as a sitemap index and
point to language-specific paginated sitemap files such as:

``` text
https://example.com/sitemap.xml
https://example.com/sitemap.en-GB.1.xml
https://example.com/sitemap.tr-TR.1.xml
```

If a language contains more URLs than the configured maximum links per
sitemap file, JT SEO Control Center automatically splits that language
into additional sitemap files, for example `sitemap.en-GB.2.xml`.

In multilingual mode, article sitemap generation respects Joomla content
language values and uses language-aware Joomla routing when building
article URLs.

If a Joomla menu item has a robots value containing `noindex`, that menu
path and its child URLs are excluded from sitemap output. For example,
if `/tag` is marked as `noindex`, `/tag` and `/tag/*` URLs are removed
from the sitemap.

If the Joomla root folder is not writable, physical sitemap generation
may fail. The dashboard system status will warn about this.

------------------------------------------------------------------------

### Multilingual Sitemap Support

JT SEO Control Center supports multilingual Joomla websites with
multiple published content languages.

When multilingual sitemap support is enabled, `/sitemap.xml` can act as
a master sitemap index and link to dedicated sitemap files for each
published language.

Example sitemap index output:

``` xml
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>https://example.com/sitemap.en-GB.1.xml</loc>
    </sitemap>
    <sitemap>
        <loc>https://example.com/sitemap.tr-TR.1.xml</loc>
    </sitemap>
</sitemapindex>
```

Example language sitemap files:

``` text
/sitemap.en-GB.1.xml
/sitemap.tr-TR.1.xml
```

Large language sitemaps can be split into multiple files based on the
configured maximum links per sitemap file, for example:

``` text
/sitemap.en-GB.1.xml
/sitemap.en-GB.2.xml
```

Each language sitemap is generated from published Joomla articles
assigned to the matching content language.

Recommended Joomla multilingual setup:

-   Enable the Joomla Language Filter plugin.
-   Publish the required content languages.
-   Assign a home menu item for each site language.
-   Assign articles to the correct content language.
-   Generate the sitemap from JT SEO Control Center.

------------------------------------------------------------------------

### Redirects and 404 Manager

JT SEO Control Center includes a redirect and 404 manager for migration
and broken URL handling.

Redirect features:

-   Create redirect rules
-   Create redirects directly from 404 log entries
-   Track redirect hits
-   Delete redirect rules
-   Clear redirect rules
-   Mark removed URLs as `410 Gone`

Supported redirect statuses:

``` text
301 Moved Permanently
302 Found
307 Temporary Redirect
308 Permanent Redirect
410 Gone
```

The old URL should be a site-local relative URL such as:

``` text
/old-page
```

The target URL can be a relative site URL or a safe HTTP/HTTPS absolute
URL when required.

------------------------------------------------------------------------

### 404 Logging

When enabled, frontend 404 requests are logged in the dashboard.

Logged data may include:

-   Requested URL
-   Referrer URL, if referrer logging is enabled
-   Query string, if query string logging is enabled
-   User agent, if optional user-agent logging is enabled
-   Last hit date
-   Hit count

404 logs help administrators find broken URLs and create redirect rules.

JT SEO Control Center is designed to avoid unnecessary database writes
on normal frontend page views. It logs real 404 requests instead of
writing temporary records for every successful page request.

For privacy-friendly defaults, optional logging fields such as query
strings and user agents should remain disabled unless they are needed
for troubleshooting.

------------------------------------------------------------------------

### Structured Data Preview

JT SEO Control Center includes a safe Structured Data preview panel.

The panel shows how Joomla site and article data can map to common
JSON-LD types.

Supported preview mappings:

-   Organization
-   WebSite
-   Article
-   BreadcrumbList

This release uses safe preview mode. The Structured Data panel does not
automatically inject duplicate JSON-LD into the frontend.

Breadcrumb note:

The Joomla core Breadcrumbs module can be assigned site-wide. Duplicate
`BreadcrumbList` JSON-LD validation is only required when a template or
another SEO/schema extension also outputs breadcrumb schema.

------------------------------------------------------------------------

### System Status

The dashboard includes a setup and health check panel.

It checks:

-   System plugin status
-   Content plugin status
-   Sitemap folder writability
-   Physical `sitemap.xml` availability
-   Redirect database table
-   404 log database table
-   Database schema version

If a required plugin is disabled or a database table is missing, the
dashboard displays a warning.

------------------------------------------------------------------------

## Installation

1.  Log in to the Joomla Administrator.
2.  Go to **System → Install Extensions**.
3.  Upload the package ZIP file:

``` text
pkg_jtseo_v1.0.65.2.zip
```

4.  Wait for Joomla to complete the package installation.
5.  Go to **Components → JT SEO Control Center**.
6.  Open the **System Status** section.
7.  Confirm that the required plugins and database tables are healthy.

The package attempts to enable the required JT SEO plugins automatically
after installation or update.

------------------------------------------------------------------------

## Updating

To update JT SEO Control Center:

1.  Back up the Joomla site and database.
2.  Log in to the Joomla Administrator.
3.  Go to **System → Install Extensions**.
4.  Upload the new package ZIP file.
5.  Open **Components → JT SEO Control Center**.
6.  Check **System Status**.
7.  If Joomla reports database schema warnings, go to **System →
    Maintenance → Database** and run the database structure update.

JT SEO Control Center includes database update scripts and installer
self-healing logic for existing installations.

------------------------------------------------------------------------

## Basic Setup

After installation, review the following settings.

Go to:

``` text
Components → JT SEO Control Center → Options
```

Recommended first setup:

1.  Enable homepage metadata.
2.  Add homepage SEO title.
3.  Add homepage meta description.
4.  Select a default Open Graph image.
5.  Enable canonical URL output.
6.  Enable article Open Graph metadata.
7.  Enable article images for Open Graph.
8.  Enable article SEO fields.
9.  Enable XML sitemap.
10. Enable multilingual sitemap support if the website uses multiple
    content languages.
11. Review the maximum links per sitemap file setting for large
    websites.
12. Enable physical `sitemap.xml` generation if the Joomla root folder
    is writable.
13. Enable redirects if redirect management is needed.
14. Enable 404 logging if broken URL tracking is needed.

------------------------------------------------------------------------

## Article SEO Workflow

Recommended editor workflow:

1.  Open a Joomla article.
2.  Open the **JT SEO** fieldset.
3.  Add a clear SEO title if the Joomla article title is not ideal for
    search.
4.  Add a unique SEO description.
5.  Select an Open Graph image if the article image is missing or not
    suitable for social sharing.
6.  Leave robots as default unless the article should be noindex or
    nofollow.
7.  Add a canonical URL only when the article has a preferred route.
8.  Review the live SEO score.
9.  Save the article.

------------------------------------------------------------------------

## Bulk SEO Workflow

Recommended bulk workflow:

1.  Open **Components → JT SEO Control Center**.
2.  Go to **Bulk Meta Manager**.
3.  Review the default **Needs attention** list.
4.  Use search, score/status filters or pagination to narrow the article
    list.
5.  Edit SEO title and SEO description fields directly in the dashboard.
6.  Open **Advanced fields** only when canonical, robots or Open Graph
    image overrides are needed.
7.  Save changes.
8.  Recheck the SEO audit.

For spreadsheet workflows:

1.  Export CSV.
2.  Edit supported SEO override columns.
3.  Import using preview mode.
4.  Review skipped rows and validation results.
5.  Disable preview mode and import again to apply changes.

------------------------------------------------------------------------

## Sitemap Workflow

Recommended sitemap workflow:

1.  Open component options.
2.  Enable XML sitemap.
3.  Enable homepage and article inclusion.
4.  Enable noindex exclusion.
5.  Enable fallback URL exclusion.
6.  Enable multilingual sitemap support if the site uses multiple
    content languages.
7.  Review the maximum links per sitemap file setting if the site has
    many URLs.
8.  Enable physical `sitemap.xml` generation if possible.
9.  Open the dashboard.
10. Click **Generate sitemap.xml**.
11. Visit:

``` text
https://example.com/sitemap.xml
```

12. Submit the sitemap URL to search engine webmaster tools.

For multilingual websites, also check the language-specific sitemap
files listed inside `sitemap.xml`, for example:

``` text
https://example.com/sitemap.en-GB.1.xml
https://example.com/sitemap.tr-TR.1.xml
```

If menu paths such as `/tag` should not appear in the sitemap, set the
related Joomla menu item's robots option to a value containing
`noindex`, then regenerate the sitemap.

------------------------------------------------------------------------

## Redirect Workflow

Recommended redirect workflow:

1.  Enable redirects in component options.
2.  Enable 404 logging if needed.
3.  Visit missing frontend URLs or wait for real 404 traffic.
4.  Open **Redirects / 404** in the dashboard.
5.  Create redirect rules from important 404 URLs.
6.  Use `301` for permanent URL changes.
7.  Use `410` for removed content that should not redirect.

------------------------------------------------------------------------

## Configuration Reference

### Global SEO

  -----------------------------------------------------------------------
  Option                     Description
  -------------------------- --------------------------------------------
  Enable homepage metadata   Applies configured title and description to
                             the homepage

  Homepage SEO title         Optional homepage title override

  Homepage meta description  Optional homepage description

  Default Open Graph image   Fallback image for social previews

  Twitter Card type          Selects summary or summary large image

  Add canonical URL          Outputs canonical URLs for frontend HTML
                             pages

  Add article Open Graph     Outputs article social metadata
  metadata                   

  Use article images for     Uses Joomla article images before default
  Open Graph                 fallback

  Add article SEO fields     Adds JT SEO fields to article edit forms

  Minimum article word count Used by article SEO score checks
  -----------------------------------------------------------------------

### XML Sitemap

  -----------------------------------------------------------------------
  Option                      Description
  --------------------------- -------------------------------------------
  Enable XML sitemap          Enables sitemap output

  Include homepage            Adds homepage to sitemap

  Include Joomla articles     Adds published articles

  Exclude noindex articles    Removes noindex articles from sitemap

  Exclude component fallback  Avoids fallback component article URLs
  article URLs                

  Enable multilingual sitemap Generates a sitemap index and
                              language-specific sitemap files

  Max links per sitemap file  Splits large language sitemaps into
                              paginated sitemap files

  Generate physical           Writes a real sitemap.xml file to the
  sitemap.xml                 Joomla root

  Article limit               Maximum number of articles included
  -----------------------------------------------------------------------

### Redirects and 404

  -----------------------------------------------------------------------
  Option           Description
  ---------------- ------------------------------------------------------
  Enable redirects Enables frontend redirect rule checks

  Log 404 requests Stores frontend 404 requests for review

  Log query        Optionally stores query strings for 404 diagnostics
  strings          

  Log referrers    Optionally stores referrer URLs for 404 diagnostics

  Log user agents  Optionally stores user-agent strings for
                   troubleshooting
  -----------------------------------------------------------------------

### System Plugin

  -----------------------------------------------------------------------
  Option                     Description
  -------------------------- --------------------------------------------
  Add Open Graph site name   Adds `og:site_name` using the Joomla site
                             name

  Add generator meta         Adds a JT SEO generator meta tag

  Remove Joomla generator    Removes Joomla default generator meta tag
  meta                       
  -----------------------------------------------------------------------

### Support / Community

  -----------------------------------------------------------------------
  Option               Description
  -------------------- --------------------------------------------------
  JED listing/profile  Optional Joomla Extension Directory listing or
  URL                  profile URL

  GitHub repository    Optional GitHub project URL
  URL                  

  Support website URL  Optional support or documentation URL
  -----------------------------------------------------------------------

------------------------------------------------------------------------

## Language Support

Included languages:

``` text
English: en-GB
Turkish: tr-TR
```

Language files are included for:

-   Package
-   Component
-   System plugin
-   Content plugin

------------------------------------------------------------------------

## Permissions

The component supports Joomla permissions through the component options
screen.

Administrators can manage access through:

``` text
Components → JT SEO Control Center → Options → Permissions
```

------------------------------------------------------------------------

## Privacy Notice

JT SEO Control Center can store 404 request URLs, referrer URLs, query
strings and user-agent strings when 404 tracking is enabled and the
related logging options are active.

This data is used for SEO diagnostics and redirect management.

Site owners should disclose this behavior in their privacy policy where
required.

Recommended privacy-friendly defaults:

-   Keep query string logging disabled unless needed.
-   Keep user-agent logging disabled unless troubleshooting.
-   Enable referrer logging only when it is useful for broken-link
    diagnostics.
-   Review and clear old 404 logs periodically.

------------------------------------------------------------------------

## Security Notes

-   The extension uses Joomla administrator access control.
-   State-changing dashboard actions use Joomla request token
    validation.
-   Redirect old URLs are expected to be site-local URLs.
-   Redirect targets are limited to site-local URLs or safe HTTP/HTTPS
    URLs.
-   CSV import validates uploaded CSV files and supported columns.
-   No encrypted or encoded PHP files are included.

------------------------------------------------------------------------

## Troubleshooting

### The JT SEO article fields do not appear

Check that the plugin is enabled:

``` text
Content - JT SEO Control Center
```

Then open the article edit screen again.

------------------------------------------------------------------------

### Frontend metadata is not output

Check that the plugin is enabled:

``` text
System - JT SEO Control Center
```

Also confirm that the relevant feature is enabled in the component
options.

------------------------------------------------------------------------

### sitemap.xml is missing

Check:

1.  XML sitemap is enabled.
2.  Physical sitemap generation is enabled.
3.  Joomla root folder is writable.
4.  The dashboard **Generate sitemap.xml** button has been used.

If the root folder is not writable, adjust file permissions or use the
dynamic sitemap endpoint.

------------------------------------------------------------------------

### sitemap.xml shows a sitemap index

This is expected on multilingual Joomla websites when multilingual
sitemap support is enabled.

In this case, `sitemap.xml` lists language-specific sitemap files such
as:

``` text
/sitemap.en-GB.1.xml
/sitemap.tr-TR.1.xml
```

Open each language sitemap file to review the URLs for that language.
Large language sitemaps may also include additional files such as
`sitemap.en-GB.2.xml`.

------------------------------------------------------------------------

### Noindex menu URLs still appear in the sitemap

Check:

1.  The related Joomla menu item has a robots value containing
    `noindex`.
2.  The URL belongs to that menu path or one of its child paths.
3.  Physical sitemap generation was run again after changing the menu
    robots setting.
4.  Joomla cache, browser cache, CDN cache or server cache is not
    showing an older sitemap file.

For example, if `/tag` is marked as `noindex`, `/tag` and `/tag/*` URLs
should be excluded after regenerating the sitemap.

------------------------------------------------------------------------

### Multilingual sitemap files are missing

Check:

1.  XML sitemap is enabled.
2.  Multilingual sitemap support is enabled.
3.  Joomla content languages are published.
4.  Joomla Language Filter plugin is enabled.
5.  Each site language has a valid home menu item.
6.  Articles are assigned to the correct content language.
7.  Physical sitemap generation was run again after changing language
    settings.

------------------------------------------------------------------------

### Redirects are not working

Check:

1.  The System plugin is enabled.
2.  Redirects are enabled in component options.
3.  The old URL is site-local and starts with `/`.
4.  The redirect rule is enabled.
5.  Browser cache or server cache is not showing an old response.

------------------------------------------------------------------------

### 404 logs are empty

Check:

1.  404 logging is enabled in component options.
2.  The System plugin is enabled.
3.  You are testing a real missing frontend URL.
4.  Joomla or server-level redirects are not catching the request before
    Joomla handles it.

------------------------------------------------------------------------

### Bulk Meta Manager does not show all articles

By default, Bulk Meta Manager focuses on articles that need attention.

To view more articles:

1.  Change the filter from **Needs attention** to **All articles**.
2.  Increase the page limit to 25 or 50.
3.  Use search to find a specific article.

------------------------------------------------------------------------

### CSV import skipped rows

Check:

1.  `article_id` values were not changed.
2.  Writable column names are correct.
3.  The CSV file uses a valid format.
4.  Preview mode results were reviewed before applying changes.

Writable import columns:

``` text
seo_title
seo_description
canonical
robots_index
robots_follow
og_image
```

------------------------------------------------------------------------

### Database schema warning appears

Go to:

``` text
System → Maintenance → Database
```

Run Joomla's database structure update if available.

Then return to:

``` text
Components → JT SEO Control Center → System Status
```

------------------------------------------------------------------------

## Version 1.0.65.2

### Release Type

Advanced sitemap improvements, image sitemap integration and URL
filtering improvements for Joomla 6.

### Changes

-   Added XML image sitemap support for articles and categories.
-   Added article image detection from Joomla Images and Links fields.
-   Added article content image detection from embedded HTML images.
-   Added image count display in the sitemap browser stylesheet.
-   Added global Included URLs configuration for manually adding sitemap
    URLs.
-   Added global Excluded URLs configuration for manually removing
    sitemap URLs.
-   Added wildcard support for URL exclusions such as `/tag/*`.
-   Improved sitemap image handling with support for multiple images per
    URL.
-   Improved sitemap URL filtering and normalization logic.
-   Improved Joomla 6 routing compatibility.
-   Fixed missing article images in XML sitemap output.
-   Fixed article content image extraction from `<img>` HTML elements.
-   Fixed sitemap filtering edge cases for excluded parent paths and
    child URLs.
-   Fixed JED Checker compatibility issue related to URL decoding
    patterns.
-   Updated package, component, plugin and asset versions to `1.0.65.2`.
-   Added database update marker for version `1.0.65.2`.

------------------------------------------------------------------------

## Version 1.0.64

### Release Type

Multilingual sitemap index, sitemap pagination and noindex menu
exclusion release for Joomla 6.

### Changes

-   Added multilingual sitemap index support with language-specific
    sitemap files.
-   Added paginated sitemap output for large multilingual websites.
-   Added sitemap files using language tags and page indexes, such as
    `sitemap.tr-TR.1.xml` and `sitemap.en-GB.1.xml`.
-   Added a maximum links per sitemap file setting for sitemap
    pagination.
-   Changed `sitemap.xml` to serve as a master sitemap index when
    multilingual sitemap output is enabled.
-   Improved sitemap index output to include language-specific paginated
    sitemap files.
-   Fixed sitemap exclusion for Joomla menu items with robots settings
    containing `noindex`.
-   Fixed sitemap generation so noindex menu paths and their child URLs
    are excluded from sitemap output.
-   Fixed tag menu sitemap leakage when a tag menu path such as `/tag`
    is marked as `noindex`.
-   Fixed missing XML stylesheet display for SEF `sitemap.xml` output.
-   Improved cleanup of old generated sitemap files during installation
    and updates.
-   Updated package, component, plugin and asset versions to `1.0.64`.
-   Added database update marker for version `1.0.64`.

------------------------------------------------------------------------

## Previous Version 1.0.63

### Release Type

Multilingual sitemap support release for Joomla 6.

### Changes

-   Added multilingual sitemap support for Joomla websites using
    multiple content languages.
-   Added sitemap index output for multilingual websites via
    `sitemap.xml`.
-   Added dedicated sitemap output for each published content language.
-   Improved sitemap generation to respect Joomla content language
    values.
-   Improved article URL generation by using language-aware Joomla
    routing.
-   Added a new multilingual sitemap configuration option.
-   Updated package, component and plugin versions to `1.0.63`.
-   Added database update marker for version `1.0.63`.
-   Improved sitemap compatibility for multilingual Joomla websites.
-   Added multilingual sitemap support based on community feedback from
    GitHub user `@niaziblog`.

------------------------------------------------------------------------

## Previous Version 1.0.62

### Release Type

JED-ready stable release for Joomla 6.

### Changes

-   Improved Bulk Meta Manager user interface.
-   Added default **Needs attention** filter for low-score articles.
-   Added pagination options for Bulk Meta Manager.
-   Collapsed CSV Import / Export tools for a cleaner default view.
-   Moved advanced metadata fields into an expandable section.
-   Improved Bulk Meta Manager button layout and responsive behavior.
-   Optimized 404 logging to avoid unnecessary database writes on normal
    frontend page views.
-   Added privacy-friendly 404 logging options for query strings,
    referrers and user agents.
-   Improved JED listing/profile URL handling.
-   Updated package, component and plugin versions to `1.0.62`.
-   Improved Joomla 6 and JED readiness.
-   Improved English and Turkish language strings.
-   Added database update marker for version `1.0.62`.
-   Improved package integrity for Joomla Extension Directory review.

------------------------------------------------------------------------

## License

GNU General Public License version 2 or later.

See:

``` text
LICENSE.txt
```

------------------------------------------------------------------------

## Credits

Developed by JoomTheme.

Multilingual sitemap support, sitemap index pagination and noindex menu
sitemap exclusion improvements were added based on community feedback
from GitHub user `@niaziblog`.

Website:

``` text
https://joomtheme.com
```

------------------------------------------------------------------------

## Support

For documentation, support and updates, visit:

``` text
https://github.com/joomtheme/JT-SEO-Control-Center/blob/main/README.md
```

GitHub repository:

``` text
https://github.com/joomtheme/JT-SEO-Control-Center
```

Joomla Extension Directory profile:

``` text
https://extensions.joomla.org/profile/profile/details/147240/
```
