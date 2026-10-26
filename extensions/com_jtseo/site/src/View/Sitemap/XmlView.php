<?php

/**
 * @package     JT SEO Control Center
 * @subpackage  Site
 *
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace JoomTheme\Component\Jtseo\Site\View\Sitemap;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\AbstractView;

final class XmlView extends AbstractView
{
    /**
     * Render the XML sitemap.
     *
     * @param   string|null  $tpl  Template name, unused.
     */
    public function display($tpl = null): void
    {
        $app    = Factory::getApplication();

        try {
            $app->getLanguage()->load('com_jtseo', JPATH_SITE);
        } catch (\Throwable $exception) {
            // Missing language files must not prevent XML output.
        }

        $params = ComponentHelper::getParams('com_jtseo');

        $statusCode = 200;

        if ((int) $params->get('enable_sitemap', 1) !== 1) {
            $statusCode = 404;
            $xml        = $this->buildErrorXml(404, Text::_('COM_JTSEO_SITEMAP_DISABLED'));
        } else {
            $xml = $this->buildSitemapXml($params);
        }

        $this->sendRawXml($xml, $statusCode);

        $app->close();
    }

    private function sendRawXml(string $xml, int $statusCode = 200): void
    {
        // Joomla may still have an HTML document selected for component URLs.
        // Clean buffered HTML and send native PHP headers so browsers parse the sitemap as XML.
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/xml; charset=UTF-8', true);
            header('X-Content-Type-Options: nosniff', true);
            header('X-Robots-Tag: noindex, follow', true);
        }

        echo $xml;
    }

    private function buildSitemapXml($params): string
    {
        $helperFile = JPATH_ADMINISTRATOR . '/components/com_jtseo/src/Helper/SitemapHelper.php';

        if (is_file($helperFile)) {
            require_once $helperFile;
        }

        if (class_exists('JoomTheme\\Component\\Jtseo\\Administrator\\Helper\\SitemapHelper')) {
            try {
                if (method_exists('JoomTheme\\Component\\Jtseo\\Administrator\\Helper\\SitemapHelper', 'buildForPath')) {
                    $built = \JoomTheme\Component\Jtseo\Administrator\Helper\SitemapHelper::buildForPath('sitemap.xml', $params);
                } else {
                    $built = \JoomTheme\Component\Jtseo\Administrator\Helper\SitemapHelper::buildXml($params);
                }

                return $this->ensureSitemapStylesheetInstruction((string) ($built['xml'] ?? ''));
            } catch (\Throwable $exception) {
                return $this->buildErrorXml(500, Text::_('COM_JTSEO_SITEMAP_BUILD_FAILED'));
            }
        }

        return $this->buildErrorXml(500, Text::_('COM_JTSEO_SITEMAP_HELPER_MISSING'));
    }

    private function ensureSitemapStylesheetInstruction(string $xml): string
    {
        $xml = trim($xml);

        if ($xml === '' || str_contains($xml, 'xml-stylesheet')) {
            return $xml;
        }

        if (!str_contains($xml, '<urlset') && !str_contains($xml, '<sitemapindex')) {
            return $xml;
        }

        $stylesheet = \Joomla\CMS\Uri\Uri::root(false) . 'media/com_jtseo/xsl/sitemap.xsl';
        $instruction = '<?xml-stylesheet type="text/xsl" href="' . $this->escapeXml($stylesheet) . '"?>';
        $updated = preg_replace('/^(<\?xml[^?]*\?>)\s*/', '$1' . "\n" . $instruction . "\n", $xml, 1, $count);

        if ((int) ($count ?? 0) > 0 && is_string($updated)) {
            return $updated;
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $instruction . "\n" . ltrim($xml);
    }

    private function buildErrorXml(int $code, string $message): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<error>' . "\n"
            . '  <code>' . $code . '</code>' . "\n"
            . '  <message>' . $this->escapeXml($message) . '</message>' . "\n"
            . '</error>' . "\n";
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
