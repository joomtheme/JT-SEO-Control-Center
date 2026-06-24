# JT SEO Control Center

**JT SEO Control Center** is a Joomla SEO management package for Joomla 6. It helps site owners, administrators, and developers audit SEO issues, manage article metadata, generate XML sitemaps, track 404 errors, create redirects, preview structured data mappings, and improve Joomla SEO from one clean administrator dashboard.

A clean SEO control center for Joomla: audit articles, manage metadata, generate XML sitemaps, track 404 errors, and create redirects from one dashboard.

Instead of managing common SEO tasks across multiple screens or extensions, JT SEO Control Center brings essential Joomla SEO workflows into a single control center.

The package includes:

* `com_jtseo` — JT SEO Control Center component
* `plg_system_jtseo` — frontend metadata, canonical, sitemap, redirect and 404 handling
* `plg_content_jtseo` — article edit form SEO fields and live SEO score

---

## Key Features

* SEO dashboard for Joomla administrators
* Article SEO audit and scoring
* Bulk metadata editing with pagination and filters
* CSV import and export for SEO fields
* Homepage metadata management
* Article SEO title and description overrides
* Canonical URL management
* Open Graph and Twitter Card metadata
* XML sitemap generation
* Optional physical `sitemap.xml` creation
* 404 tracking with privacy-friendly logging options
* Redirect manager with 301, 302, 307, 308 and 410 support
* Structured Data preview for common JSON-LD mappings
* English and Turkish language support

---

## Requirements

* Joomla 6.x
* PHP version supported by the installed Joomla 6 environment
* MySQL/MariaDB database supported by Joomla
* Joomla administrator access
* Writable Joomla root folder if physical `sitemap.xml` generation is enabled

---

## Package Contents

JT SEO Control Center is distributed as a Joomla package extension.

Install this file through Joomla:

```text
pkg_jtseo_v1.0.62.zip
```

The package installs the following child extensions:

```text
Component:
- JT SEO Control Center

Plugins:
- System - JT SEO Control Center
- Content - JT SEO Control Center
```

The package also includes GPL license files and Joomla language files for English and Turkish.

---

## Main Features

### SEO Dashboard

JT SEO Control Center provides a central dashboard inside the Joomla administrator area.

The dashboard includes:

* Global SEO settings overview
* Article metadata status
* XML sitemap status
* Physical `sitemap.xml` status
* SEO audit summary
* Article SEO scores
* Bulk metadata editor
* Structured Data preview
* Redirect and 404 manager
* System health checks

The dashboard is designed to help site owners quickly understand what needs attention and where to take action.

---

### Homepage Metadata

The component can apply custom SEO metadata to the default Joomla homepage menu item.

Supported homepage fields:

* Homepage SEO title
* Homepage meta description
* Default Open Graph image
* Twitter Card type

This allows the homepage to have clean and controlled SEO/social metadata without editing template files.

---

### Article SEO Fields

The Content plugin adds a **JT SEO** fieldset to Joomla article edit forms.

Supported article override fields:

* SEO title override
* SEO description override
* Canonical URL override
* Open Graph image override
* Robots index directive
* Robots follow directive

These fields are stored inside the Joomla article attributes and do not modify the article body content.

---

### Live Article SEO Score

The Content plugin includes a live SEO score panel inside the Joomla article edit screen.

The score helps editors review:

* Title length
* Meta description presence
* Meta description length
* Article image / Open Graph image availability
* Robots noindex status
* Content word count
* Canonical override usage

The score is a practical editorial guide and is not a search engine ranking guarantee.

---

### Article SEO Scores

The dashboard includes an article scoring view that lists published articles by SEO priority.

It checks for:

* Missing SEO descriptions
* Short or long descriptions
* Missing images
* Noindex articles
* Duplicate aliases
* Low word count
* Canonical overrides

The weakest articles are shown first, making it easier to prioritize SEO improvements.

---

### SEO Audit

The dashboard audit checks published Joomla articles for common SEO issues.

