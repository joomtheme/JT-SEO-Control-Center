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
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

final class BulkMetaHelper
{
    private const MAX_IMPORT_BYTES = 2097152;

    private const MAX_IMPORT_ROWS = 1000;

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getArticles(int $limit = 50): array
    {
        try {
            $rows = self::fetchArticles($limit);
            $params = ComponentHelper::getParams('com_jtseo');
            $minWordCount = max(0, (int) $params->get('seo_score_min_words', 100));
            $items = [];

            foreach ($rows as $row) {
                $attribs = new Registry((string) ($row->attribs ?? ''));
                $images = new Registry((string) ($row->images ?? ''));
                $title = trim((string) ($row->title ?? ''));
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
                $ogImage = trim((string) $attribs->get('jtseo_og_image', ''));
                $hasImage = $ogImage !== ''
                    || trim((string) $images->get('image_intro', '')) !== ''
                    || trim((string) $images->get('image_fulltext', '')) !== '';
                $bodyText = self::plainText((string) ($row->introtext ?? '') . ' ' . (string) ($row->fulltext ?? ''));
                $wordCount = self::wordCount($bodyText);
                $robotsIndex = trim((string) $attribs->get('jtseo_robots_index', ''));
                $robotsFollow = trim((string) $attribs->get('jtseo_robots_follow', ''));
                $canonical = trim((string) $attribs->get('jtseo_canonical', ''));
                $score = self::scoreArticle(
                    $title,
                    $seoTitle,
                    $descriptionLength,
                    $explicitDescription,
                    $hasImage,
                    $robotsIndex,
                    $canonical,
                    $wordCount,
                    $minWordCount
                );

                $items[] = [
                    'id' => (int) ($row->id ?? 0),
                    'title' => $title,
                    'alias' => (string) ($row->alias ?? ''),
                    'category' => (string) ($row->category_title ?? ''),
                    'seo_title' => $seoTitle,
                    'seo_description' => $seoDescription,
                    'joomla_description' => $joomlaDescription,
                    'effective_description' => $effectiveDescription,
                    'description_length' => $descriptionLength,
                    'explicit_description' => $explicitDescription,
                    'canonical' => $canonical,
                    'og_image' => $ogImage,
                    'robots_index' => in_array($robotsIndex, ['', 'index', 'noindex'], true) ? $robotsIndex : '',
                    'robots_follow' => in_array($robotsFollow, ['', 'follow', 'nofollow'], true) ? $robotsFollow : '',
                    'has_image' => $hasImage,
                    'word_count' => $wordCount,
                    'score' => (int) ($score['score'] ?? 0),
                    'bucket' => (string) ($score['bucket'] ?? 'weak'),
                    'notes' => (array) ($score['notes'] ?? []),
                    'modified' => (string) ($row->modified ?? ''),
                ];
            }

            usort($items, static function (array $a, array $b): int {
                $scoreCompare = ((int) ($a['score'] ?? 0)) <=> ((int) ($b['score'] ?? 0));

                if ($scoreCompare !== 0) {
                    return $scoreCompare;
                }

                return strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
            });

            return $items;
        } catch (\Throwable $exception) {
            return [];
        }
    }


