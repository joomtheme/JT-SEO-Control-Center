<?php

/**
 * @package     JT SEO Control Center
 * @subpackage  Installer
 *
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

final class plgSystemJtseoInstallerScript
{
    public function preflight($type, $parent): bool
    {
        if ((string) $type === 'uninstall') {
            $this->ensureLanguageFilesExist(['plg_system_jtseo.ini', 'plg_system_jtseo.sys.ini']);
        }

        return true;
    }

    /**
     * @param array<int, string> $filenames
     */
    private function ensureLanguageFilesExist(array $filenames): void
    {
        foreach (['en-GB', 'tr-TR'] as $tag) {
            foreach ([JPATH_SITE, JPATH_ADMINISTRATOR] as $basePath) {
                $directory = rtrim($basePath, "/\\") . '/language/' . $tag;

                if (!is_dir($directory) || !is_writable($directory)) {
                    continue;
                }

                foreach ($filenames as $filename) {
                    $path = $directory . '/' . $filename;

                    if (!is_file($path)) {
                        @file_put_contents($path, '');
                    }
                }
            }
        }
    }
}
