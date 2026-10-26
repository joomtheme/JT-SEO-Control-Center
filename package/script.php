<?php

/**
 * @package     JT SEO Control Center
 * @copyright   (C) 2026 JoomTheme. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

final class pkg_jtseoInstallerScript
{
    /**
     * Enable required plugins on a new installation after all children exist.
     * Preserve administrator-selected plugin states when upgrading a site.
     */
    public function postflight($type, $parent): bool
    {
        if ((string) $type !== 'install') {
            return true;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('jtseo'))
            ->whereIn($db->quoteName('folder'), ['system', 'content'], \Joomla\Database\ParameterType::STRING);
        $db->setQuery($query)->execute();

        return true;
    }
}