    /**
     * Build a CSV export containing article SEO override data.
     */
    public static function exportCsv(int $limit = 1000): string
    {
        $rows = self::getArticles($limit);
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, [
            'article_id',
            'title',
            'alias',
            'category',
            'seo_score',
            'seo_bucket',
            'issues',
            'seo_title',
            'seo_description',
            'canonical',
            'robots_index',
            'robots_follow',
            'og_image',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                (int) ($row['id'] ?? 0),
                (string) ($row['title'] ?? ''),
                (string) ($row['alias'] ?? ''),
                (string) ($row['category'] ?? ''),
                (int) ($row['score'] ?? 0),
                (string) ($row['bucket'] ?? ''),
                self::formatIssueSummary((array) ($row['notes'] ?? [])),
                (string) ($row['seo_title'] ?? ''),
                (string) ($row['seo_description'] ?? ''),
                (string) ($row['canonical'] ?? ''),
                (string) ($row['robots_index'] ?? ''),
                (string) ($row['robots_follow'] ?? ''),
                (string) ($row['og_image'] ?? ''),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return is_string($csv) ? $csv : '';
    }

    /**
     * @param   array<string, mixed>  $file
     *
     * @return  array<string, mixed>
     */
    public static function importCsv(array $file, bool $dryRun = false): array
    {
        $tmpName = (string) ($file['tmp_name'] ?? '');
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK || $tmpName === '' || !is_uploaded_file($tmpName)) {
            return [
                'success' => false,
                'updated' => 0,
                'valid' => 0,
                'skipped' => 0,
                'message' => Text::_('COM_JTSEO_BULK_ERROR_NO_READABLE_CSV'),
            ];
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0 || $size > self::MAX_IMPORT_BYTES) {
            return [
                'success' => false,
                'updated' => 0,
                'valid' => 0,
                'skipped' => 0,
                'message' => Text::sprintf('COM_JTSEO_BULK_ERROR_CSV_SIZE', self::MAX_IMPORT_BYTES / 1048576),
            ];
        }

        $handle = fopen($tmpName, 'r');

        if ($handle === false) {
            return [
                'success' => false,
                'updated' => 0,
                'valid' => 0,
                'skipped' => 0,
                'message' => Text::_('COM_JTSEO_BULK_ERROR_CSV_OPEN_FAILED'),
            ];
        }

        $header = fgetcsv($handle);

        if (!is_array($header) || empty($header)) {
            fclose($handle);

            return [
                'success' => false,
                'updated' => 0,
                'valid' => 0,
                'skipped' => 0,
                'message' => Text::_('COM_JTSEO_BULK_ERROR_CSV_HEADER_MISSING'),
            ];
        }

        $columns = [];

        foreach ($header as $index => $name) {
            $columns[self::normaliseCsvColumn((string) $name)] = (int) $index;
        }

        $idColumn = $columns['article_id'] ?? $columns['id'] ?? null;

        if ($idColumn === null) {
            fclose($handle);

            return [
                'success' => false,
                'updated' => 0,
                'valid' => 0,
                'skipped' => 0,
                'message' => Text::_('COM_JTSEO_BULK_ERROR_CSV_ARTICLE_ID_MISSING'),
            ];
        }

        $updated = 0;
        $valid = 0;
        $unchanged = 0;
        $skipped = 0;
        $errors = [];
        $processedRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $processedRows++;

            if ($processedRows > self::MAX_IMPORT_ROWS) {
                $skipped++;
                $errors[] = Text::sprintf('COM_JTSEO_BULK_ERROR_CSV_ROW_LIMIT', self::MAX_IMPORT_ROWS);
                break;
            }
            if (!is_array($row) || count($row) === 0) {
                continue;
            }

            $articleId = (int) trim((string) ($row[$idColumn] ?? ''));

            if ($articleId <= 0) {
                $skipped++;
                continue;
            }

            $values = [];
            $map = [
                'seo_title' => 'jtseo_title',
                'seo_description' => 'jtseo_description',
                'canonical' => 'jtseo_canonical',
                'canonical_url' => 'jtseo_canonical',
                'robots_index' => 'jtseo_robots_index',
                'robots_follow' => 'jtseo_robots_follow',
                'og_image' => 'jtseo_og_image',
                'open_graph_image' => 'jtseo_og_image',
            ];

            foreach ($map as $csvColumn => $fieldName) {
                if (array_key_exists($csvColumn, $columns)) {
                    $values[$fieldName] = (string) ($row[$columns[$csvColumn]] ?? '');
                }
            }

            if (empty($values)) {
                $skipped++;
                continue;
            }

            $valid++;

            if ($dryRun) {
                continue;
            }

            $result = self::saveArticle($articleId, $values);

            if (!empty($result['success'])) {
                if (!empty($result['changed'])) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            } else {
                $skipped++;

                if (!empty($result['message'])) {
                    $errors[] = (string) $result['message'];
                }
            }
        }

        fclose($handle);

