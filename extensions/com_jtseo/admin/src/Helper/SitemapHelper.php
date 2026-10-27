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

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper as ContentRouteHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

final class SitemapHelper
{
    private const DEFAULT_LINKS_PER_SITEMAP = 10000;

    /**
     * Build sitemap XML using component parameters.
     */
    public static function buildXml(?Registry $params = null): array
    {
        $params ??= ComponentHelper::getParams('com_jtseo');

        $languages = self::getSitemapLanguages($params);

        if (count($languages) > 1) {
            return self::buildSitemapIndexXml($params, $languages);
        }

        return self::buildUrlsetXml(self::getSitemapUrls($params));
    }

    /**
     * Build the correct XML document for a sitemap request path.
     */
    public static function buildForPath(string $requestPath, ?Registry $params = null): array
    {
        $params ??= ComponentHelper::getParams('com_jtseo');
        $path = self::normaliseSitemapRequestPath($requestPath);

        if ($path === '' || $path === 'sitemap.xml') {
            return self::buildXml($params);
        }

        $match = self::matchLanguageSitemapPath($path, $params);

        if ($match === null) {
            return self::buildUrlsetXml([]);
        }

        return self::buildLanguageXml($params, $match['language']['lang_code'], $match['page']);
    }

    /**
     * Write sitemap.xml and, when needed, language/paginated child sitemap files in the Joomla root folder.
     */
    public static function writePhysicalSitemap(?Registry $params = null): array
    {
        $params ??= ComponentHelper::getParams('com_jtseo');
        $path = JPATH_ROOT . '/sitemap.xml';

        if ((int) $params->get('enable_sitemap', 1) !== 1) {
            return [
                'success' => false,
                'message' => Text::_('COM_JTSEO_SITEMAP_DISABLED'),
                'path'    => $path,
                'url'     => Uri::root(false) . 'sitemap.xml',
                'count'   => 0,
            ];
        }

        if ((int) $params->get('sitemap_write_physical', 1) !== 1) {
            return [
                'success' => false,
                'message' => Text::_('COM_JTSEO_SITEMAP_PHYSICAL_DISABLED'),
                'path'    => $path,
                'url'     => Uri::root(false) . 'sitemap.xml',
                'count'   => 0,
            ];
        }

        $directory = dirname($path);

        if (!is_writable($directory)) {
            return [
                'success' => false,
                'message' => Text::_('COM_JTSEO_SITEMAP_ROOT_NOT_WRITABLE'),
                'path'    => $path,
                'url'     => Uri::root(false) . 'sitemap.xml',
                'count'   => 0,
            ];
        }

        $built = self::buildXml($params);
        $bytes = @file_put_contents($path, $built['xml'], LOCK_EX);

        if ($bytes === false) {
            return [
                'success' => false,
                'message' => Text::_('COM_JTSEO_SITEMAP_WRITE_FAILED'),
                'path'    => $path,
                'url'     => Uri::root(false) . 'sitemap.xml',
                'count'   => 0,
            ];
        }

        $totalBytes = (int) $bytes;

        foreach (($built['sitemaps'] ?? []) as $sitemap) {
            $childPath = basename((string) ($sitemap['path'] ?? ''));

            if ($childPath === '' || $childPath === 'sitemap.xml') {
                continue;
            }

            $childBuilt = self::buildLanguageXml(
                $params,
                (string) ($sitemap['language'] ?? ''),
                max(1, (int) ($sitemap['page'] ?? 1))
            );

            $childBytes = @file_put_contents(JPATH_ROOT . '/' . $childPath, $childBuilt['xml'], LOCK_EX);

            if ($childBytes === false) {
                return [
                    'success' => false,
                    'message' => Text::_('COM_JTSEO_SITEMAP_WRITE_FAILED'),
                    'path'    => JPATH_ROOT . '/' . $childPath,
                    'url'     => Uri::root(false) . $childPath,
                    'count'   => 0,
                ];
            }

            $totalBytes += (int) $childBytes;
        }

        return [
            'success' => true,
            'message' => Text::_('COM_JTSEO_SITEMAP_WRITE_SUCCESS'),
            'path'    => $path,
            'url'     => Uri::root(false) . 'sitemap.xml',
            'count'   => (int) ($built['count'] ?? 0),
            'bytes'   => $totalBytes,
        ];
    }

    public static function getPhysicalSitemapStatus(): array
    {
        $path = JPATH_ROOT . '/sitemap.xml';
        $exists = is_file($path);

        return [
            'path'               => $path,
            'url'                => Uri::root(false) . 'sitemap.xml',
            'exists'             => $exists,
            'writable'           => $exists ? is_writable($path) : is_writable(dirname($path)),
            'directory_writable' => is_writable(dirname($path)),
            'size'               => $exists ? (int) filesize($path) : 0,
            'modified'           => $exists ? (int) filemtime($path) : 0,
        ];
    }

    private static function buildSitemapIndexXml(Registry $params, array $languages): array
    {
        $sitemaps = [];
        $totalUrls = 0;
        $linksPerSitemap = self::getLinksPerSitemap($params);

        foreach ($languages as $language) {
            $languageTag = (string) ($language['lang_code'] ?? '');

            if ($languageTag === '') {
                continue;
            }

            $urls = self::getSitemapUrls($params, $languageTag);
            $urlCount = count($urls);

            if ($urlCount === 0) {
                continue;
            }

            $totalUrls += $urlCount;
            $pages = max(1, (int) ceil($urlCount / $linksPerSitemap));

            for ($page = 1; $page <= $pages; $page++) {
                $path = self::getLanguageSitemapFilename($languageTag, $page);
                $pageUrls = array_slice($urls, ($page - 1) * $linksPerSitemap, $linksPerSitemap);
                $sitemaps[] = [
                    'loc'      => Uri::root(false) . $path,
                    'lastmod'  => self::getLatestSitemapItemDate($pageUrls),
                    'language' => $languageTag,
                    'page'     => $page,
                    'path'     => $path,
                ];
            }
        }

        if ($sitemaps === []) {
            return self::buildUrlsetXml(self::getSitemapUrls($params));
        }

        $stylesheet = Uri::root(false) . 'media/com_jtseo/xsl/sitemap.xsl';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?xml-stylesheet type="text/xsl" href="' . self::escapeXml($stylesheet) . '"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $item) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . self::escapeXml((string) $item['loc']) . "</loc>\n";

