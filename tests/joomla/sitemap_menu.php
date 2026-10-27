<?php
/** Run only on a disposable installed Joomla test site; fixtures are removed afterwards. */
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
require __DIR__ . '/bootstrap.php';

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Menu;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use JoomTheme\Component\Jtseo\Administrator\Helper\SitemapHelper;

$site = $container->get(SiteApplication::class);
Factory::$application = $site;
$site->loadIdentity(new User(0));
$site->set('sef', 1);
$site->set('sef_rewrite', 0);
$checkCount = 0;
$check = static function (bool $ok, string $message) use (&$checkCount): void {
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo 'PASS: ' . $message . PHP_EOL;
    $checkCount++;
};
$fixtureIds = [];
$articleIds = [];
$originalParams = null;
$hadTurkish = (bool) $db->setQuery("SELECT lang_id FROM #__languages WHERE lang_code='tr-TR'")->loadResult();
try {
    $component = (int) $db->setQuery("SELECT extension_id FROM #__extensions WHERE element='com_content'")->loadResult();
    $add = static function (string $alias, array $overrides = []) use ($db, $component, &$fixtureIds): int {
        $row = new Menu($db);
        $data = array_replace([
            'menutype' => 'mainmenu', 'title' => $alias, 'alias' => $alias,
            'link' => 'index.php?option=com_content&view=category&layout=blog&id=2',
            'type' => 'component', 'published' => 1, 'parent_id' => 1,
            'component_id' => $component, 'access' => 1, 'home' => 0,
            'language' => '*', 'client_id' => 0, 'params' => '{}',
        ], $overrides);
        $row->setLocation($data['parent_id'], 'last-child');
        if (!$row->bind($data) || !$row->check() || !$row->store()) {
            throw new RuntimeException('Could not insert menu fixture ' . $alias);
        }
        $row->rebuildPath();
        $fixtureIds[] = (int) $row->id;
        return (int) $row->id;
    };
    $article = static function (string $alias, array $overrides = []) use ($db, &$articleIds): int {
        $row = (object) array_replace([
            'title' => $alias, 'alias' => $alias, 'introtext' => '', 'fulltext' => '',
            'state' => 1, 'catid' => 2, 'created' => '2026-01-01 00:00:00',
            'modified' => '2026-01-02 00:00:00', 'images' => '{}', 'urls' => '{}',
            'attribs' => '{}', 'metadesc' => '', 'metadata' => '{}', 'access' => 1, 'language' => '*',
        ], $overrides);
        $db->insertObject('#__content', $row, 'id');
        $articleIds[] = (int) $row->id;
        return (int) $row->id;
    };
    $article('jt-unlinked-article');
    foreach (['jt-linked-article' => [], 'jt-noindex-article' => ['attribs' => '{"jtseo_robots_index":"noindex"}'],
        'jt-private-article' => ['access' => 2], 'jt-off-article' => ['state' => 0]] as $alias => $overrides) {
        $id = $article($alias, $overrides);
        $add($alias, ['link' => 'index.php?option=com_content&view=article&id=' . $id]);
    }
    $add('jt-public');
    $add('jt-hidden', ['params' => '{"menu_show":0}']);
    $add('jt-unpublished', ['published' => 0]);
    $add('jt-private', ['access' => 2]);
    $add('jt-future', ['publish_up' => '2099-01-01 00:00:00']);
    $add('jt-expired', ['publish_down' => '2000-01-01 00:00:00']);
    $add('jt-noindex', ['params' => '{"robots":"noindex, follow"}']);
    $add('jt-excluded');
    $add('jt-external', ['type' => 'url', 'link' => 'https://example.invalid/external']);
    $add('jt-protocol-relative', ['type' => 'url', 'link' => '//example.invalid/external']);
    $add('jt-mail', ['type' => 'url', 'link' => 'mailto:hello@example.invalid']);
    $add('jt-anchor', ['type' => 'url', 'link' => '#section']);
    $add('jt-internal', ['type' => 'url', 'link' => '/jt-internal-page']);
    $add('jt-heading', ['type' => 'heading', 'link' => '']);
    $add('jt-separator', ['type' => 'separator', 'link' => '']);
    $add('jt-alias', ['type' => 'alias', 'params' => '{"aliasoptions":101}', 'link' => 'index.php?Itemid=101']);
    $parent = $add('jt-private-parent', ['access' => 2]);
    $add('jt-private-child', ['parent_id' => $parent]);
    $parent = $add('jt-future-parent', ['publish_up' => '2099-01-01 00:00:00']);
    $add('jt-future-child', ['parent_id' => $parent]);
    $parent = $add('jt-off-parent', ['published' => 0]);
    $add('jt-off-child', ['parent_id' => $parent]);
    $parent = $add('jt-noindex-parent', ['params' => '{"robots":"noindex"}']);
    $add('jt-noindex-child', ['parent_id' => $parent]);
    $add('jt-english', ['language' => 'en-GB']);
    $add('jt-turkish', ['language' => 'tr-TR']);
    // A second content language is enough to exercise language buckets even
    // without its optional administrator translation pack installed.
    if (!$hadTurkish) {
        $db->setQuery("INSERT INTO #__languages (lang_code,title,title_native,sef,image,description,metakey,metadesc,sitename,published,access,ordering) VALUES ('tr-TR','Turkish','Türkçe','tr','tr','','','','',1,1,2) ")->execute();
    }
    $site->getMenu()->load();
    $base = [
        'sitemap_include_homepage' => 0, 'sitemap_include_articles' => 0,
        'sitemap_multilingual_index' => 0, 'sitemap_exclude_fallback_urls' => 0,
    ];
    $build = static function (array $settings = [], ?string $path = null) use ($base): array {
        $params = new Registry(array_replace($base, $settings));
        $result = $path === null ? SitemapHelper::buildXml($params) : SitemapHelper::buildForPath($path, $params);
        $xml = simplexml_load_string($result['xml']);
        if ($xml === false) {
            throw new RuntimeException('Invalid sitemap XML');
        }
        return array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
    };
    $check($build() === [], 'unset menu option preserves empty articles-off sitemap');
    $enabled = ['sitemap_include_menu_items' => 1];
    $urls = $build($enabled);
    $has = static fn (array $urls, string $needle): bool => count(array_filter($urls, static fn ($url) => str_contains($url, $needle))) > 0;
    $check($has($urls, 'jt-public'), 'menu items work with articles disabled through Joomla routing');
    $check($has($urls, 'jt-linked-article') && !$has($urls, 'jt-unlinked-article'), 'menu-only mode includes linked articles and omits unlinked articles');
    $check($has($urls, 'jt-hidden'), 'public menu items hidden from navigation are still included');
    foreach (['jt-unpublished', 'jt-private', 'jt-future', 'jt-expired', 'jt-noindex', 'jt-external', 'jt-protocol-relative', 'jt-mail', 'jt-anchor', 'jt-heading', 'jt-separator', 'jt-alias', 'jt-off-child', 'jt-private-child', 'jt-future-child', 'jt-noindex-child', 'jt-noindex-article', 'jt-private-article', 'jt-off-article'] as $excluded) {
        $check(!$has($urls, $excluded), $excluded . ' is excluded');
    }
    $check($has($urls, 'jt-internal-page'), 'internal URL-type menu links are included');
    $check(count($urls) === count(array_unique($urls)), 'menu destinations are deduplicated');
    $filtered = $build($enabled + ['sitemap_excluded_urls' => '/*jt-excluded']);
    $check(!$has($filtered, 'jt-excluded') && $has($filtered, 'jt-public'), 'existing exclusion rules apply to menus');
    $overridden = $build($enabled + ['sitemap_excluded_urls' => '/*jt-excluded', 'sitemap_included_urls' => '/*jt-excluded']);
    $check($has($overridden, 'jt-excluded'), 'explicit include rules retain priority over exclusions');
    $check($has($build($enabled + ['sitemap_included_urls' => '/*jt-noindex']), 'jt-noindex'), 'explicit include rules retain priority over menu noindex');
    $check($has($build($enabled + ['sitemap_exclude_noindex' => 0]), 'jt-noindex'), 'noindex inclusion follows existing switch');
    $both = $build($enabled + ['sitemap_include_articles' => 1]);
    $check($has($both, 'jt-unlinked-article'), 'article source still enumerates articles when enabled');
    $check(count(array_filter($both, static fn ($url) => str_contains($url, 'jt-linked-article'))) === 1, 'article and menu sources share deduplication');
    $home = $build($enabled + ['sitemap_include_homepage' => 1]);
    $check(count($home) === count($urls) + 1, 'homepage remains separately controlled');
    $lang = $enabled + ['sitemap_multilingual_index' => 1];
    $en = $build($lang, 'sitemap.en-GB.1.xml');
    $tr = $build($lang, 'sitemap.tr-TR.1.xml');
    $check($has($en, 'jt-english') && !$has($en, 'jt-turkish'), 'English sitemap excludes Turkish menu items');
    $check($has($tr, 'jt-turkish') && !$has($tr, 'jt-english'), 'Turkish sitemap excludes English menu items');
    $check($has($en, 'jt-public') && $has($tr, 'jt-public'), 'all-language menus belong to both language sitemaps');
    $index = $build($lang);
    $check($has($index, 'sitemap.en-GB.1.xml') && $has($index, 'sitemap.tr-TR.1.xml'), 'multilingual sitemap index stays intact');
    $xml = simplexml_load_file(dirname(__DIR__, 2) . '/extensions/com_jtseo/admin/config.xml');
    $field = $xml->xpath('//field[@name="sitemap_include_menu_items"]')[0];
    $check((string) $field['default'] === '0', 'Joomla option defaults to off');
    $form = new \Joomla\CMS\Form\Form('com_jtseo.config');
    $check($form->loadFile(JPATH_ADMINISTRATOR . '/components/com_jtseo/config.xml', true, '/config/fieldset') && $form->getField('sitemap_include_menu_items') !== false, 'installed option loads as a Joomla form field');
    if (getenv('JTSEO_TEST_ORIGIN')) {
        $originalParams = (string) $db->setQuery("SELECT params FROM #__extensions WHERE element='com_jtseo'")->loadResult();
        $httpParams = new Registry($originalParams);
        foreach (array_replace($base, $enabled, ['enable_sitemap' => 1]) as $key => $value) {
            $httpParams->set($key, $value);
        }
        $db->setQuery("UPDATE #__extensions SET params=" . $db->quote((string) $httpParams) . " WHERE element='com_jtseo'")->execute();
        $response = file_get_contents(rtrim(getenv('JTSEO_TEST_ORIGIN'), '/') . '/index.php?option=com_jtseo&view=sitemap&format=xml', false,
            stream_context_create(['http' => ['timeout' => 15]]));
        $httpXml = simplexml_load_string($response);
        $check($httpXml !== false && $httpXml->getName() === 'urlset', 'HTTP sitemap endpoint returns valid urlset XML');
        $check(str_contains($response, 'jt-public') && !str_contains($response, 'jt-unlinked-article'), 'HTTP endpoint serves menu-only sitemap from installed package');
    }
    echo "Completed $checkCount sitemap checks on Joomla " . (new \Joomla\CMS\Version())->getShortVersion() . ', PHP ' . PHP_VERSION . ".\n";
} finally {
    if ($originalParams !== null) {
        $db->setQuery("UPDATE #__extensions SET params=" . $db->quote($originalParams) . " WHERE element='com_jtseo'")->execute();
    }
    if ($articleIds !== []) {
        $db->setQuery('DELETE FROM #__content WHERE id IN (' . implode(',', $articleIds) . ')')->execute();
    }
    if ($fixtureIds !== []) {
        $db->setQuery('DELETE FROM #__menu WHERE id IN (' . implode(',', $fixtureIds) . ')')->execute();
        (new Menu($db))->rebuild();
    }
    if (!$hadTurkish) {
        $db->setQuery("DELETE FROM #__languages WHERE lang_code='tr-TR'")->execute();
    }
}
