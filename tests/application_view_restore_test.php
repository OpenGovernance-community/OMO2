<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/common/application_view_preferences.php';

function checkRestore(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$context = array('application' => 'documents', 'organizationId' => 1, 'holonId' => 674, 'canSavePersonal' => true);
$payload = array('organizationId' => 1, 'holonId' => 674, 'temporary' => array('scope' => 'descendants'), 'saved' => array('scope' => 'children'));
$_GET = array('restore_view' => json_encode($payload));
$withDefault = $context + array('defaultView' => array('scope' => 'contextual'));
checkRestore(omoApplicationViewPreferencesGetBrowserRestore($withDefault)['view']['scope'] === 'descendants', 'Temporary browser view wins.');
unset($payload['temporary']);
$_GET['restore_view'] = json_encode($payload);
checkRestore(omoApplicationViewPreferencesGetBrowserRestore($withDefault)['view']['scope'] === 'contextual', 'Server defaults precede legacy document views.');
checkRestore(omoApplicationViewPreferencesGetBrowserRestore($context)['view']['scope'] === 'children', 'Legacy view is kept when permitted.');
$context['canSavePersonal'] = false;
checkRestore(omoApplicationViewPreferencesGetBrowserRestore($context)['view'] === array(), 'Do not restore legacy personal views without permission.');
checkRestore(omoApplicationViewPreferencesGetBrowserRestore($context + array('isPvApplicationTab' => true)) === array(), 'PV view revisions are isolated.');
foreach (array('organizationId' => 2, 'holonId' => 675) as $key => $value) {
    $other = $context;
    $other[$key] = $value;
    checkRestore(omoApplicationViewPreferencesGetBrowserRestore($other) === array(), 'Context mismatch is rejected.');
}
foreach (array('open_document_id', 'open_event_id') as $key) {
    $_GET[$key] = 42;
    checkRestore(omoApplicationViewPreferencesGetBrowserRestore($context) === array(), 'Deep links keep their context.');
    unset($_GET[$key]);
}
$context['application'] = 'calendar';
$payload = array('organizationId' => 1, 'holonId' => 674, 'position' => array('date' => '2026-08-01', 'view' => 'month', 'scope' => 'contextual', 'unsafe' => 'ignored'));
$_GET['restore_view'] = json_encode($payload);
$restore = omoApplicationViewPreferencesGetBrowserRestore($context);
checkRestore($restore['position']['date'] === '2026-08-01', 'Calendar position is resolved before data access.');
checkRestore(!isset($restore['position']['unsafe']), 'Only display fields are accepted.');
checkRestore(omoApplicationViewPreferencesGetBrowserRestore($context + array('defaultView' => array('view' => 'week')))['position'] === array(), 'Configured view still wins over old session position.');
$payload['temporary'] = array('view' => 'day');
$_GET['restore_view'] = json_encode($payload);
$restore = omoApplicationViewPreferencesGetBrowserRestore($context);
checkRestore($restore['view']['view'] === 'day' && $restore['position'] === array(), 'Temporary filter overrides position.');
foreach (array('{broken', str_repeat('x', 4097), array('unexpected')) as $raw) {
    $_GET['restore_view'] = $raw;
    checkRestore(omoApplicationViewPreferencesGetBrowserRestore($context) === array(), 'Malformed payload falls back safely.');
}
echo "application_view_restore_test: OK\n";
