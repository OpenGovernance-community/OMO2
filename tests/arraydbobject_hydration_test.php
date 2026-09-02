<?php
declare(strict_types=1);

use dbObject\ArrayDbObject;
use dbObject\DbObject;

require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
require_once dirname(__DIR__) . '/class/dbobject/arraydbobject.class.php';

final class ArrayDbObjectHydrationProbe extends DbObject
{
    public static array $rows = [
        1 => [
            'id' => 1,
            'title' => 'Reloaded title',
            'description' => 'Reloaded description',
        ],
    ];

    public int $loadCalls = 0;

    public static function tableName()
    {
        return 'arraydbobjecthydrationprobe';
    }

    public static function rules()
    {
        return [
            [['id'], 'integer'],
            [['title', 'description'], 'string'],
        ];
    }

    public static function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'description' => 'Description',
        ];
    }

    public function load($id, $forced = false)
    {
        $this->loadCalls += 1;
        $id = (int)$id;

        return isset(self::$rows[$id]) && $this->hydrateFromDatabaseRow(self::$rows[$id], true);
    }
}

final class ArrayDbObjectHydrationProbeCollection extends ArrayDbObject
{
    public static function objectName()
    {
        return ArrayDbObjectHydrationProbe::class;
    }
}

function assertArrayDbObjectHydration(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$partial = new ArrayDbObjectHydrationProbe();
assertArrayDbObjectHydration(
    $partial->hydrateFromDatabaseRow(['id' => 1, 'title' => 'Preloaded title'], false),
    'Partial hydration must accept a row with an identifier.'
);
assertArrayDbObjectHydration($partial->isFieldLoaded('title'), 'The selected field must be marked as loaded.');
assertArrayDbObjectHydration(!$partial->isFieldLoaded('description'), 'A missing field must remain marked as unloaded.');
assertArrayDbObjectHydration($partial->get('title') === 'Preloaded title', 'A preloaded field must be returned directly.');
assertArrayDbObjectHydration($partial->loadCalls === 0, 'Reading a preloaded field must not reload the object.');
assertArrayDbObjectHydration($partial->get('description') === 'Reloaded description', 'A missing field must trigger a full reload.');
assertArrayDbObjectHydration($partial->loadCalls === 1, 'Only the accessed partial object must be reloaded.');
assertArrayDbObjectHydration($partial->get('title') === 'Reloaded title', 'The full reload must replace partial values.');

$complete = new ArrayDbObjectHydrationProbe();
assertArrayDbObjectHydration(
    $complete->hydrateFromDatabaseRow(ArrayDbObjectHydrationProbe::$rows[1], true),
    'Complete hydration must accept a full row.'
);
assertArrayDbObjectHydration($complete->isFieldLoaded('description'), 'A complete row must expose all fields as loaded.');
assertArrayDbObjectHydration($complete->get('description') === 'Reloaded description', 'A complete row must expose its values.');
assertArrayDbObjectHydration($complete->loadCalls === 0, 'A complete row must not reload when reading its fields.');

$arrayDbObjectSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/arraydbobject.class.php');
assertArrayDbObjectHydration(
    strpos($arrayDbObjectSource, 'function loadHydrated(') !== false,
    'Collections must expose a dedicated hydration loader.'
);
assertArrayDbObjectHydration(
    strpos($arrayDbObjectSource, "\$params['hydrate']") !== false,
    'Collections must support the hydration option in their regular loader.'
);
assertArrayDbObjectHydration(
    strpos($arrayDbObjectSource, 'hydrateFromDatabaseRow($row, $hydrate === true)') !== false,
    'Hydrated collection rows must populate their dbObjects directly.'
);

$calendarSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/calendar/index.php');
assertArrayDbObjectHydration(
    strpos($calendarSource, 'loadForOrganizationDateRange($organizationId, $calendarEarliestEventEndAt, null, false, [') !== false,
    'Calendar lists must hydrate their date-bounded event rows in one collection query.'
);

$calDavSource = (string)file_get_contents(dirname(__DIR__) . '/common/caldav.php');
assertArrayDbObjectHydration(
    substr_count($calDavSource, 'loadForOrganization($organizationId, false, true)') === 2,
    'CalDAV event loops must hydrate all event fields before iteration.'
);

$documentCollectionSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/arraydocument.class.php');
assertArrayDbObjectHydration(
    substr_count($documentCollectionSource, "'hydrate' => \\dbObject\\Document::getCollectionHydrationFields()") === 3,
    'Document collections must preload their lightweight metadata fields.'
);

$decisionCollectionSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/arraydecisionprocess.class.php');
assertArrayDbObjectHydration(
    strpos($decisionCollectionSource, "'hydrate' => true") !== false
        && strpos($decisionCollectionSource, 'hydrateFromDatabaseRow($row, true)') !== false,
    'Decision collections must use the rows already returned by their list query.'
);

$projectsIndexSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/index.php');
assertArrayDbObjectHydration(
    strpos($projectsIndexSource, '$projectHolons->load([') !== false
        && strpos($projectsIndexSource, "'hydrate' => true") !== false
        && strpos($projectsIndexSource, '$projectHolonsById') !== false,
    'Project lists must batch-load their distinct holons before rendering.'
);
assertArrayDbObjectHydration(
    strpos($projectsIndexSource, '$projectResponsibles->load([') !== false
        && strpos($projectsIndexSource, "'hydrate' => ['firstname', 'lastname', 'username', 'email']") !== false
        && strpos($projectsIndexSource, '$projectResponsiblesById') !== false,
    'Project lists must batch-load only the responsible user fields they render.'
);

$documentCollectionSource = (string)file_get_contents(dirname(__DIR__) . '/class/dbobject/arraydocument.class.php');
assertArrayDbObjectHydration(
    strpos($documentCollectionSource, 'function loadListMetadataForOrganization') !== false
        && strpos($documentCollectionSource, "'documentsWithChildren'") !== false,
    'Document lists must compute folder activity and child existence from one metadata collection.'
);

$documentsIndexSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/documents/index.php');
assertArrayDbObjectHydration(
    strpos($documentsIndexSource, 'ArrayDocument::loadListMetadataForOrganization') !== false
        && strpos($documentsIndexSource, "'canDelete' => \$canManageLifecycle") !== false
        && strpos($documentsIndexSource, 'canManageInOrganizationContextWithVisibilityRule') !== false,
    'The documents page must use the batched metadata instead of querying children per item.'
);
assertArrayDbObjectHydration(
    strpos($documentsIndexSource, 'data-omo-documents-loading-indicator') !== false
        && strpos($documentsIndexSource, 'omoSetDocumentsPanelLoadingState') !== false
        && strpos($documentsIndexSource, 'omoSetPanelResultsLoadingSkeleton') !== false
        && strpos($documentsIndexSource, "showResultsPlaceholder: true") !== false,
    'Changing a documents view must expose a visible loading state and replace the list with a skeleton.'
);

$omoAppSource = (string)file_get_contents(dirname(__DIR__) . '/omo/assets/js/app.js');
assertArrayDbObjectHydration(
    strpos($omoAppSource, 'function omoSetPanelResultsLoadingSkeleton') !== false,
    'OMO must expose one shared skeleton helper for reloaded result panels.'
);
foreach (array('calendar', 'decision', 'policy', 'stats', 'team') as $applicationName) {
    $applicationSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/' . $applicationName . '/index.php');
    assertArrayDbObjectHydration(
        strpos($applicationSource, 'omoSetPanelResultsLoadingSkeleton') !== false,
        ucfirst($applicationName) . ' filters must use the shared loading skeleton while reloading.'
    );
}
$projectsScriptSource = (string)file_get_contents(dirname(__DIR__) . '/omo/api/projects/projects.js');
assertArrayDbObjectHydration(
    strpos($projectsScriptSource, 'omoSetPanelResultsLoadingSkeleton') !== false,
    'Project filters must use the shared loading skeleton while reloading.'
);

echo "arraydbobject_hydration_test: OK\n";
