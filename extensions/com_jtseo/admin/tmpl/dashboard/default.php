<?php

/**
 * @package     JT SEO Control Center
 * @subpackage  Administrator
 *
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

$wa = $this->document->getWebAssetManager();
try {
    $wa->getRegistry()->addExtensionRegistryFile('com_jtseo');
} catch (\Throwable $exception) {
    // Registry may already be loaded.
}
$wa->useStyle('com_jtseo.admin');
try {
    $wa->useScript('bootstrap.collapse');
} catch (\Throwable $exception) {
    // Bootstrap collapse may already be available in the administrator template.
}
$wa->useScript('com_jtseo.dashboard');
$this->document->addScriptOptions('com_jtseo.dashboard', [
    'auditFilterCount' => Text::_('COM_JTSEO_AUDIT_FILTER_COUNT'),
    'bulkFilterCount'  => Text::_('COM_JTSEO_BULK_FILTER_COUNT'),
]);

/** @var \JoomTheme\Component\Jtseo\Administrator\View\Dashboard\HtmlView $this */
$params = $this->params;

$normaliseSupportUrl = static function (string $url): string {
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    return preg_match('#^https?://#i', $url) ? $url : '';
};

$supportCardEnabled = (int) $params->get('show_support_card', 1) === 1;
$supportJedUrl = $normaliseSupportUrl((string) $params->get('jed_review_url', 'https://extensions.joomla.org/profile/profile/details/147240/'));
$supportGithubUrl = $normaliseSupportUrl((string) $params->get('github_url', 'https://github.com/joomtheme/JT-SEO-Control-Center'));
$supportDevelopmentUrl = $normaliseSupportUrl((string) $params->get('support_development_url', 'https://joomtheme.com/support/jt-seo-control-center'));

$homepageTitle       = (string) $params->get('homepage_title', '');
$homepageDescription = (string) $params->get('homepage_description', '');
$defaultOgImage      = (string) $params->get('default_og_image', '');
$twitterCard         = (string) $params->get('twitter_card', 'summary_large_image');
$canonicalEnabled    = (int) $params->get('enable_canonical', 1) === 1;
$articleMetadata    = (int) $params->get('enable_article_metadata', 1) === 1;
$articleImages      = (int) $params->get('enable_article_images', 1) === 1;
$articleFields      = (int) $params->get('enable_article_fields', 1) === 1;
$sitemapEnabled    = (int) $params->get('enable_sitemap', 1) === 1;
$sitemapUrl        = rtrim(Uri::root(), '/') . '/index.php?option=com_jtseo&view=sitemap&format=xml';
$sitemapShortUrl   = rtrim(Uri::root(), '/') . '/sitemap.xml';
$physicalSitemap   = (array) $this->physicalSitemapStatus;
$physicalEnabled   = (int) $params->get('sitemap_write_physical', 1) === 1;
$audit            = (array) $this->audit;
$auditStats       = (array) ($audit['stats'] ?? []);
$auditIssues      = (array) ($audit['issues'] ?? []);
$auditScores      = (array) ($audit['scores'] ?? []);
$bulkArticles     = (array) $this->bulkArticles;
$auditIssueCount  = (int) ($audit['issue_count'] ?? 0);
$redirectSummary = (array) $this->redirectSummary;
$redirects = (array) $this->redirects;
$notFoundLogs = (array) $this->notFoundLogs;
$redirectEnabled = (int) $params->get('enable_redirects', 1) === 1;
$log404Enabled = (int) $params->get('enable_404_logging', 1) === 1;
$redirectCardStatus = ($redirectEnabled && $log404Enabled)
    ? 'COM_JTSEO_STATUS_ACTIVE'
    : ((!$redirectEnabled && !$log404Enabled) ? 'COM_JTSEO_STATUS_DISABLED' : 'COM_JTSEO_STATUS_NEEDS_ATTENTION');
$redirectCardClass = ($redirectEnabled && $log404Enabled)
    ? 'success'
    : ((!$redirectEnabled && !$log404Enabled) ? 'secondary' : 'warning text-dark');
$healthStatus = (array) $this->healthStatus;
$healthItems = (array) ($healthStatus['items'] ?? []);
$healthWarnings = (int) ($healthStatus['warnings'] ?? 0);
$healthOkCount = (int) ($healthStatus['ok_count'] ?? 0);
$healthTotal = (int) ($healthStatus['total'] ?? count($healthItems));
$structuredData = (array) $this->structuredData;
$structuredMappings = (array) ($structuredData['mappings'] ?? []);
$structuredPreviewJson = (string) ($structuredData['preview_json'] ?? '');
$structuredSampleArticle = (array) ($structuredData['sample_article'] ?? []);

$items = [
    [
        'title'       => 'COM_JTSEO_CARD_METADATA_TITLE',
        'description' => 'COM_JTSEO_CARD_METADATA_DESC',
        'status'      => $articleMetadata ? 'COM_JTSEO_STATUS_ACTIVE' : 'COM_JTSEO_STATUS_DISABLED',
        'class'       => $articleMetadata ? 'success' : 'secondary',
    ],
    [
        'title'       => 'COM_JTSEO_CARD_CANONICAL_TITLE',
        'description' => 'COM_JTSEO_CARD_CANONICAL_DESC',
        'status'      => $canonicalEnabled ? 'COM_JTSEO_STATUS_ACTIVE' : 'COM_JTSEO_STATUS_DISABLED',
        'class'       => $canonicalEnabled ? 'success' : 'secondary',
    ],
    [
        'title'       => 'COM_JTSEO_CARD_SITEMAP_TITLE',
        'description' => 'COM_JTSEO_CARD_SITEMAP_DESC',
        'status'      => $sitemapEnabled ? 'COM_JTSEO_STATUS_ACTIVE' : 'COM_JTSEO_STATUS_DISABLED',
        'class'       => $sitemapEnabled ? 'success' : 'secondary',
    ],
    [
        'title'       => 'COM_JTSEO_CARD_SCHEMA_TITLE',
        'description' => 'COM_JTSEO_CARD_SCHEMA_DESC',
        'status'      => 'COM_JTSEO_STATUS_ACTIVE',
        'class'       => 'success',
    ],
    [
        'title'       => 'COM_JTSEO_CARD_REDIRECT_TITLE',
        'description' => 'COM_JTSEO_CARD_REDIRECT_DESC',
        'status'      => $redirectCardStatus,
        'class'       => $redirectCardClass,
    ],
    [
        'title'       => 'COM_JTSEO_CARD_AUDIT_TITLE',
        'description' => 'COM_JTSEO_CARD_AUDIT_DESC',
        'status'      => 'COM_JTSEO_STATUS_ACTIVE',
        'class'       => $auditIssueCount > 0 ? 'warning text-dark' : 'success',
    ],
];
$allowedTabs = ['overview', 'audit', 'bulk', 'structured', 'redirects'];
$input = Factory::getApplication()->input;
$activeTab = (string) $input->getCmd('jtseo_tab', 'overview');

if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'overview';
}

$tabBaseUrl = 'index.php?option=com_jtseo&view=dashboard';
$tabDefinitions = [
    'overview'   => 'COM_JTSEO_TAB_OVERVIEW',
    'audit'      => 'COM_JTSEO_TAB_AUDIT',
    'bulk'       => 'COM_JTSEO_TAB_BULK',
    'structured' => 'COM_JTSEO_TAB_STRUCTURED',
    'redirects'  => 'COM_JTSEO_TAB_REDIRECTS',
];

$auditFilterSearch = trim((string) $input->getString('audit_search', ''));
$auditFilterSeverity = (string) $input->getCmd('audit_severity', '');
$auditFilterType = (string) $input->getCmd('audit_type', '');
$allowedAuditSeverities = ['', 'danger', 'warning', 'info'];
$allowedAuditTypes = ['', 'description', 'image', 'robots', 'alias'];

if (!in_array($auditFilterSeverity, $allowedAuditSeverities, true)) {
    $auditFilterSeverity = '';
}

if (!in_array($auditFilterType, $allowedAuditTypes, true)) {
    $auditFilterType = '';
}

$containsText = static function (string $haystack, string $needle): bool {
    if ($needle === '') {
        return true;
    }

    if (function_exists('mb_stripos')) {
        return mb_stripos($haystack, $needle, 0, 'UTF-8') !== false;
    }

    return stripos($haystack, $needle) !== false;
};

$filteredAuditIssues = [];

