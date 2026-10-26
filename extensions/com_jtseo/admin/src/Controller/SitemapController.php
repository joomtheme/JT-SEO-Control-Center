<?php

/**
 * @package     JT SEO Control Center
 * @subpackage  Administrator
 *
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace JoomTheme\Component\Jtseo\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use JoomTheme\Component\Jtseo\Administrator\Helper\SitemapHelper;

final class SitemapController extends BaseController
{
    private function ensureCanManage(): bool
    {
        $user = $this->app->getIdentity();

        if ($user && $user->authorise('core.manage', 'com_jtseo')) {
            return true;
        }

        $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-overview');

        return false;
    }

    /**
     * Generate the physical /sitemap.xml file.
     */
    public function generate(): void
    {
        if (!$this->ensureCanManage()) {
            return;
        }

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-overview');

            return;
        }

        $result = SitemapHelper::writePhysicalSitemap();

        if (!empty($result['success'])) {
            $message = Text::sprintf('COM_JTSEO_SITEMAP_GENERATE_SUCCESS', (int) ($result['count'] ?? 0));
            $this->app->enqueueMessage($message, 'message');
        } else {
            $this->app->enqueueMessage(Text::sprintf('COM_JTSEO_SITEMAP_GENERATE_FAILED', (string) ($result['message'] ?? Text::_('COM_JTSEO_ERROR_UNKNOWN'))), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-overview');
    }
}
