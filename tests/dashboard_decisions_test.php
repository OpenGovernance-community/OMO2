<?php
declare(strict_types=1);

// This isolated test intentionally does not initialize the application or database.
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'dbObject\\')) {
        $path = dirname(__DIR__) . '/class/dbobject/' . strtolower(substr($class, 9)) . '.class.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});
require_once dirname(__DIR__) . '/omo/api/dashboard/modules/registry.php';

use dbObject\DecisionGroup;
use dbObject\DecisionParticipant;
use dbObject\DecisionProcess;
use dbObject\DecisionResponse;
use dbObject\Organization;
use dbObject\UserHolon;

function assertDashboardDecisions(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class DashboardDecisionFixture extends DecisionProcess
{
    public array $groups = array();
    public function getDecisionGroups($activeOnly = false)
    {
        return $this->groups;
    }
}

class DashboardDecisionGroupFixture extends DecisionGroup
{
    public array $responses = array();
    public function getResponses($status = '')
    {
        return $this->responses;
    }
}

$decision = new DashboardDecisionFixture();
$decision->hydrateFromDatabaseRow(array('id' => 100), true);
$decision->set('status', DecisionProcess::STATUS_EVALUATION);
$decision->set('evaluation_start_at', new DateTimeImmutable('-1 hour'));
$participant = new DecisionParticipant();
$participant->hydrateFromDatabaseRow(array('id' => 200), true);
$participant->set('IDdecision_process', 100);
$participant->set('active', 1);
$participant->set('status', DecisionParticipant::STATUS_ACTIVE);
$firstGroup = new DashboardDecisionGroupFixture();
$firstGroup->hydrateFromDatabaseRow(array('id' => 300), true);
$firstGroup->set('evaluation_method', DecisionProcess::METHOD_SIMPLE_VOTE);
$secondGroup = new DashboardDecisionGroupFixture();
$secondGroup->hydrateFromDatabaseRow(array('id' => 301), true);
$secondGroup->set('evaluation_method', DecisionProcess::METHOD_CONSENT);
$decision->groups = array($firstGroup, $secondGroup);
assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === 300, 'An unanswered evaluation must require a response.');

$response = new DecisionResponse();
$response->set('IDdecision_participant', 200);
$response->set('status', DecisionResponse::STATUS_DRAFT);
$firstGroup->responses = array($response);
assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === 300, 'A saved draft must still require submission.');
$response->set('status', DecisionResponse::STATUS_SUBMITTED);
assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === 301, 'Submitting one group must not hide an unanswered second group.');
$secondGroup->responses = array($response);
assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === null, 'A fully submitted evaluation must no longer require a response.');
$response->set('status', DecisionResponse::STATUS_INVALIDATED);
$response->set('submitted_at', new DateTimeImmutable('-1 minute'));
assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === 300, 'An invalidated response must require a new submission even when it has a submission date.');
$response->set('IDdecision_participant', 201);
$response->set('status', DecisionResponse::STATUS_SUBMITTED);
assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === 300, 'Another participant response must not satisfy the current participant.');

foreach (array(DecisionParticipant::STATUS_DECLINED, DecisionParticipant::STATUS_REVOKED) as $status) {
    $participant->set('status', $status);
    assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === null, 'Declined or revoked invitations must not require a response.');
}
$participant->set('status', DecisionParticipant::STATUS_ACTIVE);
$participant->set('active', 0);
assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === null, 'Inactive participants must not require a response.');
$participant->set('active', 1);
$participant->set('IDdecision_process', 101);
assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === null, 'A participant from another decision must not be counted.');
$participant->set('IDdecision_process', 100);
$decision->set('evaluation_start_at', new DateTimeImmutable('+1 day'));
assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === null, 'A future evaluation must not yet require a response.');
$decision->set('evaluation_start_at', new DateTimeImmutable('-1 hour'));
foreach (array(DecisionProcess::STATUS_DRAFT, DecisionProcess::STATUS_SCHEDULED, DecisionProcess::STATUS_CONSULTATION, DecisionProcess::STATUS_RESULTS, DecisionProcess::STATUS_ARCHIVED) as $status) {
    $decision->set('status', $status);
    assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === null, 'Only the evaluation phase must require a vote.');
}
$decision->set('status', DecisionProcess::STATUS_EVALUATION);
$decision->groups = array($firstGroup);
$firstGroup->set('evaluation_method', DecisionProcess::METHOD_CONSULTATION_ONLY);
assertDashboardDecisions($decision->getPendingEvaluationGroupIdForParticipant($participant) === null, 'Consultation-only groups must not require votes.');

