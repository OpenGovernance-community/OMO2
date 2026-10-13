<?php
declare(strict_types=1);
// Local Docker, run as www-data. Only test-owned fixtures are removed.
$_SERVER['HTTP_HOST'] = 'localtest.me'; $_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{Application, Authority, DeferredProposal, DecisionProcess, Document, DocumentPvPoint,
    HolonPermission, Permission, OrganizationApplication, UserHolon};
function removalProposalHttp(string $path, ?array $post = null): array {
    if ($post !== null) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id(); session_write_close();
    $curl = curl_init('https://localhost/omo/api/' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $body = curl_exec($curl); $code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE); session_start();
    return ['code' => $code, 'body' => $body, 'json' => json_decode((string)$body, true)];
}
$before = $_SESSION; $items = mcpFixtures(); $decisions = [];
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $rid = (int)$items['root']->getId();
    $hid = (int)$items['role']->getId();
    $items['root']->set('IDholon_org', $rid); $items['root']->save();
    $items['org']->set('interface_level', \dbObject\Organization::INTERFACE_LEVEL_EXPERT); $items['org']->save();
    $items['membership']->set('parameters', ['isAdmin' => true]); $items['membership']->save();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid, 'auth_security_version' => (int)$items['user']->get('security_version')];
    commonSetCurrentUserAdminMode(true, $oid);
    foreach (['documents', 'decision'] as $name) {
        $app = new Application(); mcpCheck((bool)$app->load(['hash', $name]), 'Application ' . $name);
        $items['app_' . $name] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(),
            'active' => 1, 'parameters' => ['decisionSettings' => ['governance' => ['enabled' => true]]]]);
    }
    $items['assignment'] = mcpFixture(UserHolon::class, ['IDholon' => $rid, 'IDuser' => $uid, 'active' => 1, 'is_membership' => 1]);
    $items['collective_delete'] = mcpFixture(HolonPermission::class, ['IDholon' => $rid,
        'IDpermission' => Permission::findByKey('CAN_DELETE_HOLON')->getId(), 'range' => 'descendants', 'member_type' => 'collective']);
    $items['create_decision'] = mcpFixture(HolonPermission::class, ['IDholon' => $rid,
        'IDpermission' => Permission::findByKey('CAN_CREATE_DECISION')->getId(), 'range' => 'self', 'member_type' => 'member']);
    $items['authority'] = mcpFixture(Authority::class, ['IDholon' => $hid, 'label' => 'Unchanged until accepted', 'is_local' => 1]);
    $items['pv'] = mcpFixture(Document::class, ['title' => 'Authority removal PV', 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_PREPARATION, 'IDorganization' => $oid, 'IDholon' => $rid, 'IDuser' => $uid,
        'IDusercreation' => $uid, 'IDuser_pv_editor' => $uid, 'IDuser_pv_official_editor' => $uid, 'active' => 1]);
    $items['point'] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $items['pv']->getId(), 'title' => 'Removal',
        'IDholon_concerned' => $hid, 'IDuser_author' => $uid, 'pointtype' => DocumentPvPoint::TYPE_DECISION, 'active' => 1]);
    $base = ['oid' => $oid, 'point_id' => $items['point']->getId(), 'operation' => 'delete', 'holon_id' => $hid];
    foreach (['', 'invalid'] as $choice) {
        $response = removalProposalHttp('deferred_proposals/pv_holon_save.php', $base + ['payload' => json_encode(['authority_choice' => $choice])]);
        mcpCheck($response['code'] === 422 && empty($response['json']['status']), 'PV requires a valid choice: ' . $response['body']);
    }
    foreach (Authority::holonRemovalChoices() as $choice) {
        $response = removalProposalHttp('deferred_proposals/pv_holon_save.php', $base + ['payload' => json_encode(['authority_choice' => $choice])]);
        mcpCheck($response['code'] === 200 && !empty($response['json']['status']), 'PV save: ' . $response['body']);
        $proposal = new DeferredProposal(); $proposal->load((int)$response['json']['id'], true); $items['pv_' . $choice] = $proposal;
        mcpCheck(DeferredProposal::normalizeState($proposal->get('after_state'))['authority_choice'] === $choice, 'PV stores choice');
        $response = removalProposalHttp('deferred_proposals/pv_holon_editor.php?stage=capture&oid=' . $oid . '&point_id=' . $items['point']->getId() . '&proposal_id=' . $proposal->getId());
        mcpCheck($response['code'] === 200 && str_contains($response['body'], 'value="' . $choice . '" selected'), 'PV reopens saved choice');
    }
    $decisionBase = ['oid' => $oid, 'cid' => $rid, 'process_title' => 'Authority removal decision', 'consent_question' => 'Remove?',
        'consultation_end_at' => (new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s'),
        'evaluation_end_at' => (new DateTimeImmutable('+2 days'))->format('Y-m-d H:i:s')];
    foreach (['', 'invalid', ...Authority::holonRemovalChoices()] as $choice) {
        $post = $decisionBase + ['governance_blueprint' => json_encode([['title' => 'Remove role', 'actions' => [
            ['type' => 'holon.delete', 'holonId' => $rid, 'targetId' => $hid, 'after' => ['authority_choice' => $choice]]]]])];
        $response = removalProposalHttp('decision/governance/save.php', $post);
        if (!Authority::validateHolonRemovalChoice($choice)) {
            mcpCheck($response['code'] === 422 && empty($response['json']['status']), 'Decision requires valid choice: ' . $response['body']);
            continue;
        }
        mcpCheck($response['code'] === 200 && !empty($response['json']['status']), 'Decision save: ' . $response['body']);
        $decision = new DecisionProcess(); $decision->load((int)$response['json']['decisionId'], true); $decisions[] = $decision;
        $proposals = iterator_to_array($decision->getProposals(false));
        $changes = $proposals[0]->getDeferredProposals();
        mcpCheck(count($changes) === 1 && DeferredProposal::normalizeState($changes[0]->get('after_state'))['authority_choice'] === $choice, 'Decision stores choice');
    }
    mcpCheck($items['authority']->load($items['authority']->getId(), true), 'Preparing proposals never removes authorities');
    mcpCheck($items['role']->load($hid, true) && $items['role']->get('active'), 'Preparing proposals never removes holon');
    echo "holon_removal_proposal_http_test: OK\n";
} finally {
    foreach ($decisions as $decision) $decision->delete();
    mcpCleanup($items); $_SESSION = $before;
}
