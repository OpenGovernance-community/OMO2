<?php
declare(strict_types=1);

// Local MariaDB integration test. Every fixture is rolled back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, ArrayDbObject, ArrayOrganization, ArrayUserOrganization, Holon, Rule, History,
    DecisionProcess, DecisionProposal, DecisionParticipant, DecisionResponse, DecisionGovernanceAction,
    DeferredProposal, Document, DocumentPvPoint, Event};

function provenanceCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function provenanceSave(DbObject $object): void {
    $result = $object->save();
    provenanceCheck(!empty($result['status']), (string)($result['text'] ?? 'Save failed'));
}
function provenanceSet(DbObject $object, array $values): void {
    foreach ($values as $field => $value) $object->set($field, $value);
}

$organizations = new ArrayOrganization();
$organizations->load();
foreach ($organizations as $candidate) {
    $root = $candidate->getStructuralRootHolon();
    $members = new ArrayUserOrganization();
    $members->loadActiveForOrganization((int)$candidate->getId(), true);
    if ($root instanceof Holon && count($members) >= 2) { $organization = $candidate; break; }
}
provenanceCheck(isset($organization), 'A structured local organization is required.');
$organizationId = (int)$organization->getId();
$authorId = (int)$members[0]->get('IDuser');
$managerId = (int)$members[1]->get('IDuser');
$_SESSION['currentUser'] = $managerId;
$_SESSION['currentOrganization'] = $organizationId;
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $holon = new Holon();
    provenanceSet($holon, ['name' => 'Provenance fixture', 'IDholon_org' => $root->getId(),
        'IDholon_parent' => $root->getId(), 'IDtypeholon' => 2]);
    provenanceSave($holon);
    $decision = new DecisionProcess();
    provenanceSet($decision, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser' => $managerId,
        'title' => 'Provenance decision', 'decision_type' => DecisionProcess::TYPE_DECISION,
        'status' => DecisionProcess::STATUS_RESULTS, 'evaluation_method' => DecisionProcess::METHOD_CONSENT,
        'visibility_type' => DecisionProcess::getDefaultVisibilityType(), 'parameters' => ['workflow_type' => DecisionProcess::WORKFLOW_GOVERNANCE]]);
    provenanceSave($decision);
    $group = $decision->getPrimaryGroup(true);
    $proposal = new DecisionProposal();
    provenanceSet($proposal, ['IDdecision_group' => $group->getId(), 'IDuser_author' => $authorId,
        'title' => 'Provenance proposal', 'active' => 1]);
    provenanceSave($proposal);
    $state = ['IDholon' => (int)$holon->getId(), 'title' => 'Collective rule', 'description' => '<p>Rule content</p>',
        'scope' => Rule::SCOPE_LOCAL, 'review_date' => '2027-10-12', 'expiration_date' => '2028-10-12'];
    $change = new DeferredProposal();
    provenanceSet($change, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser_author' => $authorId,
        'IDdecision_proposal' => $proposal->getId(), 'target_type' => 'rule', 'operation' => 'create',
        'status' => DeferredProposal::STATUS_PENDING, 'after_state' => $state]);
    provenanceSave($change);
    $result = DeferredProposal::applyAcceptedForDecision($decision);
    provenanceCheck(!empty($result['status']) && $result['applied'] === 1, 'Accepted rule must be applied.');
    $change->load($change->getId(), true);
    $rule = new Rule();
    provenanceCheck($rule->load((int)$change->get('target_id')), 'Created rule must exist.');
    provenanceCheck((int)$rule->get('IDuser_creation') === $authorId, 'Creator must be the proposer, not the organizer or current reader.');
    provenanceCheck((int)$rule->get('IDuser_modification') === $authorId, 'First editor must match the proposer.');
    provenanceCheck($rule->getCreationContext()['id'] === (int)$decision->getId(), 'Creation must retain its decision.');
    provenanceCheck(str_ends_with(History::getResourceReferenceUrl('decision', (int)$decision->getId(), $organizationId), '#decision-v' . $decision->getId()), 'Provenance must link to the decision results.');
    provenanceCheck(str_ends_with(History::getResourceReferenceUrl('rule', (int)$rule->getId(), $organizationId), '#policy-r' . $rule->getId()), 'Rule impact must open the specific rule.');
    $conflictingChange = new DeferredProposal();
    provenanceSet($conflictingChange, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser_author' => $authorId,
        'IDdecision_proposal' => $proposal->getId(), 'target_type' => 'rule', 'operation' => 'update', 'target_id' => $rule->getId(),
        'status' => DeferredProposal::STATUS_PENDING, 'before_state' => array_merge($state, ['title' => 'Stale title']),
        'after_state' => array_merge($state, ['title' => 'Conflicting title'])]);
    provenanceSave($conflictingChange);
    $conflictResult = DeferredProposal::applyBatch([$conflictingChange], (int)$holon->getId(), $managerId, 'decision:' . $decision->getId());
    provenanceCheck(!empty($conflictResult['conflict']), 'A stale snapshot must remain a conflict.');
    History::recordDecisionCompletion($decision);
    History::recordDecisionCompletion($decision);
    $getLogs = static function (DecisionProcess $target) use ($organizationId): array {
        $entries = new class extends ArrayDbObject {
            public static function objectName() { return History::class; }
        };
        $entries->load(['where' => [['field' => 'IDorganization', 'value' => $organizationId], ['field' => 'action', 'value' => 'decision_completed']]]);
        return array_values(array_filter($entries->getArrayCopy(), static fn ($entry) => (int)(json_decode((string)$entry->get('parameters'), true)['decision_id'] ?? 0) === (int)$target->getId()));
    };
    $logs = $getLogs($decision);
    provenanceCheck(count($logs) === 1, 'Repeated reads must not duplicate completion history.');
    provenanceCheck(json_decode((string)$logs[0]->get('parameters'), true)['outcome'] === 'success', 'Accepted decision must report success.');
    provenanceCheck(str_contains($logs[0]->get('content'), '[rule|' . $rule->getId() . '|'), 'History must reference the applied rule.');
    provenanceCheck(str_contains($logs[0]->get('content'), 'en conflit'), 'History must distinguish an accepted vote from an application conflict.');
    provenanceCheck($logs[0]->get('target_type') === 'holon_resource', 'History must appear in the context feed without invalidating structure.');

    // Collective edit keeps the original creation context and records its proposer.
    $update = new DeferredProposal();
    provenanceSet($update, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser_author' => $managerId,
        'IDdecision_proposal' => $proposal->getId(), 'target_type' => 'rule', 'operation' => 'update', 'target_id' => $rule->getId(),
        'status' => DeferredProposal::STATUS_PENDING, 'before_state' => DecisionGovernanceAction::captureRuleState($rule),
        'after_state' => array_merge($state, ['title' => 'Updated rule'])]);
    provenanceSave($update);
    $result = DeferredProposal::applyBatch([$update], (int)$holon->getId(), $managerId, 'decision:' . $decision->getId());
    provenanceCheck(!empty($result['status']), 'Collective edit must succeed.');
    $rule->load($rule->getId(), true);
    provenanceCheck((int)$rule->get('IDuser_creation') === $authorId && (int)$rule->get('IDuser_modification') === $managerId, 'An edit must preserve the creator and identify the new proposer.');
    provenanceCheck($rule->getCreationContext()['id'] === (int)$decision->getId(), 'Editing must preserve the creation context.');
    provenanceCheck(History::getResourceReferenceUrl('rule', (int)$rule->getId(), $organizationId + 999999) === '', 'Links must not cross organizations.');

    $rejected = new DecisionProcess();
    provenanceSet($rejected, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser' => $managerId,
        'title' => 'Rejected decision', 'decision_type' => DecisionProcess::TYPE_DECISION, 'status' => DecisionProcess::STATUS_RESULTS,
        'evaluation_method' => DecisionProcess::METHOD_CONSENT, 'visibility_type' => DecisionProcess::getDefaultVisibilityType()]);
    provenanceSave($rejected);
    $rejectedGroup = $rejected->getPrimaryGroup(true);
    $rejectedProposal = new DecisionProposal();
    provenanceSet($rejectedProposal, ['IDdecision_group' => $rejectedGroup->getId(), 'title' => 'Rejected proposal', 'active' => 1]);
    provenanceSave($rejectedProposal);
    $participant = new DecisionParticipant();
    provenanceSet($participant, ['IDdecision_process' => $rejected->getId(), 'IDuser' => $authorId,
        'role' => DecisionParticipant::ROLE_PARTICIPANT, 'status' => DecisionParticipant::STATUS_INVITED, 'active' => 1]);
    provenanceSave($participant);
    $response = new DecisionResponse();
    provenanceSet($response, ['IDdecision_group' => $rejectedGroup->getId(), 'IDdecision_participant' => $participant->getId(),
        'status' => DecisionResponse::STATUS_SUBMITTED, 'parameters' => ['consent' => ['choices' => [$rejectedProposal->getId() => 'objection']]]]);
    provenanceSave($response);
    History::recordDecisionCompletion($rejected);
    provenanceCheck(json_decode((string)$getLogs($rejected)[0]->get('parameters'), true)['outcome'] === 'rejected', 'An objection must be recorded as rejection.');

    $event = new Event();
    provenanceSet($event, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser' => $managerId,
        'title' => 'Reunion OP', 'status' => Event::STATUS_CONFIRMED, 'start_at' => '2026-10-12 10:00:00', 'end_at' => '2026-10-12 11:00:00']);
    provenanceSave($event);
    $document = new Document();
    provenanceSet($document, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser' => $managerId,
        'IDevent' => $event->getId(), 'title' => 'PV provenance', 'documenttype' => Document::TYPE_PV]);
    provenanceSave($document);
    $point = new DocumentPvPoint();
    provenanceSet($point, ['IDdocument' => $document->getId(), 'title' => 'PV point', 'pointtype' => DocumentPvPoint::TYPE_DECISION]);
    provenanceSave($point);
    $pvChange = new DeferredProposal();
    provenanceSet($pvChange, ['IDorganization' => $organizationId, 'IDholon' => $holon->getId(), 'IDuser_author' => $authorId,
        'IDdocument_pv_point' => $point->getId(), 'target_type' => 'rule', 'operation' => 'create',
        'status' => DeferredProposal::STATUS_PENDING, 'after_state' => $state]);
    provenanceSave($pvChange);
    $result = DeferredProposal::applyForPvPoint($point, $managerId);
    provenanceCheck(!empty($result['status']) && $result['applied'] === 1, 'PV must create the rule.');
    $pvChange->load($pvChange->getId(), true);
    $pvRule = new Rule();
    provenanceCheck($pvRule->load((int)$pvChange->get('target_id')), 'PV rule must exist.');
    $context = $pvRule->getCreationContext();
    provenanceCheck($context['type'] === 'event' && $context['id'] === (int)$event->getId(), 'Meeting rule must retain its event.');
    provenanceCheck($context['label'] === 'Reunion OP du 12.10.2026', 'Meeting provenance must include its date.');
    provenanceCheck((int)$pvRule->get('IDuser_creation') === $authorId, 'PV rule must retain its proposer.');
    $document->set('IDevent', null);
    provenanceSave($document);
    provenanceCheck(Rule::buildProposalCreationContext($pvChange)['type'] === 'document', 'A PV without event must link to its document.');
    provenanceCheck($pvRule->getCreationContext() === $context, 'Stored origin must survive changes to its source.');
    echo "decision_provenance_integration_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
