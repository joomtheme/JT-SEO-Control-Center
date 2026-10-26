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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

final class StructuredDataHelper
{
    /**
     * Build the structured data output overview for the dashboard.
     *
     * @return array<string, mixed>
     */
    public static function getDashboardData(int $limit = 5): array
    {
        $articles = self::getPublishedArticles($limit);
        $params = ComponentHelper::getParams('com_jtseo');
        $organizationName = trim((string) $params->get('organization_name', ''));
        $siteName = trim((string) $params->get('website_name', ''));

        if ($organizationName === '') {
            $organizationName = (string) Factory::getApplication()->get('sitename', '');
        }

        if ($siteName === '') {
            $siteName = $organizationName;
        }

        $rootUrl = rtrim(Uri::root(), '/');
        $sampleArticle = $articles[0] ?? null;
        $preview = self::buildPreviewGraph($organizationName, $siteName, $rootUrl, $sampleArticle);

        return [
            'active' => (int) $params->get('enable_structured_data', 1) === 1,
            'mode' => 'frontend',
            'site_name' => $siteName,
            'site_url' => $rootUrl,
            'article_count' => self::countPublishedArticles(),
            'sample_article' => $sampleArticle,
            'mappings' => self::getMappings($sampleArticle !== null),
            'preview_json' => json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getMappings(bool $hasArticle): array
    {
        return [
            [
                'type' => 'Organization',
                'source' => Text::_('COM_JTSEO_STRUCTURED_SOURCE_SITE_IDENTITY'),
                'status' => 'ready',
                'note' => Text::_('COM_JTSEO_STRUCTURED_NOTE_SITE_IDENTITY'),
            ],
            [
                'type' => 'WebSite',
                'source' => Text::_('COM_JTSEO_STRUCTURED_SOURCE_WEBSITE'),
                'status' => 'ready',
                'note' => Text::_('COM_JTSEO_STRUCTURED_NOTE_WEBSITE'),
            ],
            [
                'type' => 'WebPage',
                'source' => Text::_('COM_JTSEO_STRUCTURED_SOURCE_WEBSITE'),
                'status' => 'ready',
                'note' => Text::_('COM_JTSEO_STRUCTURED_NOTE_WEBSITE'),
            ],
            [
                'type' => 'Article',
                'source' => Text::_('COM_JTSEO_STRUCTURED_SOURCE_ARTICLE'),
                'status' => $hasArticle ? 'ready' : 'missing',
                'note' => $hasArticle ? Text::_('COM_JTSEO_STRUCTURED_NOTE_ARTICLE_READY') : Text::_('COM_JTSEO_STRUCTURED_NOTE_ARTICLE_MISSING'),
            ],
        ];
    }

    /**
     * @param   array<string, mixed>|null  $article
     *
     * @return array<string, mixed>
     */
    private static function buildPreviewGraph(string $organizationName, string $siteName, string $rootUrl, ?array $article): array
    {
        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $rootUrl . '/#organization',
                'name' => $organizationName,
                'url' => $rootUrl . '/',
            ],
            [
                '@type' => 'WebSite',
                '@id' => $rootUrl . '/#website',
                'url' => $rootUrl . '/',
                'name' => $siteName,
                'publisher' => [
                    '@id' => $rootUrl . '/#organization',
                ],
            ],
        ];

        if ($article !== null) {
            $articleUrl = self::buildArticlePreviewUrl($rootUrl, $article);
            $webPageId = $articleUrl . '#webpage';
            $articleId = $articleUrl . '#article';

            $graph[] = [
                '@type' => 'WebPage',
                '@id' => $webPageId,
                'url' => $articleUrl,
                'name' => (string) ($article['seo_title'] ?: $article['title']),
                'isPartOf' => [
                    '@id' => $rootUrl . '/#website',
                ],
                'mainEntity' => [
                    '@id' => $articleId,
                ],
            ];

            $articleNode = [
                '@type' => 'Article',
                '@id' => $articleId,
                'mainEntityOfPage' => $articleUrl,
                'headline' => (string) ($article['seo_title'] ?: $article['title']),
                'description' => (string) ($article['description'] ?? ''),
                'datePublished' => (string) (($article['publish_up'] ?? '') ?: ($article['created'] ?? '')),
                'dateModified' => (string) (($article['modified'] ?? '') ?: (($article['publish_up'] ?? '') ?: ($article['created'] ?? ''))),
                'publisher' => [
                    '@id' => $rootUrl . '/#organization',
                ],
            ];

            if (!empty($article['image'])) {
                $articleNode['image'] = self::absoluteUrl((string) $article['image'], $rootUrl);
            }

            $graph[] = $articleNode;
        } else {
            $graph[] = [
                '@type' => 'WebPage',
                '@id' => $rootUrl . '/#webpage',
                'url' => $rootUrl . '/',
                'name' => $siteName,
                'isPartOf' => [
                    '@id' => $rootUrl . '/#website',
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getPublishedArticles(int $limit): array
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $now = Factory::getDate()->toSql();

            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('a.id'),
                    $db->quoteName('a.title'),
                    $db->quoteName('a.alias'),
                    $db->quoteName('a.introtext'),
                    $db->quoteName('a.fulltext'),
                    $db->quoteName('a.metadesc'),
                    $db->quoteName('a.attribs'),
                    $db->quoteName('a.images'),
                    $db->quoteName('a.publish_up'),
                    $db->quoteName('a.created'),
                    $db->quoteName('a.modified'),
                    $db->quoteName('a.catid'),
                    $db->quoteName('c.title', 'category_title'),
                    $db->quoteName('c.alias', 'category_alias'),
                ])
                ->from($db->quoteName('#__content', 'a'))
                ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
                ->where($db->quoteName('a.state') . ' = 1')
                ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' = ' . $db->quote($db->getNullDate()) . ' OR ' . $db->quoteName('a.publish_up') . ' <= :nowUp)')
                ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' = ' . $db->quote($db->getNullDate()) . ' OR ' . $db->quoteName('a.publish_down') . ' >= :nowDown)')
                ->bind(':nowUp', $now)
                ->bind(':nowDown', $now)
                ->order($db->quoteName('a.modified') . ' DESC');

