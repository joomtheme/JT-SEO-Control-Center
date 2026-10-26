<?php

/**
 * @package     JT SEO Control Center
 * @subpackage  Administrator
 *
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Component installer script.
 */
final class com_jtseoInstallerScript
{
    public function preflight($type, $parent): bool
    {
        if ((string) $type === 'uninstall') {
            $this->ensureLanguageFilesExist([
                [JPATH_SITE, 'com_jtseo.ini'],
                [JPATH_ADMINISTRATOR, 'com_jtseo.ini'],
                [JPATH_ADMINISTRATOR, 'com_jtseo.sys.ini'],
            ]);
        }

        return true;
    }

    /**
     * Keep JT SEO owned database structures aligned after install/update.
     *
     * SQL schema update files intentionally avoid conditional ALTER statements
     * because supported MySQL/MariaDB versions differ in IF NOT EXISTS support.
     * This script performs a safe column-existence check before applying fixes.
     *
     * @param   string  $type    Install action.
     * @param   mixed   $parent  Installer adapter.
     *
     * @return  bool
     */
    public function postflight($type, $parent): bool
    {
        if (!in_array((string) $type, ['install', 'update'], true)) {
            return true;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $this->ensureRedirectTable($db);
            $this->ensureNotFoundTable($db);
            $this->ensureNotFoundProvisionalColumn($db);
        } catch (\Throwable $exception) {
            // Do not block the installer here. Runtime logging performs its own
            // schema availability checks and will fail safely if the DB is locked.
        }

        try {
            $this->refreshPhysicalSitemapFiles();
        } catch (\Throwable $exception) {
            // A sitemap refresh failure must never block extension installation.
            // Runtime sitemap generation still works through the system plugin.
        }

        return true;
    }

    private function refreshPhysicalSitemapFiles(): void
    {
        $params        = ComponentHelper::getParams('com_jtseo');
        $sitemapPath   = JPATH_ROOT . '/sitemap.xml';
        $writePhysical = (int) $params->get('sitemap_write_physical', 1) === 1;

        if ($writePhysical) {
            $written = false;
            $helperFile = JPATH_ADMINISTRATOR . '/components/com_jtseo/src/Helper/SitemapHelper.php';

            if (is_file($helperFile)) {
                require_once $helperFile;
            }

            if (class_exists('JoomTheme\Component\Jtseo\Administrator\Helper\SitemapHelper')) {
                try {
                    $result  = \JoomTheme\Component\Jtseo\Administrator\Helper\SitemapHelper::writePhysicalSitemap($params);
                    $written = !empty($result['success']);
                } catch (\Throwable $exception) {
                    $written = false;
                }
            }

            if ($written) {
                $this->removeLegacyChildSitemapFiles();

                return;
            }

            if ($this->looksLikeSitemapFile($sitemapPath) && $this->isLegacyUnstyledSitemapFile($sitemapPath)) {
                $this->injectStylesheetIntoSitemapFile($sitemapPath);
            }

            return;
        }

        // If physical sitemap output has been disabled, remove stale JT SEO static files
        // so /sitemap.xml can be served dynamically by the system plugin instead.
        if ($this->looksLikeSitemapFile($sitemapPath) && $this->isLegacyUnstyledSitemapFile($sitemapPath)) {
            @unlink($sitemapPath);
        }

        $this->removeLegacyChildSitemapFiles();
    }

    private function removeLegacyChildSitemapFiles(): void
    {
        foreach (glob(JPATH_ROOT . '/sitemap-*.xml') ?: [] as $file) {
            if (!is_string($file) || !is_file($file)) {
                continue;
            }

            $name = basename($file);

            if (preg_match('/^sitemap-[A-Za-z0-9_-]+(?:-[0-9]+)?\.xml$/', $name) !== 1) {
                continue;
            }

            if ($this->looksLikeSitemapFile($file)) {
                @unlink($file);
            }
        }
    }

