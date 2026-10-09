<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, ArrayOrganization, ArrayUser, DecisionProcess, DecisionParticipant, DecisionResponse};

function attentionCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function attentionSave(DbObject $object): void
{
    $result = $object->save();
    attentionCheck(!empty($result['status']), (string)($result['text'] ?? 'Save failed'));
}

$organizations = new ArrayOrganization();
$organizations->load(['limit' => 2]);
$users = new ArrayUser();
$users->load(['limit' => 2]);
$organizationId = (int)$organizations[0]->getId();
$userId = (int)$users[0]->getId();
$otherUserId = (int)$users[1]->getId();
$_SESSION['currentUser'] = $userId;
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $decision = new DecisionProcess();
    foreach ([
        'IDorganization' => $organizationId, 'IDuser' => $otherUserId,
        'title' => 'Attention integration fixture', 'decision_type' => DecisionProcess::TYPE_DECISION,
        'status' => DecisionProcess::STATUS_EVALUATION, 'evaluation_method' => DecisionProcess::METHOD_SIMPLE_VOTE,
        'visibility_type' => DecisionProcess::getDefaultVisibilityType(),
        'evaluation_start_at' => new DateTimeImmutable('-1 hour'),
    ] as $field => $value) $decision->set($field, $value);
    attentionSave($decision);
    $decisionId = (int)$decision->getId();
    $pending = static function () use ($organizationId, $userId, $decisionId): array {
        return array_values(array_filter(DecisionProcess::getPendingEvaluationsForUser($organizationId, $userId),
            static fn(array $row): bool => (int)$row['id'] === $decisionId));
    };
    attentionCheck($pending() === [], 'Uninvited users must not receive a signal.');
    $participant = new DecisionParticipant();
    foreach (['IDdecision_process' => $decisionId, 'IDuser' => $userId, 'role' => DecisionParticipant::ROLE_PARTICIPANT,
        'status' => DecisionParticipant::STATUS_INVITED, 'active' => 1] as $field => $value) $participant->set($field, $value);
    attentionSave($participant);
    $group = $decision->getPrimaryGroup(true);
    attentionCheck(count($pending()) === 1, 'An invited user must see an unanswered decision outside the current space.');
    attentionCheck($pending()[0]['pending_group_id'] === (int)$group->getId(), 'The first unanswered group must be identified.');
    $_GET['cid'] = 999999;
    attentionCheck(count($pending()) === 1, 'Navigation context must not filter reminders.');
    $otherPending = DecisionProcess::getPendingEvaluationsForUser((int)$organizations[1]->getId(), $userId);
    attentionCheck(!in_array($decisionId, array_column($otherPending, 'id')), 'Another organization must not expose this decision.');

    $response = new DecisionResponse();
    foreach (['IDdecision_process' => $decisionId, 'IDdecision_group' => $group->getId(),
        'IDdecision_participant' => $participant->getId(), 'status' => DecisionResponse::STATUS_DRAFT,
        'parameters' => []] as $field => $value) $response->set($field, $value);
    attentionSave($response);
    attentionCheck(count($pending()) === 1, 'A draft must still require submission.');
    $response->set('status', DecisionResponse::STATUS_SUBMITTED);
    attentionSave($response);
    attentionCheck($pending() === [], 'A submitted response must clear the signal.');
    $secondGroup = $decision->addDecisionGroup(DecisionProcess::METHOD_CONSENT, DecisionProcess::TYPE_DECISION, 'Second ballot');
    attentionCheck($secondGroup !== null && count($pending()) === 1, 'A partial submission must still signal one decision.');
    attentionCheck($pending()[0]['pending_group_id'] === (int)$secondGroup->getId(), 'The remaining unanswered group must be identified.');
    $secondGroup->set('active', 0);
    attentionSave($secondGroup);
    attentionCheck($pending() === [], 'Inactive groups must not require responses.');
    $response->set('status', DecisionResponse::STATUS_INVALIDATED);
    $response->set('submitted_at', new DateTimeImmutable('-1 minute'));
    attentionSave($response);
    attentionCheck(count($pending()) === 1, 'Invalidation must restore the signal despite a previous submission date.');
    foreach ([DecisionParticipant::STATUS_DECLINED, DecisionParticipant::STATUS_REVOKED] as $status) {
        $participant->set('status', $status);
        attentionSave($participant);
        attentionCheck($pending() === [], 'Declined and revoked invitations must not require a response.');
    }
    $participant->set('status', DecisionParticipant::STATUS_INVITED);
    $participant->set('active', 0);
    attentionSave($participant);
    attentionCheck($pending() === [], 'Inactive invitations must not require a response.');
    $participant->set('active', 1);
    $participant->set('IDuser', null);
    $participant->set('email', $users[0]->getScopedEmail($organizationId));
    attentionSave($participant);
    attentionCheck(count($pending()) === 1, 'Invitations by scoped email must also signal the current user.');
    $decision->set('evaluation_start_at', new DateTimeImmutable('+1 day'));
    attentionSave($decision);
    attentionCheck($pending() === [], 'Future evaluations must not require a response yet.');
    $decision->set('evaluation_start_at', new DateTimeImmutable('-1 hour'));
    $decision->set('evaluation_end_at', new DateTimeImmutable('-1 minute'));
    attentionSave($decision);
    $before = new DecisionProcess();
    $before->load($decisionId, true);
    attentionCheck($pending() === [], 'Expired evaluations must not signal while waiting for lifecycle synchronization.');
    $after = new DecisionProcess();
    $after->load($decisionId, true);
    attentionCheck($after->get('status') === $before->get('status'), 'Background signal calculation must not mutate lifecycle state.');
    attentionCheck(DecisionProcess::getPendingEvaluationsForUser($organizationId, 0) === [], 'Anonymous users must not receive signals.');
    echo "attention_decisions_integration_test: OK\n";
} finally {
    $pdo->rollBack();
}
