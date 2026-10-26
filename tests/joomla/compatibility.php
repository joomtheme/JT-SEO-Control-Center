<?php
require __DIR__ . '/bootstrap.php';
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Form\Form;
use Joomla\Event\Event;

$expectedVersion = trim((string) simplexml_load_file(dirname(__DIR__, 2) . '/package/pkg_jtseo.xml')->version);
$count = 0;
$check = static function (bool $ok, string $message) use (&$count): void {
    if (!$ok) {
        throw new RuntimeException($message);
    }
    $count++;
    echo 'PASS: ' . $message . PHP_EOL;
};
$rows = $db->setQuery("SELECT element, folder, enabled, manifest_cache FROM #__extensions WHERE element IN ('pkg_jtseo', 'com_jtseo', 'jtseo')")->loadObjectList();
$check(count($rows) === 4, 'package, component and two plugins installed');
foreach ($rows as $row) {
    $check((json_decode($row->manifest_cache)->version ?? '') === $expectedVersion, $row->folder . '/' . $row->element . ' version is ' . $expectedVersion);
    $check((int) $row->enabled === 1, $row->folder . '/' . $row->element . ' enabled');
}
$schema = $db->setQuery("SELECT s.version_id FROM #__schemas s JOIN #__extensions e ON s.extension_id=e.extension_id WHERE e.element='com_jtseo'")->loadResult();
$check($schema === $expectedVersion, 'component database schema marker is current');
$plugin = $app->bootPlugin('jtseo', 'content');
$newForm = static function (string $name): Form {
    $form = new Form($name);
    $form->load('<form><fields name="attribs"><fieldset name="basic"><field name="existing" type="text" /></fieldset></fields></form>');
    return $form;
};
foreach (['com_content.article', 'com_content.form'] as $context) {
    foreach (['typed', 'subject', 'form', 'positional'] as $kind) {
        $form = $newForm($context);
        $event = match ($kind) {
            'typed' => new PrepareFormEvent('onContentPrepareForm', ['subject' => $form, 'data' => new stdClass()]),
            'subject' => new Event('onContentPrepareForm', ['subject' => $form, 'data' => []]),
            'form' => new Event('onContentPrepareForm', ['form' => $form, 'data' => []]),
            'positional' => new Event('onContentPrepareForm', [$form, []]),
        };
        $plugin->onContentPrepareForm($event);
        foreach (['jtseo_title', 'jtseo_description', 'jtseo_canonical', 'jtseo_og_image', 'jtseo_robots_index', 'jtseo_robots_follow'] as $field) {
            $check($form->getField($field, 'attribs') !== false, "$context / $kind / $field");
        }
        $check($form->getField('existing', 'attribs') !== false, "$context / $kind preserves original fields");
    }
}
$params = ComponentHelper::getParams('com_jtseo');
$previous = $params->get('enable_article_fields', 1);
$params->set('enable_article_fields', 0);
$disabled = $newForm('com_content.article');
$plugin->onContentPrepareForm(new PrepareFormEvent('onContentPrepareForm', ['subject' => $disabled, 'data' => []]));
$check($disabled->getField('jtseo_title', 'attribs') === false, 'disabled article-fields setting is respected');
$params->set('enable_article_fields', $previous);
$foreign = $newForm('com_contact.contact');
$plugin->onContentPrepareForm(new PrepareFormEvent('onContentPrepareForm', ['subject' => $foreign, 'data' => []]));
$check($foreign->getField('jtseo_title', 'attribs') === false, 'unrelated forms remain unchanged');
echo "Completed $count checks on Joomla " . (new \Joomla\CMS\Version())->getShortVersion() . ", PHP " . PHP_VERSION . ".\n";
