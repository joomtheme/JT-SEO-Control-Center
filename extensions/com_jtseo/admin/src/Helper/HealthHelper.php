<?php

/**
 * @package     JT SEO Control Center
 * @subpackage  Administrator
 *
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace JoomTheme\Component\Jtseo\Administrator\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class HealthHelper
{
    public const VERSION = '1.0.69';

    /**
     * @return array<string, mixed>
     */
    public static function getDashboardStatus(?Registry $params = null): array
    {
        $params ??= ComponentHelper::getParams('com_jtseo');
        $physicalSitemap = SitemapHelper::getPhysicalSitemapStatus();
        $items = [];

        $items[] = self::statusItem(
            'system_plugin',
            'COM_JTSEO_HEALTH_SYSTEM_PLUGIN',
            self::isPluginEnabled('system', 'jtseo'),
            'COM_JTSEO_HEALTH_SYSTEM_PLUGIN_OK',
            'COM_JTSEO_HEALTH_SYSTEM_PLUGIN_BAD'
        );

        $items[] = self::statusItem(
            'content_plugin',
            'COM_JTSEO_HEALTH_CONTENT_PLUGIN',
            self::isPluginEnabled('content', 'jtseo'),
            'COM_JTSEO_HEALTH_CONTENT_PLUGIN_OK',
            'COM_JTSEO_HEALTH_CONTENT_PLUGIN_BAD'
        );

        $items[] = self::statusItem(
            'sitemap_writable',
            'COM_JTSEO_HEALTH_SITEMAP_WRITABLE',
            !empty($physicalSitemap['directory_writable']),
            'COM_JTSEO_HEALTH_SITEMAP_WRITABLE_OK',
            'COM_JTSEO_HEALTH_SITEMAP_WRITABLE_BAD'
        );

        $sitemapEnabled = (int) $params->get('enable_sitemap', 1) === 1
            && (int) $params->get('sitemap_write_physical', 1) === 1;

        $items[] = self::statusItem(
            'sitemap_exists',
            'COM_JTSEO_HEALTH_SITEMAP_EXISTS',
            !$sitemapEnabled || !empty($physicalSitemap['exists']),
            'COM_JTSEO_HEALTH_SITEMAP_EXISTS_OK',
            'COM_JTSEO_HEALTH_SITEMAP_EXISTS_BAD',
            $sitemapEnabled ? 'warning' : 'secondary'
        );

        $items[] = self::statusItem(
            'redirect_table',
            'COM_JTSEO_HEALTH_REDIRECT_TABLE',
            self::tableExists('#__jtseo_redirects'),
            'COM_JTSEO_HEALTH_REDIRECT_TABLE_OK',
            'COM_JTSEO_HEALTH_REDIRECT_TABLE_BAD'
        );

        $notFoundLoggingEnabled = (int) $params->get('enable_404_logging', 1) === 1;

        $items[] = [
            'key'         => 'notfound_logging',
            'label_key'   => 'COM_JTSEO_HEALTH_404_LOGGING',
            'ok'          => true,
            'message_key' => $notFoundLoggingEnabled ? 'COM_JTSEO_HEALTH_404_LOGGING_ON' : 'COM_JTSEO_HEALTH_404_LOGGING_OFF',
            'severity'    => $notFoundLoggingEnabled ? 'success' : 'secondary',
            'value'       => '',
            'badge_key'   => $notFoundLoggingEnabled ? 'COM_JTSEO_STATUS_ACTIVE' : 'COM_JTSEO_STATUS_DISABLED',
        ];

        $items[] = self::statusItem(
            'notfound_table',
            'COM_JTSEO_HEALTH_404_TABLE',
            self::notFoundTableReady(),
            'COM_JTSEO_HEALTH_404_TABLE_OK',
            'COM_JTSEO_HEALTH_404_TABLE_BAD'
        );

        $schemaVersion = self::getComponentSchemaVersion();
        $schemaOk = $schemaVersion !== '' && version_compare($schemaVersion, self::VERSION, '>=');

        $items[] = self::statusItem(
            'schema_version',
            'COM_JTSEO_HEALTH_SCHEMA_VERSION',
            $schemaOk,
            'COM_JTSEO_HEALTH_SCHEMA_VERSION_OK',
            'COM_JTSEO_HEALTH_SCHEMA_VERSION_BAD',
            'warning',
            $schemaVersion !== '' ? $schemaVersion : 'unknown'
        );

        $warnings = 0;
        $critical = 0;

        foreach ($items as $item) {
            if (!empty($item['ok'])) {
                continue;
            }

            $warnings++;

            if (($item['severity'] ?? 'warning') === 'danger') {
                $critical++;
            }
        }

        return [
            'items'          => $items,
            'warnings'       => $warnings,
            'critical'       => $critical,
            'ok_count'       => count($items) - $warnings,
            'total'          => count($items),
            'all_ok'         => $warnings === 0,
            'schema_version' => $schemaVersion,
            'target_version' => self::VERSION,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function statusItem(
        string $key,
        string $labelKey,
        bool $ok,
        string $okKey,
        string $badKey,
        string $severity = 'danger',
        string $value = ''
    ): array {
        return [
            'key'         => $key,
            'label_key'   => $labelKey,
            'ok'          => $ok,
            'message_key' => $ok ? $okKey : $badKey,
            'severity'    => $ok ? 'success' : $severity,
            'value'       => $value,
        ];
    }

    private static function isPluginEnabled(string $folder, string $element): bool
    {
        try {
            $db = self::db();
            $query = $db->getQuery(true)
                ->select($db->quoteName('enabled'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote($folder))
                ->where($db->quoteName('element') . ' = ' . $db->quote($element));

            $db->setQuery($query);

            return (int) $db->loadResult() === 1;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private static function tableExists(string $table): bool
    {
        try {
            $db = self::db();
            $tableName = $db->replacePrefix($table);
            $db->setQuery('SHOW TABLES LIKE ' . $db->quote($tableName));

            return (string) $db->loadResult() !== '';
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private static function notFoundTableReady(): bool
    {
        try {
            $db = self::db();
            $columns = $db->getTableColumns('#__jtseo_404_logs', false) ?: [];

            return isset(
                $columns['request_url'],
                $columns['url_hash'],
                $columns['hits'],
                $columns['first_hit'],
                $columns['last_hit']
            );
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private static function getComponentSchemaVersion(): string
    {
        try {
            $db = self::db();
            $query = $db->getQuery(true)
                ->select($db->quoteName('extension_id'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_jtseo'));

            $db->setQuery($query);
            $extensionId = (int) $db->loadResult();

            if ($extensionId <= 0) {
                return '';
            }

            $query = $db->getQuery(true)
                ->select($db->quoteName('version_id'))
                ->from($db->quoteName('#__schemas'))
                ->where($db->quoteName('extension_id') . ' = ' . (int) $extensionId);

            $db->setQuery($query);

            return trim((string) $db->loadResult());
        } catch (\Throwable $exception) {
            return '';
        }
    }

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }
}
