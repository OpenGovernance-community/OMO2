<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $_POST = $_REQUEST = $request['input'];
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = ($request['mode'] ?? '') === 'render' ? 'GET' : 'POST';
    $_SERVER['REQUEST_URI'] = '/omo/api/decision/action.php';
    if (($request['mode'] ?? '') === 'render') {
        require dirname(__DIR__) . '/omo/api/bootstrap.php';
        require_once dirname(__DIR__) . '/omo/api/decision/modules/context.php';
        require_once dirname(__DIR__) . '/omo/api/decision/modules/registry.php';
        $context = omoDecisionResolveEditorContext($_GET + ['intent' => 'manage']);
        ob_start(); require dirname(__DIR__) . '/omo/api/decision/edit_shared.php'; $html = ob_get_clean();
        echo json_encode(['html' => $html], JSON_THROW_ON_ERROR); exit;
    }
    ob_start();
    register_shutdown_function(static function (): void {
        $body = ob_get_clean();
        echo json_encode(['code' => http_response_code(), 'payload' => json_decode($body, true)], JSON_THROW_ON_ERROR);
    });
    require dirname(__DIR__) . '/omo/api/decision/action.php'; exit;
}
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{Application, OrganizationApplication, DecisionProcess, DecisionParticipant, DecisionInvitation, DecisionProposal, DecisionResponse, User, UserOrganization, UserHolon};

function finishEvaluationRequest(array $request): array
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request, JSON_THROW_ON_ERROR))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Start finish request'); fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]); $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Finish request failed: ' . $errors . $output);
    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}
function finishEvaluationAssertAdjacent(string $html, string $attribute): void
{
    $dom = new DOMDocument(); $previous = libxml_use_internal_errors(true); $dom->loadHTML('<meta charset="utf-8">' . $html); libxml_clear_errors(); libxml_use_internal_errors($previous);
    mcpCheck((new DOMXPath($dom))->query('//select[@name="status"]/following-sibling::button[@' . $attribute . ']')->length === 1, 'Lifecycle action sits next to the status selector');
}

