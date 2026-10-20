<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{Application, ControlActivity, DeferredProposal, Document, DocumentPvPoint, HolonPermission,
    OrganizationApplication, Permission, Project, StatIndicator, UserHolon};

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = $_POST ? 'POST' : 'GET';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    if (!in_array($request['endpoint'], ['pv_project_editor.php', 'pv_project_save.php', 'pv_object_editor.php', 'pv_object_save.php'], true)) exit(1);
    $_SERVER['REQUEST_URI'] = '/omo/api/deferred_proposals/' . $request['endpoint'];
    require dirname(__DIR__) . $_SERVER['REQUEST_URI'];
    exit;
}

function creationSpaceRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Cannot start proposal endpoint.');
    fclose($pipes[0]);
    $body = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0, 'Proposal endpoint failed: ' . $errors);
    return $body;
}

function creationSpaceForm(string $body): array
{
    $dom = new DOMDocument(); @$dom->loadHTML('<?xml encoding="UTF-8">' . $body);
    $xpath = new DOMXPath($dom);
    $button = $xpath->query('//*[@data-holon-target-selector]')->item(0);
    mcpCheck($button instanceof DOMElement, 'Creation form must contain the shared space picker.');
    mcpCheck($xpath->query('//input[@data-holon-target-label and @readonly]')->length === 1, 'Space label must be readonly.');
    mcpCheck($xpath->query('//input[@name="holon_id"]')->length === 1, 'Exactly one submitted destination is required.');
    mcpCheck($xpath->query('//select[@name="holon_id"]')->length === 0, 'Use the text field and attached picker button.');
    return ['config' => json_decode($button->getAttribute('data-holon-target-selector'), true, 512, JSON_THROW_ON_ERROR),
        'selected' => (int)$xpath->query('//input[@name="holon_id"]')->item(0)->getAttribute('value')];
}