            if (!empty($item['lastmod'])) {
                $xml .= '    <lastmod>' . self::escapeXml((string) $item['lastmod']) . "</lastmod>\n";
            }

            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>' . "\n";

        return [
            'xml'           => $xml,
            'count'         => $totalUrls,
            'sitemap_count' => count($sitemaps),
            'sitemaps'      => $sitemaps,
            'urls'          => [],
        ];
    }

    private static function buildLanguageXml(Registry $params, string $languageTag, int $page = 1): array
    {
        $page = max(1, $page);
        $linksPerSitemap = self::getLinksPerSitemap($params);
        $urls = self::getSitemapUrls($params, $languageTag);
        $total = count($urls);
        $urls = array_slice($urls, ($page - 1) * $linksPerSitemap, $linksPerSitemap);
        $built = self::buildUrlsetXml($urls);
        $built['total_count'] = $total;
        $built['language'] = $languageTag;
        $built['page'] = $page;

        return $built;
    }

    private static function buildUrlsetXml(array $urls): array
    {
        $urls = self::deduplicateSitemapItems($urls);
        $stylesheet = Uri::root(false) . 'media/com_jtseo/xsl/sitemap.xsl';
        $hasImages = false;
        $imageCount = 0;

        foreach ($urls as $item) {
            $images = self::normaliseImageItems((array) ($item['images'] ?? []));

            if ($images !== []) {
                $hasImages = true;
                $imageCount += count($images);
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?xml-stylesheet type="text/xsl" href="' . self::escapeXml($stylesheet) . '"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';

        if ($hasImages) {
            $xml .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        }

        $xml .= '>' . "\n";

        foreach ($urls as $item) {
            $loc = trim((string) ($item['loc'] ?? ''));

            if ($loc === '') {
                continue;
            }

            $xml .= "  <url>\n";
            $xml .= '    <loc>' . self::escapeXml($loc) . "</loc>\n";

            if (!empty($item['lastmod'])) {
                $xml .= '    <lastmod>' . self::escapeXml((string) $item['lastmod']) . "</lastmod>\n";
            }

            if (!empty($item['changefreq'])) {
                $xml .= '    <changefreq>' . self::escapeXml((string) $item['changefreq']) . "</changefreq>\n";
            }

            if (!empty($item['priority'])) {
                $xml .= '    <priority>' . self::escapeXml((string) $item['priority']) . "</priority>\n";
            }

            foreach (self::normaliseImageItems((array) ($item['images'] ?? [])) as $imageUrl) {
                $xml .= "    <image:image>\n";
                $xml .= '      <image:loc>' . self::escapeXml($imageUrl) . "</image:loc>\n";
                $xml .= "    </image:image>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>' . "\n";

        return [
            'xml'         => $xml,
            'count'       => count($urls),
            'image_count' => $imageCount,
            'urls'        => $urls,
        ];
    }

    private static function getSitemapUrls(Registry $params, ?string $languageTag = null): array
    {
        $urls = [];
        $homepageSettings = self::getHomepageSitemapSettings($params, $languageTag);

        if ((int) $params->get('sitemap_include_homepage', 1) === 1) {
            $urls[] = [
                'loc'        => self::getHomepageUrl($languageTag),
                'lastmod'    => self::getHomepageLastmod($params, $languageTag, $homepageSettings),
                'changefreq' => $homepageSettings['changefreq'],
                'priority'   => $homepageSettings['priority'],
                'images'     => [],
            ];
        }

        if ((int) $params->get('sitemap_include_articles', 1) === 1) {
            $urls = array_merge($urls, self::getArticleSitemapItems($params, $languageTag));
        }

        if ((int) $params->get('sitemap_include_menu_items', 0) === 1) {
            $urls = array_merge($urls, self::getMenuSitemapItems($params, $languageTag));
        }

        $urls = array_merge($urls, self::getExplicitIncludedUrlItems($params, $languageTag));
        $urls = self::deduplicateSitemapItems($urls);

        return self::filterSitemapUrls($urls, $params, $languageTag);
    }

    /**
     * Add public site menu destinations independently of article enumeration.
     * Home items remain controlled by sitemap_include_homepage. Aliases,
     * headings and separators are navigation structure, not new destinations.
     */
    private static function getMenuSitemapItems(Registry $params, ?string $languageTag = null): array
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $now = Factory::getDate()->toSql();
            $levels = array_values(array_unique(array_filter(
                array_map('intval', Access::getAuthorisedViewLevels(0) ?: [1]),
                static fn (int $level): bool => $level > 0
            )));
            $publicLevels = implode(',', $levels ?: [1]);
            $query = $db->getQuery(true)
                ->select('m.*')
                ->from($db->quoteName('#__menu', 'm'))
                ->where($db->quoteName('m.client_id') . ' = 0')
                ->where($db->quoteName('m.published') . ' = 1')
                ->where($db->quoteName('m.access') . ' IN (' . $publicLevels . ')')
                ->where($db->quoteName('m.home') . ' = 0')
                ->where($db->quoteName('m.type') . ' IN (' . $db->quote('component') . ',' . $db->quote('url') . ')')
                ->order($db->quoteName('m.lft') . ' ASC');

            // A public child beneath a restricted or unpublished parent is not public.
            $unavailable = [
                $db->quoteName('p.published') . ' <> 1',
                $db->quoteName('p.access') . ' NOT IN (' . $publicLevels . ')',
            ];
            foreach (['publish_up' => '>', 'publish_down' => '<'] as $column => $operator) {
                $field = $db->quoteName('p.' . $column);
                $unavailable[] = '(' . $field . ' IS NOT NULL AND ' . $field . ' <> ' . $db->quote('0000-00-00 00:00:00') . ' AND ' . $field . ' ' . $operator . ' ' . $db->quote($now) . ')';
            }
            $ancestor = $db->getQuery(true)
                ->select('1')
                ->from($db->quoteName('#__menu', 'p'))
                ->where($db->quoteName('p.lft') . ' < ' . $db->quoteName('m.lft'))
                ->where($db->quoteName('p.rgt') . ' > ' . $db->quoteName('m.rgt'))
                ->where($db->quoteName('p.parent_id') . ' > 0')
                ->where('(' . implode(' OR ', $unavailable) . ')');
            $query->where('NOT EXISTS (' . $ancestor . ')');

            foreach (['publish_up' => '<=', 'publish_down' => '>='] as $column => $operator) {
                $field = $db->quoteName('m.' . $column);
                $query->where('(' . $field . ' IS NULL OR ' . $field . ' = ' . $db->quote('0000-00-00 00:00:00') . ' OR ' . $field . ' ' . $operator . ' ' . $db->quote($now) . ')');
            }

            if ($languageTag !== null && $languageTag !== '') {
                $query->where($db->quoteName('m.language') . ' IN (' . $db->quote('*') . ', :jtseo_menu_language)')
                    ->bind(':jtseo_menu_language', $languageTag);
            }

            $rows = $db->setQuery($query)->loadObjectList() ?: [];
        } catch (\Throwable $exception) {
            return [];
        }

        $items = [];
        $root = rtrim(Uri::root(false), '/') . '/';
        $homepage = self::normaliseUrlKey(self::getHomepageUrl($languageTag));

        foreach ($rows as $row) {
            $link = trim(html_entity_decode((string) ($row->link ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            // Reject external destinations, protocol-relative links and non-page schemes.
            if ($link === '' || str_starts_with($link, '//') || str_contains($link, '\\')
                || self::containsControlCharacter($link) || str_starts_with($link, '#')
                || (preg_match('#^[a-z][a-z0-9+.-]*:#i', $link) === 1 && preg_match('#^https?://#i', $link) !== 1)) {
                continue;
            }

            $absolute = self::toAbsoluteUrl($link);

            if ($absolute === '' || !str_starts_with($absolute, $root)) {
                continue;
            }

            parse_str((string) parse_url($link, PHP_URL_QUERY), $target);

            if (($target['option'] ?? '') === 'com_content' && ($target['view'] ?? '') === 'article') {
                $articleId = (int) ($target['id'] ?? 0);

                // Reuse article visibility, publication, canonical and noindex rules
                // without enumerating every article when the source is disabled.
                if ($articleId <= 0 || self::getArticleSitemapItems($params, $languageTag, $articleId) === []) {
                    continue;
                }
            }

            $route = 'index.php?Itemid=' . (int) $row->id;
            $sef = self::getLanguageSef($languageTag ?: ((string) $row->language !== '*' ? (string) $row->language : null));

            if ($sef !== '') {
                $route .= '&lang=' . rawurlencode($sef);
            }

            // URL-type items have no component router; route their actual internal link.
            if ((string) $row->type === 'url') {
                $route = $link;
            }

            $url = self::toRoutedUrl($route);

            if ($url === '' || !str_starts_with($url, $root)
                || self::normaliseUrlKey($url) === $homepage
                || ((int) $params->get('sitemap_exclude_fallback_urls', 1) === 1 && self::isFallbackArticleUrl($url))) {
                continue;
            }

            $items[] = [
                'loc'        => $url,
                'lastmod'    => '',
                'changefreq' => self::normaliseChangefreq((string) $params->get('sitemap_internal_changefreq', 'weekly'), 'weekly'),
                'priority'   => self::normalisePriority((string) $params->get('sitemap_internal_priority', '0.8'), '0.8'),
                'images'     => [],
            ];
        }

        return $items;
    }

    private static function getArticleSitemapItems(Registry $params, ?string $languageTag = null, ?int $articleId = null): array
    {
        try {
            /** @var DatabaseInterface $db */
            $db  = Factory::getContainer()->get(DatabaseInterface::class);
            $now = Factory::getDate()->toSql();

            $guestViewLevels = array_map('intval', Access::getAuthorisedViewLevels(0) ?: [1]);
            $guestViewLevels = array_values(array_unique(array_filter($guestViewLevels, static fn (int $level): bool => $level > 0)));

            if ($guestViewLevels === []) {
                $guestViewLevels = [1];
            }

            $quotedGuestViewLevels = implode(',', array_map(static fn (int $level): string => (string) $level, $guestViewLevels));
            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('a.id'),
                    $db->quoteName('a.catid'),
                    $db->quoteName('a.alias'),
                    $db->quoteName('a.attribs'),
                    $db->quoteName('a.created'),
                    $db->quoteName('a.modified'),
                    $db->quoteName('a.publish_up'),
                    $db->quoteName('a.access'),
                    $db->quoteName('a.language'),
                    $db->quoteName('a.images'),
                    $db->quoteName('a.introtext'),
                    $db->quoteName('a.fulltext'),
                    $db->quoteName('c.alias') . ' AS ' . $db->quoteName('category_alias'),
                    $db->quoteName('c.access') . ' AS ' . $db->quoteName('category_access'),
                    $db->quoteName('c.language') . ' AS ' . $db->quoteName('category_language'),
                    $db->quoteName('c.params') . ' AS ' . $db->quoteName('category_params'),
                ])
                ->from($db->quoteName('#__content', 'a'))
                ->leftJoin($db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
                ->where($db->quoteName('a.state') . ' = 1')
                ->where($db->quoteName('a.access') . ' IN (' . $quotedGuestViewLevels . ')')
                ->where('(' . $db->quoteName('c.access') . ' IN (' . $quotedGuestViewLevels . ') OR ' . $db->quoteName('c.access') . ' IS NULL)')
                ->where('(' . $db->quoteName('c.published') . ' = 1 OR ' . $db->quoteName('c.published') . ' IS NULL)')
                ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' = ' . $db->quote('0000-00-00 00:00:00') . ' OR ' . $db->quoteName('a.publish_up') . ' <= ' . $db->quote($now) . ')')
                ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' = ' . $db->quote('0000-00-00 00:00:00') . ' OR ' . $db->quoteName('a.publish_down') . ' >= ' . $db->quote($now) . ')')
                ->order($db->quoteName('a.modified') . ' DESC, ' . $db->quoteName('a.id') . ' DESC');

            if ($languageTag !== null && $languageTag !== '') {
                $query->where('(' . $db->quoteName('a.language') . ' = ' . $db->quote('*') . ' OR ' . $db->quoteName('a.language') . ' = :jtseo_language)')
                    ->where('(' . $db->quoteName('c.language') . ' = ' . $db->quote('*') . ' OR ' . $db->quoteName('c.language') . ' = :jtseo_category_language OR ' . $db->quoteName('c.language') . ' IS NULL)')
                    ->bind(':jtseo_language', $languageTag)
                    ->bind(':jtseo_category_language', $languageTag);
            }

            if ($articleId !== null) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) $articleId);
            }

            $limit = max(1, min(50000, (int) $params->get('sitemap_article_limit', 50000)));
            $db->setQuery($query, 0, $limit);

            $rows = $db->loadObjectList() ?: [];
        } catch (\Throwable $exception) {
            return [];
        }

        $items                     = [];
        $excludeNoindex            = (int) $params->get('sitemap_exclude_noindex', 1) === 1;
        $excludeFallbackArticleUrl = (int) $params->get('sitemap_exclude_fallback_urls', 1) === 1;
        $root                      = rtrim(Uri::root(false), '/') . '/';

        foreach ($rows as $row) {
            $attribs = new Registry((string) ($row->attribs ?? ''));

            if ($excludeNoindex && (string) $attribs->get('jtseo_robots_index', '') === 'noindex') {
                continue;
            }

            $canonicalOverride = trim((string) $attribs->get('jtseo_canonical', ''));

            if ($canonicalOverride !== '') {
                $url = self::toAbsoluteUrl($canonicalOverride);

                if (!str_starts_with($url, $root)) {
                    continue;
                }
            } else {
                $url = self::getArticleUrl($row, $languageTag);
            }

            if ($url === '') {
                continue;
            }

            if ($excludeFallbackArticleUrl && self::isFallbackArticleUrl($url)) {
                continue;
            }

            $lastmod = self::toSitemapDate((string) ($row->modified ?? ''));

            if ($lastmod === '') {
                $lastmod = self::toSitemapDate((string) ($row->created ?? ''));
            }

            $items[] = [
                'loc'        => $url,
                'lastmod'    => $lastmod,
                'changefreq' => self::normaliseChangefreq((string) $params->get('sitemap_internal_changefreq', 'weekly'), 'weekly'),
                'priority'   => self::normalisePriority((string) $params->get('sitemap_internal_priority', '0.8'), '0.8'),
                'images'     => (int) $params->get('sitemap_include_images', 0) === 1
                    ? self::extractSitemapImages($row)
                    : [],
            ];
        }

        return $items;
    }

    private static function getHomepageSitemapSettings(Registry $params, ?string $languageTag = null): array
    {
        $settings = [
            'changefreq' => self::normaliseChangefreq((string) $params->get('sitemap_homepage_changefreq', 'daily'), 'daily'),
            'priority'   => self::normalisePriority((string) $params->get('sitemap_homepage_priority', '1.0'), '1.0'),
            'lastmod'    => '',
        ];

        if ($languageTag === null || $languageTag === '') {
            return $settings;
        }

        foreach (self::normaliseSubformRows($params->get('sitemap_homepage_language_settings', [])) as $row) {
            $rowLanguage = trim((string) ($row['language'] ?? ''));

            if ($rowLanguage === '' || strcasecmp($rowLanguage, $languageTag) !== 0) {
                continue;
            }

            $settings['changefreq'] = self::normaliseChangefreq((string) ($row['changefreq'] ?? ''), $settings['changefreq']);
            $settings['priority'] = self::normalisePriority((string) ($row['priority'] ?? ''), $settings['priority']);
            $settings['lastmod'] = self::toSitemapDate((string) ($row['lastmod'] ?? ''));
            break;
        }

        return $settings;
    }

    private static function getHomepageLastmod(Registry $params, ?string $languageTag, array $homepageSettings): string
    {
        $override = self::toSitemapDate((string) ($homepageSettings['lastmod'] ?? ''));

        if ($override !== '') {
            return $override;
        }

        if ((string) $params->get('sitemap_homepage_lastmod_source', 'none') !== 'latest_article') {
            return '';
        }

        return self::getLatestPublishedArticleDate($languageTag);
    }

    private static function getLatestPublishedArticleDate(?string $languageTag = null): string
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $now = Factory::getDate()->toSql();
            $guestViewLevels = array_values(array_unique(array_filter(
                array_map('intval', Access::getAuthorisedViewLevels(0) ?: [1]),
                static fn (int $level): bool => $level > 0
            )));

            if ($guestViewLevels === []) {
                $guestViewLevels = [1];
            }

            $quotedLevels = implode(',', array_map(static fn (int $level): string => (string) $level, $guestViewLevels));
            $query = $db->getQuery(true)
                ->select($db->quoteName('a.modified'))
                ->select($db->quoteName('a.created'))
                ->from($db->quoteName('#__content', 'a'))
                ->leftJoin($db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
                ->where($db->quoteName('a.state') . ' = 1')
                ->where($db->quoteName('a.access') . ' IN (' . $quotedLevels . ')')
                ->where('(' . $db->quoteName('c.access') . ' IN (' . $quotedLevels . ') OR ' . $db->quoteName('c.access') . ' IS NULL)')
                ->where('(' . $db->quoteName('c.published') . ' = 1 OR ' . $db->quoteName('c.published') . ' IS NULL)')
                ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' = ' . $db->quote('0000-00-00 00:00:00') . ' OR ' . $db->quoteName('a.publish_up') . ' <= ' . $db->quote($now) . ')')
                ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' = ' . $db->quote('0000-00-00 00:00:00') . ' OR ' . $db->quoteName('a.publish_down') . ' >= ' . $db->quote($now) . ')')
                ->order('CASE WHEN ' . $db->quoteName('a.modified') . ' IS NULL OR ' . $db->quoteName('a.modified') . ' = ' . $db->quote('0000-00-00 00:00:00') . ' THEN ' . $db->quoteName('a.created') . ' ELSE ' . $db->quoteName('a.modified') . ' END DESC');

            if ($languageTag !== null && $languageTag !== '') {
                $query->where('(' . $db->quoteName('a.language') . ' = ' . $db->quote('*') . ' OR ' . $db->quoteName('a.language') . ' = :jtseo_latest_language)')
                    ->where('(' . $db->quoteName('c.language') . ' = ' . $db->quote('*') . ' OR ' . $db->quoteName('c.language') . ' = :jtseo_latest_category_language OR ' . $db->quoteName('c.language') . ' IS NULL)')
                    ->bind(':jtseo_latest_language', $languageTag)
                    ->bind(':jtseo_latest_category_language', $languageTag);
            }

            $db->setQuery($query, 0, 1);
            $row = $db->loadObject();
        } catch (\Throwable $exception) {
            return '';
        }

        if (!$row) {
            return '';
        }

        $date = self::toSitemapDate((string) ($row->modified ?? ''));

        return $date !== '' ? $date : self::toSitemapDate((string) ($row->created ?? ''));
    }

    private static function extractSitemapImages(object $row): array
    {
        $images = [];
        $articleImages = new Registry((string) ($row->images ?? ''));
        $categoryParams = new Registry((string) ($row->category_params ?? ''));

        foreach (['image_intro', 'image_fulltext'] as $key) {
            $images[] = (string) $articleImages->get($key, '');
        }

        $images[] = (string) $categoryParams->get('image', '');

        // Extract images inserted directly into article HTML content.
        // Joomla editor images may not be stored in the Images and Links fields.
        $content = (string) ($row->introtext ?? '') . (string) ($row->fulltext ?? '');

        if ($content !== '' && preg_match_all('#<img[^>]+src=[\"\']([^\"\']+)[\"\']#i', $content, $matches)) {
            foreach ($matches[1] as $image) {
                $images[] = $image;
            }
        }

        return self::normaliseImageItems($images);
    }

    private static function normaliseImageItems(array $images): array
    {
        $normalised = [];

        foreach ($images as $image) {
            if (is_array($image)) {
                $image = (string) ($image['loc'] ?? '');
            }

            $image = html_entity_decode(trim((string) $image), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($image === '') {
                continue;
            }

            $url = self::toAbsoluteUrl($image);

            if ($url === '') {
                continue;
            }

            $normalised[self::normaliseUrlKey($url)] = $url;

            if (count($normalised) >= 1000) {
                break;
            }
        }

        return array_values($normalised);
    }

    private static function normaliseSubformRows(mixed $value): array
    {
        if ($value instanceof Registry) {
            $value = $value->toArray();
        } elseif (is_object($value)) {
            $value = json_decode(json_encode($value), true);
        } elseif (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $row) {
            if ($row instanceof Registry) {
                $row = $row->toArray();
            } elseif (is_object($row)) {
                $row = (array) $row;
            }

            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private static function normaliseChangefreq(string $value, string $fallback): string
    {
        $allowed = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
        $value = strtolower(trim($value));

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function normalisePriority(string $value, string $fallback): string
    {
        if ($value === '' || !is_numeric($value)) {
            return $fallback;
        }

        return number_format(max(0.0, min(1.0, (float) $value)), 1, '.', '');
    }

    private static function getArticleUrl(object $row, ?string $languageTag = null): string
    {
        $id            = (int) ($row->id ?? 0);
        $catid         = (int) ($row->catid ?? 0);
        $alias         = trim((string) ($row->alias ?? ''));
        $categoryAlias = trim((string) ($row->category_alias ?? ''));

        if ($id <= 0) {
            return '';
        }

        $slug = $alias !== '' ? $id . ':' . $alias : (string) $id;
        $catslug = $catid > 0
            ? ($categoryAlias !== '' ? $catid . ':' . $categoryAlias : (string) $catid)
            : '';

        try {
            $route = self::getContentArticleRoute($slug, $catslug, $languageTag);

            if (method_exists(Route::class, 'link')) {
                $route = Route::link('site', $route, false);
            } else {
                $route = Route::_($route, false);
            }
        } catch (\Throwable $exception) {
            $route = 'index.php?option=com_content&view=article&id=' . $id;

            $sef = self::getLanguageSef($languageTag);

            if ($sef !== '') {
                $route .= '&lang=' . rawurlencode($sef);
            }
        }

        return self::toAbsoluteUrl($route);
    }

    private static function getContentArticleRoute(string $slug, string $catslug, ?string $languageTag = null): string
    {
        if ($languageTag !== null && $languageTag !== '') {
            try {
                $reflection = new \ReflectionMethod(ContentRouteHelper::class, 'getArticleRoute');

                if ($reflection->getNumberOfParameters() >= 3) {
                    return ContentRouteHelper::getArticleRoute($slug, $catslug, $languageTag);
                }
            } catch (\Throwable $exception) {
                // Fall back to the two-argument signature below.
            }
        }

        return ContentRouteHelper::getArticleRoute($slug, $catslug);
    }

    private static function getHomepageUrl(?string $languageTag = null): string
    {
        $sef = self::getLanguageSef($languageTag);
        $itemId = self::getHomeMenuItemId($languageTag);

        if ($itemId > 0) {
            $route = 'index.php?Itemid=' . $itemId;

            if ($sef !== '') {
                $route .= '&lang=' . rawurlencode($sef);
            }

            try {
                if (method_exists(Route::class, 'link')) {
                    $route = Route::link('site', $route, false);
                } else {
                    $route = Route::_($route, false);
                }

                $url = self::toAbsoluteUrl($route);

                if ($url !== '') {
                    return $url;
                }
            } catch (\Throwable $exception) {
                // Fall through to a simple language-prefixed homepage URL.
            }
        }

        if ($sef !== '') {
            return rtrim(Uri::root(false), '/') . '/' . trim($sef, '/') . '/';
        }

        return Uri::root(false);
    }

    private static function getHomeMenuItemId(?string $languageTag = null): int
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__menu'))
                ->where($db->quoteName('client_id') . ' = 0')
                ->where($db->quoteName('published') . ' = 1')
                ->where($db->quoteName('home') . ' = 1');

            if ($languageTag !== null && $languageTag !== '') {
                $query->where($db->quoteName('language') . ' = :jtseo_home_language')
                    ->bind(':jtseo_home_language', $languageTag);
            } else {
                $query->where($db->quoteName('language') . ' IN (' . $db->quote('*') . ', ' . $db->quote('') . ')');
            }

            $query->order($db->quoteName('id') . ' ASC');
            $db->setQuery($query, 0, 1);
            $id = (int) $db->loadResult();

            if ($id > 0) {
                return $id;
            }

            if ($languageTag !== null && $languageTag !== '') {
                return self::getHomeMenuItemId(null);
            }
        } catch (\Throwable $exception) {
            return 0;
        }

        return 0;
    }

    private static function filterSitemapUrls(array $items, Registry $params, ?string $languageTag = null): array
    {
        $includePatterns = self::compileUrlPatterns((string) $params->get('sitemap_included_urls', ''));
        $excludePatterns = self::compileUrlPatterns((string) $params->get('sitemap_excluded_urls', ''));
        $noindexPrefixes = (int) $params->get('sitemap_exclude_noindex', 1) === 1
            ? self::getNoindexMenuPathPrefixes($languageTag)
            : [];

        return array_values(array_filter($items, static function (array $item) use ($includePatterns, $excludePatterns, $noindexPrefixes, $languageTag): bool {
            $loc = trim((string) ($item['loc'] ?? ''));

            if ($loc === '') {
                return false;
            }

            if (self::urlMatchesPatterns($loc, $includePatterns, $languageTag)) {
                return true;
            }

            if (self::urlMatchesPatterns($loc, $excludePatterns, $languageTag)) {
                return false;
            }

            return !self::urlMatchesExcludedPrefix($loc, $noindexPrefixes);
        }));
    }

    private static function getExplicitIncludedUrlItems(Registry $params, ?string $languageTag = null): array
    {
        $items = [];
        $changefreq = self::normaliseChangefreq((string) $params->get('sitemap_internal_changefreq', 'weekly'), 'weekly');
        $priority = self::normalisePriority((string) $params->get('sitemap_internal_priority', '0.8'), '0.8');

        foreach (self::getUrlRuleLines((string) $params->get('sitemap_included_urls', '')) as $rule) {
            if (str_contains($rule, '*') || str_contains($rule, '?')) {
                continue;
            }

            if (!self::urlRuleBelongsToLanguage($rule, $languageTag)) {
                continue;
            }

            $url = self::toAbsoluteUrl($rule);

            if ($url === '') {
                continue;
            }

            $items[] = [
                'loc'        => $url,
                'lastmod'    => '',
                'changefreq' => $changefreq,
                'priority'   => $priority,
                'images'     => [],
            ];
        }

        return $items;
    }

    private static function getUrlRuleLines(string $rules): array
    {
        $lines = preg_split('/\R/u', $rules) ?: [];
        $normalised = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('#^https?://#i', $line) === 1) {
                $path = parse_url($line, PHP_URL_PATH);
                $line = is_string($path) ? $path : '';
            }

            if ($line === '' || !str_starts_with($line, '/')) {
                continue;
            }

            $lowerLine = strtolower($line);

            if ($lowerLine === '/index.php') {
                $line = '/';
            } elseif (str_starts_with($lowerLine, '/index.php/')) {
                $line = substr($line, 10);
            }

            $normalised[] = self::collapsePathSlashes($line);
        }

        return array_values(array_unique($normalised));
    }

    private static function collapsePathSlashes(string $value): string
    {
        $result = '';
        $previousWasSlash = false;
        $length = strlen($value);

        for ($index = 0; $index < $length; $index++) {
            $character = $value[$index];

            if ($character === '/') {
                if ($previousWasSlash) {
                    continue;
                }

                $previousWasSlash = true;
            } else {
                $previousWasSlash = false;
            }

            $result .= $character;
        }

        return $result !== '' ? $result : '/';
    }

    private static function isHexCharacter(string $character): bool
    {
        return ($character >= '0' && $character <= '9')
            || ($character >= 'a' && $character <= 'f')
            || ($character >= 'A' && $character <= 'F');
    }

    private static function normalisePercentTriplets(string $value): string
    {
        $result = '';
        $length = strlen($value);

        for ($index = 0; $index < $length; $index++) {
            $character = $value[$index];

            if (
                $character === '%'
                && $index + 2 < $length
                && self::isHexCharacter($value[$index + 1])
                && self::isHexCharacter($value[$index + 2])
            ) {
                $result .= '%' . strtoupper($value[$index + 1] . $value[$index + 2]);
                $index += 2;
                continue;
            }

            $result .= $character;
        }

        return $result;
    }

    private static function wildcardRuleToRegex(string $rule): string
    {
        $regex = '';
        $literal = '';
        $length = strlen($rule);

        for ($index = 0; $index < $length; $index++) {
            $character = $rule[$index];

            if ($character !== '*' && $character !== '?') {
                $literal .= $character;
                continue;
            }

            if ($literal !== '') {
                $regex .= preg_quote($literal, '#');
                $literal = '';
            }

            $regex .= $character === '*' ? '.*' : '.';
        }

        if ($literal !== '') {
            $regex .= preg_quote($literal, '#');
        }

        return '#^' . $regex . '$#iu';
    }

    private static function compileUrlPatterns(string $rules): array
    {
        $patterns = [];

        foreach (self::getUrlRuleLines($rules) as $rule) {
            $patterns[] = self::wildcardRuleToRegex(self::normalisePercentTriplets($rule));
        }

        return $patterns;
    }

    private static function urlMatchesPatterns(string $url, array $patterns, ?string $languageTag = null): bool
    {
        if ($patterns === []) {
            return false;
        }

        foreach (self::getRuleCandidatePaths($url, $languageTag) as $path) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $path) === 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return both the routed path and its language-neutral variant.
     *
     * A global rule such as /mycotoxin/* must also match a multilingual URL
     * such as /en/mycotoxin/article. An explicitly prefixed rule such as
     * /en/mycotoxin/* still matches only that prefixed route.
     *
     * @return array<int, string>
     */
    private static function getRuleCandidatePaths(string $url, ?string $languageTag = null): array
    {
        $path = self::normaliseRuleCandidatePath($url);

        if ($path === '') {
            return [];
        }

        $paths = [$path];
        $languageSefs = [];

        if ($languageTag !== null && $languageTag !== '') {
            $sef = trim(self::getLanguageSef($languageTag), '/');

            if ($sef !== '') {
                $languageSefs[] = $sef;
            }
        } else {
            foreach (self::getPublishedContentLanguages() as $language) {
                $sef = trim((string) ($language['sef'] ?? ''), '/');

                if ($sef !== '') {
                    $languageSefs[] = $sef;
                }
            }
        }

        foreach (array_values(array_unique($languageSefs)) as $sef) {
            $prefix = '/' . self::normalisePercentTriplets($sef);

            if (strcasecmp($path, $prefix) === 0 || strcasecmp($path, $prefix . '/') === 0) {
                $paths[] = '/';
                continue;
            }

            if (strncasecmp($path, $prefix . '/', strlen($prefix) + 1) === 0) {
                $stripped = substr($path, strlen($prefix));
                $paths[] = $stripped !== '' ? $stripped : '/';
            }
        }

        return array_values(array_unique(array_map(
            static fn (string $candidate): string => self::collapsePathSlashes($candidate),
            $paths
        )));
    }

    private static function normaliseRuleCandidatePath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return '';
        }

        $path = '/' . ltrim(self::normalisePercentTriplets($path), '/');
        $rootPath = parse_url(Uri::root(false), PHP_URL_PATH);
        $rootPath = is_string($rootPath) ? '/' . trim($rootPath, '/') : '';

        if ($rootPath !== '' && $rootPath !== '/' && ($path === $rootPath || str_starts_with($path, $rootPath . '/'))) {
            $path = substr($path, strlen($rootPath)) ?: '/';
        }

        return self::collapsePathSlashes($path);
    }

    private static function urlRuleBelongsToLanguage(string $rule, ?string $languageTag): bool
    {
        if ($languageTag === null || $languageTag === '') {
            return true;
        }

        $path = trim((string) parse_url($rule, PHP_URL_PATH), '/');
        $firstSegment = strtolower((string) strtok($path, '/'));
        $matchedLanguage = null;

        foreach (self::getPublishedContentLanguages() as $language) {
            if ($firstSegment !== '' && $firstSegment === strtolower((string) ($language['sef'] ?? ''))) {
                $matchedLanguage = (string) ($language['lang_code'] ?? '');
                break;
            }
        }

        if ($matchedLanguage !== null) {
            return strcasecmp($matchedLanguage, $languageTag) === 0;
        }

        return strcasecmp(self::getDefaultSiteLanguageTag(), $languageTag) === 0;
    }

    private static function getDefaultSiteLanguageTag(): string
    {
        try {
            $languageParams = ComponentHelper::getParams('com_languages');
            $tag = trim((string) $languageParams->get('site', ''));

            if ($tag !== '') {
                return $tag;
            }
        } catch (\Throwable $exception) {
            // Fall back to the active application language below.
        }

        try {
            return (string) Factory::getApplication()->getLanguage()->getTag();
        } catch (\Throwable $exception) {
            return '';
        }
    }

    private static function excludeNoindexMenuUrls(array $items, ?string $languageTag = null): array
    {
        $prefixes = self::getNoindexMenuPathPrefixes($languageTag);

        if ($prefixes === []) {
            return $items;
        }

        return array_values(array_filter($items, static function (array $item) use ($prefixes): bool {
            $loc = trim((string) ($item['loc'] ?? ''));

            return $loc !== '' && !self::urlMatchesExcludedPrefix($loc, $prefixes);
        }));
    }

    private static function getNoindexMenuPathPrefixes(?string $languageTag = null): array
    {
        static $cache = [];

        $cacheKey = $languageTag ?: '*';

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $prefixes = [];
        $sef = self::getLanguageSef($languageTag);

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('id'),
                    $db->quoteName('path'),
                    $db->quoteName('link'),
                    $db->quoteName('params'),
                    $db->quoteName('language'),
                ])
                ->from($db->quoteName('#__menu'))
                ->where($db->quoteName('client_id') . ' = 0')
                ->where($db->quoteName('published') . ' = 1');

            if ($languageTag !== null && $languageTag !== '') {
                $query->where($db->quoteName('language') . ' IN (' . $db->quote('*') . ', :jtseo_menu_language)')
                    ->bind(':jtseo_menu_language', $languageTag);
            }

            $db->setQuery($query);
            $rows = $db->loadObjectList() ?: [];
        } catch (\Throwable $exception) {
            $cache[$cacheKey] = [];

            return [];
        }

        foreach ($rows as $row) {
            $menuParams = new Registry((string) ($row->params ?? ''));
            $robots = strtolower((string) $menuParams->get('robots', ''));

            if (!str_contains($robots, 'noindex')) {
                continue;
            }

            $path = trim((string) ($row->path ?? ''), '/');

            if ($path !== '') {
                $prefixes[] = self::normaliseComparablePath('/' . $path);

                if ($sef !== '' && !str_starts_with(strtolower($path), strtolower($sef) . '/')) {
                    $prefixes[] = self::normaliseComparablePath('/' . trim($sef, '/') . '/' . $path);
                }
            }

            $itemId = (int) ($row->id ?? 0);

            if ($itemId > 0) {
                $prefixes[] = self::normaliseComparablePathFromUrl(self::toRoutedUrl('index.php?Itemid=' . $itemId));
            }

            $link = trim((string) ($row->link ?? ''));

            if ($link !== '') {
                $prefixes[] = self::normaliseComparablePathFromUrl(self::toRoutedUrl($link . ($itemId > 0 ? '&Itemid=' . $itemId : '')));
            }
        }

        $prefixes = array_values(array_unique(array_filter($prefixes, static fn (string $prefix): bool => $prefix !== '' && $prefix !== '/')));
        $cache[$cacheKey] = $prefixes;

        return $prefixes;
    }

    private static function toRoutedUrl(string $route): string
    {
        try {
            if (method_exists(Route::class, 'link')) {
                $route = Route::link('site', $route, false);
            } else {
                $route = Route::_($route, false);
            }
        } catch (\Throwable $exception) {
            // Keep the original route and let toAbsoluteUrl normalise it.
        }

        return self::toAbsoluteUrl($route);
    }

    private static function urlMatchesExcludedPrefix(string $url, array $prefixes): bool
    {
        $path = self::normaliseComparablePathFromUrl($url);

        if ($path === '') {
            return false;
        }

        foreach ($prefixes as $prefix) {
            if ($prefix === '' || $prefix === '/') {
                continue;
            }

            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    private static function getSitemapLanguages(Registry $params): array
    {
        if ((int) $params->get('sitemap_multilingual_index', 1) !== 1) {
            return [];
        }

        return self::getPublishedContentLanguages();
    }

    private static function getPublishedContentLanguages(): array
    {
        static $languages = null;

        if (is_array($languages)) {
            return $languages;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('lang_code'),
                    $db->quoteName('sef'),
                    $db->quoteName('title'),
                    $db->quoteName('title_native'),
                ])
                ->from($db->quoteName('#__languages'))
                ->where($db->quoteName('published') . ' = 1')
                ->order($db->quoteName('ordering') . ' ASC, ' . $db->quoteName('lang_code') . ' ASC');

            $db->setQuery($query);
            $rows = $db->loadObjectList() ?: [];
        } catch (\Throwable $exception) {
            $languages = [];

            return [];
        }

        $languages = [];

        foreach ($rows as $row) {
            $tag = trim((string) ($row->lang_code ?? ''));
            $sef = trim((string) ($row->sef ?? ''));

            if ($tag === '') {
                continue;
            }

            $languages[] = [
                'lang_code' => $tag,
                'sef'       => $sef !== '' ? $sef : strtolower(substr($tag, 0, 2)),
                'title'     => trim((string) (($row->title_native ?? '') ?: ($row->title ?? '') ?: $tag)),
            ];
        }

        return $languages;
    }

    private static function matchLanguageSitemapPath(string $path, Registry $params): ?array
    {
        $patterns = [
            '#^sitemap\.([A-Za-z0-9_-]+)\.(\d+)\.xml$#',
            '#^sitemap-([A-Za-z0-9_-]+)-(\d+)\.xml$#',
            '#^sitemap-([A-Za-z0-9_-]+)\.xml$#',
            '#^sitemap\.([A-Za-z0-9_-]+)\.xml$#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }

            $token = (string) ($matches[1] ?? '');
            $page = max(1, (int) ($matches[2] ?? 1));
            $language = self::findSitemapLanguage($token, $params);

            if ($language === null) {
                return null;
            }

            return [
                'language' => $language,
                'page'     => $page,
            ];
        }

        return null;
    }

    private static function findSitemapLanguage(string $token, Registry $params): ?array
    {
        $token = strtolower(trim($token));

        if ($token === '') {
            return null;
        }

        foreach (self::getSitemapLanguages($params) as $language) {
            $tag = strtolower((string) ($language['lang_code'] ?? ''));
            $sef = strtolower((string) ($language['sef'] ?? ''));

            if ($token === $tag || $token === $sef) {
                return $language;
            }
        }

        return null;
    }

    private static function getLanguageSef(?string $languageTag): string
    {
        if ($languageTag === null || $languageTag === '') {
            return '';
        }

        foreach (self::getPublishedContentLanguages() as $language) {
            if (strcasecmp((string) $language['lang_code'], $languageTag) === 0) {
                return trim((string) ($language['sef'] ?? ''));
            }
        }

        return strtolower(substr($languageTag, 0, 2));
    }

    private static function normaliseLanguageToken(string $languageTag): string
    {
        $result = '';
        $length = strlen($languageTag);

        for ($index = 0; $index < $length; $index++) {
            $character = $languageTag[$index];
            $isAsciiLetter = ($character >= 'a' && $character <= 'z')
                || ($character >= 'A' && $character <= 'Z');
            $isDigit = $character >= '0' && $character <= '9';
            $result .= $isAsciiLetter || $isDigit || $character === '_' || $character === '-'
                ? $character
                : '-';
        }

        return $result;
    }

    private static function getLanguageSitemapFilename(string $languageTag, int $page): string
    {
        return 'sitemap.' . self::normaliseLanguageToken($languageTag) . '.' . max(1, $page) . '.xml';
    }

    private static function getLinksPerSitemap(Registry $params): int
    {
        return max(1, min(50000, (int) $params->get('sitemap_links_per_file', self::DEFAULT_LINKS_PER_SITEMAP)));
    }

    private static function normaliseSitemapRequestPath(string $path): string
    {
        $path = trim($path);
        $path = parse_url($path, PHP_URL_PATH) ?: $path;
        $path = trim($path, '/');

        $base = trim((string) Uri::base(true), '/');

        if ($base !== '' && str_starts_with($path, $base . '/')) {
            $path = substr($path, strlen($base) + 1);
        }

        if (str_starts_with($path, 'index.php/')) {
            $path = substr($path, 10);
        }

        return $path;
    }

    private static function toAbsoluteUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

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

        if (preg_match('#^https?://#i', $url) === 1) {
            return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
        }

        $root = rtrim(Uri::root(false), '/');

        if (str_starts_with($url, '/')) {
            return $root . $url;
        }

        return $root . '/' . ltrim($url, '/');
    }

    private static function deduplicateSitemapItems(array $items): array
    {
        $seen = [];
        $unique = [];

        foreach ($items as $item) {
            $loc = trim((string) ($item['loc'] ?? ''));

            if ($loc === '') {
                continue;
            }

            $key = self::normaliseUrlKey($loc);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $item;
        }

        return $unique;
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

    private static function normaliseUrlKey(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return rtrim($url, '/');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $query = (string) ($parts['query'] ?? '');

        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/') ?: '/';

        if ($query !== '') {
            parse_str($query, $queryParams);
            ksort($queryParams);
            $query = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
        }

        return $scheme . '://' . $host . $path . ($query !== '' ? '?' . $query : '');
    }

    private static function normaliseComparablePathFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return '';
        }

        return self::normaliseComparablePath($path);
    }

    private static function normaliseComparablePath(string $path): string
    {
        $path = '/' . ltrim(self::normalisePercentTriplets($path), '/');
        $rootPath = parse_url(Uri::root(false), PHP_URL_PATH);
        $rootPath = is_string($rootPath) ? '/' . trim($rootPath, '/') : '';

        if ($rootPath !== '' && $rootPath !== '/' && str_starts_with($path, $rootPath . '/')) {
            $path = substr($path, strlen($rootPath));
        }

        $path = self::collapsePathSlashes($path);
        $path = rtrim(strtolower($path), '/') ?: '/';

        return $path;
    }

    private static function isFallbackArticleUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $path = is_string($path) ? strtolower($path) : '';
        $query = is_string($query) ? strtolower($query) : '';

        if (str_contains($path, '/component/content/article')) {
            return true;
        }

        if (str_contains($query, 'option=com_content') && str_contains($query, 'view=article')) {
            return true;
        }

        return false;
    }

    private static function getLatestSitemapItemDate(array $items): string
    {
        $latest = '';

        foreach ($items as $item) {
            $date = self::toSitemapDate((string) ($item['lastmod'] ?? ''));

            if ($date !== '' && ($latest === '' || strcmp($date, $latest) > 0)) {
                $latest = $date;
            }
        }

        return $latest;
    }

    private static function toSitemapDate(string $date): string
    {
        $date = trim($date);

        if ($date === '' || $date === '0000-00-00 00:00:00') {
            return '';
        }

        try {
            return (new \DateTime($date))->format('Y-m-d');
        } catch (\Throwable $exception) {
            return '';
        }
    }

    private static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
