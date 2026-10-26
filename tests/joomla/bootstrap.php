<?php
define('_JEXEC', 1);
define('JPATH_BASE', getenv('JTSEO_JOOMLA_ROOT') ?: getcwd());
if (!is_file(JPATH_BASE . '/configuration.php')) {
    throw new RuntimeException('Set JTSEO_JOOMLA_ROOT to an installed Joomla development site.');
}
$_SERVER['HTTP_HOST'] = parse_url(getenv('JTSEO_TEST_ORIGIN') ?: 'http://127.0.0.1', PHP_URL_HOST) ?: '127.0.0.1';
$_SERVER['SERVER_NAME'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';
$container = \Joomla\CMS\Factory::getContainer();
foreach (['session', 'JSession', \Joomla\CMS\Session\Session::class,
    \Joomla\Session\Session::class, \Joomla\Session\SessionInterface::class] as $alias) {
    $container->alias($alias, 'session.cli');
}
$app = $container->get(\Joomla\Console\Application::class);
\Joomla\CMS\Factory::$application = $app;
$app->createExtensionNamespaceMap();
$db = $container->get(\Joomla\Database\DatabaseInterface::class);
