-- JT SEO Control Center uninstall cleanup.
-- Redirect rules and 404 logs are extension-owned operational data.

DROP TABLE IF EXISTS `#__jtseo_redirects`;
DROP TABLE IF EXISTS `#__jtseo_404_logs`;
