<?php

/**
 * @package     JT SEO Control Center
 * @subpackage  Administrator
 *
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace JoomTheme\Component\Jtseo\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use JoomTheme\Component\Jtseo\Administrator\Model\DashboardModel;

final class HtmlView extends BaseHtmlView
{
    /** @var \Joomla\Registry\Registry */
    protected $params;

    /** @var array<string, mixed> */
    protected $physicalSitemapStatus = [];

    /** @var array<string, mixed> */
    protected $audit = [];

    /** @var array<string, mixed> */
    protected $redirectSummary = [];

    /** @var array<int, object> */
    protected $redirects = [];

    /** @var array<int, object> */
    protected $notFoundLogs = [];

    /** @var array<string, mixed> */
    protected $healthStatus = [];

    /** @var array<int, array<string, mixed>> */
    protected $bulkArticles = [];

    /** @var array<string, mixed> */
    protected $structuredData = [];

    public function display($tpl = null): void
    {
        $this->params = ComponentHelper::getParams('com_jtseo');

        $dashboardModel = $this->getModel();

        if (!$dashboardModel instanceof DashboardModel) {
            $dashboardModel = $this->getModel('Dashboard');
        }

        if (!$dashboardModel instanceof DashboardModel) {
            throw new \RuntimeException('JT SEO dashboard model could not be loaded.');
        }

        $dashboardData = $dashboardModel->getDashboardData($this->params);

        $this->physicalSitemapStatus = (array) ($dashboardData['physicalSitemapStatus'] ?? []);
        $this->healthStatus = (array) ($dashboardData['healthStatus'] ?? []);
        $this->audit = (array) ($dashboardData['audit'] ?? []);
        $this->bulkArticles = (array) ($dashboardData['bulkArticles'] ?? []);
        $this->structuredData = (array) ($dashboardData['structuredData'] ?? []);
        $this->redirectSummary = (array) ($dashboardData['redirectSummary'] ?? []);
        $this->redirects = (array) ($dashboardData['redirects'] ?? []);
        $this->notFoundLogs = (array) ($dashboardData['notFoundLogs'] ?? []);

        ToolbarHelper::title(Text::_('COM_JTSEO_DASHBOARD'), 'search');
        ToolbarHelper::preferences('com_jtseo');

        parent::display($tpl);
    }
}
