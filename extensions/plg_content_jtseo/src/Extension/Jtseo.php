<?php

/**
 * @package     JT SEO Control Center
 * @subpackage  Content Plugin
 *
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace JoomTheme\Plugin\Content\Jtseo\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

final class Jtseo extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load plugin language automatically.
     *
     * @var bool
     */
    protected $autoloadLanguage = true;

    /**
     * Subscribe to Joomla events using the modern plugin event API.
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareForm' => 'onContentPrepareForm',
            'onContentAfterSave'   => 'onContentAfterSave',
        ];
    }

    /**
     * Add JT SEO article fields to com_content article forms.
     */
    public function onContentPrepareForm(Event $event): void
    {
        $form = $event instanceof PrepareFormEvent
            ? $event->getForm()
            : ($this->getEventArgument($event, 'subject', 0)
                ?? $this->getEventArgument($event, 'form', 0));

        if (!$form instanceof Form) {
            return;
        }

        if (!in_array($form->getName(), ['com_content.article', 'com_content.form'], true)) {
            return;
        }

        $componentParams = ComponentHelper::getParams('com_jtseo');

        if ((int) $componentParams->get('enable_article_fields', 1) !== 1) {
            return;
        }

        $fieldPath = dirname(__DIR__, 2) . '/forms';
        $fieldFile = $fieldPath . '/article.xml';

        if (is_file($fieldFile)) {
            if ($form->loadFile($fieldFile, false)) {
                $this->addLiveScoreAssets($componentParams);
            }
        }
    }

    /**
     * Refresh the physical sitemap after Joomla content is saved.
     */
    public function onContentAfterSave(Event $event): void
    {
        $context = (string) $this->getEventArgument($event, 'context', 0);

        if (!in_array($context, ['com_content.article', 'com_content.form'], true)) {
            return;
        }

        $componentParams = ComponentHelper::getParams('com_jtseo');

        if ((int) $componentParams->get('enable_sitemap', 1) !== 1
            || (int) $componentParams->get('sitemap_write_physical', 1) !== 1
        ) {
            return;
        }

        $helperFile = JPATH_ADMINISTRATOR . '/components/com_jtseo/src/Helper/SitemapHelper.php';

        if (!is_file($helperFile)) {
            return;
        }

        require_once $helperFile;

        if (!class_exists('JoomTheme\\Component\\Jtseo\\Administrator\\Helper\\SitemapHelper')) {
            return;
        }

        try {
            \JoomTheme\Component\Jtseo\Administrator\Helper\SitemapHelper::writePhysicalSitemap($componentParams);
        } catch (\Throwable $exception) {
            // Avoid blocking article saves if sitemap generation fails.
        }
    }

    /**
     * Add a lightweight live SEO score widget to the article edit screen.
     *
     * @param   mixed  $componentParams  Component parameters.
     */
    private function addLiveScoreAssets($componentParams): void
    {
        static $assetsAdded = false;

        if ($assetsAdded) {
            return;
        }

        $assetsAdded = true;

        try {
            $app = Factory::getApplication();

            if (method_exists($app, 'isClient') && !$app->isClient('administrator')) {
                return;
            }

            $document = $app->getDocument();
        } catch (\Throwable $exception) {
            return;
        }

        $labels = [
            'title' => Text::_('PLG_CONTENT_JTSEO_LIVE_SCORE_TITLE'),
            'subtitle' => Text::_('PLG_CONTENT_JTSEO_LIVE_SCORE_SUBTITLE'),
            'score' => Text::_('PLG_CONTENT_JTSEO_LIVE_SCORE_LABEL'),
            'excellent' => Text::_('PLG_CONTENT_JTSEO_SCORE_BUCKET_EXCELLENT'),
            'good' => Text::_('PLG_CONTENT_JTSEO_SCORE_BUCKET_GOOD'),
            'fair' => Text::_('PLG_CONTENT_JTSEO_SCORE_BUCKET_FAIR'),
            'weak' => Text::_('PLG_CONTENT_JTSEO_SCORE_BUCKET_WEAK'),
            'titleLength' => Text::_('PLG_CONTENT_JTSEO_SCORE_NOTE_TITLE_LENGTH'),
            'missingDescription' => Text::_('PLG_CONTENT_JTSEO_SCORE_NOTE_MISSING_DESCRIPTION'),
            'shortDescription' => Text::_('PLG_CONTENT_JTSEO_SCORE_NOTE_SHORT_DESCRIPTION'),
            'longDescription' => Text::_('PLG_CONTENT_JTSEO_SCORE_NOTE_LONG_DESCRIPTION'),
            'missingImage' => Text::_('PLG_CONTENT_JTSEO_SCORE_NOTE_MISSING_IMAGE'),
            'noindex' => Text::_('PLG_CONTENT_JTSEO_SCORE_NOTE_NOINDEX'),
            'lowWordCount' => Text::_('PLG_CONTENT_JTSEO_SCORE_NOTE_LOW_WORD_COUNT'),
            'canonicalOverride' => Text::_('PLG_CONTENT_JTSEO_SCORE_NOTE_CANONICAL_OVERRIDE'),
            'goodNote' => Text::_('PLG_CONTENT_JTSEO_SCORE_NOTE_GOOD'),
            'meta' => Text::_('PLG_CONTENT_JTSEO_LIVE_SCORE_META'),
        ];

        if (method_exists($document, 'addScriptOptions')) {
            $document->addScriptOptions('plg_content_jtseo.liveScore', [
                'minWords' => max(0, (int) $componentParams->get('seo_score_min_words', 100)),
                'labels'   => $labels,
            ]);
        }

        try {
            $wa = $document->getWebAssetManager();

            if (method_exists($wa, 'getRegistry')) {
                try {
                    $wa->getRegistry()->addExtensionRegistryFile('com_jtseo');
                } catch (\Throwable $exception) {
                    // The registry may already be loaded or the method may reject duplicates.
                }
            }

            $wa->useStyle('com_jtseo.live-score')
                ->useScript('com_jtseo.live-score');
        } catch (\Throwable $exception) {
            // Live scoring is helpful, but it must never block the edit form.
            return;
        }
    }

    private function getEventArgument(Event $event, string $name, int $index): mixed
    {
        if (isset($event[$name])) {
            return $event[$name];
        }

        $arguments = $event->getArguments();

        return $arguments[$index] ?? null;
    }
}
