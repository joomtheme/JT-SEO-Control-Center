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
use JoomTheme\Component\Jtseo\Administrator\Helper\RedirectHelper;

final class RedirectController extends BaseController
{
    private function ensureCan(string $action): bool
    {
        $user = $this->app->getIdentity();

        if ($user && $user->authorise($action, 'com_jtseo')) {
            return true;
        }

        $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false));

        return false;
    }

    public function save(): void
    {
        if (!$this->ensureCan('core.edit')) {
            return;
        }

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false));

            return;
        }

        $oldUrl = (string) $this->input->post->get('old_url', '', 'raw');
        $newUrl = (string) $this->input->post->get('new_url', '', 'raw');
        $status = (int) $this->input->post->getInt('status_code', 301);
        $enabled = (int) $this->input->post->getInt('enabled', 1);

        $result = RedirectHelper::saveRedirect($oldUrl, $newUrl, $status, $enabled);

        if (!empty($result['success'])) {
            $this->app->enqueueMessage(Text::_('COM_JTSEO_REDIRECT_SAVE_SUCCESS'), 'message');
        } else {
            $this->app->enqueueMessage(Text::sprintf('COM_JTSEO_REDIRECT_SAVE_FAILED', (string) ($result['message'] ?? Text::_('COM_JTSEO_ERROR_UNKNOWN'))), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-redirects');
    }


    public function saveFrom404(): void
    {
        if (!$this->ensureCan('core.edit')) {
            return;
        }

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false));

            return;
        }

        $logId = (int) $this->input->post->getInt('log_id', 0);
        $oldUrl = (string) $this->input->post->get('old_url', '', 'raw');
        $newUrl = (string) $this->input->post->get('new_url', '', 'raw');
        $status = (int) $this->input->post->getInt('status_code', 301);

        $result = RedirectHelper::saveRedirect($oldUrl, $newUrl, $status, 1);

        if (!empty($result['success'])) {
            if ($logId > 0) {
                RedirectHelper::delete404Log($logId);
            }

            $this->app->enqueueMessage(Text::_('COM_JTSEO_404_REDIRECT_CREATED'), 'message');
        } else {
            $this->app->enqueueMessage(Text::sprintf('COM_JTSEO_404_REDIRECT_CREATE_FAILED', (string) ($result['message'] ?? Text::_('COM_JTSEO_ERROR_UNKNOWN'))), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-redirects');
    }

    public function delete(): void
    {
        if (!$this->ensureCan('core.delete')) {
            return;
        }

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false));

            return;
        }

        $id = (int) $this->input->post->getInt('id', 0);

        if (RedirectHelper::deleteRedirect($id)) {
            $this->app->enqueueMessage(Text::_('COM_JTSEO_REDIRECT_DELETE_SUCCESS'), 'message');
        } else {
            $this->app->enqueueMessage(Text::_('COM_JTSEO_REDIRECT_DELETE_FAILED'), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-redirects');
    }


    public function clearRedirects(): void
    {
        if (!$this->ensureCan('core.delete')) {
            return;
        }

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false));

            return;
        }

        if (RedirectHelper::clearRedirects()) {
            $this->app->enqueueMessage(Text::_('COM_JTSEO_REDIRECT_CLEAR_SUCCESS'), 'message');
        } else {
            $this->app->enqueueMessage(Text::_('COM_JTSEO_REDIRECT_CLEAR_FAILED'), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-redirects');
    }

    public function clear404(): void
    {
        if (!$this->ensureCan('core.delete')) {
            return;
        }

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false));

            return;
        }

        if (RedirectHelper::clear404Logs()) {
            $this->app->enqueueMessage(Text::_('COM_JTSEO_404_CLEAR_SUCCESS'), 'message');
        } else {
            $this->app->enqueueMessage(Text::_('COM_JTSEO_404_CLEAR_FAILED'), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-redirects');
    }
}
