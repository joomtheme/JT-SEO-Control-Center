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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use JoomTheme\Component\Jtseo\Administrator\Helper\BulkMetaHelper;

final class BulkController extends BaseController
{
    private function ensureCanManage(): bool
    {
        $user = $this->app->getIdentity();

        if ($user && $user->authorise('core.manage', 'com_jtseo')) {
            return true;
        }

        $this->app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false));

        return false;
    }

    public function save(): void
    {
        if (!$this->ensureCanManage()) {
            return;
        }

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false));

            return;
        }

        $bulk = $this->input->post->get('bulk', [], 'array');
        $result = BulkMetaHelper::saveBulk(is_array($bulk) ? $bulk : []);
        $updated = (int) ($result['updated'] ?? 0);

        if ($updated > 0) {
            $this->app->enqueueMessage(Text::sprintf('COM_JTSEO_BULK_SAVE_SUCCESS', $updated), 'message');
        } else {
            $message = (string) ($result['message'] ?? '');
            $this->app->enqueueMessage($message !== '' ? Text::sprintf('COM_JTSEO_BULK_SAVE_FAILED', $message) : Text::_('COM_JTSEO_BULK_SAVE_NOTHING'), 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-bulk');
    }

    public function export(): void
    {
        if (!$this->ensureCanManage()) {
            return;
        }

        if (!Session::checkToken('get')) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-bulk');

            return;
        }

        $csv = BulkMetaHelper::exportCsv(1000);
        $date = Factory::getDate()->format('Ymd-His');
        $filename = 'jtseo-meta-export-' . $date . '.csv';

        $this->app->setHeader('Content-Type', 'text/csv; charset=utf-8', true);
        $this->app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
        $this->app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate', true);
        $this->app->sendHeaders();

        echo BulkMetaHelper::utf8Bom() . $csv;
        $this->app->close();
    }

    public function import(): void
    {
        if (!$this->ensureCanManage()) {
            return;
        }

        if (!Session::checkToken('post')) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-bulk');

            return;
        }

        $file = $this->input->files->get('seo_csv', [], 'array');
        $dryRun = (int) $this->input->post->get('dry_run', 0, 'int') === 1;
        $result = BulkMetaHelper::importCsv(is_array($file) ? $file : [], $dryRun);

        if (empty($result['success'])) {
            $message = (string) ($result['message'] ?? '');
            $this->app->enqueueMessage(Text::sprintf('COM_JTSEO_BULK_IMPORT_FAILED', $message !== '' ? $message : Text::_('COM_JTSEO_ERROR_UNKNOWN')), 'error');
        } elseif ($dryRun) {
            $this->app->enqueueMessage(Text::sprintf('COM_JTSEO_BULK_IMPORT_PREVIEW_SUCCESS', (int) ($result['valid'] ?? 0), (int) ($result['skipped'] ?? 0)), 'message');
        } else {
            $updated = (int) ($result['updated'] ?? 0);
            $unchanged = (int) ($result['unchanged'] ?? 0);
            $skipped = (int) ($result['skipped'] ?? 0);
            $this->app->enqueueMessage(Text::sprintf('COM_JTSEO_BULK_IMPORT_SUCCESS', $updated, $unchanged, $skipped), 'message');
        }

        $this->setRedirect(Route::_('index.php?option=com_jtseo&view=dashboard', false) . '#jtseo-bulk');
    }
}