$before = $_SESSION;
$items = mcpFixtures();
$browser = ['html' => [], 'reopened' => []];
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    foreach (['projects', 'stats', 'activities', 'documents'] as $hash) {
        $app = new Application(); mcpCheck($app->load([['hash', $hash]]), 'Missing app ' . $hash);
        $items['app_' . $hash] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $items['collective'] = mcpFixture(McpTestHolon::class, ['IDorganization' => $oid, 'IDholon_org' => $items['root']->getId(),
        'IDholon_parent' => $items['root']->getId(), 'IDtypeholon' => 2, 'name' => 'PV collective', 'visible' => 1, 'active' => 1]);
    $hid = (int)$items['collective']->getId();
    $items['destination'] = mcpFixture(McpTestHolon::class, ['IDorganization' => $oid, 'IDholon_org' => $items['root']->getId(),
        'IDholon_parent' => $hid, 'IDtypeholon' => 2, 'name' => 'Allowed destination', 'visible' => 1, 'active' => 1]);
    $destination = (int)$items['destination']->getId();
    foreach (['CAN_CREATE_PROJECT', 'CAN_CREATE_INDICATOR', 'CAN_CREATE_RECURRING_TASK'] as $key) {
        foreach (['self', 'direct_children'] as $range) {
            $items[$key . $range] = mcpFixture(HolonPermission::class, ['IDholon' => $hid,
                'IDpermission' => Permission::findByKey($key)->getId(), 'range' => $range, 'member_type' => 'collective']);
        }
        // A personal grant outside the collective scope must never authorize a proposal destination.
        $items['personal_' . $key] = mcpFixture(HolonPermission::class, ['IDholon' => $items['role']->getId(),
            'IDpermission' => Permission::findByKey($key)->getId(), 'range' => 'self', 'member_type' => 'member']);
    }
    $items['holon_membership'] = mcpFixture(UserHolon::class, ['IDholon' => $items['role']->getId(), 'IDuser' => $uid, 'active' => 1, 'is_membership' => 1]);
    $items['propose_only'] = mcpFixture(HolonPermission::class, ['IDholon' => $hid,
        'IDpermission' => Permission::findByKey('CAN_PROPOSE_PROJECT')->getId(), 'range' => 'parent_circle', 'member_type' => 'collective']);
    $items['pv'] = mcpFixture(Document::class, ['IDorganization' => $oid, 'IDholon' => $hid, 'IDuser' => $uid,
        'IDusercreation' => $uid, 'IDuser_pv_editor' => $uid, 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_MEETING, 'title' => 'Creation destination fixture', 'active' => 1]);
    $items['point'] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $items['pv']->getId(), 'IDuser_author' => $uid,
        'IDholon_concerned' => $hid, 'title' => 'Creation proposals', 'position' => 1]);
    $browser['destination'] = $destination;
    $browser['proposeOnly'] = (int)$items['root']->getId();
    foreach (['project' => Project::class, 'indicator' => StatIndicator::class, 'recurring_task' => ControlActivity::class] as $type => $objectClass) {
        $isProject = $type === 'project';
        $get = ['oid' => $oid, 'point_id' => $items['point']->getId(), 'holon_id' => $hid, 'operation' => 'create', 'target_type' => $type];
        $request = ['session' => $_SESSION, 'get' => $get, 'post' => [], 'endpoint' => $isProject ? 'pv_project_editor.php' : 'pv_object_editor.php'];
        $html = creationSpaceRequest($request); $browser['html'][$type] = $html;
        $field = creationSpaceForm($html);
        $expected = [$hid, $destination]; if ($isProject) $expected[] = (int)$items['root']->getId();
        sort($expected); $actual = $field['config']['selectableHolonIds']; sort($actual);
        mcpCheck($actual === $expected && !$field['config']['allowOrganization'], $type . ' destinations must use only collective creation rights.');
        $post = $get + ['project_id' => 0, 'object_id' => 0, 'title' => 'Created elsewhere', 'name' => 'Created elsewhere',
            'frequency' => 'daily', 'schedule' => '09:00', 'source_type' => StatIndicator::SOURCE_MANUAL];
        $post['holon_id'] = $destination;
        $request['endpoint'] = $isProject ? 'pv_project_save.php' : 'pv_object_save.php'; $request['post'] = $post;
        $result = json_decode(creationSpaceRequest($request), true);
        mcpCheck(!empty($result['status']), $type . ' must save at another authorized destination.');
        $proposal = new DeferredProposal(); mcpCheck($proposal->load((int)$result['id']), 'Cannot reload proposal.');
        $items['proposal_' . $type] = $proposal;
        mcpCheck((int)$proposal->get('IDholon') === $destination, 'Proposal must store its chosen destination.');
        mcpCheck((int)DeferredProposal::normalizeState($proposal->get('after_state'))['IDholon'] === $destination, 'Snapshot must match its destination.');
        // Reopening a pending creation must retain the selected space and allow a new authorized one.
        $edit = ['session' => $_SESSION, 'get' => $get + ['proposal_id' => $proposal->getId()], 'post' => [],
            'endpoint' => $isProject ? 'pv_project_editor.php' : 'pv_object_editor.php'];
        $browser['reopened'][$type] = creationSpaceRequest($edit);
        mcpCheck(creationSpaceForm($browser['reopened'][$type])['selected'] === $destination, 'Reopen must retain the destination.');
        $request['post']['proposal_id'] = $proposal->getId();
        $request['post']['holon_id'] = $isProject ? $items['root']->getId() : $hid;
        mcpCheck(!empty(json_decode(creationSpaceRequest($request), true)['status']), 'Pending creation must allow another authorized space.');
        $request['post']['holon_id'] = $destination;
        mcpCheck(!empty(json_decode(creationSpaceRequest($request), true)['status']), 'Pending creation must allow returning to the destination.');
        foreach ([$items['role']->getId(), $items['other_root']->getId(), 0] as $deniedId) {
            $request['post']['holon_id'] = $deniedId;
            mcpCheck(empty(json_decode(creationSpaceRequest($request), true)['status']), 'Reject forged, personal-only, foreign or empty destination.');
        }
        $request['post']['holon_id'] = $destination;
        $permissionKey = match ($type) { 'project' => 'CAN_CREATE_PROJECT', 'indicator' => 'CAN_CREATE_INDICATOR', default => 'CAN_CREATE_RECURRING_TASK' };
        $grant = $items[$permissionKey . 'direct_children'];
        $grant->set('range', 'organization_root'); $grant->save();
        mcpCheck(empty(json_decode(creationSpaceRequest($request), true)['status']), 'A permission revoked after opening must block save.');
        $grant->set('range', 'direct_children'); $grant->save();
        $proposal->load($proposal->getId(), true);
        $applied = DeferredProposal::applyBatch([$proposal], $hid, $uid, 'creation-space-test');
        mcpCheck(!empty($applied['status']), 'Proposal application must succeed: ' . json_encode($applied));
        $proposal->load($proposal->getId(), true);
        $object = new $objectClass();
        mcpCheck($object->load((int)$proposal->get('target_id')), 'Application must create the object.');
        $items['created_' . $type] = $object;
        mcpCheck((int)$object->get('IDholon') === $destination, 'The created object must use the chosen destination.');
    }
    file_put_contents(dirname(__DIR__) . '/tmp/pv-proposal-creation-space.json', json_encode($browser, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    echo "pv_proposal_creation_space_test: OK\n";
} catch (Throwable $error) {
    fwrite(STDERR, (string)$error . "\n"); throw $error;
} finally {
    mcpCleanup($items); $_SESSION = $before;
}
