<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session'];
    $_POST = $_REQUEST = $request['post'];
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/omo/api/decision/save_multi.php';
    require dirname(__DIR__) . '/omo/api/decision/save_multi.php';
    exit;
}
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{DecisionProcess, User, UserOrganization, UserHolon, Application, OrganizationApplication};

$items = mcpFixtures();
$decision = null;
try {
    $oid = (int)$items['org']->getId();
    $ownerId = (int)$items['user']->getId();
    $roleId = (int)$items['role']->getId();
    $app = new Application(); mcpCheck($app->load(['hash', 'decision']), 'Decision app exists');
    $items['decision_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $items['editor'] = mcpFixture(User::class, ['email' => 'decision-editor-' . bin2hex(random_bytes(5)) . '@example.invalid', 'active' => 1]);
    $editorId = (int)$items['editor']->getId();
    $items['editor_org'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $editorId, 'active' => 1]);
    $items['editor_role'] = mcpFixture(UserHolon::class, ['IDholon' => $roleId, 'IDuser' => $editorId, 'active' => 1, 'is_membership' => 1]);
    $decision = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $roleId, 'IDuser' => $ownerId,
        'title' => 'Multi visibility', 'evaluation_method' => 'simple_vote', 'status' => 'draft']);
    mcpCheck(!empty($decision->saveVisibilityRulesFromInput(['edit_visibility_type' => 'role'])['status']), 'Role edit scope saved');
    $first = $decision->ensurePrimaryGroup();
    $second = $decision->addDecisionGroup('simple_vote', 'decision', 'Second');
    $groups = [];
    foreach ([$first, $second] as $index => $group) {
        $groups[] = http_build_query(['oid' => $oid, 'cid' => $roleId, 'id' => $decision->getId(), 'gid' => $group->getId(),
            'method' => 'simple_vote', 'process_title' => 'Saved multi visibility', 'title' => 'Saved question ' . $index,
            'proposals' => ['A', 'B'], 'proposal_content_title' => 1, 'status' => 'draft', 'is_anonymous' => 1,
            'visibility_type' => 'self', 'edit_visibility_type' => 'self']);
    }
    $request = ['session' => ['currentUser' => $editorId, 'currentOrganization' => $oid,
        'auth_security_version' => $items['editor']->get('security_version')], 'post' => ['groups' => $groups]];
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request, JSON_THROW_ON_ERROR))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Start batch request');
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Batch request failed: ' . $errors . $output);
    $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($result['status']) && count($result['results']) === 2, 'Both questions saved before the editor loses access: ' . $output);
    $decision->load((int)$decision->getId(), true);
    mcpCheck($decision->get('title') === 'Saved multi visibility' && $decision->get('visibility_type') === 'self'
        && $decision->getPrimaryEditVisibilityRuleRow()['visibility_type'] === 'self', 'Final access scopes persisted');
    mcpCheck(!$decision->canUseManagementPermission('CAN_EDIT_DECISION', $editorId)
        && $decision->canUseManagementPermission('CAN_EDIT_DECISION', $ownerId), 'Author retains access after peer narrows scope');
    echo "decision_visibility_multi_save_test: OK\n";
} finally {
    if ($decision) $decision->deleteWithRelations();
    mcpCleanup($items);
}
