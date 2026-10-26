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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class RedirectHelper
{
    /**
     * @return array<string, int|string>
     */
    public static function getDashboardSummary(): array
    {
        return [
            'enabled_redirects' => self::countRows('#__jtseo_redirects', 'enabled = 1'),
            'total_redirects'   => self::countRows('#__jtseo_redirects'),
            'logged_404s'       => self::countRows('#__jtseo_404_logs'),
            'total_404_hits'    => self::sumHits('#__jtseo_404_logs'),
            'last_404'          => self::last404Date(),
        ];
    }

    /**
     * @return array<int, object>
     */
    public static function getRedirects(int $limit = 10): array
    {
        try {
            $db = self::db();
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__jtseo_redirects'))
                ->order($db->quoteName('modified') . ' DESC, ' . $db->quoteName('id') . ' DESC');

            $db->setQuery($query, 0, max(1, min(100, $limit)));

            return $db->loadObjectList() ?: [];
        } catch (\Throwable $exception) {
            return [];
        }
    }

    /**
     * @return array<int, object>
     */
    public static function get404Logs(int $limit = 10): array
    {
        try {
            $db = self::db();
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__jtseo_404_logs'))
                ->order($db->quoteName('last_hit') . ' DESC, ' . $db->quoteName('hits') . ' DESC');

            $db->setQuery($query, 0, max(1, min(100, $limit)));

            return $db->loadObjectList() ?: [];
        } catch (\Throwable $exception) {
            return [];
        }
    }

    public static function saveRedirect(string $oldUrl, string $newUrl, int $statusCode = 301, int $enabled = 1): array
    {
        $oldUrl = self::normalizeOldUrl($oldUrl);
        $newUrl = self::normalizeNewUrl($newUrl);

        if (!in_array($statusCode, [301, 302, 307, 308, 410], true)) {
            $statusCode = 301;
        }

        if ($oldUrl === '' || ($newUrl === '' && $statusCode !== 410)) {
            return ['success' => false, 'message' => Text::_('COM_JTSEO_REDIRECT_ERROR_URLS_REQUIRED')];
        }

        try {
            $db = self::db();
            $now = Factory::getDate()->toSql();
            $hash = sha1($oldUrl);

            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__jtseo_redirects'))
                ->where($db->quoteName('old_hash') . ' = :old_hash')
                ->bind(':old_hash', $hash);

            $db->setQuery($query);
            $existingId = (int) $db->loadResult();

            if ($existingId > 0) {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__jtseo_redirects'))
                    ->set($db->quoteName('old_url') . ' = :old_url')
                    ->set($db->quoteName('new_url') . ' = :new_url')
                    ->set($db->quoteName('status_code') . ' = :status_code')
                    ->set($db->quoteName('enabled') . ' = :enabled')
                    ->set($db->quoteName('modified') . ' = :modified')
                    ->where($db->quoteName('id') . ' = :id')
                    ->bind(':old_url', $oldUrl)
                    ->bind(':new_url', $newUrl)
                    ->bind(':status_code', $statusCode, ParameterType::INTEGER)
                    ->bind(':enabled', $enabled, ParameterType::INTEGER)
                    ->bind(':modified', $now)
                    ->bind(':id', $existingId, ParameterType::INTEGER);

                $db->setQuery($query)->execute();

                return ['success' => true, 'message' => Text::_('COM_JTSEO_REDIRECT_MESSAGE_UPDATED')];
            }

            $columns = ['old_url', 'old_hash', 'new_url', 'status_code', 'enabled', 'hits', 'created', 'modified'];
            $values = [
                $db->quote($oldUrl),
                $db->quote($hash),
                $db->quote($newUrl),
                (string) $statusCode,
                (string) $enabled,
                '0',
                $db->quote($now),
                $db->quote($now),
            ];

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__jtseo_redirects'))
                ->columns($db->quoteName($columns))
                ->values(implode(',', $values));

            $db->setQuery($query)->execute();

            return ['success' => true, 'message' => Text::_('COM_JTSEO_REDIRECT_MESSAGE_CREATED')];
        } catch (\Throwable $exception) {
            return ['success' => false, 'message' => $exception->getMessage()];
        }
    }

    public static function deleteRedirect(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $db = self::db();
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__jtseo_redirects'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);

            $db->setQuery($query)->execute();

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }


    public static function clearRedirects(): bool
    {
        try {
            $db = self::db();
            $query = $db->getQuery(true)->delete($db->quoteName('#__jtseo_redirects'));
            $db->setQuery($query)->execute();

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    public static function clear404Logs(): bool
    {
        try {
            $db = self::db();
            $query = $db->getQuery(true)->delete($db->quoteName('#__jtseo_404_logs'));
            $db->setQuery($query)->execute();

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }


    public static function delete404Log(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $db = self::db();
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__jtseo_404_logs'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);

            $db->setQuery($query)->execute();

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    public static function normalizeOldUrl(string $url): string
    {
        $url = self::stripUnsafeUrl($url);

        if ($url === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            $site = rtrim(Uri::root(false), '/');

            if (str_starts_with($url, $site . '/')) {
                $url = substr($url, strlen($site));
            } else {
                return '';
            }
        }

        if ($url === '') {
            return '/';
        }

        if ($url[0] !== '/') {
            $url = '/' . $url;
        }

        return $url;
    }

    public static function normalizeNewUrl(string $url): string
    {
        $url = self::stripUnsafeUrl($url);

        if ($url === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
        }

        if ($url[0] !== '/') {
            $url = '/' . $url;
        }

        return $url;
    }

    private static function stripUnsafeUrl(string $url): string
    {
        $url = trim(strip_tags($url));
        $url = strtok($url, '#') ?: $url;
        $url = trim($url);

        if ($url === '' || self::containsControlCharacter($url)) {
            return '';
        }

        if (preg_match('#^(?:javascript|data|vbscript|file):#i', $url) === 1) {
            return '';
        }

        if (strpos($url, '//') === 0) {
            return '';
        }

        return $url;
    }


    private static function containsControlCharacter(string $value): bool
    {
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            $code = ord($value[$i]);

            if ($code < 32 || $code === 127) {
                return true;
            }
        }

        return false;
    }

    private static function countRows(string $table, string $where = ''): int
    {
        try {
            $db = self::db();
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName($table));

            if ($where !== '') {
                $query->where($where);
            }

            $db->setQuery($query);

            return (int) $db->loadResult();
        } catch (\Throwable $exception) {
            return 0;
        }
    }

    private static function sumHits(string $table): int
    {
        try {
            $db = self::db();
            $query = $db->getQuery(true)
                ->select('COALESCE(SUM(' . $db->quoteName('hits') . '), 0)')
                ->from($db->quoteName($table));

            $db->setQuery($query);

            return (int) $db->loadResult();
        } catch (\Throwable $exception) {
            return 0;
        }
    }

    private static function last404Date(): string
    {
        try {
            $db = self::db();
            $query = $db->getQuery(true)
                ->select('MAX(' . $db->quoteName('last_hit') . ')')
                ->from($db->quoteName('#__jtseo_404_logs'));

            $db->setQuery($query);

            return (string) $db->loadResult();
        } catch (\Throwable $exception) {
            return '';
        }
    }

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }
}