foreach ($auditIssues as $issue) {
    $severity = (string) ($issue['severity'] ?? 'secondary');
    $type = (string) ($issue['type'] ?? '');

    if ($auditFilterSeverity !== '' && $severity !== $auditFilterSeverity) {
        continue;
    }

    if ($auditFilterType !== '' && $type !== $auditFilterType) {
        continue;
    }

    $message = Text::_((string) ($issue['message_key'] ?? ''));
    $suggestion = Text::_((string) ($issue['suggestion_key'] ?? ''));
    $searchText = trim((string) ($issue['title'] ?? '') . ' ' . $message . ' ' . $suggestion . ' ' . (string) ($issue['detail'] ?? ''));

    if (!$containsText($searchText, $auditFilterSearch)) {
        continue;
    }

    $filteredAuditIssues[] = $issue;
}

$auditVisibleCount = count($filteredAuditIssues);
$bulkFilterSearch = trim((string) $input->getString('bulk_search', ''));
$bulkFilterStatus = (string) $input->getCmd('bulk_filter', 'needs-attention');
$allowedBulkStatuses = ['needs-attention', 'all', 'missing-description', 'low-score', 'noindex', 'excellent'];

if (!in_array($bulkFilterStatus, $allowedBulkStatuses, true)) {
    $bulkFilterStatus = 'needs-attention';
}

$bulkLimit = (int) $input->getUint('bulk_limit', 10);
$allowedBulkLimits = [10, 25, 50];

if (!in_array($bulkLimit, $allowedBulkLimits, true)) {
    $bulkLimit = 10;
}

$bulkPage = max(1, (int) $input->getUint('bulk_page', 1));
$filteredBulkArticles = [];

foreach ($bulkArticles as $bulkArticle) {
    $bulkScoreFilter = max(0, min(100, (int) ($bulkArticle['score'] ?? 0)));
    $bulkSearchTextFilter = trim((string) ($bulkArticle['title'] ?? '') . ' ' . (string) ($bulkArticle['category'] ?? '') . ' ' . (string) ($bulkArticle['alias'] ?? '') . ' ' . (string) ($bulkArticle['seo_title'] ?? '') . ' ' . (string) ($bulkArticle['seo_description'] ?? ''));
    $bulkMissingDescriptionFilter = empty($bulkArticle['explicit_description']);
    $bulkLowScoreFilter = $bulkScoreFilter < 70;
    $bulkNoindexFilter = (string) ($bulkArticle['robots_index'] ?? '') === 'noindex';
    $bulkExcellentFilter = (string) ($bulkArticle['bucket'] ?? '') === 'excellent' && $bulkScoreFilter >= 100;
    $bulkNeedsAttentionFilter = !$bulkExcellentFilter;

    if (!$containsText($bulkSearchTextFilter, $bulkFilterSearch)) {
        continue;
    }

    if ($bulkFilterStatus === 'needs-attention' && !$bulkNeedsAttentionFilter) {
        continue;
    }

    if ($bulkFilterStatus === 'missing-description' && !$bulkMissingDescriptionFilter) {
        continue;
    }

    if ($bulkFilterStatus === 'low-score' && !$bulkLowScoreFilter) {
        continue;
    }

    if ($bulkFilterStatus === 'noindex' && !$bulkNoindexFilter) {
        continue;
    }

    if ($bulkFilterStatus === 'excellent' && !$bulkExcellentFilter) {
        continue;
    }

    $filteredBulkArticles[] = $bulkArticle;
}

$bulkFilteredTotal = count($filteredBulkArticles);
$bulkPageCount = max(1, (int) ceil($bulkFilteredTotal / $bulkLimit));
$bulkPage = min($bulkPage, $bulkPageCount);
$bulkOffset = ($bulkPage - 1) * $bulkLimit;
$bulkPagedArticles = array_slice($filteredBulkArticles, $bulkOffset, $bulkLimit);
$bulkVisibleCount = count($bulkPagedArticles);
$bulkShownStart = $bulkFilteredTotal > 0 ? $bulkOffset + 1 : 0;
$bulkShownEnd = $bulkFilteredTotal > 0 ? $bulkOffset + $bulkVisibleCount : 0;
$bulkPageUrl = static function (int $page) use ($bulkFilterSearch, $bulkFilterStatus, $bulkLimit): string {
    $query = [
        'option'      => 'com_jtseo',
        'view'        => 'dashboard',
        'jtseo_tab'   => 'bulk',
        'bulk_filter' => $bulkFilterStatus,
        'bulk_limit'  => $bulkLimit,
        'bulk_page'   => max(1, $page),
    ];

    if ($bulkFilterSearch !== '') {
        $query['bulk_search'] = $bulkFilterSearch;
    }

    return Route::_('index.php?' . http_build_query($query, '', '&')) . '#jtseo-bulk-meta';
};
?>


<div class="com-jtseo-dashboard">
    <div class="alert alert-info">
        <h2 class="h4"><?php echo Text::_('COM_JTSEO_WELCOME_TITLE'); ?></h2>
        <p class="mb-0"><?php echo Text::_('COM_JTSEO_WELCOME_DESC'); ?></p>
    </div>

    <?php echo HTMLHelper::_('uitab.startTabSet', 'jtseoDashboardTabs', ['active' => $activeTab]); ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'jtseoDashboardTabs', 'overview', Text::_('COM_JTSEO_TAB_OVERVIEW')); ?>
