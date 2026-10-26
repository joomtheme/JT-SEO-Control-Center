# JT SEO Control Center

**Current release: [v1.0.69](https://github.com/joomtheme/JT-SEO-Control-Center/releases/tag/v1.0.69)**

[Download the Joomla installation package](https://github.com/joomtheme/JT-SEO-Control-Center/releases/download/v1.0.69/pkg_jtseo_v1.0.69.zip)
· [Visual walkthrough](docs/demo/README.md)
· [Changelog](updates/changelog.xml)

## Current Release

Version **1.0.69** restores the JT SEO fields in Joomla article editing and
corrects the JavaScript/CSS paths used by the dashboard and live SEO score.
It enables the required plugins on new installations while preserving
administrator-selected plugin states during upgrades.

The package passed JED Checker and was tested with Joomla 6.2.0 / PHP 8.4.27,
including installation, upgrades, article SEO saving, frontend metadata and
`noindex` sitemap filtering. See the [validation report](downloads/TEST-REPORT.md).

```text
SHA-256: 3c83151265975c92d960713031d1c0cd68720e254c2534f50f0df513d687e60d
```

**JT SEO Control Center** is a Joomla SEO management package for Joomla
6. It helps site owners, administrators, and developers audit SEO
issues, manage article metadata, generate XML sitemaps, manage
multilingual sitemap output, track 404 errors, create redirects, generate
frontend JSON-LD structured data, and improve Joomla SEO from one clean
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
-   Multilingual-aware wildcard URL filtering for sitemap include and exclude rules
-   Noindex menu path exclusion for sitemap output
-   Optional physical `sitemap.xml` creation
-   404 tracking with privacy-friendly logging options
-   Redirect manager with 301, 302, 307, 308 and 410 support
-   Frontend JSON-LD structured data output and dashboard preview
-   Homepage `Organization` or `LocalBusiness` Schema
-   `WebSite`, `WebPage` and `Article` Schema output
-   Generic `WebPage` Schema support for SP Page Builder and other non-article component pages
-   Dedicated global and language-specific `WebSite.name` configuration
-   Multilingual Schema identity fields based on Joomla language tags
-   Article `datePublished` and `dateModified` structured data using Joomla publication and modification dates
-   Absolute Schema image and logo URL normalization for multilingual routes
-   Joomla core Schema.org graph enhancement without competing `Article` / `WebPage` graphs
-   Preservation of explicitly selected Joomla core item schemas such as `Book`, `Event` and `Recipe`
-   Existing JSON-LD detection that distinguishes declared entities from reference-only `@id` values
-   English and Turkish language support

------------------------------------------------------------------------

## Requirements

-   Joomla 6.x
-   PHP 8.3 or later, supported by the installed Joomla 6 environment
-   MySQL/MariaDB database supported by Joomla
-   Joomla administrator access
-   Writable Joomla root folder if physical `sitemap.xml` generation is
    enabled

------------------------------------------------------------------------

## Package Contents

JT SEO Control Center is distributed as a Joomla package extension.

Install this file through Joomla:

``` text
pkg_jtseo_v1.0.69.zip
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
-   Structured Data output and preview
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

For site identity, the configured Organization/LocalBusiness logo is used in
Schema.org structured data. If no normal Open Graph fallback image is
available, the configured organization logo can also be used as the standard
`og:image` fallback. JT SEO does not output a non-standard `og:logo` property.

`og:site_name` uses the configured website name. On multilingual websites,
the active language-specific website name is used when available, with fallback
to the global website name.

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

### Chrome XSLT notice

Chrome may display an XSLT deprecation notice when opening a sitemap. The
stylesheet controls browser presentation; the sitemap XML remains available
for search engines. Version 1.0.69 still uses XSLT for the browser table.

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

### Custom Sitemap Include and Exclude Rules

JT SEO Control Center supports custom path rules for controlling generated
sitemap URLs.

Enter one rule per line. Rules should use site-local SEF paths beginning
with `/`; do not include the domain name.

Examples:

``` text
/mycotoxin
/mycotoxin/*
```

Rule behavior:

-   `/mycotoxin` matches only the exact path.
-   `/mycotoxin/*` matches child paths below `/mycotoxin/`.
-   To remove both the parent path and every child URL, add both rules.
-   Included URL rules take priority over Excluded URL rules for matching
    URLs already produced by sitemap generation.

On multilingual websites, an unprefixed logical path rule is also matched
against language-prefixed routes. For example:

``` text
/mycotoxin/*
```

matches generated URLs such as:

``` text
/en/mycotoxin/magazine/invisible-your-eggs
/tr/mycotoxin/magazine/invisible-your-eggs
```

To target only a language-prefixed route, include the Joomla URL language
code in the rule:

``` text
/en/mycotoxin
/en/mycotoxin/*
```

This leaves other language-prefixed paths unchanged. If Joomla removes the
language code from the default language URL, path-only rules cannot
distinguish that default language from another unprefixed route.

After changing Included URLs or Excluded URLs settings, save the component
options and click **Generate sitemap.xml** again. Review the relevant
language-specific sitemap files, not only the main sitemap index.

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

### Structured Data and JSON-LD

JT SEO Control Center can generate frontend Schema.org JSON-LD and also
provides a dashboard preview of the mappings used by the extension.

Supported frontend structured data includes:

-   `Organization` or `LocalBusiness` for site identity
-   `WebSite` for the website
-   `WebPage` for frontend pages
-   `Article` for Joomla article pages when Joomla does not provide an explicit item-level type
-   Joomla core item schemas such as `Book`, `Event` and `Recipe` when configured for the content item
-   `ImageObject` when a suitable page or article image is available

#### Homepage Schema

The homepage can use either `Organization` or `LocalBusiness` as the site
identity type. Website identity and organization identity are configured
separately so the `WebSite.name` value does not have to match the organization
or business name.

Configurable fields include:

-   Website name
-   Organization / LocalBusiness name
-   Alternate name
-   Description
-   Logo
-   Email
-   Telephone
-   Street address
-   Locality / city
-   Region
-   Postal code
-   Country
-   `sameAs` social/profile URLs

JT SEO also connects the site identity to `WebSite` and `WebPage` nodes by
stable `@id` references.

#### SP Page Builder and other component pages

When general WebPage Schema output is enabled, JT SEO can add safe `WebPage`
structured data to non-article frontend pages. This is component-independent,
so pages rendered by SP Page Builder and other Joomla components do not need a
dedicated integration just to receive the basic page-level Schema.

Generic `WebPage` output does not receive synthetic `datePublished` or
`dateModified` values. Article publication and modification dates are reserved
for Joomla article structured data.

#### Multilingual Schema identity

Language-specific Schema values can be configured using Joomla content language
tags such as `en-GB`, `de-DE` and `tr-TR`.

Per-language overrides are available for:

-   Website name
-   Organization / LocalBusiness name
-   Alternate name
-   Description
-   Logo
-   Email
-   Telephone
-   Street address
-   Locality / city
-   Region
-   Postal code
-   Country

When a matching language row exists, JT SEO uses those values for the active
frontend language. Empty language-specific values fall back to the global
Schema settings.

The website name is independent from the Organization / LocalBusiness name.
This allows `WebSite.name` and `og:site_name` to use the correct localized site
name while the organization identity keeps its own localized name.

#### Article dates

Joomla article pages can output both `datePublished` and `dateModified`.
`datePublished` uses the Joomla article publication date and falls back to the
created date when the publication date is unavailable. `dateModified` uses the
Joomla article modification date and falls back to `datePublished` when a
separate modification date is unavailable.

#### Absolute Schema media URLs

JT SEO normalizes Schema image and logo paths to absolute site-root URLs. This
prevents relative paths such as `images/logo.png` from resolving below a
multilingual page route such as `/en/products/images/logo.png`.

#### Joomla core Schema.org compatibility

Joomla core Schema.org support may remain enabled. When JT SEO detects Joomla's
native Schema graph, it enhances the existing graph in place instead of
publishing a competing `Article` or `WebPage` graph.

Explicitly selected Joomla item-level types such as `Book`, `Event` and `Recipe`
are preserved. If Joomla's base graph is present but the selected item-level
entity is missing from the rendered graph, JT SEO can restore that entity from
Joomla's own `#__schemaorg` record and connect `WebPage.mainEntity` to it.

When Joomla core Schema output is disabled or unavailable and no explicit core
item schema is active, JT SEO continues to generate its fallback `Article`
entity for Joomla article pages. This also works on SEF and multilingual Single
Article menu routes where the routed article id may only be available from the
active menu item during late rendering events.

#### Duplicate Schema protection

JT SEO can inspect existing `application/ld+json` blocks before inserting its
own graph. When duplicate protection is enabled, matching Schema types and
`@id` values are handled conservatively to reduce competing structured data.

A reference-only `@id` such as a `WebPage.mainEntity` pointer is not treated as
an existing declared entity unless the referenced node itself declares an
`@type`. This prevents references from accidentally suppressing JT SEO's real
fallback `Article` entity.

JSON-LD duplicate protection cannot safely remove Article Microdata embedded
directly by a template with `itemscope`, `itemtype` and `itemprop`. If a
template publishes a separate Article Microdata entity, remove that Article
Microdata from the template when using Joomla / JT SEO JSON-LD Article output
to avoid two Article entities in structured-data validators.

The dashboard Structured Data panel remains useful for reviewing the generated
mappings and sample JSON-LD.

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
pkg_jtseo_v1.0.69.zip
```

4.  Wait for Joomla to complete the package installation.
5.  Go to **Components → JT SEO Control Center**.
6.  Open the **System Status** section.
7.  Confirm that the required plugins and database tables are healthy.

A fresh installation enables both JT SEO plugins automatically. Updates preserve
existing plugin states. If a required plugin is disabled, enable it through
**System → Manage → Plugins** before testing its features.

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
9.  Enable Structured Data output.
10. Choose `Organization` or `LocalBusiness` for the homepage identity.
11. Configure the website name separately from the organization / business name.
12. Configure the logo and other organization identity fields.
13. Add language-specific website and organization identity values if the site
    is multilingual.
14. Keep existing Schema detection enabled when Joomla core Schema.org, another
    extension, template or page builder may already output structured data.
15. Enable XML sitemap.
16. Enable multilingual sitemap support if the website uses multiple content
    languages.
17. Review the maximum links per sitemap file setting for large websites.
18. Enable physical `sitemap.xml` generation if the Joomla root folder is
    writable.
19. Enable redirects if redirect management is needed.
20. Enable 404 logging if broken URL tracking is needed.

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

### Structured Data

  -----------------------------------------------------------------------
  Option                       Description
  ---------------------------- ------------------------------------------
  Enable Structured Data       Outputs JT SEO frontend JSON-LD

  Enable WebPage Schema        Adds general `WebPage` Schema to supported
                               non-article frontend pages

  Skip existing Schema         Detects existing JSON-LD and reduces
                               competing structured-data output

  Homepage Schema type         Selects `Organization` or `LocalBusiness`

  Website name                 Configures the global `WebSite.name` value and
                               `og:site_name` identity

  Organization identity        Configures organization / business name,
                               alternate name, description, logo, email and
                               telephone

  Organization address         Configures street, locality, region, postal
                               code and country

  Organization sameAs          Adds valid HTTP/HTTPS social or profile URLs

  Language-specific Schema     Overrides website name and organization
  settings                     identity fields for published Joomla language
                               tags
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
  Add Open Graph site name   Adds `og:site_name` using the configured
                             website name for the active language

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

### Frontend Structured Data is not output

Check:

1.  **System - JT SEO Control Center** is enabled.
2.  Structured Data output is enabled in component options.
3.  General WebPage Schema is enabled when testing a non-article component page.
4.  The response is a normal frontend HTML page.
5.  Joomla, template, server or CDN cache is not serving older HTML.

For article pages without an explicit Joomla core item schema, inspect the page
source for an `application/ld+json` script and verify `Article`,
`datePublished` and `dateModified` when the values are available. If an
explicit Joomla type such as `Book` is configured for the article, verify that
selected type instead of expecting a competing fallback `Article` entity.

------------------------------------------------------------------------

### Structured Data appears to be missing when Joomla core or another extension outputs Schema

If **Skip existing Schema** is enabled, JT SEO intentionally checks existing
JSON-LD before publishing its own graph. On Joomla pages where the native core
Schema.org graph is available, JT SEO can enhance that graph in place instead
of adding a second competing `Article` or `WebPage` graph.

Inspect the complete page source before assuming JT SEO output is missing. Look
inside the existing Joomla JSON-LD graph for the configured multilingual
identity, absolute media URLs and the configured item-level entity. For normal
Article output, also verify `datePublished` / `dateModified` values.

If Joomla has an explicit item schema such as `Book`, `Event` or `Recipe`, JT
SEO preserves that entity and links `WebPage.mainEntity` to it. It does not add
a fallback `Article` solely to create a second content entity.

Avoid publishing multiple competing `Article`, `WebPage`, `Organization` or
`WebSite` graphs unless you intentionally manage their relationships.

If a validator still reports two Article entities, inspect the template for
Article Microdata using `itemscope`, `itemtype` or `itemprop`. JSON-LD duplicate
protection does not remove template Microdata; remove the duplicate Article
Microdata from the template when Joomla / JT SEO JSON-LD is the intended source.

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

### Custom Excluded URLs still appear in the sitemap

Check:

1.  Each rule starts with `/`.
2.  Wildcard child rules use the form `/parent/*`.
3.  The exact parent path is also listed separately when it must be
    excluded.
4.  Component options were saved before regenerating the sitemap.
5.  **Generate sitemap.xml** was clicked after changing the rules.
6.  The correct language-specific sitemap file was checked.
7.  Joomla cache, browser cache, CDN cache or server cache is not serving
    an older sitemap file.

For example, to remove both the parent path and all descendants, use:

``` text
/mycotoxin
/mycotoxin/*
```

On multilingual sites, `/mycotoxin/*` also matches language-prefixed child
URLs such as `/en/mycotoxin/...`. To target only English prefixed URLs,
use `/en/mycotoxin` and `/en/mycotoxin/*`.

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

## Development

The repository includes the complete component, both plugins and the package
installer for the current release.

| Path | Contents |
| --- | --- |
| `extensions/com_jtseo/` | Joomla administrator/site component, SQL migrations and media assets |
| `extensions/plg_system_jtseo/` | Frontend SEO, structured data, sitemap, redirect and 404 plugin |
| `extensions/plg_content_jtseo/` | Article SEO fields and live score plugin |
| `package/` | Package manifest, installer, language files and license |
| `tools/build.py` | Reproducible source-package builder using the Python standard library |
| `tests/` | Release metadata, archive integrity and source validation |
| `updates/` | Joomla update feed and changelog |
| `downloads/` | Published installation ZIP, checksum and validation report |

From the repository root, use **Python 3.9 or newer**:

```sh
python3 -m unittest discover -s tests -v
python3 tools/build.py
```

The build creates `dist/pkg_jtseo_v1.0.69.zip`, including all three child
extensions. Install the generated package through Joomla to test source
changes. The standard-library checks validate the update feed and release
checksum, all manifest references, consistent versions, unchanged published
runtime files and reproducible builds. GitHub Actions also checks PHP syntax
with PHP 8.3.

The SHA-256 above identifies the published release asset. Source builds
include the current package README, so their archive checksum can differ
while containing the same extension code. Keep the published ZIP and its
update-feed checksum together; do not replace a release asset with a
development build without updating the release metadata.

Retain the complete SQL update chain when preparing a release so existing
Joomla installations can migrate their database schema.

------------------------------------------------------------------------

## License

GNU General Public License version 2 or later.

See:

[LICENSE](LICENSE)

------------------------------------------------------------------------

## Credits

Developed by JoomTheme.

Multilingual sitemap support, sitemap index pagination, noindex menu
sitemap exclusion, multilingual Included/Excluded URL filtering, homepage
Schema, multilingual structured data and Article Schema improvements were
developed with community feedback and testing from GitHub user `@niaziblog`.

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
