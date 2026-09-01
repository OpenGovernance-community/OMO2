<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/holon.class.php';
require_once dirname(__DIR__) . '/class/dbobject/userholon.class.php';
require_once dirname(__DIR__) . '/omo/api/dashboard/modules/registry.php';

use dbObject\UserHolon;
use dbObject\Holon;

function assertDashboardLayout(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$layout = UserHolon::normalizeDashboardLayout(array(
    array('id' => 'wide', 'type' => 'projects', 'row' => 0, 'column' => 0, 'rowSpan' => 1, 'columnSpan' => 2),
    array('id' => 'collision', 'type' => 'team', 'row' => 0, 'column' => 1, 'rowSpan' => 1, 'columnSpan' => 1),
    array('id' => 'vertical', 'type' => 'stats', 'row' => 1, 'column' => 1, 'rowSpan' => 2, 'columnSpan' => 1),
    array('id' => 'square', 'type' => 'rules', 'row' => 3, 'column' => 0, 'rowSpan' => 2, 'columnSpan' => 2),
    array('id' => 'unknown', 'type' => 'unknown', 'row' => 4, 'column' => 0, 'rowSpan' => 1, 'columnSpan' => 1),
));

assertDashboardLayout(count($layout) === 2, 'Only valid, non-overlapping modules must remain.');
assertDashboardLayout($layout[0]['id'] === 'wide' && $layout[0]['columnSpan'] === 2, 'A horizontal double module must be preserved.');
assertDashboardLayout($layout[1]['id'] === 'vertical' && $layout[1]['rowSpan'] === 2, 'A vertical double module must be preserved.');
assertDashboardLayout(count(UserHolon::getDefaultDashboardLayout()) === 8, 'The default layout must expose the eight initial modules.');
assertDashboardLayout(array_keys(UserHolon::getDashboardModuleCatalog()) === array_keys(omoDashboardGetModuleDefinitions()), 'The persistence catalog and UI registry must expose the same module identifiers.');

$templateKey = UserHolon::makeDashboardTemplateKey(1, 'Facilitateur');
$templateLayouts = UserHolon::normalizeDashboardTemplateLayouts(array(
    $templateKey => array(),
    'invalid' => UserHolon::getDefaultDashboardLayout(),
));
assertDashboardLayout(UserHolon::normalizeDashboardTemplateKey($templateKey) === $templateKey, 'A template dashboard key must remain stable.');
assertDashboardLayout($templateLayouts === array($templateKey => array()), 'An explicit empty template layout must be preserved.');

$preference = new UserHolon();
assertDashboardLayout($preference->getDashboardLayoutPreference() === null, 'An absent personal layout must leave room for default fallbacks.');
$holon = new Holon();
assertDashboardLayout($holon->getDashboardDefaultLayout() === null, 'A holon without a configured layout must not override fallbacks.');
$holon->setDashboardDefaultLayout(array());
assertDashboardLayout($holon->getDashboardDefaultLayout() === array(), 'An empty holon default layout must be preserved as an explicit preference.');

echo "user_holon_dashboard_layout_test: OK\n";
