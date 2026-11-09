<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session'];
    $_POST = $_REQUEST = ['groups' => $request['groups'], 'deleted_groups' => $request['deleted']];
    $_SERVER['HTTP_HOST'] = 'localtest.me'; $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/omo/api/decision/save_multi.php';
    require dirname(__DIR__) . '/omo/api/decision/save_multi.php';
    exit;
}
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{DecisionProcess, DecisionGroup, DecisionProposal, Event, User, Application, OrganizationApplication};

function saveQuestionDeletion(array $session, array $groups, array $deleted = []): array
{
    $request = base64_encode(json_encode(['session' => $session, 'groups' => array_map('http_build_query', $groups), 'deleted' => $deleted], JSON_THROW_ON_ERROR));
    $process = proc_open([PHP_BINARY, __FILE__, '--request', $request], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Start batch request');
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Batch request failed: ' . $errors . $output);
    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}

$items = mcpFixtures(); $decision = null; $other = null;
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $app = new Application(); mcpCheck($app->load(['hash', 'decision']), 'Decision application exists');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $fields = ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid, 'title' => 'Delete fixture', 'status' => 'draft', 'evaluation_method' => 'simple_vote'];
    $decision = mcpFixture(DecisionProcess::class, $fields);
    $other = mcpFixture(DecisionProcess::class, $fields);
    $groups = [$decision->getPrimaryGroup(false), $decision->addDecisionGroup('majority_judgment'), $decision->addDecisionGroup('consent')];
    $payloads = [];
    foreach ($groups as $index => $group) {
        $payloads[] = ['oid' => $oid, 'cid' => $hid, 'id' => $decision->getId(), 'gid' => $group->getId(), 'method' => $group->get('evaluation_method'),
            'process_title' => 'Delete fixture', 'title' => 'Question ' . $index, 'short_title' => 'Bloc ' . $index,
            'proposals' => ['A', 'B'], 'proposal_content_title' => 1, 'status' => 'draft', 'mention_customization_enabled' => 1,
            'mention_labels' => [6 => 'Superbe'], 'mention_active' => [0 => 1, 6 => 1]];
    }
    $session = ['currentUser' => $uid, 'currentOrganization' => $oid, 'auth_security_version' => $items['user']->get('security_version')];
    $initial = saveQuestionDeletion($session, $payloads);
    mcpCheck(!empty($initial['status']), 'Initial questionnaire saved: ' . json_encode($initial));
    $proposal = $groups[0]->getProposals(true)[0];
    $event = mcpFixture(Event::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'IDdecision_proposal' => $proposal->getId(),
        'title' => 'Question date', 'status' => 'option', 'start_at' => '2090-01-01 10:00:00', 'end_at' => '2090-01-01 11:00:00']);
    $eventId = (int)$event->getId(); $proposalId = (int)$proposal->getId();
    $foreignId = (int)$other->getPrimaryGroup(false)->getId();
    $invalid = $payloads; $invalid[0]['process_title'] = 'Must roll back';
    mcpCheck(empty(saveQuestionDeletion($session, $invalid, [$foreignId])['status']), 'Cross-scrutin deletion rejected');
    $decision->load((int)$decision->getId(), true);
    mcpCheck($decision->get('title') === 'Delete fixture', 'Failed deletion rolls back the whole save');
    mcpCheck(empty(saveQuestionDeletion($session, $payloads, [$groups[0]->getId()])['status']), 'A question cannot be saved and deleted together');
    $items['stranger'] = mcpFixture(User::class, ['email' => 'delete-stranger-' . bin2hex(random_bytes(5)) . '@example.invalid', 'active' => 1]);
    mcpCheck(empty($decision->deleteDecisionGroupsForManager([(int)$groups[0]->getId()], (int)$items['stranger']->getId())['status']), 'Unauthorized deletion rejected');
    mcpCheck(empty($decision->deleteDecisionGroupsForManager(array_map(static fn($group) => (int)$group->getId(), $groups), $uid)['status']), 'Deleting all questions is rejected');
    mcpCheck(!empty(saveQuestionDeletion($session, array_slice($payloads, 1), [(int)$groups[0]->getId()])['status']), 'The first question can be deleted');
    $gone = new DecisionGroup(); $goneProposal = new DecisionProposal(); $goneEvent = new Event();
    mcpCheck(!$gone->load((int)$groups[0]->getId(), true) && !$goneProposal->load($proposalId, true) && !$goneEvent->load($eventId, true), 'The question, every proposal and linked calendar event are removed');
    $decision->load((int)$decision->getId(), true);
    mcpCheck($decision->get('evaluation_method') === 'majority_judgment'
        && (int)$decision->getPrimaryGroup(false)->getId() === (int)$groups[1]->getId(), 'The remaining first question becomes primary');
    $parameters = $decision->get('parameters');
    if (is_string($parameters)) $parameters = json_decode($parameters, true);
    mcpCheck($parameters['majority_judgment']['mention_options'][6]['label'] === 'Superbe', 'The new primary configuration is preserved');
    mcpCheck(count($groups[2]->getProposals(true)) === 2, 'Other questions keep their proposals');
    $decision->set('evaluation_start_at', '2020-01-01 00:00:00'); $decision->set('status', 'evaluation'); $decision->save();
    $locked = array_slice($payloads, 1, 1); $locked[0]['status'] = 'evaluation'; $locked[0]['evaluation_start_at'] = '2020-01-01 00:00:00';
    mcpCheck(empty(saveQuestionDeletion($session, $locked, [(int)$groups[2]->getId()])['status']), 'The server rejects deletion after evaluation starts');
    mcpCheck(count($decision->getDecisionGroups(false)) === 2, 'Locked deletion leaves both questions intact');
    $decision->set('evaluation_start_at', null); $decision->set('status', 'draft'); $decision->save();
    // A replacement added in the same save counts as a surviving question.
    $replacement = $payloads[0]; $replacement['gid'] = 0; $replacement['group_action'] = 'create'; $replacement['title'] = 'Replacement';
    mcpCheck(!empty(saveQuestionDeletion($session, [$replacement], [(int)$groups[1]->getId(), (int)$groups[2]->getId()])['status']), 'All saved questions can be replaced by a new question atomically');
    $decision->load((int)$decision->getId(), true);
    mcpCheck(count($decision->getDecisionGroups(false)) === 1 && $decision->getPrimaryGroup(false)->get('title') === 'Replacement', 'Only the new question remains');
    mcpCheck(empty($decision->deleteDecisionGroupsForManager([(int)$decision->getPrimaryGroup(false)->getId()], $uid)['status']), 'The last question remains protected');
    echo "decision_question_deletion_test: OK\n";
} finally {
    if ($other) $other->deleteWithRelations();
    if ($decision) $decision->deleteWithRelations();
    mcpCleanup($items);
}
