# PHP compatibility and 503 investigation

JT SEO Control Center declares PHP 8.3 or later. PHP 8.4 and 8.5 users should
not be required to downgrade as the resolution of a compatibility defect.

## Functional checks performed

The published v1.0.69 package was exercised on these local installations:

| Joomla | PHP | Result |
| --- | --- | --- |
| 6.2.0 | 8.4.27 | Installation, administrator, article SEO and sitemap checks passed |
| 6.1.4 | 8.4.27 | Installation, 68 form checks, repeated administrator navigation and Chromium article editing passed |
| 6.1.4 | 8.5.12 | 68 form checks, repeated administrator navigation and Chromium article editing passed |

The servers use Apache, MariaDB 11.4 and a small development dataset. Browser
checks cover the JT SEO tab, live score updates, saving/reloading overrides,
frontend title/description, canonical URL, Open Graph image, JSON-LD and robots
metadata. Repeated navigation checks request the Joomla dashboard, article
list and JT SEO dashboard 15 times. Article saves completed in under a second
in these installations. These observations do not resolve a production-only
503 or cover every hosting stack and third-party extension combination.

## Repeatable checks

GitHub Actions validates package metadata and PHP syntax on **8.3, 8.4 and
8.5**. These checks do not start Joomla or reproduce the production server.

For installed-site tests, use a development Joomla site with this package
installed and both JT SEO plugins enabled:

```sh
JTSEO_JOOMLA_ROOT=/absolute/path/to/joomla php tests/joomla/compatibility.php
```

The command checks 68 installation/form assertions, including typed and
generic Joomla events, both article form contexts, all six SEO fields,
original-field preservation, the disabled-fields setting and unrelated forms.
It reports the actual Joomla and CLI PHP versions. It only reads the database
and changes component parameters in the current process; credentials are not
printed. Joomla may refresh its normal extension namespace cache during boot.

The CLI PHP binary can differ from the web server's PHP version. Validate
administrator HTTP requests and article saves under the affected web PHP
runtime as well as running the CLI test.

## Open production report

A Joomla 6.1.4 installation returned 503 and experienced administrator stalls
under PHP 8.4. Switching that installation to PHP 8.3 restored service. The
local Apache tests did not reproduce this failure under PHP 8.4 or PHP 8.5.
The cause remains undetermined; neither a general PHP 8.4 incompatibility nor
a hosting-only fault has been established.

To isolate the cause, capture the affected request and timestamp, exact PHP
patch version, web server/PHP handler, and corresponding web/PHP error logs.
Compare the installed PHP extensions and memory/execution/worker limits
between the working and failing PHP configurations. Determine whether the
stall affects every administrator request or only JT SEO, article saves or
sitemap generation. On a staging copy, compare behavior with each JT SEO
plugin enabled and disabled while keeping the remaining site unchanged.

Preserve the failing logs before changing the runtime. A generic
"Service Unavailable / The server is temporarily busy" page alone does not
identify which PHP operation failed. Keep this incident open until its
trigger can be reproduced or traced to a specific logged failure.
