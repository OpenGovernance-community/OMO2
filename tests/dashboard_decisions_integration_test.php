<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, ArrayOrganization, ArrayUser, DecisionProcess, DecisionParticipant, DecisionResponse};

function dashboardIntegrationCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function dashboardIntegrationSave(DbObject $object): void
{
    $result = $object->save();
    dashboardIntegrationCheck(!empty($result['status']), (string)($result['text'] ?? 'Save failed'));
}

$organizations = new ArrayOrganization();
$organizations->load(array('limit' => 1));
$users = new ArrayUser();
$users->load(array('limit' => 1));
$organization = $organizations[0];
$user = $users[0];
$currentOrganizationId = (int)$organization->getId();
$currentUserId = (int)$user->getId();
$_SESSION['currentUser'] = $currentUserId;
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $decision = new DecisionProcess();
    foreach (array(
        'IDorganization' => $currentOrganizationId, 'IDuser' => $currentUserId,
        'title' => 'Dashboard integration fixture', 'decision_type' => DecisionProcess::TYPE_DECISION,
        'status' => DecisionProcess::STATUS_EVALUATION, 'evaluation_method' => DecisionProcess::METHOD_SIMPLE_VOTE,
        'visibility_type' => DecisionProcess::getDefaultVisibilityType(),
        'evaluation_start_at' => new DateTimeImmutable('-1 hour'),
    ) as $field => $value) {
        $decision->set($field, $value);
    }
    dashboardIntegrationSave($decision);
    $decisionId = (int)$decision->getId();
    $group = $decision->getPrimaryGroup(false);
    dashboardIntegrationCheck($group !== null, 'Saving a decision must provide an evaluation group.');

    $enabledAppHashes = array('decision' => true);
    $dashboardIsOrganizationHolon = true;
    $dashboardModuleScopeHolonIdMap = array();
    $dashboardDecisionUserEmail = '';
    $dashboardDecisionRows = array_filter(
        DecisionProcess::fetchListRowsForOrganization($currentOrganizationId, $currentUserId),
        static fn(array $row): bool => (int)$row['id'] === $decisionId
    );
    $dashboardDecisionPendingGroups = array();
    include dirname(__DIR__) . '/omo/api/dashboard/modules/data/decisions.php';
    dashboardIntegrationCheck($dashboardDecisionCounts === array('active' => 1, 'elaboration' => 0, 'pending' => 1), 'An owner must be reminded before the voting form creates a participant record, without counting evaluation as elaboration.');

    $participant = new DecisionParticipant();
    foreach (array('IDdecision_process' => $decisionId, 'IDuser' => $currentUserId, 'role' => DecisionParticipant::ROLE_OWNER, 'status' => DecisionParticipant::STATUS_ACTIVE, 'active' => 1) as $field => $value) {
        $participant->set($field, $value);
    }
    dashboardIntegrationSave($participant);
    $response = new DecisionResponse();
    foreach (array('IDdecision_process' => $decisionId, 'IDdecision_group' => $group->getId(), 'IDdecision_participant' => $participant->getId(), 'status' => DecisionResponse::STATUS_SUBMITTED, 'parameters' => array()) as $field => $value) {
        $response->set($field, $value);
    }
    dashboardIntegrationSave($response);
    $dashboardDecisionPendingGroups = array();
    include dirname(__DIR__) . '/omo/api/dashboard/modules/data/decisions.php';
    dashboardIntegrationCheck($dashboardDecisionCounts['pending'] === 0, 'A submitted response must clear the owner reminder.');

    $secondGroup = $decision->addDecisionGroup(DecisionProcess::METHOD_CONSENT, DecisionProcess::TYPE_DECISION, 'Second group');
    dashboardIntegrationCheck($secondGroup !== null, 'The fixture must support a second evaluation group.');
    $dashboardDecisionPendingGroups = array();
    include dirname(__DIR__) . '/omo/api/dashboard/modules/data/decisions.php';
    dashboardIntegrationCheck($dashboardDecisionCounts['pending'] === 1, 'A second unanswered group must restore one reminder for the decision.');
    $participant->set('status', DecisionParticipant::STATUS_REVOKED);
    dashboardIntegrationSave($participant);
    $dashboardDecisionPendingGroups = array();
    include dirname(__DIR__) . '/omo/api/dashboard/modules/data/decisions.php';
    dashboardIntegrationCheck($dashboardDecisionCounts['pending'] === 0, 'Revoked participation must not require a vote.');
    $decision->set('status', DecisionProcess::STATUS_CONSULTATION);
    $decision->set('consultation_start_at', new DateTimeImmutable('-1 hour'));
    $decision->set('evaluation_start_at', null);
    dashboardIntegrationSave($decision);
    $dashboardDecisionRows = array_filter(
        DecisionProcess::fetchListRowsForOrganization($currentOrganizationId, $currentUserId),
        static fn(array $row): bool => (int)$row['id'] === $decisionId
    );
    include dirname(__DIR__) . '/omo/api/dashboard/modules/data/decisions.php';
    dashboardIntegrationCheck($dashboardDecisionCounts === array('active' => 1, 'elaboration' => 1, 'pending' => 0), 'The consultation phase must count as elaboration without requiring a vote.');
    echo "dashboard_decisions_integration_test: OK\n";
} finally {
    $pdo->rollBack();
}
