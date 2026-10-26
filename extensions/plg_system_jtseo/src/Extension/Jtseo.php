<?php

/**
 * @package     JT SEO Control Center
 * @subpackage  System Plugin
 *
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace JoomTheme\Plugin\System\Jtseo\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

final class Jtseo extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    private static bool $notFoundShutdownRegistered = false;

    private static bool $notFoundLogged = false;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterInitialise'    => 'onAfterInitialise',
            'onAfterRoute'         => 'onAfterRoute',
            'onAfterRender'        => 'onAfterRender',
            'onAfterRespond'       => 'onAfterRespond',
            'onBeforeCompileHead'  => 'onBeforeCompileHead',
            'onError'              => 'onError',
        ];
    }

    public function onAfterInitialise(Event $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        // Redirect rules must run before Joomla's router turns unknown SEF paths into 404 pages.
        $this->handleRedirectRequest();

        $componentParams = ComponentHelper::getParams('com_jtseo');

        // Start a lightweight provisional 404 row before routing. Joomla can resolve
        // malformed article URLs to a valid article id and then raise the 404 later,
        // so relying only on route variables misses real broken URLs such as
        // /article-alias124. Successful responses remove this provisional row.
        if ((int) $componentParams->get('enable_404_logging', 1) === 1) {
            $this->markProvisionalNotFoundRequest($componentParams);
        }

        // Some Joomla error responses bypass late plugin events. A shutdown fallback
        // makes confirmed 404 logging reliable without changing the frontend error page.
        $this->registerNotFoundShutdownLogger();
    }

    public function onError(Event $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        if (!$this->isNotFoundErrorEvent($event)) {
            return;
        }

        $componentParams = ComponentHelper::getParams('com_jtseo');

        if ((int) $componentParams->get('enable_404_logging', 1) !== 1) {
            return;
        }

        $this->logNotFoundRequestOnce($componentParams);
    }


    public function onAfterRoute(Event $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $componentParams = ComponentHelper::getParams('com_jtseo');

        // Remove early/provisional rows only for routes we can confidently
        // identify as real successful pages. Do not clean up from onAfterRespond
        // using HTTP 200 because some Joomla 404 error pages are rendered through
        // a successful-looking late response.
        $this->removeProvisionalForSuccessfulRouteIfNeeded();

        // Joomla can render some 404 pages without firing a clean error event.
        // After routing, unresolved frontend requests can be detected before the
        // component dispatch stage and logged without waiting for the error page.
        $this->logRouteMissIfNeeded($componentParams);

        $sitemapRequestPath = $this->getSitemapRequestPath();

        if ($sitemapRequestPath === '') {
            return;
        }

        if ((int) $componentParams->get('enable_sitemap', 1) !== 1) {
            return;
        }

        $xml = $this->buildSitemapXml($componentParams, $sitemapRequestPath);

        $app->setHeader('Content-Type', 'application/xml; charset=utf-8', true);
        $app->setHeader('X-Robots-Tag', 'noindex, follow', true);

        echo $xml;
        $app->close();
    }


    public function onAfterRespond(Event $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $componentParams = ComponentHelper::getParams('com_jtseo');

        if ((int) $componentParams->get('enable_404_logging', 1) !== 1) {
            return;
        }

        $statusCode = $this->getResponseStatusCode();

        if ($statusCode === 404 || $this->looksLikeNotFoundResponse()) {
            $this->confirmProvisionalNotFoundRequest($componentParams);

            return;
        }

        // Do not remove provisional rows here based on a 200/3xx status.
        // Several Joomla error-page flows can still look successful to late
        // response events. Successful routes are cleaned earlier in onAfterRoute
        // only when the route is clearly valid.
    }

    public function onBeforeCompileHead(Event $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $document = $app->getDocument();

        if (!$document instanceof HtmlDocument) {
            return;
        }

        $componentParams = ComponentHelper::getParams('com_jtseo');
        $isHome          = $this->isHomePage();
        $isArticle       = $this->isContentArticlePage();
        $article         = $isArticle ? $this->getCurrentArticle() : null;
        $canonicalUrl    = $this->getCanonicalUrl($article);
        $pageImage       = '';
        $pageImageAlt    = '';

        if ((int) $this->params->get('remove_joomla_generator', 1) === 1) {
            $this->setGenerator($document, '');
        }

        if ((int) $componentParams->get('enable_canonical', 1) === 1 && $canonicalUrl !== '') {
            $this->replaceCanonicalLink($document, $canonicalUrl);
            $this->addPropertyMeta($document, 'og:url', $canonicalUrl);
        }

        if ((int) $this->params->get('add_og_site_name', 1) === 1) {
            $languageTag = trim((string) $app->getLanguage()->getTag());
            $identity = $this->getSchemaIdentity($componentParams, $languageTag);
            $siteName = trim((string) ($identity['website_name'] ?? $identity['name'] ?? ''));

            if ($siteName !== '') {
                $this->addPropertyMeta($document, 'og:site_name', $siteName);
            }
        }

        if ((int) $componentParams->get('enable_article_metadata', 1) === 1 && $article !== null) {
            $imageData    = $this->getArticleImageData($article);
            $pageImage    = (string) ($imageData['image'] ?? '');
            $pageImageAlt = (string) ($imageData['alt'] ?? '');

            $this->addArticleMetadata($document, $article);
            $this->addArticleRobotsMetadata($document, $article);
        }

        if ($isHome && (int) $componentParams->get('enable_homepage_metadata', 1) === 1) {
            $this->addHomepageMetadata($document, $componentParams);
        }

        if ($pageImage !== '' && (int) $componentParams->get('enable_article_images', 1) === 1) {
            $this->addImageMetadata($document, $pageImage, $pageImageAlt);
        } else {
            $this->addDefaultImageMetadata($document, $componentParams);
        }

        $this->addTwitterCardMetadata($document, $componentParams);

        if ((int) $this->params->get('add_generator_meta', 0) === 1) {
            $this->setGenerator($document, 'JT SEO Control Center');
        }
    }


    public function onAfterRender(Event $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $document = $app->getDocument();

        if (!$document instanceof HtmlDocument) {
            return;
        }

        $componentParams = ComponentHelper::getParams('com_jtseo');

        if ((int) $componentParams->get('enable_structured_data', 1) !== 1) {
            return;
        }

        $statusCode = $this->getResponseStatusCode();

        if ($statusCode >= 400 || $this->looksLikeNotFoundResponse()) {
            return;
        }

        $body = (string) $app->getBody();

        if ($body === '' || stripos($body, '</head>') === false) {
            return;
        }

        // Joomla's core Schema.org system plugin may already have produced the
        // canonical Organization/WebSite/WebPage graph. When that graph exists,
        // enhance it in place instead of publishing a competing second graph.
        // This keeps Joomla core enabled while making JT SEO the source for the
        // multilingual identity, absolute media URLs and Article dates.
        [$body, $coreSchemaHandled] = $this->enhanceJoomlaCoreStructuredData(
            $body,
            $document,
            $componentParams
        );

        if ($coreSchemaHandled) {
            $app->setBody($body);
            return;
        }

        $graph = $this->buildStructuredDataGraph($document, $componentParams);

        if ($graph === []) {
            return;
        }

        if ((int) $componentParams->get('schema_skip_existing', 1) === 1) {
            $existing = $this->collectExistingStructuredData($body);
            $graph = $this->filterDuplicateStructuredDataNodes($graph, $existing);
        }

        if ($graph === []) {
            return;
        }

        $json = json_encode(
            [
                '@context' => 'https://schema.org',
                '@graph'   => array_values($graph),
            ],
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );

        if (!is_string($json) || $json === '') {
            return;
        }

        $script = '<script type="application/ld+json" data-jtseo-schema="1">' . $json . '</script>';
        $updatedBody = preg_replace('#</head>#i', $script . "\n</head>", $body, 1);

        if (is_string($updatedBody) && $updatedBody !== $body) {
            $app->setBody($updatedBody);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildStructuredDataGraph(HtmlDocument $document, Registry $params): array
    {
        $isHome    = $this->isHomePage();
        $isArticle = $this->isContentArticlePage();
        $article   = $isArticle ? $this->getCurrentArticle() : null;

        if (!$isHome && !$isArticle && (int) $params->get('enable_webpage_schema', 1) !== 1) {
            return [];
        }

        $rootUrl      = rtrim(Uri::root(), '/');
        $homeUrl      = $rootUrl . '/';
        $canonicalUrl = $this->getCanonicalUrl($article);
        $languageTag  = trim((string) $this->getApplication()->getLanguage()->getTag());
        $identity     = $this->getSchemaIdentity($params, $languageTag);
        $organizationId = $homeUrl . '#organization';
        $websiteId      = $homeUrl . '#website';
        $webpageId      = $canonicalUrl . '#webpage';
        $graph           = [];

        $organization = [
            '@type' => $identity['type'],
            '@id'   => $organizationId,
            'name'  => $identity['name'],
            'url'   => $homeUrl,
        ];

        if ($identity['alternate_name'] !== '') {
            $organization['alternateName'] = $identity['alternate_name'];
        }

        if ($identity['description'] !== '') {
            $organization['description'] = $identity['description'];
        }

        if ($identity['logo'] !== '') {
            $organization['logo'] = [
                '@type'      => 'ImageObject',
                '@id'        => $homeUrl . '#organization-logo',
                'url'        => $identity['logo'],
                'contentUrl' => $identity['logo'],
            ];
            $organization['image'] = ['@id' => $homeUrl . '#organization-logo'];
        }

        if ($identity['email'] !== '') {
            $organization['email'] = $identity['email'];
        }

        if ($identity['telephone'] !== '') {
            $organization['telephone'] = $identity['telephone'];
        }

        if ($identity['same_as'] !== []) {
            $organization['sameAs'] = $identity['same_as'];
        }

        if ($identity['address'] !== []) {
            $organization['address'] = array_merge(
                ['@type' => 'PostalAddress'],
                $identity['address']
            );
        }

        $graph[] = $organization;

        $website = [
            '@type'     => 'WebSite',
            '@id'       => $websiteId,
            'url'       => $homeUrl,
            'name'      => $identity['website_name'],
            'publisher' => ['@id' => $organizationId],
        ];

        if ($languageTag !== '') {
            $website['inLanguage'] = $languageTag;
        }

        $graph[] = $website;

        $pageTitle = trim((string) $document->getTitle());
        $pageDescription = $this->getDocumentDescription($document);

        if ($isHome) {
            $configuredTitle = trim((string) $params->get('homepage_title', ''));
            $configuredDescription = trim((string) $params->get('homepage_description', ''));

            if ($configuredTitle !== '') {
                $pageTitle = $configuredTitle;
            }

            if ($configuredDescription !== '') {
                $pageDescription = $configuredDescription;
            }
        }

        if ($article !== null) {
            $titleOverride = $this->getArticleParam($article, 'jtseo_title');
            $descriptionOverride = $this->getArticleParam($article, 'jtseo_description');

            $pageTitle = $titleOverride !== ''
                ? $titleOverride
                : trim((string) ($article->title ?? $pageTitle));

            $pageDescription = $descriptionOverride !== ''
                ? $descriptionOverride
                : $this->getArticleDescription($article);
        }

        $webPage = [
            '@type'     => 'WebPage',
            '@id'       => $webpageId,
            'url'       => $canonicalUrl,
            'name'      => $pageTitle !== '' ? $pageTitle : $identity['name'],
            'isPartOf'  => ['@id' => $websiteId],
            'publisher' => ['@id' => $organizationId],
        ];

        if ($pageDescription !== '') {
            $webPage['description'] = $pageDescription;
        }

        if ($languageTag !== '') {
            $webPage['inLanguage'] = $languageTag;
        }

        $pageImage = $this->getStructuredDataPageImage($params, $article, $identity);

        if ($pageImage !== '') {
            $imageId = $canonicalUrl . '#primaryimage';
            $graph[] = [
                '@type' => 'ImageObject',
                '@id'   => $imageId,
                'url'   => $pageImage,
            ];
            $webPage['primaryImageOfPage'] = ['@id' => $imageId];
        }

        if ($article !== null) {
            $articleId = $canonicalUrl . '#article';
            $webPage['mainEntity'] = ['@id' => $articleId];
            $graph[] = $webPage;

            $published = $this->toAtomDate((string) ($article->publish_up ?? ''));

            if ($published === '') {
                $published = $this->toAtomDate((string) ($article->created ?? ''));
            }

            $modified = $this->toAtomDate((string) ($article->modified ?? ''));

            if ($modified === '') {
                $modified = $published;
            }

            $articleNode = [
                '@type'            => 'Article',
                '@id'              => $articleId,
                'url'              => $canonicalUrl,
                'mainEntityOfPage' => $canonicalUrl,
                'headline'         => $pageTitle !== '' ? $pageTitle : trim((string) ($article->title ?? '')),
                'publisher'        => ['@id' => $organizationId],
                'isPartOf'         => ['@id' => $websiteId],
            ];

            if ($pageDescription !== '') {
                $articleNode['description'] = $pageDescription;
            }

            if ($published !== '') {
                $articleNode['datePublished'] = $published;
            }

            if ($modified !== '') {
                $articleNode['dateModified'] = $modified;
            }

            if ($languageTag !== '') {
                $articleNode['inLanguage'] = $languageTag;
            }

            $section = trim((string) ($article->category_title ?? ''));

            if ($section !== '') {
                $articleNode['articleSection'] = $section;
            }

            $authorName = trim((string) ($article->created_by_alias ?? ''));

            if ($authorName === '') {
                $authorName = trim((string) ($article->author_name ?? ''));
            }

            if ($authorName !== '') {
                $articleNode['author'] = [
                    '@type' => 'Person',
                    'name'  => $authorName,
                ];
            }

            if ($pageImage !== '') {
                $articleNode['image'] = [$pageImage];
            }

            $graph[] = $articleNode;

            return $graph;
        }

        $graph[] = $webPage;

        return $graph;
    }

    /**
     * Enhance Joomla's native Schema.org graph instead of outputting a second
     * competing graph. Returns the updated HTML and whether a Joomla core graph
     * was found/handled.
     *
     * @return array{0: string, 1: bool}
     */
    private function enhanceJoomlaCoreStructuredData(
        string $html,
        HtmlDocument $document,
        Registry $params
    ): array {
        $handled = false;
        $pattern = '#(<script\b(?=[^>]*\btype\s*=\s*(["\'])application/ld\+json\2)[^>]*>)(.*?)(</script>)#is';

        $updated = preg_replace_callback(
            $pattern,
            function (array $matches) use ($document, $params, &$handled): string {
                $decoded = json_decode(
                    html_entity_decode(trim((string) ($matches[3] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    true
                );

                if (!is_array($decoded) || !$this->isJoomlaCoreStructuredDataGraph($decoded)) {
                    return (string) $matches[0];
                }

                $handled = true;
                $graph = is_array($decoded['@graph'] ?? null) ? $decoded['@graph'] : [];
                $languageTag = trim((string) $this->getApplication()->getLanguage()->getTag());
                $identity = $this->getSchemaIdentity($params, $languageTag);
                $rootUrl = rtrim(Uri::root(), '/') . '/';
                $organizationId = $rootUrl . '#/schema/Organization/base';
                $websiteId = $rootUrl . '#/schema/WebSite/base';
                $webpageId = $rootUrl . '#/schema/WebPage/base';
                $organizationIndex = null;
                $websiteIndex = null;
                $webpageIndex = null;
                $articleIndex = null;
                $contentEntityIndex = null;
                $currentArticleId = $this->getCurrentArticleId();
                $contentEntitySuffix = $currentArticleId > 0
                    ? '#/schema/com_content/article/' . $currentArticleId
                    : '';

                foreach ($graph as $index => $node) {
                    if (!is_array($node)) {
                        continue;
                    }

                    $type = strtolower(trim((string) ($node['@type'] ?? '')));
                    $id = trim((string) ($node['@id'] ?? ''));

                    if (
                        $organizationIndex === null
                        && in_array($type, ['organization', 'localbusiness'], true)
                        && str_contains($id, '#/schema/')
                    ) {
                        $organizationIndex = $index;
                        if ($id !== '') {
                            $organizationId = $id;
                        }
                    }

                    if ($websiteIndex === null && $type === 'website' && str_ends_with($id, '#/schema/WebSite/base')) {
                        $websiteIndex = $index;
                        $websiteId = $id;
                    }

                    if ($webpageIndex === null && $type === 'webpage' && str_ends_with($id, '#/schema/WebPage/base')) {
                        $webpageIndex = $index;
                        $webpageId = $id;
                    }

                    if ($articleIndex === null && in_array($type, ['article', 'newsarticle', 'blogposting'], true)) {
                        $articleIndex = $index;
                    }

                    if (
                        $contentEntityIndex === null
                        && $contentEntitySuffix !== ''
                        && str_ends_with($id, $contentEntitySuffix)
                    ) {
                        $contentEntityIndex = $index;
                    }
                }

                if ($organizationIndex !== null) {
                    $organization = $graph[$organizationIndex];
                    $organization['@type'] = $identity['type'];
                    $organization['@id'] = $organizationId;
                    $organization['name'] = $identity['name'];
                    $organization['url'] = $rootUrl;

                    $this->setSchemaValue($organization, 'alternateName', $identity['alternate_name']);
                    $this->setSchemaValue($organization, 'description', $identity['description']);
                    $this->setSchemaValue($organization, 'email', $identity['email']);
                    $this->setSchemaValue($organization, 'telephone', $identity['telephone']);

                    if ($identity['logo'] !== '') {
                        $logoId = $rootUrl . '#/schema/ImageObject/logo';
                        $existingLogo = is_array($organization['logo'] ?? null) ? $organization['logo'] : [];
                        $organization['logo'] = array_merge(
                            $existingLogo,
                            [
                                '@type'      => 'ImageObject',
                                '@id'        => $logoId,
                                'url'        => $identity['logo'],
                                'contentUrl' => $identity['logo'],
                            ]
                        );
                        $organization['image'] = ['@id' => $logoId];
                    } elseif (isset($organization['logo']) && is_array($organization['logo'])) {
                        $organization['logo'] = $this->normaliseStructuredDataMediaUrls($organization['logo']);
                    }

                    if ($identity['same_as'] !== []) {
                        $organization['sameAs'] = $identity['same_as'];
                    }

                    if ($identity['address'] !== []) {
                        $organization['address'] = array_merge(
                            ['@type' => 'PostalAddress'],
                            $identity['address']
                        );
                    }

                    $graph[$organizationIndex] = $this->normaliseStructuredDataMediaUrls($organization);
                }

                if ($websiteIndex !== null) {
                    $website = $graph[$websiteIndex];
                    $website['name'] = $identity['website_name'];
                    $website['publisher'] = ['@id' => $organizationId];

                    if ($languageTag !== '') {
                        $website['inLanguage'] = $languageTag;
                    }

                    $graph[$websiteIndex] = $website;
                }

                if ($webpageIndex !== null) {
                    $webpage = $graph[$webpageIndex];
                    $webpage['publisher'] = ['@id' => $organizationId];

                    if ($languageTag !== '') {
                        $webpage['inLanguage'] = $languageTag;
                    }

                    $graph[$webpageIndex] = $this->normaliseStructuredDataMediaUrls($webpage);
                }

                // Joomla normally injects the schema selected in the article's
                // Schema.org tab into the same graph (Book, Event, Recipe, etc.).
                // If that item-level node is missing from the rendered graph,
                // restore it from Joomla's own #__schemaorg record. This also
                // prevents JT SEO from replacing an explicitly selected core
                // schema type with a fallback Article node.
                $storedCoreSchema = $this->getJoomlaCoreArticleSchemaNode($webpageId);
                $hasCustomCoreContentEntity = false;

                if ($storedCoreSchema !== null) {
                    $storedType = strtolower(trim((string) ($storedCoreSchema['@type'] ?? '')));
                    $storedId = trim((string) ($storedCoreSchema['@id'] ?? ''));

                    if ($contentEntityIndex !== null) {
                        $renderedType = strtolower(trim((string) ($graph[$contentEntityIndex]['@type'] ?? '')));

                        // Keep Joomla's already-rendered node when it is the
                        // selected type because core/schemaorg plugins may have
                        // normalized dates or other fields. Replace only a
                        // mismatching fallback node (typically Article) with the
                        // explicitly selected stored type.
                        if ($renderedType !== $storedType) {
                            $graph[$contentEntityIndex] = $storedCoreSchema;
                        } else {
                            $storedId = trim((string) ($graph[$contentEntityIndex]['@id'] ?? $storedId));
                        }
                    } else {
                        $graph[] = $storedCoreSchema;
                        $contentEntityIndex = array_key_last($graph);
                    }

                    if ($storedId !== '' && $webpageIndex !== null) {
                        $graph[$webpageIndex]['mainEntity'] = ['@id' => $storedId];
                    }

                    if (in_array($storedType, ['article', 'newsarticle', 'blogposting'], true)) {
                        $articleIndex = $contentEntityIndex;
                    } else {
                        $articleIndex = null;
                        $hasCustomCoreContentEntity = true;
                    }
                } elseif ($contentEntityIndex !== null) {
                    $contentType = strtolower(trim((string) ($graph[$contentEntityIndex]['@type'] ?? '')));

                    if ($contentType !== '' && !in_array($contentType, ['article', 'newsarticle', 'blogposting'], true)) {
                        $hasCustomCoreContentEntity = true;

                        if ($webpageIndex !== null) {
                            $contentId = trim((string) ($graph[$contentEntityIndex]['@id'] ?? ''));

                            if ($contentId !== '') {
                                $graph[$webpageIndex]['mainEntity'] = ['@id' => $contentId];
                            }
                        }
                    }
                }

                $article = $this->isContentArticlePage() ? $this->getCurrentArticle() : null;

                if ($article !== null && !$hasCustomCoreContentEntity) {
                    $canonicalUrl = $this->getCanonicalUrl($article);
                    $pageImage = $this->getStructuredDataPageImage($params, $article, $identity);
                    $pageTitle = $this->getArticleParam($article, 'jtseo_title');
                    $pageDescription = $this->getArticleParam($article, 'jtseo_description');

                    if ($pageTitle === '') {
                        $pageTitle = trim((string) ($article->title ?? $document->getTitle()));
                    }

                    if ($pageDescription === '') {
                        $pageDescription = $this->getArticleDescription($article);
                    }

                    $published = $this->toAtomDate((string) ($article->publish_up ?? ''));
                    if ($published === '') {
                        $published = $this->toAtomDate((string) ($article->created ?? ''));
                    }

                    $modified = $this->toAtomDate((string) ($article->modified ?? ''));
                    if ($modified === '') {
                        $modified = $published;
                    }

                    if ($articleIndex !== null) {
                        $articleNode = $graph[$articleIndex];
                    } else {
                        $articleNode = [
                            '@type' => 'Article',
                            '@id' => $rootUrl . '#/schema/com_content/article/' . (int) ($article->id ?? 0),
                            'isPartOf' => ['@id' => $webpageId],
                        ];
                    }

                    $articleNode['url'] = $canonicalUrl;
                    $articleNode['name'] = $pageTitle;
                    $articleNode['headline'] = $pageTitle;
                    $articleNode['mainEntityOfPage'] = ['@id' => $webpageId];
                    $articleNode['publisher'] = ['@id' => $organizationId];

                    if ($pageDescription !== '') {
                        $articleNode['description'] = $pageDescription;
                    }

                    if ($published !== '') {
                        $articleNode['datePublished'] = $published;
                    }

                    if ($modified !== '') {
                        $articleNode['dateModified'] = $modified;
                    }

                    if ($languageTag !== '') {
                        $articleNode['inLanguage'] = $languageTag;
                    }

                    $section = trim((string) ($article->category_title ?? ''));
                    if ($section !== '') {
                        $articleNode['articleSection'] = $section;
                    }

                    $authorName = trim((string) ($article->created_by_alias ?? ''));
                    if ($authorName === '') {
                        $authorName = trim((string) ($article->author_name ?? ''));
                    }
                    if ($authorName !== '') {
                        $articleNode['author'] = ['@type' => 'Person', 'name' => $authorName];
                    }

                    if ($pageImage !== '') {
                        $articleNode['image'] = [$pageImage];
                        $articleNode['thumbnailUrl'] = $pageImage;
                    }

                    $articleNode = $this->normaliseStructuredDataMediaUrls($articleNode);

                    if ($articleIndex !== null) {
                        $graph[$articleIndex] = $articleNode;
                    } else {
                        $graph[] = $articleNode;
                    }

                    if ($webpageIndex !== null) {
                        $graph[$webpageIndex]['mainEntity'] = ['@id' => $articleNode['@id']];
                    }
                }

                foreach ($graph as $index => $node) {
                    if (is_array($node)) {
                        $graph[$index] = $this->normaliseStructuredDataMediaUrls($node);
                    }
                }

                $decoded['@graph'] = array_values($graph);
                $json = json_encode(
                    $decoded,
                    JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                    | JSON_HEX_TAG
                    | JSON_HEX_AMP
                    | JSON_HEX_APOS
                    | JSON_HEX_QUOT
                );

                if (!is_string($json) || $json === '') {
                    return (string) $matches[0];
                }

                return (string) $matches[1] . $json . (string) $matches[4];
            },
            $html
        );

        return [is_string($updated) ? $updated : $html, $handled];
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function isJoomlaCoreStructuredDataGraph(array $decoded): bool
    {
        $graph = $decoded['@graph'] ?? null;

        if (!is_array($graph)) {
            return false;
        }

        $hasWebsiteBase = false;
        $hasWebPageBase = false;

        foreach ($graph as $node) {
            if (!is_array($node)) {
                continue;
            }

            $id = trim((string) ($node['@id'] ?? ''));

            if (str_ends_with($id, '#/schema/WebSite/base')) {
                $hasWebsiteBase = true;
            }

            if (str_ends_with($id, '#/schema/WebPage/base')) {
                $hasWebPageBase = true;
            }
        }

        return $hasWebsiteBase && $hasWebPageBase;
    }

    /**
     * Load the schema explicitly selected in Joomla's article Schema.org tab.
     *
     * Joomla stores that item-level schema in #__schemaorg. Normally the core
     * system plugin adds it to the rendered graph before JT SEO runs. If the
     * node is missing, restoring the same stored data here keeps types such as
     * Book, Event and Recipe intact instead of silently falling back to Article.
     *
     * @return array<string, mixed>|null
     */
    private function getJoomlaCoreArticleSchemaNode(string $webpageId): ?array
    {
        if (!$this->isContentArticlePage()) {
            return null;
        }

        $itemId = $this->getCurrentArticleId();

        if ($itemId <= 0) {
            return null;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $context = 'com_content.article';
            $query = $db->getQuery(true)
                ->select([
                    $db->quoteName('schemaType'),
                    $db->quoteName('schema'),
                ])
                ->from($db->quoteName('#__schemaorg'))
                ->where($db->quoteName('itemId') . ' = :itemId')
                ->where($db->quoteName('context') . ' = :context')
                ->bind(':itemId', $itemId, ParameterType::INTEGER)
                ->bind(':context', $context, ParameterType::STRING);

            $db->setQuery($query);
            $stored = $db->loadObject();

            if (!is_object($stored)) {
                return null;
            }

            $schemaType = trim((string) ($stored->schemaType ?? ''));

            if ($schemaType === '' || strcasecmp($schemaType, 'None') === 0) {
                return null;
            }

            $registry = new Registry((string) ($stored->schema ?? ''));
            $node = $registry->toArray();

            if (!is_array($node)) {
                return null;
            }

            if (trim((string) ($node['@type'] ?? '')) === '') {
                $node['@type'] = $schemaType;
            }

            $rootUrl = rtrim(Uri::root(), '/') . '/';
            $node['@id'] = $rootUrl . '#/schema/com_content/article/' . $itemId;
            $node['isPartOf'] = ['@id' => $webpageId];
            $node = $this->cleanupStoredJoomlaSchemaNode($node);

            return $this->normaliseStructuredDataMediaUrls($node);
        } catch (\Throwable $exception) {
            // Joomla installations without the Schema.org table/plugin should
            // continue through JT SEO's normal fallback path.
            return null;
        }
    }

    /**
     * Apply the important parts of Joomla's schema cleanup to a node restored
     * from #__schemaorg after core rendering has already finished.
     *
     * @param array<string|int, mixed> $schema
     * @return array<string, mixed>
     */
    private function cleanupStoredJoomlaSchemaNode(array $schema): array
    {
        $result = [];

        foreach ($schema as $key => $value) {
            if ($key === 'genericField' && is_array($value)) {
                foreach ($value as $field) {
                    if (!is_array($field)) {
                        continue;
                    }

                    $title = trim((string) ($field['genericTitle'] ?? ''));

                    if ($title === '') {
                        continue;
                    }

                    $genericValue = $field['genericValue'] ?? null;

                    if ($genericValue !== null && $genericValue !== '') {
                        $result[$title] = $genericValue;
                    }
                }

                continue;
            }

            if (is_array($value)) {
                if (array_is_list($value)) {
                    $cleanList = [];

                    foreach ($value as $child) {
                        if (is_array($child)) {
                            $child = $this->cleanupStoredJoomlaSchemaNode($child);

                            if ($child === [] || (count($child) === 1 && isset($child['@type']))) {
                                continue;
                            }
                        }

                        if ($child !== null && $child !== '') {
                            $cleanList[] = $child;
                        }
                    }

                    $value = $cleanList;
                } else {
                    $value = $this->cleanupStoredJoomlaSchemaNode($value);

                    if ($value === [] || (count($value) === 1 && isset($value['@type']))) {
                        $value = null;
                    }
                }
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $node
     */
    private function setSchemaValue(array &$node, string $key, string $value): void
    {
        $value = trim($value);

        if ($value !== '') {
            $node[$key] = $value;
        }
    }

    /**
     * Make media properties absolute without rewriting Schema @id references.
     *
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function normaliseStructuredDataMediaUrls(array $value): array
    {
        foreach ($value as $key => $child) {
            if (is_array($child)) {
                $value[$key] = $this->normaliseStructuredDataMediaUrls($child);
                continue;
            }

            if (!is_string($child)) {
                continue;
            }

            if (is_int($key) && $this->looksLikeMediaPath($child)) {
                $absolute = $this->toAbsoluteUrl($child);
                if ($absolute !== '') {
                    $value[$key] = $absolute;
                }
                continue;
            }

            if (in_array($key, ['contentUrl', 'thumbnailUrl'], true)) {
                $absolute = $this->toAbsoluteUrl($child);
                if ($absolute !== '') {
                    $value[$key] = $absolute;
                }
                continue;
            }

            if ($key === 'url' && $this->looksLikeMediaPath($child)) {
                $absolute = $this->toAbsoluteUrl($child);
                if ($absolute !== '') {
                    $value[$key] = $absolute;
                }
            }
        }

        if (isset($value['image']) && is_string($value['image'])) {
            $absolute = $this->toAbsoluteUrl($value['image']);
            if ($absolute !== '') {
                $value['image'] = $absolute;
            }
        }

        return $value;
    }

    private function looksLikeMediaPath(string $value): bool
    {
        $path = (string) parse_url($value, PHP_URL_PATH);
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['avif', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'webp'], true)
            || str_starts_with(ltrim($path, '/'), 'images/');
    }

    /**
     * @return array<string, mixed>
     */
    private function getSchemaIdentity(Registry $params, string $languageTag): array
    {
        $type = trim((string) $params->get('homepage_schema_type', 'Organization'));

        if (!in_array($type, ['Organization', 'LocalBusiness'], true)) {
            $type = 'Organization';
        }

        $settings = [
            'type'             => $type,
            'name'             => trim((string) $params->get('organization_name', '')),
            'website_name'     => trim((string) $params->get('website_name', '')),
            'alternate_name'   => trim((string) $params->get('organization_alternate_name', '')),
            'description'      => trim((string) $params->get('organization_description', '')),
            'logo'             => trim((string) $params->get('organization_logo', '')),
            'email'            => trim((string) $params->get('organization_email', '')),
            'telephone'        => trim((string) $params->get('organization_telephone', '')),
            'street_address'   => trim((string) $params->get('organization_street_address', '')),
            'address_locality' => trim((string) $params->get('organization_address_locality', '')),
            'address_region'   => trim((string) $params->get('organization_address_region', '')),
            'postal_code'      => trim((string) $params->get('organization_postal_code', '')),
            'address_country'  => trim((string) $params->get('organization_address_country', '')),
        ];

        foreach ($this->normaliseSubformRows($params->get('schema_language_settings', [])) as $row) {
            $rowLanguage = trim((string) ($row['language'] ?? ''));

            if ($rowLanguage === '' || strcasecmp($rowLanguage, $languageTag) !== 0) {
                continue;
            }

            foreach (array_keys($settings) as $key) {
                if ($key === 'type') {
                    continue;
                }

                $value = trim((string) ($row[$key] ?? ''));

                if ($value !== '') {
                    $settings[$key] = $value;
                }
            }

            break;
        }

        if ($settings['name'] === '') {
            $settings['name'] = trim((string) $this->getApplication()->get('sitename', ''));
        }

        if ($settings['name'] === '') {
            $settings['name'] = (string) Uri::getInstance()->getHost();
        }

        if ($settings['website_name'] === '') {
            $settings['website_name'] = $settings['name'];
        }

        if ($settings['website_name'] === '') {
            $settings['website_name'] = trim((string) $this->getApplication()->get('sitename', ''));
        }

        $settings['logo'] = $this->toAbsoluteUrl($settings['logo']);

        $settings['same_as'] = $this->normaliseSameAs(
            (string) $params->get('organization_same_as', '')
        );

        $address = [];

        foreach (
            [
                'streetAddress'   => 'street_address',
                'addressLocality' => 'address_locality',
                'addressRegion'   => 'address_region',
                'postalCode'      => 'postal_code',
                'addressCountry'  => 'address_country',
            ] as $schemaKey => $settingKey
        ) {
            if ($settings[$settingKey] !== '') {
                $address[$schemaKey] = $settings[$settingKey];
            }
        }

        $settings['address'] = $address;

        return $settings;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normaliseSubformRows(mixed $value): array
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

    /**
     * @return array<int, string>
     */
    private function normaliseSameAs(string $value): array
    {
        $urls = preg_split('/[\r\n,]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $normalised = [];

        foreach ($urls as $url) {
            $url = trim($url);

            if (preg_match('#^https?://#i', $url) !== 1 || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $normalised[$url] = $url;
        }

        return array_values($normalised);
    }

    private function getDocumentDescription(HtmlDocument $document): string
    {
        $description = '';

        if (method_exists($document, 'getDescription')) {
            $description = trim((string) $document->getDescription());
        }

        if ($description === '' && method_exists($document, 'getMetaData')) {
            $description = trim((string) $document->getMetaData('description'));
        }

        return $this->truncateText($this->cleanText($description), 320);
    }

    /**
     * @param   array<string, mixed>  $identity
     */
    private function getStructuredDataPageImage(Registry $params, ?object $article, array $identity): string
    {
        if ($article !== null && (int) $params->get('enable_article_images', 1) === 1) {
            $imageData = $this->getArticleImageData($article);
            $image = $this->toAbsoluteUrl((string) ($imageData['image'] ?? ''));

            if ($image !== '') {
                return $image;
            }
        }

        $defaultImage = $this->toAbsoluteUrl((string) $params->get('default_og_image', ''));

        if ($defaultImage !== '') {
            return $defaultImage;
        }

        return (string) ($identity['logo'] ?? '');
    }

    /**
     * @return array{types: array<string, bool>, ids: array<string, bool>}
     */
    private function collectExistingStructuredData(string $html): array
    {
        $result = [
            'types' => [],
            'ids'   => [],
        ];

        if (
            preg_match_all(
                '#<script\b(?=[^>]*\btype\s*=\s*(["\'])application/ld\+json\1)[^>]*>(.*?)</script>#is',
                $html,
                $matches
            ) !== false
        ) {
            foreach ($matches[2] ?? [] as $json) {
                $decoded = json_decode(
                    html_entity_decode(trim((string) $json), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    true
                );

                if (is_array($decoded)) {
                    $this->collectStructuredDataIdentifiers($decoded, $result);
                }
            }
        }

        return $result;
    }

    /**
     * @param   array<string, mixed>|array<int, mixed>  $value
     * @param   array{types: array<string, bool>, ids: array<string, bool>}  $result
     */
    private function collectStructuredDataIdentifiers(array $value, array &$result): void
    {
        $declaresEntity = false;

        if (isset($value['@type'])) {
            $types = is_array($value['@type']) ? $value['@type'] : [$value['@type']];

            foreach ($types as $type) {
                $type = strtolower(trim((string) $type));

                if ($type !== '') {
                    $result['types'][$type] = true;
                    $declaresEntity = true;
                }
            }
        }

        // An @id without @type is often only a reference (for example a
        // WebPage.mainEntity pointer). Treating references as declared nodes
        // caused JT SEO to suppress the real Article entity in v1.0.67.
        if ($declaresEntity && isset($value['@id'])) {
            $id = trim((string) $value['@id']);

            if ($id !== '') {
                $result['ids'][$id] = true;
            }
        }

        foreach ($value as $child) {
            if (is_array($child)) {
                $this->collectStructuredDataIdentifiers($child, $result);
            }
        }
    }

    /**
     * @param   array<int, array<string, mixed>>  $graph
     * @param   array{types: array<string, bool>, ids: array<string, bool>}  $existing
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterDuplicateStructuredDataNodes(array $graph, array $existing): array
    {
        $articleAliases = ['article', 'newsarticle', 'blogposting'];
        $webPageAliases = [
            'webpage',
            'aboutpage',
            'checkoutpage',
            'collectionpage',
            'contactpage',
            'faqpage',
            'itempage',
            'medicalwebpage',
            'profilepage',
            'qapage',
            'realestatelisting',
            'searchresultspage',
        ];

        $hasExistingArticle = false;
        $hasExistingWebPage = false;

        foreach ($articleAliases as $alias) {
            if (isset($existing['types'][$alias])) {
                $hasExistingArticle = true;
                break;
            }
        }

        foreach ($webPageAliases as $alias) {
            if (isset($existing['types'][$alias])) {
                $hasExistingWebPage = true;
                break;
            }
        }

        $filtered = [];

        foreach ($graph as $node) {
            $id = trim((string) ($node['@id'] ?? ''));
            $type = strtolower(trim((string) ($node['@type'] ?? '')));

            if ($id !== '' && isset($existing['ids'][$id])) {
                continue;
            }

            if ($type === 'article' && $hasExistingArticle) {
                continue;
            }

            if ($type === 'webpage' && $hasExistingWebPage) {
                continue;
            }

            // Keep supporting Organization, WebSite and ImageObject nodes unless
            // their exact @id already exists. Page-level type matching alone is
            // not enough to remove them because other generated nodes may refer
            // to those stable identifiers.
            if ($type === 'webpage' && $hasExistingArticle) {
                unset($node['mainEntity']);
            }

            $filtered[] = $node;
        }

        return $filtered;
    }

    private function handleRedirectRequest(): void
    {
        $app = $this->getApplication();
        $componentParams = ComponentHelper::getParams('com_jtseo');

        if ((int) $componentParams->get('enable_redirects', 1) !== 1) {
            return;
        }

        $method = strtoupper((string) $app->getInput()->server->getString('REQUEST_METHOD', 'GET'));

        if (!in_array($method, ['GET', 'HEAD'], true)) {
            return;
        }

        if ($this->isSitemapRequest()) {
            return;
        }

        $redirect = $this->findRedirectForCurrentRequest();

        if (!$redirect) {
            return;
        }

        $statusCode = (int) ($redirect->status_code ?? 301);
        $this->recordRedirectHit((int) ($redirect->id ?? 0));

        if ($statusCode === 410) {
            if (function_exists('http_response_code')) {
                http_response_code(410);
            }

            $app->setHeader('Status', '410 Gone', true);
            $app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
            echo 'Gone';
            $app->close();
        }

        if (!in_array($statusCode, [301, 302, 307, 308], true)) {
            $statusCode = 301;
        }

        $target = $this->toAbsoluteUrl((string) ($redirect->new_url ?? ''));

        if ($target === '') {
            return;
        }

        $current = Uri::getInstance()->toString(['scheme', 'host', 'port', 'path', 'query']);

        if (rtrim($target, '/') === rtrim($current, '/')) {
            return;
        }

        $app->redirect($target, $statusCode);
        $app->close();
    }

    private function findRedirectForCurrentRequest(): ?object
    {
        $candidates = $this->getCurrentRequestCandidates();

        if ($candidates === []) {
            return null;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            foreach ($candidates as $candidate) {
                $hash = sha1($candidate);
                $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->quoteName('#__jtseo_redirects'))
                    ->where($db->quoteName('enabled') . ' = 1')
                    ->where($db->quoteName('old_hash') . ' = :old_hash')
                    ->bind(':old_hash', $hash);

                $db->setQuery($query, 0, 1);
                $row = $db->loadObject();

                if (is_object($row)) {
                    return $row;
                }
            }
        } catch (\Throwable $exception) {
            return null;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function getCurrentRequestCandidates(): array
    {
        $uri = Uri::getInstance();
        $query = trim((string) $uri->getQuery());
        $path = '/' . ltrim($this->getRequestPathWithoutBase(), '/');
        $path = $path === '//' ? '/' : $path;
        $relative = $query !== '' ? $path . '?' . $query : $path;
        $full = $uri->toString(['scheme', 'host', 'port', 'path', 'query']);

        return array_values(array_unique(array_filter([$relative, $path, $full])));
    }

    private function getRequestPathWithoutBase(): string
    {
        $uri  = Uri::getInstance();
        $path = trim((string) $uri->getPath(), '/');
        $base = trim((string) Uri::base(true), '/');

        if ($base !== '' && str_starts_with($path, $base . '/')) {
            $path = substr($path, strlen($base) + 1);
        }

        return '/' . ltrim($path, '/');
    }

    private function recordRedirectHit(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $now = Factory::getDate()->toSql();
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__jtseo_redirects'))
                ->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
                ->set($db->quoteName('last_hit') . ' = :last_hit')
                ->set($db->quoteName('modified') . ' = :modified')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':last_hit', $now)
                ->bind(':modified', $now)
                ->bind(':id', $id, ParameterType::INTEGER);

            $db->setQuery($query)->execute();
        } catch (\Throwable $exception) {
            // Do not interrupt a redirect because a hit counter failed.
        }
    }

    private function removeProvisionalForSuccessfulRouteIfNeeded(): void
    {
        if (!$this->isNotFoundLoggableRequest()) {
            return;
        }

        if ($this->isClearlySuccessfulRoutedRequest()) {
            $this->removeProvisionalNotFoundRequest();
        }
    }

    private function isClearlySuccessfulRoutedRequest(): bool
    {
        $app = $this->getApplication();
        $input = $app->getInput();
        $option = trim((string) $input->getCmd('option', ''));
        $view = trim((string) $input->getCmd('view', ''));

        if ($option === '') {
            return false;
        }

        // Article pages are successful only when Joomla has resolved a real
        // published article id. Broken aliases often look like com_content but
        // have id=0 or a missing id.
        if ($option === 'com_content') {
            if ($view === 'article') {
                $id = (int) $input->getInt('id', 0);

                return $id > 0 && $this->contentArticleRouteLooksValid($id);
            }

            // Common com_content list views are considered valid once routed.
            if (in_array($view, ['category', 'categories', 'featured', 'archive', 'form'], true)) {
                return true;
            }

            return false;
        }

        // Other routed Joomla components are normally real pages. This keeps
        // valid contact/search/users/tags pages out of the 404 table.
        return str_starts_with($option, 'com_');
    }

    private function logRouteMissIfNeeded(Registry $params): void
    {
        if (self::$notFoundLogged) {
            return;
        }

        if ((int) $params->get('enable_404_logging', 1) !== 1) {
            return;
        }

        if (!$this->isNotFoundLoggableRequest()) {
            return;
        }

        $app = $this->getApplication();
        $input = $app->getInput();
        $option = trim((string) $input->getCmd('option', ''));
        $view = trim((string) $input->getCmd('view', ''));

        // A routed site request with no component option is normally an
        // unresolved SEF path. This catches /missing-page style URLs.
        if ($option === '') {
            $this->confirmProvisionalNotFoundRequest($params);

            return;
        }

        // Content article aliases that fail to resolve may still leave a
        // com_content/article request with no valid id. Catch those too.
        if ($option === 'com_content' && $view === 'article') {
            $id = (int) $input->getInt('id', 0);

            if ($id <= 0 || !$this->contentArticleRouteLooksValid($id)) {
                $this->confirmProvisionalNotFoundRequest($params);
            }
        }
    }

    private function contentArticleExists(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('id') . ' = :id')
                ->where($db->quoteName('state') . ' = 1')
                ->bind(':id', $id, ParameterType::INTEGER);

            $db->setQuery($query);

            return (int) $db->loadResult() > 0;
        } catch (\Throwable $exception) {
            return true;
        }
    }

    private function contentArticleRouteLooksValid(int $id): bool
    {
        if (!$this->contentArticleExists($id)) {
            return false;
        }

        // A menu item that directly targets the article is a valid route even if
        // the visible menu alias does not contain the article alias.
        if ($this->activeMenuTargetsArticle($id)) {
            return true;
        }

        // For category/article SEF routes, require an exact article alias segment.
        // This avoids treating /123-article-alias124 as a successful article route
        // simply because Joomla extracted id=123 before raising the 404 page.
        return $this->requestPathContainsArticleAlias($id);
    }

    private function activeMenuTargetsArticle(int $id): bool
    {
        try {
            $menu = $this->getApplication()->getMenu();
            $active = $menu ? $menu->getActive() : null;

            if (!$active || empty($active->query) || !is_array($active->query)) {
                return false;
            }

            if ((string) ($active->query['option'] ?? '') !== 'com_content'
                || (string) ($active->query['view'] ?? '') !== 'article'
                || (int) ($active->query['id'] ?? 0) !== $id) {
                return false;
            }

            return $this->requestPathMatchesMenuRoute($active);
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function normaliseEncodedRoute(string $value): string
    {
        $result = '';
        $length = strlen($value);

        for ($index = 0; $index < $length; $index++) {
            if ($value[$index] === '%' && $index + 2 < $length) {
                $triplet = strtolower(substr($value, $index, 3));

                if ($triplet === '%2f') {
                    $result .= '/';
                    $index += 2;
                    continue;
                }

                if ($triplet === '%20') {
                    $result .= ' ';
                    $index += 2;
                    continue;
                }
            }

            $result .= $value[$index];
        }

        return $result;
    }

    private function stripRouteExtension(string $value): string
    {
        foreach (['.html', '.htm', '.php'] as $extension) {
            if (str_ends_with(strtolower($value), $extension)) {
                return substr($value, 0, -strlen($extension));
            }
        }

        return $value;
    }

    private function requestPathMatchesMenuRoute(object $menuItem): bool
    {
        $route = trim((string) ($menuItem->route ?? ''), '/');

        if ($route === '') {
            $route = trim((string) ($menuItem->alias ?? ''), '/');
        }

        if ($route === '') {
            return false;
        }

        $path = trim($this->normaliseEncodedRoute($this->getRequestPathWithoutBase()), '/');

        if ($path === '') {
            return false;
        }

        $normalise = function (string $value): string {
            $value = trim(strtolower($value), '/');
            $value = $this->stripRouteExtension($value);

            return $value;
        };

        $path = $normalise($path);
        $route = $normalise($this->normaliseEncodedRoute($route));

        return $path === $route || str_ends_with($path, '/' . $route);
    }

    private function requestPathContainsArticleAlias(int $id): bool
    {
        $alias = $this->getContentArticleAlias($id);

        if ($alias === '') {
            return false;
        }

        $path = trim($this->getRequestPathWithoutBase(), '/');

        if ($path === '') {
            return false;
        }

        $segments = array_filter(explode('/', $this->normaliseEncodedRoute($path)), static fn ($segment): bool => $segment !== '');
        $alias = strtolower($alias);
        $validSegments = [
            $alias,
            strtolower((string) $id),
            strtolower((string) $id . '-' . $alias),
        ];

        foreach ($segments as $segment) {
            $segment = strtolower(trim((string) $segment));
            $segment = $this->stripRouteExtension($segment);

            if (in_array($segment, $validSegments, true)) {
                return true;
            }
        }

        return false;
    }

    private function getContentArticleAlias(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('alias'))
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('id') . ' = :id')
                ->where($db->quoteName('state') . ' = 1')
                ->bind(':id', $id, ParameterType::INTEGER);

            $db->setQuery($query);

            return trim((string) $db->loadResult());
        } catch (\Throwable $exception) {
            return '';
        }
    }

    private function registerNotFoundShutdownLogger(): void
    {
        if (self::$notFoundShutdownRegistered) {
            return;
        }

        self::$notFoundShutdownRegistered = true;

        register_shutdown_function(function (): void {
            try {
                $app = $this->getApplication();

                if (!$app->isClient('site')) {
                    return;
                }

                $componentParams = ComponentHelper::getParams('com_jtseo');

                if ((int) $componentParams->get('enable_404_logging', 1) !== 1) {
                    return;
                }

                $statusCode = $this->getResponseStatusCode();

                if ($statusCode === 404 || $this->looksLikeNotFoundResponse()) {
                    $this->confirmProvisionalNotFoundRequest($componentParams);

                    return;
                }

                // Do not clean provisional rows from the shutdown handler based
                // on an apparent 200/3xx response; Joomla may render its 404 page
                // through a late successful-looking response.
            } catch (\Throwable $exception) {
                // Shutdown handlers must never leak errors to visitors.
            }
        });
    }

    private function isNotFoundErrorEvent(Event $event): bool
    {
        $error = $this->getEventArgument($event, 'error', 0);

        if (!$error instanceof \Throwable) {
            $error = $this->getEventArgument($event, 'exception', 0);
        }

        if ($error instanceof \Throwable) {
            $code = (int) $error->getCode();

            if ($code === 404) {
                return true;
            }

            return stripos($error->getMessage(), '404') !== false
                || stripos($error->getMessage(), 'not found') !== false;
        }

        $code = (int) $this->getEventArgument($event, 'code', 1);

        return $code === 404;
    }

    private function looksLikeNotFoundResponse(): bool
    {
        foreach (headers_list() as $header) {
            if (stripos($header, '404') !== false && stripos($header, 'not found') !== false) {
                return true;
            }
        }

        return false;
    }

    private function logNotFoundRequestOnce(Registry $params): void
    {
        $this->confirmProvisionalNotFoundRequest($params);
    }

    private function markProvisionalNotFoundRequest(Registry $params): void
    {
        if (self::$notFoundLogged) {
            return;
        }

        if (!$this->isNotFoundLoggableRequest()) {
            return;
        }

        $this->upsertNotFoundLog($params, true, true);
    }

    private function confirmProvisionalNotFoundRequest(Registry $params): void
    {
        if (self::$notFoundLogged) {
            return;
        }

        self::$notFoundLogged = true;
        $this->upsertNotFoundLog($params, false, false);
    }

    private function removeProvisionalNotFoundRequest(): void
    {
        if (!self::$notFoundShutdownRegistered && self::$notFoundLogged) {
            return;
        }

        if (!$this->isNotFoundLoggableRequest()) {
            return;
        }

        $url = $this->getCurrentRequestUrl(ComponentHelper::getParams('com_jtseo'));

        if ($url === '') {
            return;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $columns = $this->getNotFoundLogColumns();

            if ($columns === [] || !isset($columns['url_hash'])) {
                return;
            }

            $hash = sha1($url);
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__jtseo_404_logs'))
                ->where($db->quoteName('url_hash') . ' = :url_hash')
                ->bind(':url_hash', $hash);

            if (isset($columns['provisional'])) {
                $query->where($db->quoteName('provisional') . ' = 1');
            }

            $db->setQuery($query)->execute();
        } catch (\Throwable $exception) {
            // Cleanup must never affect frontend rendering.
        }
    }

    private function isNotFoundLoggableRequest(): bool
    {
        $app = $this->getApplication();
        $method = strtoupper((string) $app->getInput()->server->getString('REQUEST_METHOD', 'GET'));

        if (!in_array($method, ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($this->isSitemapRequest()) {
            return false;
        }

        $path = $this->getRequestPathWithoutBase();

        if ($path === '/' || $path === '') {
            return false;
        }

        $filesystemPath = JPATH_ROOT . '/' . ltrim($path, '/');

        if (is_file($filesystemPath) || is_dir($filesystemPath)) {
            return false;
        }

        return true;
    }

    private function getCurrentRequestUrl(Registry $params): string
    {
        $parts = ['scheme', 'host', 'port', 'path'];

        if ((int) $params->get('redirect_log_query_strings', 0) === 1) {
            $parts[] = 'query';
        }

        return trim(Uri::getInstance()->toString($parts));
    }

    private function getResponseStatusCode(): int
    {
        $app = $this->getApplication();

        try {
            if (method_exists($app, 'getResponse')) {
                $response = $app->getResponse();

                if (is_object($response) && method_exists($response, 'getStatusCode')) {
                    return (int) $response->getStatusCode();
                }
            }
        } catch (\Throwable $exception) {
            // Fall back below.
        }

        if (function_exists('http_response_code')) {
            return (int) http_response_code();
        }

        return 0;
    }

    private function logNotFoundRequest(Registry $params): void
    {
        $this->upsertNotFoundLog($params, false, true);
    }

    private function getNotFoundLogColumns(): array
    {
        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            return $db->getTableColumns('#__jtseo_404_logs', false) ?: [];
        } catch (\Throwable $exception) {
            return [];
        }
    }

    private function ensureNotFoundLogSchema(array $columns): bool
    {
        return isset(
            $columns['request_url'],
            $columns['url_hash'],
            $columns['hits'],
            $columns['first_hit'],
            $columns['last_hit']
        );
    }

    private function upsertNotFoundLog(Registry $params, bool $provisional, bool $incrementExisting): void
    {
        $url = $this->getCurrentRequestUrl($params);

        if ($url === '') {
            return;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $columns = $this->getNotFoundLogColumns();
            $hasProvisionalColumn = isset($columns['provisional']);

            if (!$this->ensureNotFoundLogSchema($columns)) {
                return;
            }
            $now = Factory::getDate()->toSql();
            $hash = sha1($url);
            $referrer = '';
            $userAgent = '';

            if ((int) $params->get('redirect_log_referrers', 1) === 1) {
                $referrer = substr((string) $this->getApplication()->getInput()->server->getString('HTTP_REFERER', ''), 0, 2048);
            }

            if ((int) $params->get('redirect_log_user_agents', 0) === 1) {
                $userAgent = substr((string) $this->getApplication()->getInput()->server->getString('HTTP_USER_AGENT', ''), 0, 512);
            }

            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__jtseo_404_logs'))
                ->where($db->quoteName('url_hash') . ' = :url_hash')
                ->bind(':url_hash', $hash);

            $db->setQuery($query);
            $existingId = (int) $db->loadResult();

            if ($existingId > 0) {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__jtseo_404_logs'))
                    ->set($db->quoteName('referrer') . ' = :referrer')
                    ->set($db->quoteName('user_agent') . ' = :user_agent')
                    ->set($db->quoteName('last_hit') . ' = :last_hit')
                    ->where($db->quoteName('id') . ' = :id')
                    ->bind(':referrer', $referrer)
                    ->bind(':user_agent', $userAgent)
                    ->bind(':last_hit', $now)
                    ->bind(':id', $existingId, ParameterType::INTEGER);

                if ($hasProvisionalColumn) {
                    $query->set($db->quoteName('provisional') . ' = :provisional')
                        ->bind(':provisional', $provisional ? 1 : 0, ParameterType::INTEGER);
                }

                if ($incrementExisting) {
                    $query->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1');
                }

                $db->setQuery($query)->execute();

                return;
            }

            $insertColumns = ['request_url', 'url_hash', 'referrer', 'user_agent', 'hits', 'first_hit', 'last_hit'];
            $values = [
                $db->quote($url),
                $db->quote($hash),
                $db->quote($referrer),
                $db->quote($userAgent),
                '1',
                $db->quote($now),
                $db->quote($now),
            ];

            if ($hasProvisionalColumn) {
                $insertColumns[] = 'provisional';
                $values[] = $provisional ? '1' : '0';
            }

            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__jtseo_404_logs'))
                ->columns($db->quoteName($insertColumns))
                ->values(implode(',', $values));

            $db->setQuery($query)->execute();
        } catch (\Throwable $exception) {
            // 404 logging must never break frontend rendering.
        }
    }

    private function isSitemapRequest(): bool
    {
        return $this->getSitemapRequestPath() !== '';
    }

    private function getSitemapRequestPath(): string
    {
        $uri  = Uri::getInstance();
        $path = trim((string) $uri->getPath(), '/');
        $base = trim((string) Uri::base(true), '/');

        if ($base !== '' && str_starts_with($path, $base . '/')) {
            $path = substr($path, strlen($base) + 1);
        }

        if (str_starts_with($path, 'index.php/')) {
            $path = substr($path, 10);
        }

        if (preg_match('#^sitemap(?:\.xml|[.-][A-Za-z0-9_-]+(?:[.-]\d+)?\.xml)$#', $path) !== 1) {
            return '';
        }

        return $path;
    }

    private function buildSitemapXml(Registry $params, string $requestPath = 'sitemap.xml'): string
    {
        $helperFile = JPATH_ADMINISTRATOR . '/components/com_jtseo/src/Helper/SitemapHelper.php';

        if (is_file($helperFile)) {
            require_once $helperFile;
        }

        if (class_exists('JoomTheme\\Component\\Jtseo\\Administrator\\Helper\\SitemapHelper')) {
            try {
                if (method_exists('JoomTheme\\Component\\Jtseo\\Administrator\\Helper\\SitemapHelper', 'buildForPath')) {
                    $built = \JoomTheme\Component\Jtseo\Administrator\Helper\SitemapHelper::buildForPath($requestPath, $params);
                } else {
                    $built = \JoomTheme\Component\Jtseo\Administrator\Helper\SitemapHelper::buildXml($params);
                }

                return $this->ensureSitemapStylesheetInstruction((string) ($built['xml'] ?? ''));
            } catch (\Throwable $exception) {
                // Fall through to a valid but empty sitemap below.
            }
        }

        return $this->ensureSitemapStylesheetInstruction(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . '</urlset>' . "\n"
        );
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

        $stylesheet = Uri::root(false) . 'media/com_jtseo/xsl/sitemap.xsl';
        $instruction = '<?xml-stylesheet type="text/xsl" href="' . $this->escapeXml($stylesheet) . '"?>';
        if (str_starts_with($xml, '<?xml')) {
            $declarationEnd = strpos($xml, '?>');

            if ($declarationEnd !== false) {
                $declarationEnd += 2;

                return substr($xml, 0, $declarationEnd)
                    . "\n" . $instruction . "\n"
                    . ltrim(substr($xml, $declarationEnd));
            }
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $instruction . "\n" . ltrim($xml);
    }

    private function addHomepageMetadata(HtmlDocument $document, Registry $params): void
    {
        $title       = trim((string) $params->get('homepage_title', ''));
        $description = trim((string) $params->get('homepage_description', ''));

        if ($title !== '') {
            $document->setTitle($title);
            $this->addPropertyMeta($document, 'og:title', $title);
            $this->addNameMeta($document, 'twitter:title', $title);
        }

        if ($description !== '') {
            $this->setDocumentDescription($document, $description);
            $this->addPropertyMeta($document, 'og:description', $description);
            $this->addNameMeta($document, 'twitter:description', $description);
        }

        $this->addPropertyMeta($document, 'og:type', 'website');
    }

    private function addArticleMetadata(HtmlDocument $document, object $article): void
    {
        $titleOverride       = $this->getArticleParam($article, 'jtseo_title');
        $descriptionOverride = $this->getArticleParam($article, 'jtseo_description');
        $title               = $titleOverride !== '' ? $titleOverride : trim((string) ($article->title ?? ''));
        $description         = $descriptionOverride !== '' ? $descriptionOverride : $this->getArticleDescription($article);

        if ($title === '') {
            $title = trim((string) $document->getTitle());
        }

        if ($titleOverride !== '') {
            $document->setTitle($titleOverride);
        }

        // Keep Joomla's standard meta description aligned with the value
        // used for Open Graph/Twitter. In earlier builds this only ran for
        // explicit overrides, which left Joomla's native description in place
        // when the plugin generated the article description from intro/full text.
        if ($description !== '') {
            $this->setDocumentDescription($document, $description);
        }

        if ($title !== '') {
            $this->addPropertyMeta($document, 'og:title', $title);
            $this->addNameMeta($document, 'twitter:title', $title);
        }

        if ($description !== '') {
            $this->addPropertyMeta($document, 'og:description', $description);
            $this->addNameMeta($document, 'twitter:description', $description);
        }

        $this->addPropertyMeta($document, 'og:type', 'article');

        $section = trim((string) ($article->category_title ?? ''));

        if ($section !== '') {
            $this->addPropertyMeta($document, 'article:section', $section);
        }

        $publishedTime = $this->toAtomDate((string) ($article->publish_up ?? ''));
        $modifiedTime  = $this->toAtomDate((string) ($article->modified ?? ''));

        if ($publishedTime !== '') {
            $this->addPropertyMeta($document, 'article:published_time', $publishedTime);
        }

        if ($modifiedTime !== '') {
            $this->addPropertyMeta($document, 'article:modified_time', $modifiedTime);
        }
    }

    private function addArticleRobotsMetadata(HtmlDocument $document, object $article): void
    {
        $index  = $this->getArticleParam($article, 'jtseo_robots_index');
        $follow = $this->getArticleParam($article, 'jtseo_robots_follow');
        $robots = [];

        if (in_array($index, ['index', 'noindex'], true)) {
            $robots[] = $index;
        }

        if (in_array($follow, ['follow', 'nofollow'], true)) {
            $robots[] = $follow;
        }

        if ($robots !== []) {
            $document->setMetaData('robots', implode(', ', $robots));
        }
    }

    private function addDefaultImageMetadata(HtmlDocument $document, Registry $params): void
    {
        $image = trim((string) $params->get('default_og_image', ''));

        if ($image === '') {
            $languageTag = trim((string) $this->getApplication()->getLanguage()->getTag());
            $identity = $this->getSchemaIdentity($params, $languageTag);
            $image = (string) ($identity['logo'] ?? '');
        }

        if ($image === '') {
            return;
        }

        $this->addImageMetadata($document, $image);
    }

    private function addImageMetadata(HtmlDocument $document, string $image, string $alt = ''): void
    {
        $imageUrl = $this->toAbsoluteUrl($image);

        if ($imageUrl === '') {
            return;
        }

        $this->addPropertyMeta($document, 'og:image', $imageUrl);
        $this->addNameMeta($document, 'twitter:image', $imageUrl);

        $alt = trim($alt);

        if ($alt !== '') {
            $this->addPropertyMeta($document, 'og:image:alt', $alt);
            $this->addNameMeta($document, 'twitter:image:alt', $alt);
        }
    }

    private function addTwitterCardMetadata(HtmlDocument $document, Registry $params): void
    {
        $card = (string) $params->get('twitter_card', 'summary_large_image');

        if (!in_array($card, ['summary', 'summary_large_image'], true)) {
            $card = 'summary_large_image';
        }

        $this->addNameMeta($document, 'twitter:card', $card);
    }

    private function isHomePage(): bool
    {
        $app  = $this->getApplication();
        $menu = $app->getMenu();

        if (!$menu) {
            return false;
        }

        $active = $menu->getActive();

        if (!$active) {
            return false;
        }

        $language = $app->getLanguage()->getTag();
        $home     = $menu->getDefault($language) ?: $menu->getDefault('*');

        return $home && (int) $active->id === (int) $home->id;
    }

    private function isContentArticlePage(): bool
    {
        return $this->getCurrentArticleId() > 0;
    }

    /**
     * Resolve the current com_content article id from routed input, with an
     * active-menu fallback for SEF/menu routes where Joomla does not leave the
     * article id/view in Input by the time late rendering events run.
     */
    private function getCurrentArticleId(): int
    {
        $app = $this->getApplication();
        $input = $app->getInput();
        $option = trim((string) $input->getCmd('option', ''));
        $view = trim((string) $input->getCmd('view', ''));
        $id = (int) $input->getInt('id', 0);

        if ($option === 'com_content' && $view === 'article' && $id > 0) {
            return $id;
        }

        // Direct Single Article menu items can provide the authoritative id
        // even when a late event sees incomplete routed input.
        if ($option === '' || $option === 'com_content') {
            try {
                $menu = $app->getMenu();
                $active = $menu ? $menu->getActive() : null;
                $query = $active && is_array($active->query ?? null) ? $active->query : [];

                if (
                    (string) ($query['option'] ?? '') === 'com_content'
                    && (string) ($query['view'] ?? '') === 'article'
                    && (int) ($query['id'] ?? 0) > 0
                ) {
                    return (int) $query['id'];
                }
            } catch (\Throwable $exception) {
                // Fall through to the non-article result.
            }
        }

        return 0;
    }

    private function getCurrentArticle(): ?object
    {
        $id = $this->getCurrentArticleId();

        if ($id <= 0) {
            return null;
        }

        try {
            /** @var DatabaseInterface $db */
            $db = Factory::getContainer()->get(DatabaseInterface::class);

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
                    $db->quoteName('a.created'),
                    $db->quoteName('a.modified'),
                    $db->quoteName('a.publish_up'),
                    $db->quoteName('a.created_by'),
                    $db->quoteName('a.created_by_alias'),
                    $db->quoteName('a.language'),
                    $db->quoteName('u.name') . ' AS ' . $db->quoteName('author_name'),
                    $db->quoteName('c.title') . ' AS ' . $db->quoteName('category_title'),
                ])
                ->from($db->quoteName('#__content', 'a'))
                ->leftJoin($db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
                ->leftJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.created_by'))
                ->where($db->quoteName('a.id') . ' = :id')
                ->bind(':id', $id, ParameterType::INTEGER);

            $db->setQuery($query);

            $article = $db->loadObject();

            return is_object($article) ? $article : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function getArticleDescription(object $article): string
    {
        $description = trim((string) ($article->metadesc ?? ''));

        if ($description === '') {
            $description = $this->cleanText((string) ($article->introtext ?? ''));
        }

        if ($description === '') {
            $description = $this->cleanText((string) ($article->fulltext ?? ''));
        }

        return $this->truncateText($description, 180);
    }

    private function getArticleImageData(object $article): array
    {
        $overrideImage = $this->getArticleParam($article, 'jtseo_og_image');

        if ($overrideImage !== '') {
            return [
                'image' => $overrideImage,
                'alt'   => '',
            ];
        }

        $images = new Registry((string) ($article->images ?? ''));

        $image = trim((string) $images->get('image_intro', ''));
        $alt   = trim((string) $images->get('image_intro_alt', ''));

        if ($image === '') {
            $image = trim((string) $images->get('image_fulltext', ''));
            $alt   = trim((string) $images->get('image_fulltext_alt', ''));
        }

        return [
            'image' => $image,
            'alt'   => $alt,
        ];
    }

    private function getCanonicalUrl(?object $article = null): string
    {
        if ($article !== null) {
            $override = $this->getArticleParam($article, 'jtseo_canonical');

            if ($override !== '') {
                return $this->toAbsoluteUrl($override);
            }
        }

        $uri = Uri::getInstance();

        return $uri->toString(['scheme', 'host', 'port', 'path']);
    }

    private function getArticleParam(object $article, string $key): string
    {
        $attribs = new Registry((string) ($article->attribs ?? ''));

        return trim((string) $attribs->get($key, ''));
    }

    private function toAbsoluteUrl(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        $path = strtok($path, '#') ?: $path;
        $path = trim($path);

        if ($path === '' || $this->containsControlCharacter($path)) {
            return '';
        }

        if (preg_match('#^(?:javascript|data|vbscript|file):#i', $path) === 1) {
            return '';
        }

        if (strpos($path, '//') === 0) {
            return '';
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return filter_var($path, FILTER_VALIDATE_URL) ? $path : '';
        }

        return Uri::root(false) . ltrim($path, '/');
    }


    private function containsControlCharacter(string $value): bool
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

    private function cleanText(string $text): string
    {
        $filteredText = preg_filter('#<(script|style)[^>]*>.*?</\\1>#is', ' ', $text);
        $text = is_string($filteredText) ? $filteredText : $text;
        $text = str_ireplace(['</p>', '<br>', '<br/>', '<br />'], ' ', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $text = implode(' ', $parts);

        return trim($text);
    }

    private function truncateText(string $text, int $maxLength): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $maxLength) {
                return $text;
            }

            return rtrim(mb_substr($text, 0, $maxLength - 1, 'UTF-8')) . '…';
        }

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return rtrim(substr($text, 0, $maxLength - 3)) . '...';
    }

    private function toAtomDate(string $date): string
    {
        $date = trim($date);

        if ($date === '' || $date === '0000-00-00 00:00:00') {
            return '';
        }

        try {
            return Factory::getDate($date, 'UTC')->format(DATE_ATOM);
        } catch (\Throwable $exception) {
            return '';
        }
    }

    private function setDocumentDescription(HtmlDocument $document, string $description): void
    {
        $description = trim($description);

        if ($description === '') {
            return;
        }

        // Joomla can hold the description in more than one head data bucket,
        // depending on which core/plugin code wrote it first. Update both to
        // make article-level overrides win without producing a second tag.
        if (method_exists($document, 'setDescription')) {
            $document->setDescription($description);
        }

        $document->setMetaData('description', $description);

        if (method_exists($document, 'getHeadData') && method_exists($document, 'setHeadData')) {
            $headData = $document->getHeadData();

            if (isset($headData['description'])) {
                $headData['description'] = $description;
            }

            if (isset($headData['metaTags']['standard']['description'])) {
                $headData['metaTags']['standard']['description'] = $description;
            }

            $document->setHeadData($headData);
        }
    }

    private function replaceCanonicalLink(HtmlDocument $document, string $canonicalUrl): void
    {
        $canonicalUrl = trim($canonicalUrl);

        if ($canonicalUrl === '') {
            return;
        }

        $this->removeCanonicalLinks($document);
        $document->addHeadLink($canonicalUrl, 'canonical');
    }

    private function removeCanonicalLinks(HtmlDocument $document): void
    {
        if (!method_exists($document, 'getHeadData') || !method_exists($document, 'setHeadData')) {
            return;
        }

        $headData = $document->getHeadData();

        if (empty($headData['links']) || !is_array($headData['links'])) {
            return;
        }

        foreach ($headData['links'] as $href => $attributes) {
            if (!is_array($attributes)) {
                continue;
            }

            $relation = strtolower((string) ($attributes['relation'] ?? $attributes['rel'] ?? ''));
            $relType  = strtolower((string) ($attributes['relType'] ?? 'rel'));

            if ($relation === 'canonical' || ($relType === 'canonical')) {
                unset($headData['links'][$href]);
            }
        }

        $document->setHeadData($headData);
    }

    private function setGenerator(HtmlDocument $document, string $value): void
    {
        if (method_exists($document, 'setGenerator')) {
            $document->setGenerator($value);

            return;
        }

        $document->setMetaData('generator', $value);
    }

    private function addPropertyMeta(HtmlDocument $document, string $property, string $content): void
    {
        $content = trim($content);

        if ($content === '') {
            return;
        }

        $document->addCustomTag(
            '<meta property="' . $this->escape($property) . '" content="' . $this->escape($content) . '">'
        );
    }

    private function addNameMeta(HtmlDocument $document, string $name, string $content): void
    {
        $content = trim($content);

        if ($content === '') {
            return;
        }

        $document->addCustomTag(
            '<meta name="' . $this->escape($name) . '" content="' . $this->escape($content) . '">'
        );
    }

    private function getEventArgument(Event $event, string $name, int $index): mixed
    {
        if (isset($event[$name])) {
            return $event[$name];
        }

        $arguments = $event->getArguments();

        return $arguments[$index] ?? null;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
