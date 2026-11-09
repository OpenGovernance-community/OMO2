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
    if (($request['mode'] ?? '') === 'response') {
        $response = new \dbObject\DecisionResponse();
        foreach ($request['response'] as $key => $value) $response->set($key, $value);
        echo json_encode($response->saveForParticipation(), JSON_THROW_ON_ERROR);
        exit;
    }
    if (($request['mode'] ?? '') === 'render') {
        require dirname(__DIR__) . '/omo/api/bootstrap.php';
        require_once dirname(__DIR__) . '/omo/api/decision/modules/context.php';
        require_once dirname(__DIR__) . '/omo/api/decision/modules/registry.php';
        $context = omoDecisionResolveEditorContext($_GET + ['intent' => 'manage']);
        ob_start(); require dirname(__DIR__) . '/omo/api/decision/edit_shared.php'; $html = ob_get_clean();
        echo json_encode(['button' => str_contains($html, 'data-omo-decision-interrupt')], JSON_THROW_ON_ERROR);
        exit;
    }
    ob_start();
    register_shutdown_function(static function (): void {
        $body = ob_get_clean();
        echo json_encode(['code' => http_response_code(), 'payload' => json_decode($body, true)], JSON_THROW_ON_ERROR);
    });
    require dirname(__DIR__) . '/omo/api/decision/action.php';
    exit;
}
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/auth.php';
use dbObject\{DbObject, Application, OrganizationApplication, User, UserOrganization, UserHolon, DecisionProcess, DecisionResponse, DecisionParticipant};

