<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/choice/participation-summary.php';

use dbObject\{DbObject, ArrayUser, Organization, DecisionProcess, DecisionParticipant, DecisionResponse};

function respondentCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function respondentFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $field => $value) $object->set($field, $value);
    $result = $object->save();
    respondentCheck(!empty($result['status']), 'Save ' . $class . ': ' . json_encode($result));
    return $object;
}

$users = new ArrayUser();
$users->load(['limit' => 1]);
$userId = (int)$users[0]->getId();
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $organization = respondentFixture(Organization::class, ['name' => 'Respondent counts fixture',
        'shortname' => 'respondents-' . bin2hex(random_bytes(5))]);
    $decision = respondentFixture(DecisionProcess::class, ['IDorganization' => $organization->getId(),
        'IDuser' => $userId, 'title' => 'Respondent counts fixture', 'evaluation_method' => 'simple_vote',
        'status' => 'evaluation', 'evaluation_start_at' => '2020-01-01 00:00:00']);
    $decision->syncParticipantsFromInvitations();
    $groups = [$decision->getPrimaryGroup(false), $decision->addDecisionGroup('simple_vote')];
    $participants = [DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), $userId)];
    for ($i = 1; $i < 10; $i++) {
        $participants[] = respondentFixture(DecisionParticipant::class, ['IDdecision_process' => $decision->getId(),
            'email' => 'respondent-' . $i . '@example.invalid', 'role' => 'participant', 'status' => 'active', 'active' => 1]);
    }
    $checkCounts = static function (int $respondents, int $incomplete, int $invited = 10) use ($organization, $decision, $userId): void {
        foreach ([
            DecisionProcess::fetchListRowsForOrganization((int)$organization->getId(), $userId),
            DecisionProcess::fetchRelevantRowsForUser($userId),
        ] as $rows) {
            $row = array_values(array_filter($rows, static fn($row) => (int)$row['id'] === (int)$decision->getId()))[0] ?? null;
            respondentCheck($row !== null, 'Both lists must contain the fixture.');
            respondentCheck((int)$row['response_count'] === $respondents, 'Respondents must be distinct: ' . json_encode($row));
            respondentCheck((int)$row['incomplete_response_count'] === $incomplete, 'Incomplete participation must reflect missing blocks.');
            respondentCheck((int)$row['participant_count'] === $invited, 'The invitation total must remain separate.');
        }
    };
    $submit = static fn($participant, $group, $status = 'submitted') => respondentFixture(DecisionResponse::class, [
        'IDdecision_process' => $decision->getId(), 'IDdecision_group' => $group->getId(),
        'IDdecision_participant' => $participant->getId(), 'status' => $status, 'parameters' => []]);

    $checkCounts(0, 0);
    $draft = $submit($participants[5], $groups[0], 'draft');
    $checkCounts(0, 0);
    for ($i = 0; $i < 5; $i++) {
        $submit($participants[$i], $groups[1]);
        if ($i < 4) $submit($participants[$i], $groups[0]);
    }
    // Original regression: nine submitted block ballots, five people, one incomplete.
    $checkCounts(5, 1);
    respondentCheck(commonChoiceParticipationSummary(5, 1) === '5, dont 1 incomplet', 'Singular summary.');
    respondentCheck(commonChoiceParticipationSummary(5, 2) === '5, dont 2 incomplets', 'Plural summary.');
    respondentCheck(commonChoiceParticipationSummary(5, 0) === '5', 'No incomplete suffix for complete participation.');

    $consultation = $decision->addDecisionGroup(DecisionProcess::METHOD_CONSULTATION_ONLY);
    $inactive = $decision->addDecisionGroup('simple_vote');
    $inactive->set('active', 0);
    respondentCheck(!empty($inactive->save()['status']), 'Deactivate a block.');
    $submit($participants[6], $inactive);
    $submit($participants[7], $consultation);
    $checkCounts(5, 1);

    $invalidated = DecisionResponse::findByDecisionGroupAndParticipant((int)$groups[0]->getId(), (int)$participants[0]->getId());
    $invalidated->set('status', 'invalidated');
    respondentCheck(!empty($invalidated->save()['status']), 'Invalidate a previously submitted response.');
    respondentCheck($invalidated->get('submitted_at') !== null, 'Invalidation retains the historical submission date.');
    $checkCounts(5, 2);
    $invalidated->set('status', 'submitted');
    $invalidated->save();
    $submit($participants[4], $groups[0]);
    $checkCounts(5, 0);
    // A person answering only the other block must increase the distinct total, too.
    $draft->set('status', 'submitted');
    $draft->save();
    $checkCounts(6, 1);
    $draft->set('status', 'draft');
    $draft->save();
    $checkCounts(5, 0);

    // A newly added unanswered block makes all existing respondents incomplete.
    $third = $decision->addDecisionGroup('simple_vote');
    $checkCounts(5, 5);
    $third->set('active', 0);
    $third->save();
    foreach ([['active', 0], ['active', 1], ['status', 'revoked'], ['status', 'declined']] as [$field, $value]) {
        $participants[1]->set($field, $value);
        $participants[1]->save();
        $checkCounts($value === 1 ? 5 : 4, 0, $value === 1 ? 10 : 9);
    }

    // With no active evaluation block, historical ballots must not create respondents.
    foreach ($groups as $group) {
        $group->set('evaluation_method', DecisionProcess::METHOD_CONSULTATION_ONLY);
        $group->save();
    }
    $checkCounts(0, 0, 9);
    echo "decision_respondent_counts_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