<div class="card mb-4" id="jtseo-health-check">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h3 class="h5 mb-1"><?php echo Text::_('COM_JTSEO_HEALTH_TITLE'); ?></h3>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JTSEO_HEALTH_DESC'); ?></p>
                </div>
                <div class="text-lg-end">
                    <?php if ($healthWarnings === 0) : ?>
                        <span class="badge bg-success text-white"><?php echo Text::_('COM_JTSEO_HEALTH_ALL_OK'); ?></span>
                    <?php else : ?>
                        <span class="badge bg-warning text-dark"><?php echo Text::sprintf('COM_JTSEO_HEALTH_WARNINGS', $healthWarnings); ?></span>
                    <?php endif; ?>
                    <div class="small text-muted mt-1">
                        <?php echo Text::sprintf('COM_JTSEO_HEALTH_SUMMARY', $healthOkCount, $healthTotal); ?>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <?php foreach ($healthItems as $healthItem) : ?>
                    <?php
                    $healthOk = !empty($healthItem['ok']);
                    $healthSeverity = (string) ($healthItem['severity'] ?? ($healthOk ? 'success' : 'warning'));
                    $healthBadgeClass = $healthSeverity === 'warning' ? 'bg-warning text-dark' : 'bg-' . $healthSeverity . ' text-white';
                    $healthBadgeKey = (string) ($healthItem['badge_key'] ?? ($healthOk ? 'COM_JTSEO_HEALTH_STATUS_OK' : 'COM_JTSEO_STATUS_NEEDS_ATTENTION'));
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="jtseo-health-item border rounded p-3 h-100 <?php echo $healthOk ? 'is-ok' : 'is-warning'; ?>">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <strong><?php echo Text::_((string) ($healthItem['label_key'] ?? '')); ?></strong>
                                <span class="badge <?php echo htmlspecialchars($healthBadgeClass, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo Text::_($healthBadgeKey); ?>
                                </span>
                            </div>
                            <div class="small text-muted">
                                <?php echo Text::_((string) ($healthItem['message_key'] ?? '')); ?>
                                <?php if (!empty($healthItem['value'])) : ?>
                                    <span class="d-block mt-1"><?php echo Text::sprintf('COM_JTSEO_HEALTH_VALUE', htmlspecialchars((string) $healthItem['value'], ENT_QUOTES, 'UTF-8')); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($healthWarnings > 0) : ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <?php echo Text::_('COM_JTSEO_HEALTH_WARNING_NOTE'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4" id="jtseo-global-settings">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h3 class="h5 mb-2"><?php echo Text::_('COM_JTSEO_GLOBAL_SETTINGS_TITLE'); ?></h3>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JTSEO_GLOBAL_SETTINGS_DESC'); ?></p>
                </div>
                <div>
                    <a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_config&view=component&component=com_jtseo&path='); ?>">
                        <?php echo Text::_('COM_JTSEO_OPEN_OPTIONS'); ?>
                    </a>
                </div>
            </div>

            <dl class="row mt-4 mb-0">
                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_HOMEPAGE_TITLE'); ?></dt>
                <dd class="col-sm-9"><?php echo $homepageTitle !== '' ? htmlspecialchars($homepageTitle, ENT_QUOTES, 'UTF-8') : Text::_('COM_JTSEO_VALUE_NOT_SET'); ?></dd>

                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_HOMEPAGE_DESCRIPTION'); ?></dt>
                <dd class="col-sm-9"><?php echo $homepageDescription !== '' ? htmlspecialchars($homepageDescription, ENT_QUOTES, 'UTF-8') : Text::_('COM_JTSEO_VALUE_NOT_SET'); ?></dd>

                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_DEFAULT_OG_IMAGE'); ?></dt>
                <dd class="col-sm-9"><?php echo $defaultOgImage !== '' ? htmlspecialchars($defaultOgImage, ENT_QUOTES, 'UTF-8') : Text::_('COM_JTSEO_VALUE_NOT_SET'); ?></dd>

                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_TWITTER_CARD'); ?></dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($twitterCard, ENT_QUOTES, 'UTF-8'); ?></dd>

                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_ARTICLE_METADATA'); ?></dt>
                <dd class="col-sm-9"><?php echo $articleMetadata ? Text::_('COM_JTSEO_STATUS_ACTIVE') : Text::_('COM_JTSEO_STATUS_DISABLED'); ?></dd>

                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_ARTICLE_IMAGES'); ?></dt>
                <dd class="col-sm-9"><?php echo $articleImages ? Text::_('COM_JTSEO_STATUS_ACTIVE') : Text::_('COM_JTSEO_STATUS_DISABLED'); ?></dd>

                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_ARTICLE_FIELDS'); ?></dt>
                <dd class="col-sm-9"><?php echo $articleFields ? Text::_('COM_JTSEO_STATUS_ACTIVE') : Text::_('COM_JTSEO_STATUS_DISABLED'); ?></dd>

                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_SITEMAP'); ?></dt>
                <dd class="col-sm-9"><?php echo $sitemapEnabled ? Text::_('COM_JTSEO_STATUS_ACTIVE') : Text::_('COM_JTSEO_STATUS_DISABLED'); ?></dd>

                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_SITEMAP_URL'); ?></dt>
                <dd class="col-sm-9"><a href="<?php echo htmlspecialchars($sitemapUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($sitemapUrl, ENT_QUOTES, 'UTF-8'); ?></a></dd>

                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_SITEMAP_SHORT_URL'); ?></dt>
                <dd class="col-sm-9">
                    <a href="<?php echo htmlspecialchars($sitemapShortUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($sitemapShortUrl, ENT_QUOTES, 'UTF-8'); ?></a>
                </dd>

                <dt class="col-sm-3"><?php echo Text::_('COM_JTSEO_DASHBOARD_PHYSICAL_SITEMAP'); ?></dt>
                <dd class="col-sm-9">
                    <?php if (!$physicalEnabled) : ?>
                        <span class="badge bg-secondary"><?php echo Text::_('COM_JTSEO_STATUS_DISABLED'); ?></span>
                    <?php elseif (!empty($physicalSitemap['exists'])) : ?>
                        <span class="badge bg-success"><?php echo Text::_('COM_JTSEO_STATUS_ACTIVE'); ?></span>
                        <span class="text-muted ms-2">
                            <?php echo Text::sprintf('COM_JTSEO_DASHBOARD_PHYSICAL_SITEMAP_STATUS', (int) ($physicalSitemap['size'] ?? 0)); ?>
                        </span>
                    <?php elseif (!empty($physicalSitemap['directory_writable'])) : ?>
                        <span class="badge bg-warning text-dark"><?php echo Text::_('COM_JTSEO_STATUS_MISSING'); ?></span>
                    <?php else : ?>
                        <span class="badge bg-danger"><?php echo Text::_('COM_JTSEO_STATUS_NOT_WRITABLE'); ?></span>
                    <?php endif; ?>
                    <div class="mt-2">
                        <form action="<?php echo Route::_('index.php?option=com_jtseo&task=sitemap.generate'); ?>" method="post" class="d-inline-block">
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <?php echo Text::_('COM_JTSEO_GENERATE_SITEMAP_BUTTON'); ?>
                            </button>
                            <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
                        </form>
                    </div>
                    <div class="form-text">
                        <?php echo Text::_('COM_JTSEO_DASHBOARD_PHYSICAL_SITEMAP_HINT'); ?>
                    </div>
                </dd>
            </dl>
        </div>
    </div>

    <div class="row g-3" id="jtseo-feature-cards">
        <?php foreach ($items as $item) : ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="h5 card-title"><?php echo Text::_($item['title']); ?></h3>
                        <p class="card-text"><?php echo Text::_($item['description']); ?></p>
                        <span class="badge bg-<?php echo htmlspecialchars($item['class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo Text::_($item['status']); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


    <div class="card mt-4" id="jtseo-next-steps">
        <div class="card-body">
            <h3 class="h5"><?php echo Text::_('COM_JTSEO_NEXT_STEPS_TITLE'); ?></h3>
            <ol class="mb-0">
                <li><?php echo Text::_('COM_JTSEO_NEXT_STEP_HOME_TEST'); ?></li>
                <li><?php echo Text::_('COM_JTSEO_NEXT_STEP_ARTICLE_FIELDS'); ?></li>
                <li><?php echo Text::_('COM_JTSEO_NEXT_STEP_SITEMAP'); ?></li>
                <li><?php echo Text::_('COM_JTSEO_NEXT_STEP_SCHEMA'); ?></li>
            </ol>
        </div>
    </div>

    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'jtseoDashboardTabs', 'audit', Text::_('COM_JTSEO_TAB_AUDIT')); ?>