$items = mcpFixtures(); $decision = null;
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $app = new Application(); mcpCheck($app->load(['hash', 'decision']), 'Decision app exists');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    foreach (['guest', 'editor', 'reader'] as $key) {
        $items[$key] = mcpFixture(User::class, ['email' => $key . '-' . bin2hex(random_bytes(5)) . '@example.invalid', 'active' => 1]);
        $items[$key . '_org'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $items[$key]->getId(), 'active' => 1]);
    }
    $items['editor_role'] = mcpFixture(UserHolon::class, ['IDholon' => $hid, 'IDuser' => $items['editor']->getId(), 'active' => 1, 'is_membership' => 1]);
    $decision = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'title' => 'Finish evaluation', 'evaluation_method' => 'simple_vote', 'status' => 'evaluation',
        'consultation_start_at' => '2020-01-01 00:00:00', 'consultation_end_at' => '2020-01-02 00:00:00',
        'evaluation_start_at' => '2020-01-02 00:00:00', 'evaluation_end_at' => '2090-01-01 00:00:00']);
    $decision->saveVisibilityRulesFromInput(['edit_visibility_type' => 'role']);
    mcpFixture(DecisionInvitation::class, ['IDdecision_process' => $decision->getId(), 'invitation_type' => 'user', 'IDuser' => $items['guest']->getId(), 'active' => 1, 'status' => 'invited']);
    $decision->syncParticipantsFromInvitations(true);
    $groups = [$decision->getPrimaryGroup(false), $decision->addDecisionGroup('majority_judgment'), $decision->addDecisionGroup('consent')];
    $decision->addDecisionGroup('consultation_only');
    $inactive = $decision->addDecisionGroup('simple_vote'); $inactive->set('active', 0); $inactive->save();
    foreach ($groups as $group) foreach (['A', 'B'] as $position => $title) mcpFixture(DecisionProposal::class,
        ['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $group->getId(), 'title' => $title, 'position' => $position + 1, 'active' => 1]);
    $owner = DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), $uid);
    $guest = DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), (int)$items['guest']->getId());
    $session = static fn(string $key): array => ['currentUser' => $items[$key]->getId(), 'currentOrganization' => $oid, 'auth_security_version' => $items[$key]->get('security_version')];
    $input = ['oid' => $oid, 'id' => $decision->getId(), 'decision_action' => 'finish_evaluation'];
    $request = ['session' => $session('editor'), 'input' => $input];
    $render = static fn(): string => finishEvaluationRequest(['session' => $session('editor'), 'input' => $input, 'mode' => 'render'])['html'];
    $empty = $render(); finishEvaluationAssertAdjacent($empty, 'data-omo-decision-interrupt');
    file_put_contents(dirname(__DIR__) . '/tmp/decision-finish-empty.html', $empty);
    mcpCheck(!$decision->canFinishEvaluationNow(), 'No responses cannot count as everyone having answered');
    $responses = [];
    foreach ([$owner, $guest] as $person) foreach ($groups as $group) {
        $responses[] = mcpFixture(DecisionResponse::class, ['IDdecision_process' => $decision->getId(), 'IDdecision_group' => $group->getId(),
            'IDdecision_participant' => $person->getId(), 'status' => $person === $owner ? 'submitted' : 'draft']);
    }
    mcpCheck(finishEvaluationRequest($request)['code'] === 409, 'Partially completed ballots reject early completion on the server');
    $pending = $render();
    mcpCheck(!str_contains($pending, 'data-omo-decision-finish') && !str_contains($pending, 'data-omo-decision-interrupt'), 'Partial responses expose neither immediate lifecycle action');
    foreach (array_slice($responses, 3, 2) as $response) { $response->set('status', 'submitted'); $response->save(); }
    mcpCheck(!$decision->canFinishEvaluationNow(), 'Every voting block is required');
    $last = $responses[5]; $last->set('status', 'submitted'); $last->save();
    mcpCheck($decision->canFinishEvaluationNow(), 'Completed ballots across all methods permit completion; consultation and inactive groups do not block');
    $last->set('status', 'invalidated'); $last->save();
    mcpCheck(!$decision->canFinishEvaluationNow(), 'An invalidated ballot does not count despite its old submission date');
    $last->set('status', 'submitted'); $last->save();
    $extra = mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $decision->getId(), 'email' => 'declined@example.invalid', 'status' => 'declined', 'active' => 1]);
    mcpCheck($decision->canFinishEvaluationNow(), 'Declined participants do not block completion');
    $extra->set('status', 'revoked'); $extra->save(); mcpCheck($decision->canFinishEvaluationNow(), 'Revoked participants do not block completion');
    $extra->set('status', 'invited'); $extra->save(); mcpCheck(!$decision->canFinishEvaluationNow(), 'An invited participant without a response still blocks completion');
    $extra->set('active', 0); $extra->save(); mcpCheck($decision->canFinishEvaluationNow(), 'Inactive participants do not block completion'); $extra->delete();
    $ready = $render(); finishEvaluationAssertAdjacent($ready, 'data-omo-decision-finish');
    file_put_contents(dirname(__DIR__) . '/tmp/decision-finish-ready.html', $ready);
    mcpCheck(finishEvaluationRequest(['session' => $session('reader'), 'input' => $input])['code'] === 403, 'Readers cannot end evaluation');
    $newInvitation = mcpFixture(DecisionInvitation::class, ['IDdecision_process' => $decision->getId(), 'invitation_type' => 'user', 'IDuser' => $items['reader']->getId(), 'active' => 1, 'status' => 'invited']);
    $decision->syncParticipantsFromInvitations();
    $stale = finishEvaluationRequest($request);
    mcpCheck($stale['code'] === 409, 'A newly invited voter is rechecked even after the ready button was rendered: ' . json_encode($stale));
    $newInvitation->delete(); $decision->syncParticipantsFromInvitations();
    mcpCheck(!DecisionParticipant::findByDecisionAndUser((int)$decision->getId(), (int)$items['editor']->getId()), 'The delegated manager is not made a voter');
    $before = time(); $result = finishEvaluationRequest($request); $after = time();
    mcpCheck($result['code'] === 200 && !empty($result['payload']['status']) && $result['payload']['resolvedAction'] === 'finish_evaluation', 'Delegated manager can end completed evaluation: ' . json_encode($result));
    mcpCheck($result['payload']['redirectUrl'] !== '', 'Completion returns the editor URL');
    $decision->load((int)$decision->getId(), true);
    $end = DecisionProcess::normalizeDateTimeValue($decision->get('evaluation_end_at'));
    mcpCheck($end->getTimestamp() >= $before && $end->getTimestamp() <= $after, 'Evaluation end is set to now');
    mcpCheck($decision->get('status') === 'results' && $decision->get('results_published_at') && !$decision->get('archived_at'), 'Normal result publication runs without archiving or deleting the scrutiny');
    mcpCheck(DecisionProcess::normalizeDateTimeValue($decision->get('evaluation_start_at'))->format('Y-m-d') === '2020-01-02'
        && DecisionProcess::normalizeDateTimeValue($decision->get('consultation_end_at'))->format('Y-m-d') === '2020-01-02', 'Other phase dates are preserved');
    foreach ($responses as $response) {
        mcpCheck($response->load((int)$response->getId(), true) && $response->get('status') === 'submitted', 'Every ballot is preserved');
    }
    $last->set('status', 'draft'); mcpCheck(empty($last->saveForParticipation()['status']), 'Ballot writes are blocked after completion');
    mcpCheck(finishEvaluationRequest($request)['code'] === 409, 'Completion cannot run again after the deadline');
    mcpCheck(!str_contains($render(), 'data-omo-decision-finish'), 'Result-stage editor hides the completion button');
    echo "decision_finish_evaluation_test: OK\n";
} finally {
    if ($decision) $decision->deleteWithRelations();
    mcpCleanup($items);
}