Audit checks include:

* Missing explicit descriptions
* Short descriptions
* Long descriptions
* Missing article or Open Graph images
* Noindex directives
* Duplicate aliases in the same category
* Canonical override usage

The audit table includes severity, issue type and fix suggestions.

---

### Bulk Meta Manager

The Bulk Meta Manager allows administrators to edit SEO override fields for multiple Joomla articles from one dashboard section.

The default view focuses on articles that need attention, so healthy articles do not clutter the screen.

Bulk Meta Manager features include:

* Default **Needs attention** filter
* Pagination options for 10, 25 or 50 articles
* Search by article title, alias or metadata
* Score/status filters
* Compact default editing layout
* Expandable advanced fields
* CSV Import / Export tools in a collapsible panel

Main editable fields:

* SEO title override
* SEO description override

Advanced editable fields:

* Robots index directive
* Robots follow directive
* Canonical URL override
* Open Graph image override

Bulk saves only update JT SEO article override fields. Joomla article titles and article body content are not changed.

Empty fields do not automatically rewrite Joomla article content. Only supported JT SEO override fields are saved.

---

### CSV Import / Export

JT SEO Control Center can export article SEO data to CSV and import edited SEO override fields back into Joomla.

Export data may include:

* Article ID
* Article title
* Category
* Alias
* SEO score
* SEO issue summary
* SEO title override
* SEO description override
* Canonical URL override
* Robots index directive
* Robots follow directive
* Open Graph image override

Writable import columns:

```text
seo_title
seo_description
canonical
robots_index
robots_follow
og_image
```

Important CSV rules:

* Keep `article_id` unchanged.
* Do not edit article IDs manually.
* Article body content is not changed by CSV import.
* Use preview mode before applying large CSV updates.

---

### Canonical URL Manager

The System plugin can output canonical URLs for frontend HTML pages.

Canonical support includes:

* Clean canonical URL output
* Query-string tracking parameter removal
* Article-level canonical override support
* Global enable/disable option

Use canonical overrides only when a page has a preferred URL different from the current Joomla route.

---

### Open Graph and Twitter Card Metadata

JT SEO Control Center can output social metadata for Joomla pages and articles.

Supported metadata includes:

* `og:title`
* `og:description`
* `og:type`
* `og:image`
* `og:site_name`
* `twitter:title`
* `twitter:description`
* `twitter:image`
* `twitter:card`
* Article publishing metadata when available

Article images are selected in this order:

1. JT SEO article Open Graph image override
2. Joomla article intro image
3. Joomla article full image
4. Global default Open Graph image

---

### XML Sitemap Generator

JT SEO Control Center can generate an XML sitemap for Joomla sites.

Sitemap features:

* Include homepage
* Include published Joomla articles
* Exclude articles marked as noindex
* Exclude fallback component URLs
* Configurable article limit
* Dynamic XML endpoint
* Optional physical `sitemap.xml` generation in the Joomla root folder

The physical sitemap is recommended for search engines because it can be accessed directly as:

```text
https://example.com/sitemap.xml
```

If the Joomla root folder is not writable, physical sitemap generation may fail. The dashboard system status will warn about this.

---

### Redirects and 404 Manager

JT SEO Control Center includes a redirect and 404 manager for migration and broken URL handling.

Redirect features:

* Create redirect rules
* Create redirects directly from 404 log entries
* Track redirect hits
* Delete redirect rules
* Clear redirect rules
* Mark removed URLs as `410 Gone`

Supported redirect statuses:

```text
301 Moved Permanently
302 Found
307 Temporary Redirect
308 Permanent Redirect
410 Gone
```

The old URL should be a site-local relative URL such as:

```text
/old-page
```

The target URL can be a relative site URL or a safe HTTP/HTTPS absolute URL when required.

---

### 404 Logging

When enabled, frontend 404 requests are logged in the dashboard.

Logged data may include:

* Requested URL
* Referrer URL, if referrer logging is enabled
* Query string, if query string logging is enabled
* User agent, if optional user-agent logging is enabled
* Last hit date
* Hit count

404 logs help administrators find broken URLs and create redirect rules.

JT SEO Control Center is designed to avoid unnecessary database writes on normal frontend page views. It logs real 404 requests instead of writing temporary records for every successful page request.

For privacy-friendly defaults, optional logging fields such as query strings and user agents should remain disabled unless they are needed for troubleshooting.

---

### Structured Data Preview

JT SEO Control Center includes a safe Structured Data preview panel.

The panel shows how Joomla site and article data can map to common JSON-LD types.

Supported preview mappings:

* Organization
* WebSite
* Article
* BreadcrumbList

This release uses safe preview mode. The Structured Data panel does not automatically inject duplicate JSON-LD into the frontend.

Breadcrumb note:

The Joomla core Breadcrumbs module can be assigned site-wide. Duplicate `BreadcrumbList` JSON-LD validation is only required when a template or another SEO/schema extension also outputs breadcrumb schema.

---

### System Status

The dashboard includes a setup and health check panel.

It checks:

* System plugin status
* Content plugin status
* Sitemap folder writability
* Physical `sitemap.xml` availability
* Redirect database table
* 404 log database table
* Database schema version

If a required plugin is disabled or a database table is missing, the dashboard displays a warning.

---

## Installation

1. Log in to the Joomla Administrator.
2. Go to **System → Install Extensions**.
3. Upload the package ZIP file:

```text
pkg_jtseo_v1.0.62.zip
```

4. Wait for Joomla to complete the package installation.
5. Go to **Components → JT SEO Control Center**.
6. Open the **System Status** section.
7. Confirm that the required plugins and database tables are healthy.

The package attempts to enable the required JT SEO plugins automatically after installation or update.

---

## Updating

To update JT SEO Control Center:

1. Back up the Joomla site and database.
2. Log in to the Joomla Administrator.
3. Go to **System → Install Extensions**.
4. Upload the new package ZIP file.
5. Open **Components → JT SEO Control Center**.
6. Check **System Status**.
7. If Joomla reports database schema warnings, go to **System → Maintenance → Database** and run the database structure update.

JT SEO Control Center includes database update scripts and installer self-healing logic for existing installations.

---

## Basic Setup

After installation, review the following settings.

Go to:

```text
Components → JT SEO Control Center → Options
```

Recommended first setup:

1. Enable homepage metadata.
2. Add homepage SEO title.
3. Add homepage meta description.
4. Select a default Open Graph image.
5. Enable canonical URL output.
6. Enable article Open Graph metadata.
7. Enable article images for Open Graph.
8. Enable article SEO fields.
9. Enable XML sitemap.
10. Enable physical `sitemap.xml` generation if the Joomla root folder is writable.
11. Enable redirects if redirect management is needed.
12. Enable 404 logging if broken URL tracking is needed.

---

## Article SEO Workflow

Recommended editor workflow:

1. Open a Joomla article.
2. Open the **JT SEO** fieldset.
3. Add a clear SEO title if the Joomla article title is not ideal for search.
4. Add a unique SEO description.
5. Select an Open Graph image if the article image is missing or not suitable for social sharing.
6. Leave robots as default unless the article should be noindex or nofollow.
7. Add a canonical URL only when the article has a preferred route.
8. Review the live SEO score.
9. Save the article.

---

## Bulk SEO Workflow

Recommended bulk workflow:

1. Open **Components → JT SEO Control Center**.
2. Go to **Bulk Meta Manager**.
3. Review the default **Needs attention** list.
4. Use search, score/status filters or pagination to narrow the article list.
5. Edit SEO title and SEO description fields directly in the dashboard.
6. Open **Advanced fields** only when canonical, robots or Open Graph image overrides are needed.
7. Save changes.
8. Recheck the SEO audit.

For spreadsheet workflows:

1. Export CSV.
2. Edit supported SEO override columns.
3. Import using preview mode.
4. Review skipped rows and validation results.
5. Disable preview mode and import again to apply changes.

---

## Sitemap Workflow

Recommended sitemap workflow:

1. Open component options.
2. Enable XML sitemap.
3. Enable homepage and article inclusion.
4. Enable noindex exclusion.
5. Enable fallback URL exclusion.
6. Enable physical `sitemap.xml` generation if possible.
7. Open the dashboard.
8. Click **Generate sitemap.xml**.
9. Visit:

```text
https://example.com/sitemap.xml
```

10. Submit the sitemap URL to search engine webmaster tools.

---

## Redirect Workflow

Recommended redirect workflow:

1. Enable redirects in component options.
2. Enable 404 logging if needed.
3. Visit missing frontend URLs or wait for real 404 traffic.
4. Open **Redirects / 404** in the dashboard.
5. Create redirect rules from important 404 URLs.
6. Use `301` for permanent URL changes.
7. Use `410` for removed content that should not redirect.

---

## Configuration Reference

### Global SEO

| Option                            | Description                                              |
| --------------------------------- | -------------------------------------------------------- |
| Enable homepage metadata          | Applies configured title and description to the homepage |
| Homepage SEO title                | Optional homepage title override                         |
| Homepage meta description         | Optional homepage description                            |
| Default Open Graph image          | Fallback image for social previews                       |
| Twitter Card type                 | Selects summary or summary large image                   |
| Add canonical URL                 | Outputs canonical URLs for frontend HTML pages           |
| Add article Open Graph metadata   | Outputs article social metadata                          |
| Use article images for Open Graph | Uses Joomla article images before default fallback       |
| Add article SEO fields            | Adds JT SEO fields to article edit forms                 |
| Minimum article word count        | Used by article SEO score checks                         |

### XML Sitemap

| Option                                  | Description                                       |
| --------------------------------------- | ------------------------------------------------- |
| Enable XML sitemap                      | Enables sitemap output                            |
| Include homepage                        | Adds homepage to sitemap                          |
| Include Joomla articles                 | Adds published articles                           |
| Exclude noindex articles                | Removes noindex articles from sitemap             |
| Exclude component fallback article URLs | Avoids fallback component article URLs            |
| Generate physical sitemap.xml           | Writes a real sitemap.xml file to the Joomla root |
| Article limit                           | Maximum number of articles included               |

### Redirects and 404

| Option            | Description                                              |
| ----------------- | -------------------------------------------------------- |
| Enable redirects  | Enables frontend redirect rule checks                    |
| Log 404 requests  | Stores frontend 404 requests for review                  |
| Log query strings | Optionally stores query strings for 404 diagnostics      |
| Log referrers     | Optionally stores referrer URLs for 404 diagnostics      |
| Log user agents   | Optionally stores user-agent strings for troubleshooting |

### System Plugin

| Option                       | Description                                    |
| ---------------------------- | ---------------------------------------------- |
| Add Open Graph site name     | Adds `og:site_name` using the Joomla site name |
| Add generator meta           | Adds a JT SEO generator meta tag               |
| Remove Joomla generator meta | Removes Joomla default generator meta tag      |

### Support / Community

| Option                  | Description                                                |
| ----------------------- | ---------------------------------------------------------- |
| JED listing/profile URL | Optional Joomla Extension Directory listing or profile URL |
| GitHub repository URL   | Optional GitHub project URL                                |
| Support website URL     | Optional support or documentation URL                      |

---

## Language Support

Included languages:

```text
English: en-GB
Turkish: tr-TR
```

Language files are included for:

* Package
* Component
* System plugin
* Content plugin

---

## Permissions

The component supports Joomla permissions through the component options screen.

Administrators can manage access through:

```text
Components → JT SEO Control Center → Options → Permissions
```

---

## Privacy Notice

JT SEO Control Center can store 404 request URLs, referrer URLs, query strings and user-agent strings when 404 tracking is enabled and the related logging options are active.