function interruptStartRequest(array $request): array
{
    $process = proc_open([PHP_BINARY, '-d', 'display_errors=1', '-d', 'log_errors=0', __FILE__, '--request', base64_encode(json_encode($request, JSON_THROW_ON_ERROR))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Start interruption request'); fclose($pipes[0]);
    return [$process, $pipes];
}
function interruptFinishRequest(array $handle): array
{
    [$process, $pipes] = $handle;
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Request failed: ' . $errors . $output);
    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}
function interruptRequest(array $request): array { return interruptFinishRequest(interruptStartRequest($request)); }

$items = mcpFixtures(); $decisions = [];
$pdo = DbObject::getPdo();
try {
    $oid = (int)$items['org']->getId(); $ownerId = (int)$items['user']->getId(); $roleId = (int)$items['role']->getId();
    $app = new Application(); mcpCheck($app->load(['hash', 'decision']), 'Decision app exists');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    foreach (['editor', 'reader'] as $key) {
        $items[$key] = mcpFixture(User::class, ['email' => $key . '-' . bin2hex(random_bytes(5)) . '@example.invalid', 'active' => 1]);
        $items[$key . '_org'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $items[$key]->getId(), 'active' => 1]);
    }
    $editorId = (int)$items['editor']->getId();
    $items['editor_role'] = mcpFixture(UserHolon::class, ['IDholon' => $roleId, 'IDuser' => $editorId, 'active' => 1, 'is_membership' => 1]);
    $create = static function (array $extra = []) use (&$decisions, $oid, $ownerId, $roleId): DecisionProcess {
        $decision = mcpFixture(DecisionProcess::class, array_replace(['IDorganization' => $oid, 'IDholon' => $roleId, 'IDuser' => $ownerId,
            'title' => 'Interrupt test', 'evaluation_method' => 'simple_vote', 'status' => 'evaluation',
            'consultation_start_at' => '2020-01-01 00:00:00', 'consultation_end_at' => '2090-01-01 00:00:00',
            'evaluation_start_at' => '2020-01-02 00:00:00', 'evaluation_end_at' => '2090-01-02 00:00:00'], $extra));
        $decisions[] = $decision;
        mcpCheck(!empty($decision->saveVisibilityRulesFromInput(['edit_visibility_type' => 'role'])['status']), 'Role editing saved');
        return $decision;
    };
    $requestFor = static fn(DecisionProcess $decision, User $actor, string $mode = '') => ['mode' => $mode,
        'session' => ['currentUser' => $actor->getId(), 'currentOrganization' => $oid, 'auth_security_version' => $actor->get('security_version')],
        'input' => ['oid' => $oid, 'id' => $decision->getId(), 'decision_action' => 'interrupt']];
    $decision = $create();
    mcpCheck(interruptRequest($requestFor($decision, $items['editor'], 'render'))['button'], 'Editor sees interruption button');
    mcpCheck(interruptRequest($requestFor($decision, $items['reader']))['code'] === 403, 'A reader cannot interrupt');
    $result = interruptRequest($requestFor($decision, $items['editor']));
    mcpCheck($result['code'] === 200 && !empty($result['payload']['status']) && str_contains($result['payload']['redirectUrl'], 'intent=manage'), 'Editor can interrupt without delete rights');
    $decision->load((int)$decision->getId(), true);
    mcpCheck(!$decision->get('evaluation_start_at') && $decision->get('status') === 'draft', 'Only evaluation start is cleared and status becomes preparation');
    mcpCheck($decision->get('evaluation_end_at')->format('Y-m-d H:i:s') === '2090-01-02 00:00:00'
        && $decision->get('consultation_start_at')->format('Y-m-d H:i:s') === '2020-01-01 00:00:00'
        && $decision->get('consultation_end_at')->format('Y-m-d H:i:s') === '2090-01-01 00:00:00', 'Other dates are preserved');
    $decision->syncLifecycleStatus();
    mcpCheck($decision->resolveAutomaticStatus(new DateTimeImmutable('2100-01-01')) === 'draft' && !$decision->isParticipationInterfaceOpen(), 'Retained dates never restart or finish an interrupted evaluation');
    mcpCheck(!interruptRequest($requestFor($decision, $items['editor'], 'render'))['button'], 'Button disappears in preparation');
    $decision->set('consultation_start_at', new DateTimeImmutable('+1 day')); $decision->save();
    mcpCheck(!isset($decision->get('parameters')['evaluation_interruption']) && $decision->resolveAutomaticStatus() === 'scheduled', 'A new elaboration schedule clears the interrupted state');
    $governance = $create(['consultation_end_at' => '2020-01-02 00:00:00', 'parameters' => ['workflow_type' => DecisionProcess::WORKFLOW_GOVERNANCE]]);
    mcpCheck(!empty($governance->interruptEvaluationForManager($ownerId)['status'])
        && !$governance->hasConsultationStarted() && !$governance->hasConsultationEnded(), 'Interrupted governance decisions also return to editable preparation');

    foreach (['draft', 'submitted', 'invalidated'] as $status) {
        $blocked = $create(); $group = $blocked->addDecisionGroup('simple_vote', 'decision', 'Other question');
        $participant = mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $blocked->getId(), 'IDuser' => $ownerId, 'active' => 1, 'status' => 'active']);
        mcpFixture(DecisionResponse::class, ['IDdecision_process' => $blocked->getId(), 'IDdecision_group' => $group->getId(),
            'IDdecision_participant' => $participant->getId(), 'status' => $status, 'parameters' => ['proposal_ids' => [1]]]);
        mcpCheck(!$blocked->canInterruptEvaluation(), "$status response in another question blocks interruption");
        mcpCheck(interruptRequest($requestFor($blocked, $items['user']))['code'] === 409, "$status response is checked server-side");
        mcpCheck(!interruptRequest($requestFor($blocked, $items['user'], 'render'))['button'], "$status response hides the button");
    }
    $noEnd = $create(['consultation_start_at' => null, 'consultation_end_at' => null, 'evaluation_end_at' => null]);
    mcpCheck(interruptRequest($requestFor($noEnd, $items['user']))['code'] === 200, 'No elaboration or evaluation end is required');
    $noEnd->load((int)$noEnd->getId(), true); $noEnd->set('evaluation_start_at', new DateTimeImmutable('+1 hour')); $noEnd->save();
    mcpCheck(!isset($noEnd->get('parameters')['evaluation_interruption']) && $noEnd->resolveAutomaticStatus() === 'scheduled', 'A new evaluation schedule can restart the scrutiny');

    // A response committed first prevents a concurrent interruption.
    $race = $create(); $group = $race->getPrimaryGroup(false);
    $participant = mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $race->getId(), 'IDuser' => $ownerId, 'active' => 1, 'status' => 'active']);
    $responseFields = ['IDdecision_process' => $race->getId(), 'IDdecision_group' => $group->getId(),
        'IDdecision_participant' => $participant->getId(), 'status' => 'draft', 'parameters' => ['proposal_ids' => [1]]];
    $response = new DecisionResponse(); foreach ($responseFields as $key => $value) $response->set($key, $value);
    $pdo->beginTransaction(); mcpCheck(!empty($response->saveForParticipation()['status']), 'Partial response holds the process lock');
    $pending = interruptStartRequest($requestFor($race, $items['user'])); usleep(150000); $pdo->commit();
    mcpCheck(interruptFinishRequest($pending)['code'] === 409, 'Concurrent interruption waits and sees the response');

    // An interruption committed first rejects an already prepared response.
    $race = $create(); $group = $race->getPrimaryGroup(false);
    $participant = mcpFixture(DecisionParticipant::class, ['IDdecision_process' => $race->getId(), 'IDuser' => $ownerId, 'active' => 1, 'status' => 'active']);
    $pdo->beginTransaction(); mcpCheck(!empty($race->interruptEvaluationForManager($ownerId)['status']), 'Interruption holds the process lock');
    $request = $requestFor($race, $items['user'], 'response');
    $request['response'] = array_replace($responseFields, ['IDdecision_process' => $race->getId(), 'IDdecision_group' => $group->getId(), 'IDdecision_participant' => $participant->getId()]);
    $pending = interruptStartRequest($request); usleep(150000); $pdo->commit();
    $result = interruptFinishRequest($pending);
    mcpCheck(empty($result['status']) && $result['code'] === 409 && !$race->hasEvaluationResponses(), 'A concurrent response cannot be saved after interruption');
    echo "decision_interruption_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach (array_reverse($decisions) as $decision) $decision->deleteWithRelations();
    mcpCleanup($items);
}