    private function looksLikeSitemapFile(string $path): bool
    {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }

        $size = (int) @filesize($path);

        if ($size <= 0 || $size > 10485760) {
            return false;
        }

        $content = (string) @file_get_contents($path, false, null, 0, min($size, 65536));

        return str_contains($content, '<urlset') || str_contains($content, '<sitemapindex');
    }

    private function isLegacyUnstyledSitemapFile(string $path): bool
    {
        $content = (string) @file_get_contents($path, false, null, 0, 65536);

        return $content !== ''
            && !str_contains($content, 'xml-stylesheet')
            && (str_contains($content, '<urlset') || str_contains($content, '<sitemapindex'));
    }

    private function injectStylesheetIntoSitemapFile(string $path): void
    {
        if (!is_file($path) || !is_writable($path)) {
            return;
        }

        $content = (string) @file_get_contents($path);

        if ($content === '' || str_contains($content, 'xml-stylesheet')) {
            return;
        }

        if (!str_contains($content, '<urlset') && !str_contains($content, '<sitemapindex')) {
            return;
        }

        $stylesheet = \Joomla\CMS\Uri\Uri::root(false) . 'media/com_jtseo/xsl/sitemap.xsl';
        $pi = '<?xml-stylesheet type="text/xsl" href="' . htmlspecialchars($stylesheet, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '"?>';

        $updated = preg_replace('/^(<\?xml[^?]*\?>)\s*/', '$1' . "\n" . $pi . "\n", $content, 1, $count);

        if ((int) ($count ?? 0) === 0 || !is_string($updated)) {
            $updated = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $pi . "\n" . ltrim($content);
        }

        @file_put_contents($path, $updated, LOCK_EX);
    }

    private function ensureRedirectTable(DatabaseInterface $db): void
    {
        $db->setQuery("CREATE TABLE IF NOT EXISTS `#__jtseo_redirects` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci");
        $db->execute();
    }

    private function ensureNotFoundTable(DatabaseInterface $db): void
    {
        $db->setQuery("CREATE TABLE IF NOT EXISTS `#__jtseo_404_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `request_url` varchar(2048) NOT NULL,
  `url_hash` char(40) NOT NULL,
  `referrer` varchar(2048) NOT NULL DEFAULT '',
  `user_agent` varchar(512) NOT NULL DEFAULT '',
  `hits` int unsigned NOT NULL DEFAULT 1,
  `provisional` tinyint(1) NOT NULL DEFAULT 0,
  `first_hit` datetime NOT NULL,
  `last_hit` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_jtseo_404_url_hash` (`url_hash`),
  KEY `idx_jtseo_404_last_hit` (`last_hit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci");
        $db->execute();
    }

    private function ensureNotFoundProvisionalColumn(DatabaseInterface $db): void
    {
        $columns = $db->getTableColumns('#__jtseo_404_logs', false) ?: [];

        if (isset($columns['provisional'])) {
            return;
        }

        $db->setQuery("ALTER TABLE `#__jtseo_404_logs` ADD COLUMN `provisional` tinyint(1) NOT NULL DEFAULT 0 AFTER `hits`");
        $db->execute();
    }

    /**
     * Create missing language files before Joomla core tries to delete them on uninstall.
     * This avoids non-fatal File::delete warnings on sites where an older package left
     * manifest language entries without matching files.
     *
     * @param array<int, array{0: string, 1: string}> $files
     */
    private function ensureLanguageFilesExist(array $files): void
    {
        foreach (['en-GB', 'tr-TR'] as $tag) {
            foreach ($files as [$basePath, $filename]) {
                $directory = rtrim($basePath, "/\\") . '/language/' . $tag;
                $path = $directory . '/' . $filename;

                if (is_file($path) || !is_dir($directory) || !is_writable($directory)) {
                    continue;
                }

                @file_put_contents($path, '');
            }
        }
    }
}