This data is used for SEO diagnostics and redirect management.

Site owners should disclose this behavior in their privacy policy where required.

Recommended privacy-friendly defaults:

* Keep query string logging disabled unless needed.
* Keep user-agent logging disabled unless troubleshooting.
* Enable referrer logging only when it is useful for broken-link diagnostics.
* Review and clear old 404 logs periodically.

---

## Security Notes

* The extension uses Joomla administrator access control.
* State-changing dashboard actions use Joomla request token validation.
* Redirect old URLs are expected to be site-local URLs.
* Redirect targets are limited to site-local URLs or safe HTTP/HTTPS URLs.
* CSV import validates uploaded CSV files and supported columns.
* No encrypted or encoded PHP files are included.

---

## Troubleshooting

### The JT SEO article fields do not appear

Check that the plugin is enabled:

```text
Content - JT SEO Control Center
```

Then open the article edit screen again.

---

### Frontend metadata is not output

Check that the plugin is enabled:

```text
System - JT SEO Control Center
```

Also confirm that the relevant feature is enabled in the component options.

---

### sitemap.xml is missing

Check:

1. XML sitemap is enabled.
2. Physical sitemap generation is enabled.
3. Joomla root folder is writable.
4. The dashboard **Generate sitemap.xml** button has been used.

If the root folder is not writable, adjust file permissions or use the dynamic sitemap endpoint.

---

### Redirects are not working

Check:

1. The System plugin is enabled.
2. Redirects are enabled in component options.
3. The old URL is site-local and starts with `/`.
4. The redirect rule is enabled.
5. Browser cache or server cache is not showing an old response.

---

### 404 logs are empty

Check:

1. 404 logging is enabled in component options.
2. The System plugin is enabled.
3. You are testing a real missing frontend URL.
4. Joomla or server-level redirects are not catching the request before Joomla handles it.

---

### Bulk Meta Manager does not show all articles

By default, Bulk Meta Manager focuses on articles that need attention.

To view more articles:

1. Change the filter from **Needs attention** to **All articles**.
2. Increase the page limit to 25 or 50.
3. Use search to find a specific article.

---

### CSV import skipped rows

Check:

1. `article_id` values were not changed.
2. Writable column names are correct.
3. The CSV file uses a valid format.
4. Preview mode results were reviewed before applying changes.

Writable import columns:

```text
seo_title
seo_description
canonical
robots_index
robots_follow
og_image
```

---

### Database schema warning appears

Go to:

```text
System → Maintenance → Database
```

Run Joomla's database structure update if available.

Then return to:

```text
Components → JT SEO Control Center → System Status
```

## Version 1.0.62

### Release Type

JED-ready stable release for Joomla 6.

### Changes

* Improved Bulk Meta Manager user interface.
* Added default **Needs attention** filter for low-score articles.
* Added pagination options for Bulk Meta Manager.
* Collapsed CSV Import / Export tools for a cleaner default view.
* Moved advanced metadata fields into an expandable section.
* Improved Bulk Meta Manager button layout and responsive behavior.
* Optimized 404 logging to avoid unnecessary database writes on normal frontend page views.
* Added privacy-friendly 404 logging options for query strings, referrers and user agents.
* Improved JED listing/profile URL handling.
* Updated package, component and plugin versions to `1.0.62`.
* Improved Joomla 6 and JED readiness.
* Improved English and Turkish language strings.
* Added database update marker for version `1.0.62`.
* Improved package integrity for Joomla Extension Directory review.

---

## License

GNU General Public License version 2 or later.

See:

```text
LICENSE.txt
```

---

## Credits

Developed by JoomTheme.

Website:

```text
https://joomtheme.com
```

---

## Support

For documentation, support and updates, visit:

```text
https://joomtheme.com
```

GitHub repository:

```text
https://github.com/joomtheme/JT-SEO-Control-Center
```

Joomla Extension Directory profile:

```text
https://extensions.joomla.org/profile/profile/details/147240/
```
