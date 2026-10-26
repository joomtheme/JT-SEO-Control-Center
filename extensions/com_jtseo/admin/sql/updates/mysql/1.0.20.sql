-- JT SEO Control Center 1.0.20
-- Redirects and 404 foundation.

CREATE TABLE IF NOT EXISTS `#__jtseo_redirects` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `old_url` varchar(2048) NOT NULL,
  `old_hash` char(40) NOT NULL,
  `new_url` varchar(2048) NOT NULL,
  `status_code` smallint unsigned NOT NULL DEFAULT 301,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `hits` int unsigned NOT NULL DEFAULT 0,
  `last_hit` datetime DEFAULT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_jtseo_redirect_old_hash` (`old_hash`),
  KEY `idx_jtseo_redirect_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jtseo_404_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `request_url` varchar(2048) NOT NULL,
  `url_hash` char(40) NOT NULL,
  `referrer` varchar(2048) NOT NULL DEFAULT '',
  `user_agent` varchar(512) NOT NULL DEFAULT '',
  `hits` int unsigned NOT NULL DEFAULT 1,
  `first_hit` datetime NOT NULL,
  `last_hit` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_jtseo_404_url_hash` (`url_hash`),
  KEY `idx_jtseo_404_last_hit` (`last_hit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