assertDashboardDecisions(array_keys(UserHolon::getDashboardModuleCatalog()) === array_keys(omoDashboardGetModuleDefinitions()), 'The saved layout and UI must expose the same modules.');
foreach (array('contextual', 'children', 'descendants') as $scope) {
    $layout = UserHolon::normalizeDashboardLayout(array(array('type' => 'decisions', 'row' => 0, 'column' => 0, 'settings' => array('scope' => $scope))));
    assertDashboardDecisions(count($layout) === 1 && $layout[0]['settings']['scope'] === $scope, 'Each supported decisions scope must survive layout persistence.');
}
assertDashboardDecisions(UserHolon::normalizeDashboardModuleSettings('decisions', array('scope' => 'invalid'))['scope'] === 'contextual', 'An invalid decisions scope must fall back to local.');

$enabledAppHashes = array('decision' => true);
$currentUserId = 42;
$currentOrganizationId = 1;
$organization = new Organization();
$organization->hydrateFromDatabaseRow(array('id' => 1), true);
$dashboardDecisionUserEmail = '';
$dashboardDecisionRows = array();
foreach (array(
    array(1, 10, 'draft'), array(2, 10, 'scheduled'), array(3, 10, 'consultation'),
    array(4, 10, 'evaluation'), array(5, 10, 'evaluation'), array(6, 10, 'results'),
    array(7, 10, 'archived'), array(8, 11, 'evaluation'), array(9, 12, 'evaluation'),
    array(10, null, 'consultation'), array(11, 13, 'evaluation'),
) as [$id, $holonId, $status]) {
    $dashboardDecisionRows[] = array('id' => $id, 'IDholon' => $holonId, 'IDorganization' => 1, 'IDuser' => 42, 'title' => 'Decision ' . $id, 'status' => $status);
}
$dashboardDecisionPendingGroups = array(4 => 300, 5 => null, 8 => 301, 9 => null, 11 => 302);
$dashboardIsOrganizationHolon = false;
foreach (array(
    array(array(10), array('active' => 5, 'elaboration' => 1, 'pending' => 1)),
    array(array(10, 11), array('active' => 6, 'elaboration' => 1, 'pending' => 2)),
    array(array(10, 11, 12), array('active' => 7, 'elaboration' => 1, 'pending' => 2)),
) as [$holonIds, $expectedCounts]) {
    $dashboardModuleScopeHolonIdMap = array_fill_keys($holonIds, true);
    include dirname(__DIR__) . '/omo/api/dashboard/modules/data/decisions.php';
    assertDashboardDecisions($dashboardDecisionCounts === $expectedCounts, 'Counts must follow the selected scope and exclude finished decisions and other branches.');
    assertDashboardDecisions($dashboardDecisionItems[0]['id'] === 4, 'Pending decisions must appear first.');
    $elaborationIds = array_column(array_filter($dashboardDecisionItems, static fn(array $item): bool => in_array('elaboration', $item['filters'], true)), 'id');
    assertDashboardDecisions($elaborationIds === array(3), 'The elaboration filter must include consultation decisions and exclude draft, scheduled and evaluation decisions.');
}
$dashboardIsOrganizationHolon = true;
include dirname(__DIR__) . '/omo/api/dashboard/modules/data/decisions.php';
assertDashboardDecisions($dashboardDecisionCounts['active'] === 8, 'Organization-level decisions must be included at the organization root.');
assertDashboardDecisions($dashboardDecisionCounts['elaboration'] === 2, 'Organization-level elaboration decisions must be counted at the organization root.');
$enabledAppHashes = array();
include dirname(__DIR__) . '/omo/api/dashboard/modules/data/decisions.php';
assertDashboardDecisions($dashboardDecisionCounts === array('active' => 0, 'elaboration' => 0, 'pending' => 0) && $dashboardDecisionItems === array(), 'An unavailable decision app must not load dashboard data.');

function omoApiEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function t($key, $variables, $lang, $sourceLang): string
{
    return $key;
}
$lang = $sourceLang = array();
$dashboardMetricLabels = array('decisions' => array('active' => 'Actives', 'elaboration' => 'En elaboration', 'pending' => 'A me prononcer'));
$dashboardModuleScope = 'children';
$dashboardDecisionCounts = array('active' => 1, 'elaboration' => 0, 'pending' => 1);
$dashboardDecisionItems = array(array('id' => 4, 'title' => '<script>alert("test")</script>', 'filters' => array('active', 'pending')));
ob_start();
include dirname(__DIR__) . '/omo/api/dashboard/modules/decisions.php';
$html = (string)ob_get_clean();
assertDashboardDecisions(substr_count($html, 'data-omo-dashboard-filter=') === 3, 'The rendered module must expose all three metric filters.');
assertDashboardDecisions(str_contains($html, 'data-omo-dashboard-filter="elaboration"') && str_contains($html, 'En elaboration'), 'The second metric must render the elaboration label and filter.');
assertDashboardDecisions(!str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;'), 'Decision titles must be escaped in the dashboard.');
assertDashboardDecisions(str_contains($html, 'data-omo-personal-space-route-token="decision-d4"') && str_contains($html, 'data-omo-personal-space-forced-scope="children"'), 'Opening a decision must preserve the module scope.');

echo "dashboard_decisions_test: OK\n";
