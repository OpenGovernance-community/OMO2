<?php
declare(strict_types=1);

// Run as www-data in local Docker. Fixtures and their decisions are removed afterwards.
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{ArrayApplication, OrganizationApplication, DecisionProcess, DeferredProposal, HolonPermission, Permission};

function groupedRoleHttp(string $endpoint, array $query, ?array $post = null): array
{
    if ($post !== null) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $curl = curl_init('https://localhost/omo/api/decision/governance/' . $endpoint . '?' . http_build_query($query));
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    mcpCheck(is_string($body), 'HTTP request failed.');
    return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
}

$before = $_SESSION;
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    $cid = (int)$items['root']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid,
        'auth_security_version' => (int)$items['user']->get('security_version')];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'decision']], 'limit' => 1]);
    $items['decision_app'] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    foreach (['CAN_EDIT_HOLON', 'CAN_DELETE_HOLON', 'CAN_MOVE_HOLON'] as $key) {
        $items[$key] = mcpFixture(HolonPermission::class, ['IDholon' => $cid,
            'IDpermission' => Permission::findByKey($key)->getId(), 'range' => 'descendants', 'member_type' => 'collective']);
    }
    foreach (['group' => ['root', 3], 'nested_group' => ['group', 3], 'grouped_role' => ['group', 1],
        'nested_role' => ['nested_group', 1], 'child_circle' => ['root', 2], 'foreign_role' => ['child_circle', 1]] as $name => [$parent, $type]) {
        $items[$name] = mcpFixture(McpTestHolon::class, ['IDorganization' => $oid, 'IDholon_org' => $cid,
            'IDholon_parent' => $items[$parent]->getId(), 'IDtypeholon' => $type,
            'name' => $name, 'active' => 1, 'visible' => 1]);
    }
    $items['decision'] = mcpFixture(DecisionProcess::class, ['IDorganization' => $oid, 'IDholon' => $cid, 'IDuser' => $uid,
        'title' => 'Grouped role HTTP fixture', 'decision_type' => DecisionProcess::TYPE_DECISION,
        'evaluation_method' => DecisionProcess::METHOD_CONSENT, 'status' => DecisionProcess::STATUS_CONSULTATION,
        'consultation_end_at' => new DateTimeImmutable('+1 day'), 'evaluation_end_at' => new DateTimeImmutable('+2 days'),
        'visibility_type' => DecisionProcess::getDefaultVisibilityType(),
        'parameters' => ['workflow_type' => DecisionProcess::WORKFLOW_GOVERNANCE]]);
    $context = ['oid' => $oid, 'cid' => $cid, 'id' => $items['decision']->getId()];
    $query = $context + ['target_type' => 'holon', 'operation' => 'update', 'context_holon_id' => $cid];
    $response = groupedRoleHttp('proposal_context.php', $query);
    mcpCheck($response['status'] === 200, 'Picker loads: ' . $response['body']);
    $objects = array_column($response['json']['objects'], null, 'id');
    $actions = [];
    foreach (['role', 'grouped_role', 'nested_role'] as $name) {
        $id = (int)$items[$name]->getId();
        mcpCheck(isset($objects[$id]), 'Picker includes ' . $name);
        $state = $objects[$id]['state'];
        $state['name'] = $name . ' updated';
        if (isset($state['editor_payload'])) $state['editor_payload']['name'] = $state['name'];
        $actions[] = ['type' => 'holon.update', 'targetId' => $id, 'holonId' => $cid, 'after' => $state];
    }
    mcpCheck(!isset($objects[$items['foreign_role']->getId()]), 'Picker stops at child circles.');
    $post = $context + ['process_title' => 'Grouped role HTTP fixture', 'consent_question' => 'Accept these changes?',
        'consultation_end_at' => (new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s'),
        'evaluation_end_at' => (new DateTimeImmutable('+2 days'))->format('Y-m-d H:i:s'),
        'governance_blueprint' => json_encode([['actions' => $actions]])];
    $response = groupedRoleHttp('save.php', [], $post);
    mcpCheck($response['status'] === 200 && !empty($response['json']['status']), 'Save direct and grouped roles: ' . $response['body']);
    $proposals = $items['decision']->getProposals(false);
    mcpCheck(count($proposals) === 1, 'One governance proposal saved.');
    $changes = DeferredProposal::getForDecisionProposal((int)$proposals[0]->getId());
    mcpCheck(count($changes) === 3, 'All three modifications saved.');
    foreach ($changes as $change) mcpCheck((int)$change->get('IDholon') === $cid, 'Keep the selected circle context.');
    // No structural mutation occurs when the proposal is saved.
    foreach (['grouped_role' => 'group', 'nested_role' => 'nested_group'] as $name => $parent) {
        $items[$name]->load($items[$name]->getId(), true);
        mcpCheck($items[$name]->get('name') === $name
            && (int)$items[$name]->get('IDholon_parent') === (int)$items[$parent]->getId(), 'Original grouped role is retained.');
    }
    foreach (['foreign_role', 'other_root', 'inactive'] as $invalid) {
        $forged = $actions[0];
        $forged['targetId'] = $items[$invalid]->getId();
        $rejected = groupedRoleHttp('save.php', [], array_replace($post,
            ['governance_blueprint' => json_encode([['actions' => [$forged]]])]));
        mcpCheck($rejected['status'] === 422, 'Reject invalid target ' . $invalid . ': ' . $rejected['body']);
    }
    $moveQuery = array_replace($query, ['operation' => 'move', 'moving_holon_id' => $items['nested_role']->getId()]);
    $response = groupedRoleHttp('proposal_context.php', $moveQuery);
    mcpCheck($response['status'] === 200 && !empty($response['json']['status']), 'Grouped move preparation: ' . $response['body']);
    mcpCheck((int)$response['json']['before']['parent_id'] === (int)$items['nested_group']->getId(), 'Move snapshot retains the actual group parent.');
    $response = groupedRoleHttp('proposal_context.php', array_replace($moveQuery, ['moving_holon_id' => $items['foreign_role']->getId()]));
    mcpCheck($response['status'] === 403, 'Move context cannot cross a child circle.');
    $items['CAN_EDIT_HOLON']->set('range', 'self');
    $items['CAN_EDIT_HOLON']->save();
    $response = groupedRoleHttp('save.php', [], $post);
    mcpCheck($response['status'] === 422, 'Revoked collective rights still prevent saving.');
    echo "decision_grouped_role_http_test: OK\n";
} finally {
    if (isset($items['decision'])) {
        foreach ($items['decision']->getProposals(false) as $proposal) {
            foreach (DeferredProposal::getForDecisionProposal((int)$proposal->getId()) as $change) $change->delete();
        }
    }
    mcpCleanup($items);
    $_SESSION = $before;
}
