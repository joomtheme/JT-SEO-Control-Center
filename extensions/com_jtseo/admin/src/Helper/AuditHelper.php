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

final class AuditHelper
{
    /**
     * Build a lightweight SEO audit for published Joomla articles.
     *
     * @return array<string, mixed>
     */
    public static function getDashboardAudit(int $limit = 500): array
    {
        $rows = self::getPublishedArticles($limit);
        $params = ComponentHelper::getParams('com_jtseo');
        $minWordCount = max(0, (int) $params->get('seo_score_min_words', 100));
        $issues = [];
        $stats = [
            'total_articles'             => count($rows),
            'missing_explicit_description' => 0,
            'short_description'          => 0,
            'long_description'           => 0,
            'missing_image'              => 0,
            'missing_seo_title_override' => 0,
            'noindex'                    => 0,
            'canonical_override'         => 0,
            'duplicate_alias'            => 0,
            'good_articles'              => 0,
            'health_score'               => 100,
            'average_article_score'      => 100,
            'score_excellent'            => 0,
            'score_good'                 => 0,
            'score_fair'                 => 0,
            'score_weak'                 => 0,
        ];

        $aliasMap = [];

        foreach ($rows as $row) {
            $alias = trim((string) ($row->alias ?? ''));
            $catid = (int) ($row->catid ?? 0);

            if ($alias !== '') {
                $aliasKey = $catid . ':' . $alias;
                $aliasMap[$aliasKey] ??= [];
                $aliasMap[$aliasKey][] = (int) ($row->id ?? 0);
            }
        }

        $duplicateIds = [];

        foreach ($aliasMap as $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    $duplicateIds[$id] = true;
                }
            }
        }

        $scores = [];

        foreach ($rows as $row) {
            $articleId = (int) ($row->id ?? 0);
            $title = trim((string) ($row->title ?? ''));
            $attribs = new Registry((string) ($row->attribs ?? ''));
            $images = new Registry((string) ($row->images ?? ''));

            $seoTitle = trim((string) $attribs->get('jtseo_title', ''));
            $seoDescription = trim((string) $attribs->get('jtseo_description', ''));
            $joomlaDescription = trim((string) ($row->metadesc ?? ''));
            $effectiveDescription = self::firstNonEmpty([
                $seoDescription,
                $joomlaDescription,
                self::plainTextExcerpt((string) ($row->introtext ?? '') . ' ' . (string) ($row->fulltext ?? ''), 180),
            ]);
            $descriptionLength = self::utf8Length($effectiveDescription);
            $explicitDescription = $seoDescription !== '' || $joomlaDescription !== '';

            $hasImage = trim((string) $attribs->get('jtseo_og_image', '')) !== ''
                || trim((string) $images->get('image_intro', '')) !== ''
                || trim((string) $images->get('image_fulltext', '')) !== '';

            $robotsIndex = trim((string) $attribs->get('jtseo_robots_index', ''));
            $canonical = trim((string) $attribs->get('jtseo_canonical', ''));
            $bodyText = self::plainText((string) ($row->introtext ?? '') . ' ' . (string) ($row->fulltext ?? ''));
            $wordCount = self::wordCount($bodyText);
            $score = self::scoreArticle(
                $articleId,
                $title,
                $seoTitle,
                $effectiveDescription,
                $descriptionLength,
                $explicitDescription,
                $hasImage,
                $robotsIndex,
                $canonical,
                isset($duplicateIds[$articleId]),
                $wordCount,
                $minWordCount
            );
            $scores[] = $score;
            $scoreBucket = (string) ($score['bucket'] ?? 'weak');

            if (isset($stats['score_' . $scoreBucket])) {
                $stats['score_' . $scoreBucket]++;
            }

            if (!$explicitDescription) {
                $stats['missing_explicit_description']++;
                $issues[] = self::issue($articleId, $title, 'description', 'COM_JTSEO_AUDIT_ISSUE_MISSING_DESCRIPTION', 'warning', '', 'COM_JTSEO_AUDIT_FIX_MISSING_DESCRIPTION');
            }

            if ($descriptionLength > 0 && $descriptionLength < 70) {
                $stats['short_description']++;
                $issues[] = self::issue($articleId, $title, 'description', 'COM_JTSEO_AUDIT_ISSUE_SHORT_DESCRIPTION', 'warning', (string) $descriptionLength, 'COM_JTSEO_AUDIT_FIX_SHORT_DESCRIPTION');
            }

            if ($descriptionLength > 160) {
                $stats['long_description']++;
                $issues[] = self::issue($articleId, $title, 'description', 'COM_JTSEO_AUDIT_ISSUE_LONG_DESCRIPTION', 'info', (string) $descriptionLength, 'COM_JTSEO_AUDIT_FIX_LONG_DESCRIPTION');
            }

            if (!$hasImage) {
                $stats['missing_image']++;
                $issues[] = self::issue($articleId, $title, 'image', 'COM_JTSEO_AUDIT_ISSUE_MISSING_IMAGE', 'warning', '', 'COM_JTSEO_AUDIT_FIX_MISSING_IMAGE');
            }

            if ($seoTitle === '') {
                $stats['missing_seo_title_override']++;
            }

            if ($robotsIndex === 'noindex') {
                $stats['noindex']++;
                $issues[] = self::issue($articleId, $title, 'robots', 'COM_JTSEO_AUDIT_ISSUE_NOINDEX', 'danger', '', 'COM_JTSEO_AUDIT_FIX_NOINDEX');
            }

            if ($canonical !== '') {
                $stats['canonical_override']++;
            }

            if (isset($duplicateIds[$articleId])) {
                $stats['duplicate_alias']++;
                $issues[] = self::issue($articleId, $title, 'alias', 'COM_JTSEO_AUDIT_ISSUE_DUPLICATE_ALIAS', 'warning', '', 'COM_JTSEO_AUDIT_FIX_DUPLICATE_ALIAS');
            }
        }

        $issueCount = count($issues);
        $articleIdsWithIssues = [];
        $dangerCount = 0;
        $warningCount = 0;
        $infoCount = 0;

        foreach ($issues as $issue) {
            $articleIdsWithIssues[(int) ($issue['id'] ?? 0)] = true;

            if (($issue['severity'] ?? '') === 'danger') {
                $dangerCount++;
            } elseif (($issue['severity'] ?? '') === 'warning') {
                $warningCount++;
            } elseif (($issue['severity'] ?? '') === 'info') {
                $infoCount++;
            }
        }

        $stats['good_articles'] = max(0, count($rows) - count($articleIdsWithIssues));
        $stats['health_score'] = max(0, 100 - min(100, ($dangerCount * 15) + ($warningCount * 8) + ($infoCount * 2)));

        if (!empty($scores)) {
            $stats['average_article_score'] = (int) round(array_sum(array_map(static fn (array $score): int => (int) ($score['score'] ?? 0), $scores)) / count($scores));
        }

        usort($scores, static function (array $a, array $b): int {
            $scoreCompare = ((int) ($a['score'] ?? 0)) <=> ((int) ($b['score'] ?? 0));

            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });

        return [
            'stats'  => $stats,
            'issues' => array_slice($issues, 0, 200),
            'scores' => array_slice($scores, 0, 100),
            'issue_count' => $issueCount,
            'checked_limit' => $limit,
        ];
    }

    /**
     * @return array<int, object>
     */
    private static function getPublishedArticles(int $limit): array
    {
        try {
            /** @var DatabaseInterface $db */
            $db  = Factory::getContainer()->get(DatabaseInterface::class);
            $now = Factory::getDate()->toSql();

            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('a.id'),
                    $db->quoteName('a.title'),
                    $db->quoteName('a.alias'),
                    $db->quoteName('a.catid'),
                    $db->quoteName('a.metadesc'),
                    $db->quoteName('a.introtext'),
                    $db->quoteName('a.fulltext'),
                    $db->quoteName('a.images'),
                    $db->quoteName('a.attribs'),
                    $db->quoteName('a.modified'),
                    $db->quoteName('a.created'),
                ])
                ->from($db->quoteName('#__content', 'a'))
                ->leftJoin($db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
                ->where($db->quoteName('a.state') . ' = 1')
                ->where('(' . $db->quoteName('c.published') . ' = 1 OR ' . $db->quoteName('c.published') . ' IS NULL)')
                ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' = ' . $db->quote('0000-00-00 00:00:00') . ' OR ' . $db->quoteName('a.publish_up') . ' <= ' . $db->quote($now) . ')')
                ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' = ' . $db->quote('0000-00-00 00:00:00') . ' OR ' . $db->quoteName('a.publish_down') . ' >= ' . $db->quote($now) . ')')
                ->order($db->quoteName('a.modified') . ' DESC, ' . $db->quoteName('a.id') . ' DESC');

            $db->setQuery($query, 0, max(1, min(5000, $limit)));

            return $db->loadObjectList() ?: [];
        } catch (\Throwable $exception) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function scoreArticle(
        int $id,
        string $title,
        string $seoTitle,
        string $description,
        int $descriptionLength,
        bool $explicitDescription,
        bool $hasImage,
        string $robotsIndex,
        string $canonical,
        bool $duplicateAlias,
        int $wordCount,
        int $minWordCount = 100
    ): array {
        $score = 100;
        $notes = [];
        $effectiveTitle = $seoTitle !== '' ? $seoTitle : $title;
        $titleLength = self::utf8Length(trim($effectiveTitle));

        if ($titleLength < 30 || $titleLength > 65) {
            $score -= 8;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_TITLE_LENGTH';
        }

        if (!$explicitDescription) {
            $score -= 25;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_MISSING_DESCRIPTION';
        } elseif ($descriptionLength < 120) {
            $score -= 10;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_SHORT_DESCRIPTION';
        } elseif ($descriptionLength > 160) {
            $score -= 5;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_LONG_DESCRIPTION';
        }

        if (trim($description) === '') {
            $score -= 10;
        }

        if (!$hasImage) {
            $score -= 15;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_MISSING_IMAGE';
        }

        if ($robotsIndex === 'noindex') {
            $score -= 30;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_NOINDEX';
        }

        if ($duplicateAlias) {
            $score -= 15;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_DUPLICATE_ALIAS';
        }

        if ($minWordCount > 0 && $wordCount > 0 && $wordCount < $minWordCount) {
            $score -= 10;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_LOW_WORD_COUNT';
        }

        if ($canonical !== '') {
            $notes[] = 'COM_JTSEO_SCORE_NOTE_CANONICAL_OVERRIDE';
        }

        $score = max(0, min(100, $score));
        $bucket = self::scoreBucket($score);

        if (empty($notes)) {
            $notes[] = 'COM_JTSEO_SCORE_NOTE_GOOD';
        }

        return [
            'id'                 => $id,
            'title'              => $title !== '' ? $title : ('#' . $id),
            'score'              => $score,
            'bucket'             => $bucket,
            'notes'              => array_slice(array_values(array_unique($notes)), 0, 5),
            'title_length'       => $titleLength,
            'description_length' => $descriptionLength,
            'word_count'         => $wordCount,
            'min_word_count'     => $minWordCount,
            'has_image'          => $hasImage,
            'canonical_override' => $canonical !== '',
            'noindex'            => $robotsIndex === 'noindex',
        ];
    }

    private static function scoreBucket(int $score): string
    {
        if ($score >= 90) {
            return 'excellent';
        }

        if ($score >= 75) {
            return 'good';
        }

        if ($score >= 55) {
            return 'fair';
        }

        return 'weak';
    }

    private static function plainText(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
    }

    private static function wordCount(string $text): int
    {
        $text = trim($text);

        if ($text === '') {
            return 0;
        }

        if (function_exists('preg_match_all')) {
            preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);

            return count($matches[0] ?? []);
        }

        return str_word_count($text);
    }

    /**
     * @param array<int, string> $values
     */
    private static function firstNonEmpty(array $values): string
    {
        foreach ($values as $value) {
            $value = trim($value);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function plainTextExcerpt(string $html, int $length): string
    {
        $text = self::plainText($html);

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $length, 'UTF-8');
        }

        return substr($text, 0, $length);
    }

    private static function utf8Length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($value, 'UTF-8');
        }

        return strlen($value);
    }

    /**
     * @return array<string, string|int>
     */
    private static function issue(int $id, string $title, string $type, string $messageKey, string $severity, string $detail = '', string $suggestionKey = ''): array
    {
        return [
            'id'          => $id,
            'title'       => $title !== '' ? $title : ('#' . $id),
            'type'        => $type,
            'message_key' => $messageKey,
            'severity'    => $severity,
            'detail'      => $detail,
            'suggestion_key' => $suggestionKey,
        ];
    }
}