        return [
            'success' => empty($errors),
            'updated' => $updated,
            'valid' => $valid,
            'unchanged' => $unchanged,
            'skipped' => $skipped,
            'dry_run' => $dryRun,
            'message' => implode('; ', array_slice($errors, 0, 3)),
        ];
    }

    /**
     * @param   array<int|string, mixed>  $bulk
     *
     * @return  array<string, mixed>
     */
    public static function saveBulk(array $bulk): array
    {
        $updated = 0;
        $unchanged = 0;
        $skipped = 0;
        $errors = [];

        foreach ($bulk as $id => $values) {
            $articleId = (int) $id;

            if ($articleId <= 0 || !is_array($values)) {
                $skipped++;
                continue;
            }

            $result = self::saveArticle($articleId, $values);

            if (!empty($result['success'])) {
                if (!empty($result['changed'])) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            } else {
                $skipped++;

                if (!empty($result['message'])) {
                    $errors[] = (string) $result['message'];
                }
            }
        }

        return [
            'success' => empty($errors),
            'updated' => $updated,
            'unchanged' => $unchanged,
            'skipped' => $skipped,
            'message' => implode('; ', array_slice($errors, 0, 3)),
        ];
    }

    /**
     * @param   array<string, mixed>  $values
     *
     * @return  array<string, mixed>
     */
    private static function saveArticle(int $articleId, array $values): array
    {
        try {
            $db = self::db();
            $query = $db->getQuery(true)
                ->select($db->quoteName('attribs'))
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $articleId, ParameterType::INTEGER);

            $db->setQuery($query);
            $attribsRaw = $db->loadResult();

            if ($attribsRaw === null) {
                return ['success' => false, 'message' => Text::sprintf('COM_JTSEO_BULK_ERROR_ARTICLE_NOT_FOUND', $articleId)];
            }

            $attribs = new Registry((string) $attribsRaw);
            $currentTitle = trim((string) $attribs->get('jtseo_title', ''));
            $currentDescription = trim((string) $attribs->get('jtseo_description', ''));
            $currentCanonical = trim((string) $attribs->get('jtseo_canonical', ''));
            $currentOgImage = trim((string) $attribs->get('jtseo_og_image', ''));
            $currentRobotsIndex = trim((string) $attribs->get('jtseo_robots_index', ''));
            $currentRobotsFollow = trim((string) $attribs->get('jtseo_robots_follow', ''));

            $newTitle = array_key_exists('jtseo_title', $values) ? self::cleanText((string) $values['jtseo_title'], 255) : $currentTitle;
            $newDescription = array_key_exists('jtseo_description', $values) ? self::cleanText((string) $values['jtseo_description'], 320) : $currentDescription;
            $newCanonical = array_key_exists('jtseo_canonical', $values) ? self::cleanUrl((string) $values['jtseo_canonical']) : $currentCanonical;
            $newOgImage = array_key_exists('jtseo_og_image', $values) ? self::cleanUrl((string) $values['jtseo_og_image']) : $currentOgImage;
            $robotsIndex = array_key_exists('jtseo_robots_index', $values) ? (string) $values['jtseo_robots_index'] : $currentRobotsIndex;
            $robotsFollow = array_key_exists('jtseo_robots_follow', $values) ? (string) $values['jtseo_robots_follow'] : $currentRobotsFollow;
            $newRobotsIndex = in_array($robotsIndex, ['', 'index', 'noindex'], true) ? $robotsIndex : '';
            $newRobotsFollow = in_array($robotsFollow, ['', 'follow', 'nofollow'], true) ? $robotsFollow : '';

            $changed = $currentTitle !== $newTitle
                || $currentDescription !== $newDescription
                || $currentCanonical !== $newCanonical
                || $currentOgImage !== $newOgImage
                || $currentRobotsIndex !== $newRobotsIndex
                || $currentRobotsFollow !== $newRobotsFollow;

            if (!$changed) {
                return ['success' => true, 'changed' => false];
            }

            $attribs->set('jtseo_title', $newTitle);
            $attribs->set('jtseo_description', $newDescription);
            $attribs->set('jtseo_canonical', $newCanonical);
            $attribs->set('jtseo_og_image', $newOgImage);
            $attribs->set('jtseo_robots_index', $newRobotsIndex);
            $attribs->set('jtseo_robots_follow', $newRobotsFollow);

            $json = $attribs->toString('JSON');
            $now = Factory::getDate()->toSql();

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__content'))
                ->set($db->quoteName('attribs') . ' = :attribs')
                ->set($db->quoteName('modified') . ' = :modified')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':attribs', $json)
                ->bind(':modified', $now)
                ->bind(':id', $articleId, ParameterType::INTEGER);

            $db->setQuery($query)->execute();

            return ['success' => true, 'changed' => true];
        } catch (\Throwable $exception) {
            return ['success' => false, 'message' => Text::sprintf('COM_JTSEO_BULK_ERROR_ARTICLE_SAVE_FAILED', $articleId, $exception->getMessage())];
        }
    }

    /**
     * @return array<int, object>
     */
    private static function fetchArticles(int $limit): array
    {
        $db = self::db();
        $now = Factory::getDate()->toSql();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.alias'),
                $db->quoteName('a.metadesc'),
                $db->quoteName('a.introtext'),
                $db->quoteName('a.fulltext'),
                $db->quoteName('a.images'),
                $db->quoteName('a.attribs'),
                $db->quoteName('a.modified'),
                $db->quoteName('c.title', 'category_title'),
            ])
            ->from($db->quoteName('#__content', 'a'))
            ->leftJoin($db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->where($db->quoteName('a.state') . ' = 1')
            ->where('(' . $db->quoteName('c.published') . ' = 1 OR ' . $db->quoteName('c.published') . ' IS NULL)')
            ->where('(' . $db->quoteName('a.publish_up') . ' IS NULL OR ' . $db->quoteName('a.publish_up') . ' = ' . $db->quote('0000-00-00 00:00:00') . ' OR ' . $db->quoteName('a.publish_up') . ' <= ' . $db->quote($now) . ')')
            ->where('(' . $db->quoteName('a.publish_down') . ' IS NULL OR ' . $db->quoteName('a.publish_down') . ' = ' . $db->quote('0000-00-00 00:00:00') . ' OR ' . $db->quoteName('a.publish_down') . ' >= ' . $db->quote($now) . ')')
            ->order($db->quoteName('a.modified') . ' DESC, ' . $db->quoteName('a.id') . ' DESC');

        $db->setQuery($query, 0, max(1, min(1000, $limit)));

        return $db->loadObjectList() ?: [];
    }

    /**
     * @return array<string, mixed>
     */
    private static function scoreArticle(
        string $title,
        string $seoTitle,
        int $descriptionLength,
        bool $explicitDescription,
        bool $hasImage,
        string $robotsIndex,
        string $canonical,
        int $wordCount,
        int $minWordCount
    ): array {
        $score = 100;
        $notes = [];
        $effectiveTitle = $seoTitle !== '' ? $seoTitle : $title;
        $titleLength = self::utf8Length($effectiveTitle);

        if ($titleLength < 30 || $titleLength > 65) {
            $score -= 10;
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

        if (!$hasImage) {
            $score -= 10;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_MISSING_IMAGE';
        }

        if ($robotsIndex === 'noindex') {
            $score -= 20;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_NOINDEX';
        }

        if ($minWordCount > 0 && $wordCount < $minWordCount) {
            $score -= 10;
            $notes[] = 'COM_JTSEO_SCORE_NOTE_LOW_WORD_COUNT';
        }

        if ($canonical !== '') {
            $notes[] = 'COM_JTSEO_SCORE_NOTE_CANONICAL_OVERRIDE';
        }

        $score = max(0, min(100, $score));
        $bucket = 'weak';

        if ($score >= 90) {
            $bucket = 'excellent';
        } elseif ($score >= 75) {
            $bucket = 'good';
        } elseif ($score >= 50) {
            $bucket = 'fair';
        }

        if (empty($notes)) {
            $notes[] = 'COM_JTSEO_SCORE_NOTE_GOOD';
        }

        return [
            'score' => $score,
            'bucket' => $bucket,
            'notes' => $notes,
        ];
    }

    private static function cleanText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        $parts = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $value = implode(' ', $parts);

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength);
        }

        return substr($value, 0, $maxLength);
    }

    private static function cleanUrl(string $value): string
    {
        $value = trim(strip_tags($value));
        $parts = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $value = implode('', $parts);

        if ($value === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            $value = mb_substr($value, 0, 2048);
        } else {
            $value = substr($value, 0, 2048);
        }

        if (self::containsControlCharacter($value)) {
            return '';
        }

        if (preg_match('#^(?:javascript|data|vbscript|file):#i', $value) === 1) {
            return '';
        }

        if (strpos($value, '//') === 0) {
            return '';
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
        }

        return $value;
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
        $text = self::plainText($html);

        if (function_exists('mb_substr')) {
            return trim(mb_substr($text, 0, $length));
        }

        return trim(substr($text, 0, $length));
    }

    private static function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $text = implode(' ', $parts);

        return trim($text);
    }

    private static function utf8Length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($value);
        }

        return strlen($value);
    }

    private static function wordCount(string $text): int
    {
        $text = trim($text);

        if ($text === '') {
            return 0;
        }

        if (preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches) === false) {
            return str_word_count($text);
        }

        return count($matches[0]);
    }



    /**
     * Convert score note language keys into a compact CSV-friendly issue summary.
     *
     * @param   array<int, mixed>  $notes
     */
    private static function formatIssueSummary(array $notes): string
    {
        $items = [];

        foreach ($notes as $note) {
            $key = trim((string) $note);

            if ($key === '') {
                continue;
            }

            $items[] = Text::_($key);
        }

        return implode('; ', array_values(array_unique($items)));
    }

    public static function utf8Bom(): string
    {
        return chr(239) . chr(187) . chr(191);
    }

    private static function normaliseCsvColumn(string $name): string
    {
        $name = trim($name);
        $bom = self::utf8Bom();

        if (strncmp($name, $bom, strlen($bom)) === 0) {
            $name = substr($name, strlen($bom));
        }

        $name = strtolower($name);
        $parts = preg_split('/[^a-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $name = implode('_', $parts);

        return trim($name, '_');
    }

    private static function db(): DatabaseInterface
    {
        return Factory::getContainer()->get(DatabaseInterface::class);
    }
}
