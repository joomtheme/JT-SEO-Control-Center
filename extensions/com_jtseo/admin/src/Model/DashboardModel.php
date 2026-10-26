<?php

/**
 * @package     JT SEO Control Center
 * @subpackage  Administrator
 *
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace JoomTheme\Component\Jtseo\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Registry\Registry;
use JoomTheme\Component\Jtseo\Administrator\Helper\AuditHelper;
use JoomTheme\Component\Jtseo\Administrator\Helper\BulkMetaHelper;
use JoomTheme\Component\Jtseo\Administrator\Helper\HealthHelper;
use JoomTheme\Component\Jtseo\Administrator\Helper\RedirectHelper;
use JoomTheme\Component\Jtseo\Administrator\Helper\SitemapHelper;
use JoomTheme\Component\Jtseo\Administrator\Helper\StructuredDataHelper;

final class DashboardModel extends BaseDatabaseModel
{
    /**
     * Assemble all dashboard data in the model layer so the view stays presentation-focused.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(Registry $params): array
    {
        return [
            'physicalSitemapStatus' => SitemapHelper::getPhysicalSitemapStatus(),
            'healthStatus'          => HealthHelper::getDashboardStatus($params),
            'audit'                 => AuditHelper::getDashboardAudit(500),
            'bulkArticles'          => BulkMetaHelper::getArticles(50),
            'structuredData'        => StructuredDataHelper::getDashboardData(5),
            'redirectSummary'       => RedirectHelper::getDashboardSummary(),
            'redirects'             => RedirectHelper::getRedirects(10),
            'notFoundLogs'          => RedirectHelper::get404Logs(10),
        ];
    }
}