<div class="card mt-4" id="jtseo-audit-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h3 class="h5 mb-1"><?php echo Text::_('COM_JTSEO_AUDIT_TITLE'); ?></h3>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JTSEO_AUDIT_DESC'); ?></p>
                </div>
                <div>
                    <?php if ($auditIssueCount > 0) : ?>
                        <span class="badge bg-warning text-dark"><?php echo Text::sprintf('COM_JTSEO_AUDIT_ISSUES_FOUND', $auditIssueCount); ?></span>
                    <?php else : ?>
                        <span class="badge bg-success"><?php echo Text::_('COM_JTSEO_AUDIT_NO_ISSUES'); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_AUDIT_HEALTH_SCORE'); ?></div>
                        <div class="fs-3 fw-semibold"><?php echo (int) ($auditStats['health_score'] ?? 100); ?>%</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_AUDIT_TOTAL_ARTICLES'); ?></div>
                        <div class="fs-3 fw-semibold"><?php echo (int) ($auditStats['total_articles'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_AUDIT_GOOD_ARTICLES'); ?></div>
                        <div class="fs-3 fw-semibold"><?php echo (int) ($auditStats['good_articles'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_AUDIT_MISSING_DESCRIPTIONS'); ?></div>
                        <div class="fs-3 fw-semibold"><?php echo (int) ($auditStats['missing_explicit_description'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_AUDIT_NOINDEX_ARTICLES'); ?></div>
                        <div class="fs-3 fw-semibold"><?php echo (int) ($auditStats['noindex'] ?? 0); ?></div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="alert alert-light border mb-0">
                        <strong><?php echo (int) ($auditStats['short_description'] ?? 0); ?></strong>
                        <?php echo Text::_('COM_JTSEO_AUDIT_SHORT_DESCRIPTIONS'); ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-light border mb-0">
                        <strong><?php echo (int) ($auditStats['canonical_override'] ?? 0); ?></strong>
                        <?php echo Text::_('COM_JTSEO_AUDIT_CANONICAL_OVERRIDES'); ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-light border mb-0">
                        <strong><?php echo (int) ($auditStats['duplicate_alias'] ?? 0); ?></strong>
                        <?php echo Text::_('COM_JTSEO_AUDIT_DUPLICATE_ALIASES'); ?>
                    </div>
                </div>
            </div>

            <div class="card border mb-4" id="jtseo-article-scores">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                        <div>
                            <h4 class="h6 mb-1"><?php echo Text::_('COM_JTSEO_SCORE_TITLE'); ?></h4>
                            <p class="text-muted mb-0"><?php echo Text::_('COM_JTSEO_SCORE_DESC'); ?></p>
                        </div>
                        <div class="text-lg-end">
                            <span class="badge bg-primary text-white"><?php echo Text::sprintf('COM_JTSEO_SCORE_AVERAGE', (int) ($auditStats['average_article_score'] ?? 100)); ?></span>
                            <div class="small text-muted mt-1">
                                <?php echo Text::sprintf('COM_JTSEO_SCORE_DISTRIBUTION', (int) ($auditStats['score_excellent'] ?? 0), (int) ($auditStats['score_good'] ?? 0), (int) ($auditStats['score_fair'] ?? 0), (int) ($auditStats['score_weak'] ?? 0)); ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($auditScores)) : ?>
                        <div class="table-responsive jtseo-score-table-wrap">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo Text::_('COM_JTSEO_SCORE_TABLE_SCORE'); ?></th>
                                        <th><?php echo Text::_('COM_JTSEO_SCORE_TABLE_ARTICLE'); ?></th>
                                        <th><?php echo Text::_('COM_JTSEO_SCORE_TABLE_NOTES'); ?></th>
                                        <th class="text-end"><?php echo Text::_('COM_JTSEO_AUDIT_TABLE_ACTION'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($auditScores, 0, 20) as $scoreRow) : ?>
                                        <?php
                                        $scorePercent = max(0, min(100, (int) ($scoreRow['score'] ?? 0)));
                                        $bucket = (string) ($scoreRow['bucket'] ?? 'weak');
                                        $scoreBadgeClass = 'bg-danger text-white';
                                        $progressClass = 'bg-danger';

                                        if ($bucket === 'excellent') {
                                            $scoreBadgeClass = 'bg-success text-white';
                                            $progressClass = 'bg-success';
                                        } elseif ($bucket === 'good') {
                                            $scoreBadgeClass = 'bg-info text-white';
                                            $progressClass = 'bg-info';
                                        } elseif ($bucket === 'fair') {
                                            $scoreBadgeClass = 'bg-warning text-dark';
                                            $progressClass = 'bg-warning';
                                        }
                                        ?>
                                        <tr>
                                            <td class="jtseo-score-progress">
                                                <span class="badge <?php echo htmlspecialchars($scoreBadgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $scorePercent; ?>/100</span>
                                                <div class="progress mt-2" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $scorePercent; ?>">
                                                    <div class="progress-bar <?php echo htmlspecialchars($progressClass, ENT_QUOTES, 'UTF-8'); ?>" style="width: <?php echo $scorePercent; ?>%"></div>
                                                </div>
                                                <div class="small text-muted mt-1"><?php echo Text::_('COM_JTSEO_SCORE_BUCKET_' . strtoupper($bucket)); ?></div>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars((string) ($scoreRow['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <div class="small text-muted">
                                                    <?php echo Text::sprintf('COM_JTSEO_SCORE_META', (int) ($scoreRow['description_length'] ?? 0), (int) ($scoreRow['word_count'] ?? 0)); ?>
                                                </div>
                                            </td>
                                            <td class="jtseo-score-notes">
                                                <?php foreach ((array) ($scoreRow['notes'] ?? []) as $noteKey) : ?>
                                                    <span class="badge bg-light text-dark border me-1 mb-1"><?php echo Text::_((string) $noteKey); ?></span>
                                                <?php endforeach; ?>
                                            </td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_content&task=article.edit&id=' . (int) ($scoreRow['id'] ?? 0)); ?>">
                                                    <?php echo Text::_('COM_JTSEO_AUDIT_EDIT_ARTICLE'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="form-text mt-2"><?php echo Text::_('COM_JTSEO_SCORE_HINT'); ?></div>
                    <?php else : ?>
                        <div class="alert alert-light border mb-0"><?php echo Text::_('COM_JTSEO_SCORE_EMPTY'); ?></div>
                    <?php endif; ?>
                </div>
            </div>


            <?php if (!empty($auditIssues)) : ?>
                <form id="jtseo-audit-filter-form" class="jtseo-audit-filter-bar jtseo-filter-form mb-3" action="<?php echo Route::_('index.php'); ?>" method="get">
                    <input type="hidden" name="option" value="com_jtseo">
                    <input type="hidden" name="view" value="dashboard">
                    <input type="hidden" name="jtseo_tab" value="audit">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label" for="jtseo-audit-search"><?php echo Text::_('COM_JTSEO_AUDIT_FILTER_SEARCH'); ?></label>
                            <input type="search" class="form-control" id="jtseo-audit-search" name="audit_search" value="<?php echo htmlspecialchars($auditFilterSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_JTSEO_AUDIT_FILTER_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label" for="jtseo-audit-severity"><?php echo Text::_('COM_JTSEO_AUDIT_FILTER_SEVERITY'); ?></label>
                            <select class="form-select" id="jtseo-audit-severity" name="audit_severity">
                                <option value=""<?php echo $auditFilterSeverity === '' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_AUDIT_FILTER_ALL_SEVERITIES'); ?></option>
                                <option value="danger"<?php echo $auditFilterSeverity === 'danger' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_AUDIT_SEVERITY_DANGER'); ?></option>
                                <option value="warning"<?php echo $auditFilterSeverity === 'warning' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_AUDIT_SEVERITY_WARNING'); ?></option>
                                <option value="info"<?php echo $auditFilterSeverity === 'info' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_AUDIT_SEVERITY_INFO'); ?></option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label" for="jtseo-audit-type"><?php echo Text::_('COM_JTSEO_AUDIT_FILTER_TYPE'); ?></label>
                            <select class="form-select" id="jtseo-audit-type" name="audit_type">
                                <option value=""<?php echo $auditFilterType === '' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_AUDIT_FILTER_ALL_TYPES'); ?></option>
                                <option value="description"<?php echo $auditFilterType === 'description' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_AUDIT_TYPE_DESCRIPTION'); ?></option>
                                <option value="image"<?php echo $auditFilterType === 'image' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_AUDIT_TYPE_IMAGE'); ?></option>
                                <option value="robots"<?php echo $auditFilterType === 'robots' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_AUDIT_TYPE_ROBOTS'); ?></option>
                                <option value="alias"<?php echo $auditFilterType === 'alias' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_AUDIT_TYPE_ALIAS'); ?></option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <button type="submit" class="btn btn-primary w-100"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <a class="btn btn-outline-secondary w-100" href="<?php echo Route::_('index.php?option=com_jtseo&view=dashboard&jtseo_tab=audit'); ?>#jtseo-audit-card"><?php echo Text::_('COM_JTSEO_AUDIT_FILTER_RESET'); ?></a>
                        </div>
                    </div>
                    <div class="form-text mt-2" id="jtseo-audit-filter-count" data-total="<?php echo (int) count($auditIssues); ?>">
                        <?php echo Text::sprintf('COM_JTSEO_AUDIT_FILTER_COUNT', $auditVisibleCount, count($auditIssues)); ?>
                    </div>
                </form>
                <?php if (empty($filteredAuditIssues)) : ?>
                    <div class="alert alert-light border mb-0"><?php echo Text::_('COM_JTSEO_AUDIT_FILTER_EMPTY'); ?></div>
                <?php else : ?>
                    <?php if ($auditIssueCount > 15) : ?>
                        <div class="alert alert-info py-2 mb-2">
                            <?php echo Text::sprintf('COM_JTSEO_AUDIT_SCROLL_HINT', $auditIssueCount); ?>
                        </div>
                    <?php endif; ?>
                <div class="table-responsive jtseo-audit-table-wrap<?php echo $auditIssueCount > 15 ? ' jtseo-audit-table-scroll' : ''; ?>"<?php echo $auditIssueCount > 15 ? ' style="max-height: 760px; overflow-y: auto; overflow-x: auto; display: block;"' : ''; ?>>
                    <table class="table table-striped align-middle mb-0"<?php echo $auditIssueCount > 15 ? ' style="min-width: 100%;"' : ''; ?>>
                        <thead>
                            <tr>
                                <th><?php echo Text::_('COM_JTSEO_AUDIT_TABLE_ARTICLE'); ?></th>
                                <th><?php echo Text::_('COM_JTSEO_AUDIT_TABLE_ISSUE'); ?></th>
                                <th><?php echo Text::_('COM_JTSEO_AUDIT_TABLE_FIX'); ?></th>
                                <th><?php echo Text::_('COM_JTSEO_AUDIT_TABLE_SEVERITY'); ?></th>
                                <th class="text-end"><?php echo Text::_('COM_JTSEO_AUDIT_TABLE_ACTION'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filteredAuditIssues as $issue) : ?>
                                <?php
                                $severity = (string) ($issue['severity'] ?? 'secondary');
                                $type = (string) ($issue['type'] ?? '');
                                $detail = (string) ($issue['detail'] ?? '');
                                $message = Text::_((string) ($issue['message_key'] ?? ''));
                                $suggestion = Text::_((string) ($issue['suggestion_key'] ?? ''));

                                if ($detail !== '') {
                                    $message .= ' ' . Text::sprintf('COM_JTSEO_AUDIT_DETAIL_LENGTH', htmlspecialchars($detail, ENT_QUOTES, 'UTF-8'));
                                }

                                $rowText = trim((string) ($issue['title'] ?? '') . ' ' . strip_tags($message) . ' ' . strip_tags($suggestion));
                                $badgeClass = 'bg-' . $severity;

                                if ($severity === 'warning') {
                                    $badgeClass .= ' text-dark';
                                } else {
                                    $badgeClass .= ' text-white';
                                }
                                ?>
                                <tr class="jtseo-audit-row" data-severity="<?php echo htmlspecialchars($severity, ENT_QUOTES, 'UTF-8'); ?>" data-type="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" data-text="<?php echo htmlspecialchars($rowText, ENT_QUOTES, 'UTF-8'); ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($issue['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="text-muted small">ID: <?php echo (int) ($issue['id'] ?? 0); ?></div>
                                    </td>
                                    <td><?php echo $message; ?></td>
                                    <td class="jtseo-audit-suggestion"><?php echo $suggestion !== '' ? $suggestion : Text::_('COM_JTSEO_AUDIT_FIX_GENERIC'); ?></td>
                                    <td><span class="badge <?php echo htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo Text::_('COM_JTSEO_AUDIT_SEVERITY_' . strtoupper($severity)); ?></span></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo Route::_('index.php?option=com_content&task=article.edit&id=' . (int) ($issue['id'] ?? 0)); ?>">
                                            <?php echo Text::_('COM_JTSEO_AUDIT_EDIT_ARTICLE'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            <?php else : ?>
                <div class="alert alert-success mb-0"><?php echo Text::_('COM_JTSEO_AUDIT_CLEAN_MESSAGE'); ?></div>
            <?php endif; ?>
        </div>
    </div>


    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'jtseoDashboardTabs', 'bulk', Text::_('COM_JTSEO_TAB_BULK')); ?>
<div class="card border mb-4" id="jtseo-bulk-meta">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                        <div>
                            <h4 class="h6 mb-1"><?php echo Text::_('COM_JTSEO_BULK_TITLE'); ?></h4>
                            <p class="text-muted mb-1"><?php echo Text::_('COM_JTSEO_BULK_DESC'); ?></p>
                            <div class="form-text"><?php echo Text::_('COM_JTSEO_BULK_DEFAULT_NOTE'); ?></div>
                        </div>
                        <?php if (!empty($bulkPagedArticles)) : ?>
                            <div>
                                <button type="submit" form="jtseo-bulk-meta-form" class="btn btn-primary">
                                    <?php echo Text::_('COM_JTSEO_BULK_SAVE'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($bulkArticles)) : ?>
                        <details class="jtseo-bulk-import-export mb-3" id="jtseo-bulk-import-export">
                            <summary class="jtseo-details-summary">
                                <span><?php echo Text::_('COM_JTSEO_BULK_IMPORT_EXPORT_TITLE'); ?></span>
                                <small class="text-muted"><?php echo Text::_('COM_JTSEO_BULK_IMPORT_EXPORT_SUMMARY'); ?></small>
                            </summary>
                            <div class="card border mt-2">
                                <div class="card-body">
                                    <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
                                        <div>
                                            <h5 class="h6 mb-1"><?php echo Text::_('COM_JTSEO_BULK_IMPORT_EXPORT_TITLE'); ?></h5>
                                            <p class="text-muted mb-0"><?php echo Text::_('COM_JTSEO_BULK_IMPORT_EXPORT_DESC'); ?></p>
                                        </div>
                                        <div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-start">
                                            <a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_jtseo&task=bulk.export&' . Session::getFormToken() . '=1'); ?>">
                                                <?php echo Text::_('COM_JTSEO_BULK_EXPORT_CSV'); ?>
                                            </a>
                                        </div>
                                    </div>
                                    <form action="<?php echo Route::_('index.php?option=com_jtseo&task=bulk.import'); ?>" method="post" enctype="multipart/form-data" class="row g-3 align-items-end mt-2">
                                        <div class="col-lg-5">
                                            <label class="form-label" for="jtseo-bulk-import-file"><?php echo Text::_('COM_JTSEO_BULK_IMPORT_FILE'); ?></label>
                                            <input type="file" class="form-control" id="jtseo-bulk-import-file" name="seo_csv" accept=".csv,text/csv">
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="form-check mt-lg-4">
                                                <input class="form-check-input" type="checkbox" value="1" id="jtseo-bulk-import-dry-run" name="dry_run" checked>
                                                <label class="form-check-label" for="jtseo-bulk-import-dry-run"><?php echo Text::_('COM_JTSEO_BULK_IMPORT_DRY_RUN'); ?></label>
                                            </div>
                                        </div>
                                        <div class="col-lg-2">
                                            <button type="submit" class="btn btn-secondary w-100"><?php echo Text::_('COM_JTSEO_BULK_IMPORT_CSV'); ?></button>
                                        </div>
                                        <div class="col-lg-2">
                                            <div class="small text-muted"><?php echo Text::_('COM_JTSEO_BULK_IMPORT_HINT'); ?></div>
                                        </div>
                                        <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
                                    </form>
                                </div>
                            </div>
                        </details>

                        <form id="jtseo-bulk-filter-form" class="jtseo-bulk-filter-bar jtseo-filter-form mb-3" action="<?php echo Route::_('index.php'); ?>" method="get">
                            <input type="hidden" name="option" value="com_jtseo">
                            <input type="hidden" name="view" value="dashboard">
                            <input type="hidden" name="jtseo_tab" value="bulk">
                            <input type="hidden" name="bulk_page" value="1">
                            <div class="row g-3 align-items-end">
                                <div class="col-xl-4 col-lg-5 col-md-6">
                                    <label class="form-label" for="jtseo-bulk-search"><?php echo Text::_('COM_JTSEO_BULK_FILTER_SEARCH'); ?></label>
                                    <input type="search" class="form-control" id="jtseo-bulk-search" name="bulk_search" value="<?php echo htmlspecialchars($bulkFilterSearch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(Text::_('COM_JTSEO_BULK_FILTER_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-xl-3 col-lg-3 col-md-6">
                                    <label class="form-label" for="jtseo-bulk-filter"><?php echo Text::_('COM_JTSEO_BULK_FILTER_STATUS'); ?></label>
                                    <select class="form-select" id="jtseo-bulk-filter" name="bulk_filter">
                                        <option value="needs-attention"<?php echo $bulkFilterStatus === 'needs-attention' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_BULK_FILTER_NEEDS_ATTENTION'); ?></option>
                                        <option value="all"<?php echo $bulkFilterStatus === 'all' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_BULK_FILTER_ALL'); ?></option>
                                        <option value="missing-description"<?php echo $bulkFilterStatus === 'missing-description' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_BULK_FILTER_MISSING_DESCRIPTION'); ?></option>
                                        <option value="low-score"<?php echo $bulkFilterStatus === 'low-score' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_BULK_FILTER_LOW_SCORE'); ?></option>
                                        <option value="noindex"<?php echo $bulkFilterStatus === 'noindex' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_BULK_FILTER_NOINDEX'); ?></option>
                                        <option value="excellent"<?php echo $bulkFilterStatus === 'excellent' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_BULK_FILTER_EXCELLENT'); ?></option>
                                    </select>
                                </div>
                                <div class="col-xl-2 col-lg-2 col-md-4">
                                    <label class="form-label" for="jtseo-bulk-limit"><?php echo Text::_('COM_JTSEO_BULK_PER_PAGE'); ?></label>
                                    <select class="form-select" id="jtseo-bulk-limit" name="bulk_limit">
                                        <?php foreach ($allowedBulkLimits as $allowedBulkLimit) : ?>
                                            <option value="<?php echo (int) $allowedBulkLimit; ?>"<?php echo $bulkLimit === $allowedBulkLimit ? ' selected' : ''; ?>><?php echo (int) $allowedBulkLimit; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-xl-2 col-lg-2 col-md-4 jtseo-bulk-search-action">
                                    <button type="submit" class="btn btn-primary w-100"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
                                </div>
                                <div class="col-xl-1 col-lg-12 col-md-4 jtseo-bulk-reset-action">
                                    <a class="btn btn-outline-secondary w-100" href="<?php echo Route::_('index.php?option=com_jtseo&view=dashboard&jtseo_tab=bulk'); ?>#jtseo-bulk-meta"><?php echo Text::_('COM_JTSEO_AUDIT_FILTER_RESET'); ?></a>
                                </div>
                            </div>
                            <div class="form-text mt-2" id="jtseo-bulk-count">
                                <?php echo Text::sprintf('COM_JTSEO_BULK_FILTER_COUNT_DETAILED', $bulkShownStart, $bulkShownEnd, $bulkFilteredTotal, count($bulkArticles)); ?>
                            </div>
                        </form>

                        <?php if (empty($bulkPagedArticles)) : ?>
                            <div class="alert alert-light border mb-0"><?php echo Text::_('COM_JTSEO_BULK_FILTER_EMPTY'); ?></div>
                        <?php else : ?>
                        <form id="jtseo-bulk-meta-form" action="<?php echo Route::_('index.php?option=com_jtseo&task=bulk.save'); ?>" method="post">
                            <div class="jtseo-bulk-list">
                                <?php foreach ($bulkPagedArticles as $bulkArticle) : ?>
                                    <?php
                                    $bulkId = (int) ($bulkArticle['id'] ?? 0);
                                    $bulkScore = max(0, min(100, (int) ($bulkArticle['score'] ?? 0)));
                                    $bulkBucket = (string) ($bulkArticle['bucket'] ?? 'weak');
                                    $bulkBadgeClass = 'bg-danger text-white';
                                    $bulkProgressClass = 'bg-danger';

                                    if ($bulkBucket === 'excellent') {
                                        $bulkBadgeClass = 'bg-success text-white';
                                        $bulkProgressClass = 'bg-success';
                                    } elseif ($bulkBucket === 'good') {
                                        $bulkBadgeClass = 'bg-info text-white';
                                        $bulkProgressClass = 'bg-info';
                                    } elseif ($bulkBucket === 'fair') {
                                        $bulkBadgeClass = 'bg-warning text-dark';
                                        $bulkProgressClass = 'bg-warning';
                                    }
                                    ?>
                                    <div class="jtseo-bulk-item jtseo-bulk-row">
                                        <div class="row g-3 align-items-start">
                                            <div class="col-xl-3 col-lg-4">
                                                <div class="d-flex gap-3">
                                                    <div class="jtseo-bulk-score">
                                                        <span class="badge <?php echo htmlspecialchars($bulkBadgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $bulkScore; ?>/100</span>
                                                        <div class="progress mt-2" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $bulkScore; ?>">
                                                            <div class="progress-bar <?php echo htmlspecialchars($bulkProgressClass, ENT_QUOTES, 'UTF-8'); ?>" style="width: <?php echo $bulkScore; ?>%"></div>
                                                        </div>
                                                        <div class="small text-muted mt-1"><?php echo Text::_('COM_JTSEO_SCORE_BUCKET_' . strtoupper($bulkBucket)); ?></div>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <strong><?php echo htmlspecialchars((string) ($bulkArticle['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                                        <?php if (!empty($bulkArticle['category'])) : ?>
                                                            <div class="small text-muted"><?php echo htmlspecialchars((string) $bulkArticle['category'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                        <?php endif; ?>
                                                        <div class="small text-muted">
                                                            <?php echo Text::sprintf('COM_JTSEO_SCORE_META', (int) ($bulkArticle['description_length'] ?? 0), (int) ($bulkArticle['word_count'] ?? 0)); ?>
                                                        </div>
                                                        <div class="jtseo-bulk-notes mt-2">
                                                            <?php foreach ((array) ($bulkArticle['notes'] ?? []) as $bulkNoteKey) : ?>
                                                                <span class="badge bg-light text-dark border"><?php echo Text::_((string) $bulkNoteKey); ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <a class="btn btn-sm btn-outline-primary mt-2" href="<?php echo Route::_('index.php?option=com_content&task=article.edit&id=' . $bulkId); ?>">
                                                            <?php echo Text::_('COM_JTSEO_AUDIT_EDIT_ARTICLE'); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-xl-4 col-lg-4">
                                                <label class="form-label" for="jtseo-bulk-title-<?php echo $bulkId; ?>"><?php echo Text::_('COM_JTSEO_BULK_FIELD_SEO_TITLE'); ?></label>
                                                <input type="text" class="form-control" id="jtseo-bulk-title-<?php echo $bulkId; ?>" name="bulk[<?php echo $bulkId; ?>][jtseo_title]" maxlength="255" value="<?php echo htmlspecialchars((string) ($bulkArticle['seo_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars((string) ($bulkArticle['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>

                                            <div class="col-xl-5 col-lg-4">
                                                <label class="form-label" for="jtseo-bulk-description-<?php echo $bulkId; ?>"><?php echo Text::_('COM_JTSEO_BULK_FIELD_SEO_DESCRIPTION'); ?></label>
                                                <textarea class="form-control" id="jtseo-bulk-description-<?php echo $bulkId; ?>" name="bulk[<?php echo $bulkId; ?>][jtseo_description]" rows="2" maxlength="320" placeholder="<?php echo htmlspecialchars(Text::_('COM_JTSEO_BULK_DESCRIPTION_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($bulkArticle['seo_description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                            </div>

                                            <div class="col-12">
                                                <details class="jtseo-bulk-advanced">
                                                    <summary><?php echo Text::_('COM_JTSEO_BULK_ADVANCED_SUMMARY'); ?></summary>
                                                    <div class="row g-3 mt-1">
                                                        <div class="col-lg-3 col-md-6">
                                                            <label class="form-label" for="jtseo-bulk-index-<?php echo $bulkId; ?>"><?php echo Text::_('COM_JTSEO_BULK_FIELD_ROBOTS_INDEX'); ?></label>
                                                            <select class="form-select" id="jtseo-bulk-index-<?php echo $bulkId; ?>" name="bulk[<?php echo $bulkId; ?>][jtseo_robots_index]">
                                                                <?php $bulkIndex = (string) ($bulkArticle['robots_index'] ?? ''); ?>
                                                                <option value=""<?php echo $bulkIndex === '' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_BULK_USE_DEFAULT'); ?></option>
                                                                <option value="index"<?php echo $bulkIndex === 'index' ? ' selected' : ''; ?>>index</option>
                                                                <option value="noindex"<?php echo $bulkIndex === 'noindex' ? ' selected' : ''; ?>>noindex</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-lg-3 col-md-6">
                                                            <label class="form-label" for="jtseo-bulk-follow-<?php echo $bulkId; ?>"><?php echo Text::_('COM_JTSEO_BULK_FIELD_ROBOTS_FOLLOW'); ?></label>
                                                            <select class="form-select" id="jtseo-bulk-follow-<?php echo $bulkId; ?>" name="bulk[<?php echo $bulkId; ?>][jtseo_robots_follow]">
                                                                <?php $bulkFollow = (string) ($bulkArticle['robots_follow'] ?? ''); ?>
                                                                <option value=""<?php echo $bulkFollow === '' ? ' selected' : ''; ?>><?php echo Text::_('COM_JTSEO_BULK_USE_DEFAULT'); ?></option>
                                                                <option value="follow"<?php echo $bulkFollow === 'follow' ? ' selected' : ''; ?>>follow</option>
                                                                <option value="nofollow"<?php echo $bulkFollow === 'nofollow' ? ' selected' : ''; ?>>nofollow</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-lg-3 col-md-6">
                                                            <label class="form-label" for="jtseo-bulk-canonical-<?php echo $bulkId; ?>"><?php echo Text::_('COM_JTSEO_BULK_FIELD_CANONICAL'); ?></label>
                                                            <input type="text" class="form-control" id="jtseo-bulk-canonical-<?php echo $bulkId; ?>" name="bulk[<?php echo $bulkId; ?>][jtseo_canonical]" value="<?php echo htmlspecialchars((string) ($bulkArticle['canonical'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://example.com/preferred-url">
                                                        </div>
                                                        <div class="col-lg-3 col-md-6">
                                                            <label class="form-label" for="jtseo-bulk-og-<?php echo $bulkId; ?>"><?php echo Text::_('COM_JTSEO_BULK_FIELD_OG_IMAGE'); ?></label>
                                                            <input type="text" class="form-control" id="jtseo-bulk-og-<?php echo $bulkId; ?>" name="bulk[<?php echo $bulkId; ?>][jtseo_og_image]" value="<?php echo htmlspecialchars((string) ($bulkArticle['og_image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="images/example.jpg">
                                                        </div>
                                                    </div>
                                                </details>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($bulkPageCount > 1) : ?>
                                <nav class="jtseo-bulk-pagination d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3" aria-label="<?php echo htmlspecialchars(Text::_('COM_JTSEO_BULK_TITLE'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="small text-muted"><?php echo Text::sprintf('COM_JTSEO_BULK_PAGE_STATUS', $bulkPage, $bulkPageCount); ?></div>
                                    <ul class="pagination mb-0">
                                        <li class="page-item<?php echo $bulkPage <= 1 ? ' disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo $bulkPage <= 1 ? '#' : $bulkPageUrl($bulkPage - 1); ?>"><?php echo Text::_('COM_JTSEO_BULK_PAGE_PREVIOUS'); ?></a>
                                        </li>
                                        <?php for ($pageNumber = 1; $pageNumber <= $bulkPageCount; $pageNumber++) : ?>
                                            <?php if ($pageNumber === 1 || $pageNumber === $bulkPageCount || abs($pageNumber - $bulkPage) <= 2) : ?>
                                                <li class="page-item<?php echo $pageNumber === $bulkPage ? ' active' : ''; ?>">
                                                    <a class="page-link" href="<?php echo $bulkPageUrl($pageNumber); ?>"><?php echo (int) $pageNumber; ?></a>
                                                </li>
                                            <?php elseif ($pageNumber === 2 || $pageNumber === $bulkPageCount - 1) : ?>
                                                <li class="page-item disabled"><span class="page-link">…</span></li>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <li class="page-item<?php echo $bulkPage >= $bulkPageCount ? ' disabled' : ''; ?>">
                                            <a class="page-link" href="<?php echo $bulkPage >= $bulkPageCount ? '#' : $bulkPageUrl($bulkPage + 1); ?>"><?php echo Text::_('COM_JTSEO_BULK_PAGE_NEXT'); ?></a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mt-3">
                                <div class="form-text"><?php echo Text::_('COM_JTSEO_BULK_HINT'); ?></div>
                                <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_JTSEO_BULK_SAVE'); ?></button>
                            </div>
                            <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
                        </form>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="alert alert-light border mb-0"><?php echo Text::_('COM_JTSEO_BULK_EMPTY'); ?></div>
                    <?php endif; ?>
                </div>
            </div>

    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'jtseoDashboardTabs', 'structured', Text::_('COM_JTSEO_TAB_STRUCTURED')); ?>
<div class="card mt-4 jtseo-structured-card" id="jtseo-structured-data">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h3 class="h5 mb-1"><?php echo Text::_('COM_JTSEO_STRUCTURED_TITLE'); ?></h3>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JTSEO_STRUCTURED_DESC'); ?></p>
                </div>
                <div class="text-lg-end">
                    <span class="badge bg-success text-white"><?php echo Text::_('COM_JTSEO_STATUS_ACTIVE'); ?></span>
                    <div class="small text-muted mt-1"><?php echo Text::_('COM_JTSEO_STRUCTURED_MODE_PREVIEW'); ?></div>
                </div>
            </div>

            <div class="alert alert-info">
                <?php echo Text::_('COM_JTSEO_STRUCTURED_SAFE_NOTE'); ?>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_STRUCTURED_SITE_NAME'); ?></div>
                        <div class="fw-semibold"><?php echo htmlspecialchars((string) ($structuredData['site_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_STRUCTURED_PUBLISHED_ARTICLES'); ?></div>
                        <div class="fs-3 fw-semibold"><?php echo (int) ($structuredData['article_count'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_STRUCTURED_SAMPLE_ARTICLE'); ?></div>
                        <div class="fw-semibold">
                            <?php echo !empty($structuredSampleArticle) ? htmlspecialchars((string) ($structuredSampleArticle['title'] ?? ''), ENT_QUOTES, 'UTF-8') : Text::_('COM_JTSEO_VALUE_NOT_SET'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 jtseo-structured-row">
                <div class="col-12 jtseo-structured-mapping-column">
                    <h4 class="h6"><?php echo Text::_('COM_JTSEO_STRUCTURED_MAPPING_TITLE'); ?></h4>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle jtseo-structured-mapping-table">
                            <thead>
                                <tr>
                                    <th><?php echo Text::_('COM_JTSEO_STRUCTURED_TABLE_TYPE'); ?></th>
                                    <th><?php echo Text::_('COM_JTSEO_STRUCTURED_TABLE_SOURCE'); ?></th>
                                    <th><?php echo Text::_('COM_JTSEO_STRUCTURED_TABLE_STATUS'); ?></th>
                                    <th><?php echo Text::_('COM_JTSEO_STRUCTURED_TABLE_NOTE'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($structuredMappings as $mapping) : ?>
                                    <?php
                                    $mappingStatus = (string) ($mapping['status'] ?? 'review');
                                    $mappingBadge = $mappingStatus === 'ready' ? 'success' : ($mappingStatus === 'missing' ? 'secondary' : 'warning text-dark');
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars((string) ($mapping['type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td><?php echo htmlspecialchars((string) ($mapping['source'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="badge bg-<?php echo htmlspecialchars($mappingBadge, ENT_QUOTES, 'UTF-8'); ?>"><?php echo Text::_('COM_JTSEO_STRUCTURED_STATUS_' . strtoupper($mappingStatus)); ?></span></td>
                                        <td><?php echo htmlspecialchars((string) ($mapping['note'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="accordion jtseo-json-accordion mt-4" id="jtseoJsonPreviewAccordion">
                <div class="accordion-item">
                    <h4 class="accordion-header" id="jtseoJsonPreviewHeading">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#jtseoJsonPreviewCollapse" aria-expanded="false" aria-controls="jtseoJsonPreviewCollapse">
                            <?php echo Text::_('COM_JTSEO_STRUCTURED_PREVIEW_TITLE'); ?>
                        </button>
                    </h4>
                    <div id="jtseoJsonPreviewCollapse" class="accordion-collapse collapse" aria-labelledby="jtseoJsonPreviewHeading" data-bs-parent="#jtseoJsonPreviewAccordion">
                        <div class="accordion-body">
                            <pre class="jtseo-structured-preview border rounded p-3 bg-light mb-2" tabindex="0"><code><?php echo htmlspecialchars($structuredPreviewJson, ENT_QUOTES, 'UTF-8'); ?></code></pre>
                            <div class="form-text"><?php echo Text::_('COM_JTSEO_STRUCTURED_PREVIEW_HINT'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'jtseoDashboardTabs', 'redirects', Text::_('COM_JTSEO_TAB_REDIRECTS')); ?>
<div class="card mt-4" id="jtseo-redirects">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h3 class="h5 mb-1"><?php echo Text::_('COM_JTSEO_REDIRECTS_TITLE'); ?></h3>
                    <p class="text-muted mb-0"><?php echo Text::_('COM_JTSEO_REDIRECTS_DESC'); ?></p>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end align-items-start jtseo-status-badges">
                    <span class="badge rounded-pill px-3 py-2 <?php echo $redirectEnabled ? 'bg-success' : 'bg-secondary'; ?> text-white">
                        <?php echo Text::_('COM_JTSEO_REDIRECTS_STATUS_REDIRECTS'); ?>: <?php echo Text::_($redirectEnabled ? 'COM_JTSEO_STATUS_ACTIVE' : 'COM_JTSEO_STATUS_DISABLED'); ?>
                    </span>
                    <span class="badge rounded-pill px-3 py-2 <?php echo $log404Enabled ? 'bg-success' : 'bg-secondary'; ?> text-white">
                        <?php echo Text::_('COM_JTSEO_REDIRECTS_STATUS_404_LOGGING'); ?>: <?php echo Text::_($log404Enabled ? 'COM_JTSEO_STATUS_ACTIVE' : 'COM_JTSEO_STATUS_DISABLED'); ?>
                    </span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_REDIRECTS_ENABLED'); ?></div>
                        <div class="fs-3 fw-semibold"><?php echo (int) ($redirectSummary['enabled_redirects'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_REDIRECTS_TOTAL'); ?></div>
                        <div class="fs-3 fw-semibold"><?php echo (int) ($redirectSummary['total_redirects'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_404_LOGGED_URLS'); ?></div>
                        <div class="fs-3 fw-semibold"><?php echo (int) ($redirectSummary['logged_404s'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="p-3 border rounded h-100">
                        <div class="text-muted small"><?php echo Text::_('COM_JTSEO_404_TOTAL_HITS'); ?></div>
                        <div class="fs-3 fw-semibold"><?php echo (int) ($redirectSummary['total_404_hits'] ?? 0); ?></div>
                    </div>
                </div>
            </div>

            <form action="<?php echo Route::_('index.php?option=com_jtseo&task=redirect.save'); ?>" method="post" class="border rounded p-3 mb-4">
                <h4 class="h6 mb-3"><?php echo Text::_('COM_JTSEO_REDIRECT_ADD_TITLE'); ?></h4>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label" for="jtseo-old-url"><?php echo Text::_('COM_JTSEO_REDIRECT_OLD_URL'); ?></label>
                        <input type="text" class="form-control" id="jtseo-old-url" name="old_url" placeholder="/old-url">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="jtseo-new-url"><?php echo Text::_('COM_JTSEO_REDIRECT_NEW_URL'); ?></label>
                        <input type="text" class="form-control" id="jtseo-new-url" name="new_url" placeholder="/new-url">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="jtseo-status-code"><?php echo Text::_('COM_JTSEO_REDIRECT_STATUS'); ?></label>
                        <select class="form-select" id="jtseo-status-code" name="status_code">
                            <option value="301">301</option>
                            <option value="302">302</option>
                            <option value="307">307</option>
                            <option value="308">308</option>
                            <option value="410">410</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="hidden" name="enabled" value="1">
                        <button type="submit" class="btn btn-primary w-100"><?php echo Text::_('COM_JTSEO_REDIRECT_SAVE'); ?></button>
                    </div>
                </div>
                <div class="form-text mt-2"><?php echo Text::_('COM_JTSEO_REDIRECT_ADD_HINT'); ?></div>
                <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
            </form>

            <div class="row g-4">
                <div class="col-12 col-xxl-6">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <h4 class="h6 mb-0"><?php echo Text::_('COM_JTSEO_REDIRECTS_RECENT'); ?></h4>
                        <?php if (!empty($redirects)) : ?>
                            <form action="<?php echo Route::_('index.php?option=com_jtseo&task=redirect.clearRedirects'); ?>" method="post" class="d-inline-block">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <?php echo Text::_('COM_JTSEO_REDIRECT_CLEAR'); ?>
                                </button>
                                <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($redirects)) : ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th><?php echo Text::_('COM_JTSEO_REDIRECT_OLD_URL'); ?></th>
                                        <th><?php echo Text::_('COM_JTSEO_REDIRECT_NEW_URL'); ?></th>
                                        <th><?php echo Text::_('COM_JTSEO_REDIRECT_HITS'); ?></th>
                                        <th class="text-end"><?php echo Text::_('COM_JTSEO_AUDIT_TABLE_ACTION'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($redirects as $redirect) : ?>
                                        <tr>
                                            <td class="jtseo-url-cell"><?php echo htmlspecialchars((string) ($redirect->old_url ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="jtseo-url-cell"><?php echo htmlspecialchars((string) ($redirect->new_url ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo (int) ($redirect->hits ?? 0); ?></td>
                                            <td class="text-end">
                                                <form action="<?php echo Route::_('index.php?option=com_jtseo&task=redirect.delete'); ?>" method="post" class="d-inline-block">
                                                    <input type="hidden" name="id" value="<?php echo (int) ($redirect->id ?? 0); ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <?php echo Text::_('COM_JTSEO_REDIRECT_DELETE'); ?>
                                                    </button>
                                                    <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-light border mb-0"><?php echo Text::_('COM_JTSEO_REDIRECTS_EMPTY'); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-xxl-6">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <h4 class="h6 mb-0"><?php echo Text::_('COM_JTSEO_404_RECENT'); ?></h4>
                        <?php if (!empty($notFoundLogs)) : ?>
                            <form action="<?php echo Route::_('index.php?option=com_jtseo&task=redirect.clear404'); ?>" method="post" class="d-inline-block">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <?php echo Text::_('COM_JTSEO_404_CLEAR'); ?>
                                </button>
                                <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if (!$log404Enabled) : ?>
                        <div class="alert alert-warning border mb-3"><?php echo Text::_('COM_JTSEO_404_LOGGING_DISABLED_NOTICE'); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($notFoundLogs)) : ?>
                        <div class="jtseo-404-list">
                            <?php foreach ($notFoundLogs as $log) : ?>
                                <div class="jtseo-404-item border rounded p-3 mb-3">
                                    <div class="jtseo-404-main mb-3">
                                        <div class="small text-muted mb-1"><?php echo Text::_('COM_JTSEO_404_URL'); ?></div>
                                        <code class="jtseo-404-url"><?php echo htmlspecialchars((string) ($log->request_url ?? ''), ENT_QUOTES, 'UTF-8'); ?></code>
                                        <div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
                                            <span><strong><?php echo Text::_('COM_JTSEO_REDIRECT_HITS'); ?>:</strong> <?php echo (int) ($log->hits ?? 0); ?></span>
                                            <span><strong><?php echo Text::_('COM_JTSEO_404_LAST_HIT'); ?>:</strong> <?php echo htmlspecialchars((string) ($log->last_hit ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <div class="form-text mt-2"><?php echo Text::_('COM_JTSEO_404_CREATE_REDIRECT_HINT'); ?></div>
                                    </div>
                                    <form action="<?php echo Route::_('index.php?option=com_jtseo&task=redirect.saveFrom404'); ?>" method="post" class="jtseo-404-action-form mt-3">
                                        <input type="hidden" name="log_id" value="<?php echo (int) ($log->id ?? 0); ?>">
                                        <input type="hidden" name="old_url" value="<?php echo htmlspecialchars((string) ($log->request_url ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-12">
                                                <label class="form-label" for="jtseo-404-new-url-<?php echo (int) ($log->id ?? 0); ?>"><?php echo Text::_('COM_JTSEO_REDIRECT_NEW_URL'); ?></label>
                                                <input type="text" class="form-control" id="jtseo-404-new-url-<?php echo (int) ($log->id ?? 0); ?>" name="new_url" placeholder="/target-url">
                                            </div>
                                            <div class="col-12 col-sm-4 col-lg-3">
                                                <label class="form-label" for="jtseo-404-status-<?php echo (int) ($log->id ?? 0); ?>"><?php echo Text::_('COM_JTSEO_REDIRECT_STATUS'); ?></label>
                                                <select class="form-select jtseo-404-status" id="jtseo-404-status-<?php echo (int) ($log->id ?? 0); ?>" name="status_code">
                                                    <option value="301">301</option>
                                                    <option value="302">302</option>
                                                    <option value="307">307</option>
                                                    <option value="308">308</option>
                                                    <option value="410">410</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-sm-auto">
                                                <button type="submit" class="btn btn-primary jtseo-404-submit"><?php echo Text::_('COM_JTSEO_404_CREATE_REDIRECT'); ?></button>
                                            </div>
                                        </div>
                                        <input type="hidden" name="<?php echo Session::getFormToken(); ?>" value="1">
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-light border mb-0"><?php echo Text::_('COM_JTSEO_404_EMPTY'); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

<?php if ($supportCardEnabled) : ?>
    <div class="card border shadow-sm mt-4" id="jtseo-support">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h2 class="h4 mb-2"><?php echo Text::_('COM_JTSEO_SUPPORT_PROJECT_TITLE'); ?></h2>
                    <p class="mb-0 text-body"><?php echo Text::_('COM_JTSEO_SUPPORT_PROJECT_TEXT'); ?></p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-start justify-content-lg-end flex-shrink-0">
                    <?php if ($supportJedUrl !== '') : ?>
                        <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($supportJedUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo Text::_('COM_JTSEO_SUPPORT_PROJECT_REVIEW'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($supportGithubUrl !== '') : ?>
                        <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($supportGithubUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo Text::_('COM_JTSEO_SUPPORT_PROJECT_GITHUB'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($supportDevelopmentUrl !== '') : ?>
                        <a class="btn btn-primary" href="<?php echo htmlspecialchars($supportDevelopmentUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo Text::_('COM_JTSEO_SUPPORT_PROJECT_DONATE'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>