            $db->setQuery($query, 0, max(1, $limit));
            $rows = $db->loadObjectList() ?: [];
            $articles = [];

            foreach ($rows as $row) {
                $attribs = new Registry((string) ($row->attribs ?? ''));
                $images = new Registry((string) ($row->images ?? ''));
                $seoTitle = trim((string) $attribs->get('jtseo_title', ''));
                $seoDescription = trim((string) $attribs->get('jtseo_description', ''));
                $description = self::firstNonEmpty([
                    $seoDescription,
                    trim((string) ($row->metadesc ?? '')),
                    self::plainTextExcerpt((string) ($row->introtext ?? '') . ' ' . (string) ($row->fulltext ?? ''), 160),
                ]);
                $image = self::firstNonEmpty([
                    trim((string) $attribs->get('jtseo_og_image', '')),
                    trim((string) $images->get('image_intro', '')),
                    trim((string) $images->get('image_fulltext', '')),
                ]);

                $articles[] = [
                    'id' => (int) ($row->id ?? 0),
                    'title' => trim((string) ($row->title ?? '')),
                    'alias' => (string) ($row->alias ?? ''),
                    'catid' => (int) ($row->catid ?? 0),
                    'category' => (string) ($row->category_title ?? ''),
                    'category_alias' => (string) ($row->category_alias ?? ''),
                    'seo_title' => $seoTitle,
                    'description' => $description,
                    'image' => $image,
                    'publish_up' => (string) ($row->publish_up ?? ''),
                    'created' => (string) ($row->created ?? ''),
                    'modified' => (string) ($row->modified ?? ''),
                ];
            }

            return $articles;
        } catch (\Throwable $exception) {
            return [];
        }
    }

    private static function countPublishedArticles(): int
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('state') . ' = 1');

            $db->setQuery($query);

            return (int) $db->loadResult();
        } catch (\Throwable $exception) {
            return 0;
        }
    }

    /**
     * @param   array<string, mixed>  $article
     */
    private static function buildArticlePreviewUrl(string $rootUrl, array $article): string
    {
        $id = (int) ($article['id'] ?? 0);
        $alias = trim((string) ($article['alias'] ?? ''));
        $catid = (int) ($article['catid'] ?? 0);
        $slug = $alias !== '' ? $id . ':' . $alias : (string) $id;

        return $rootUrl . '/index.php?option=com_content&view=article&id=' . rawurlencode($slug) . '&catid=' . $catid;
    }

    private static function absoluteUrl(string $path, string $rootUrl): string
    {
        if ($path === '' || preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return $rootUrl . '/' . ltrim($path, '/');
    }

    /**
     * @param   array<int, string>  $values
     */
    private static function firstNonEmpty(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function plainTextExcerpt(string $html, int $length): string
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')) ?? '');

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $length, 'UTF-8');
        }

        return substr($text, 0, $length);
    }
}
