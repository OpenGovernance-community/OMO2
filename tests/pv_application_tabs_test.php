<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/userholon.class.php';
require_once dirname(__DIR__) . '/class/dbobject/documentapplicationtab.class.php';

function assertPvApplicationTabs(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$rules = \dbObject\DocumentApplicationTab::rules();
$integerFields = [];
$foreignKeyFields = [];
foreach ($rules as $rule) {
    if (($rule[1] ?? '') === 'integer') {
        $integerFields = array_merge($integerFields, $rule[0] ?? []);
    }
    if (($rule[1] ?? '') === 'fk') {
        $foreignKeyFields = array_merge($foreignKeyFields, $rule[0] ?? []);
    }
}
assertPvApplicationTabs(in_array('IDdocument', $foreignKeyFields, true), 'The PV document link must be a foreign key.');
assertPvApplicationTabs(in_array('IDapplication', $foreignKeyFields, true), 'The application link must be a foreign key.');
assertPvApplicationTabs(!in_array('IDdocument', $integerFields, true), 'The PV foreign key must not be exposed as a plain integer field.');
assertPvApplicationTabs(!in_array('IDapplication', $integerFields, true), 'The application foreign key must not be exposed as a plain integer field.');

$tab = new \dbObject\DocumentApplicationTab();
$tab->setViewParametersArray([
    'scope' => ' descendants ',
    'view' => 'kanban',
    'invalid key' => 'ignored',
]);
$view = $tab->getViewParametersArray();
assertPvApplicationTabs(($view['scope'] ?? '') === 'descendants', 'Meeting view values must be normalized.');
assertPvApplicationTabs(($view['view'] ?? '') === 'kanban', 'Meeting view modes must be retained.');
assertPvApplicationTabs(!array_key_exists('invalid key', $view), 'Invalid meeting view keys must be discarded.');

$migrationPath = dirname(__DIR__) . '/sql/2026-09-05-02-document-application-tabs.sql';
$migration = (string)file_get_contents($migrationPath);
assertPvApplicationTabs(str_starts_with($migration, '-- @migration'), 'The application tab schema must be an automatic migration.');
assertPvApplicationTabs(strpos($migration, 'UNIQUE KEY `uniq_document_application_tab`') !== false, 'One application must produce one tab per PV.');

$documentSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/document.class.php');
assertPvApplicationTabs(strpos($documentSource, 'copyPvApplicationTabsFromTemplate') !== false, 'PV templates must copy their application tabs.');

$editorSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/pv/editor.php');
assertPvApplicationTabs(strpos($editorSource, '$document->getPvContextHolonId()') !== false, 'Application tabs must use the holon associated with the PV.');
assertPvApplicationTabs(strpos($editorSource, 'data-omo-pv-application-tab-remove') === false, 'Application tabs must not expose a direct remove button.');
assertPvApplicationTabs(strpos($editorSource, 'Promise.allSettled(operations)') !== false, 'The application picker must reconcile additions and removals safely.');
assertPvApplicationTabs(strpos($editorSource, '/omo/assets/images/documents/pv.png') !== false, 'The PV tab must display the document PV icon.');
assertPvApplicationTabs(strpos($editorSource, '--param-tabs-tab-background-active') !== false, 'Active application tabs must have a visible colored state.');
assertPvApplicationTabs(strpos($editorSource, ".omo-pv-editor__application-workspace {\n        grid-column: 3;") !== false, 'Application tabs must render in the right-hand editor column.');
assertPvApplicationTabs(strpos($editorSource, 'const pvEditorSwitchableSurfaces = [mainPanel].filter(Boolean);') !== false, 'Application tabs must only replace the main PV panel.');
assertPvApplicationTabs(strpos($editorSource, "root.querySelector('.omo-pv-editor__sidebar'), root.querySelector('.omo-pv-editor__resizer'), mainPanel") === false, 'Application tabs must keep the agenda, timer, and resizer visible.');

$viewPreferencesSource = (string)file_get_contents(dirname(__DIR__) . '/common/application_view_preferences.php');
assertPvApplicationTabs(strpos($viewPreferencesSource, "\$_GET['pv_application_tab_id']") !== false, 'Application pages must resolve their PV-specific database view.');

$appSource = (string)file_get_contents(dirname(__DIR__) . '/omo/assets/js/app.js');
assertPvApplicationTabs(strpos($appSource, "searchParams.set('pv_application_tab_id'") !== false, 'Dynamic app refreshes must retain the PV tab context.');
assertPvApplicationTabs(strpos($appSource, 'function omoFindApplicationRoot') !== false, 'Application scripts must resolve the active PV tab instance.');

$projectsSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/projects.js');
assertPvApplicationTabs(strpos($projectsSource, "omoFindApplicationRoot('omo-projects-root')") !== false, 'Project tabs must initialize inside the PV workspace.');

$documentsIndexSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/index.php');
assertPvApplicationTabs(strpos($documentsIndexSource, 'hideHeader: true') !== false, 'The PV top sheet must not repeat its title and close action.');

$calendarIndexSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/index.php');
assertPvApplicationTabs(strpos($calendarIndexSource, 'hideHeader: true') !== false, 'PV editors opened from Calendar must also hide the repeated header.');

echo "pv_application_tabs_test: OK\n";
