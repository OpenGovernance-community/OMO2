<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{Application, ControlActivity, Document, DocumentApplicationTab, HolonPermission, OrganizationApplication, Permission, Project, StatIndicator};

if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session'];
    $_GET = $request['get'];
    $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_METHOD'] = $_POST ? 'POST' : 'GET';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    $endpoint = $request['endpoint'];
    if (!in_array($endpoint, ['projects/index.php', 'projects/detail.php', 'projects/create.php', 'projects/action.php',
        'stats/index.php', 'stats/detail.php', 'stats/edit.php', 'stats/action.php',
        'activities/index.php', 'activities/detail.php', 'activities/edit.php', 'activities/action.php'], true)) exit(1);
    $_SERVER['REQUEST_URI'] = '/omo/api/' . $endpoint;
    require dirname(__DIR__) . '/omo/api/' . $endpoint;
    exit;
}

function meetingAppRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Cannot start app endpoint.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0, 'App endpoint failed: ' . $errors);
    return $output;
}

function meetingAppXpath(string $html): DOMXPath
{
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    return new DOMXPath($dom);
}

$items = mcpFixtures();
$before = $_SESSION;
try {
    $oid = (int)$items['org']->getId();
    $uid = (int)$items['user']->getId();
    $hid = (int)$items['root']->getId();
    foreach (['projects', 'stats', 'activities', 'documents'] as $hash) {
        $app = new Application();
        mcpCheck($app->load([['hash', $hash]]), 'Missing seeded app ' . $hash);
        $items['app_' . $hash] = mcpFixture(OrganizationApplication::class,
            ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    foreach (['CAN_EDIT_PROJECT', 'CAN_CREATE_PROJECT', 'CAN_EDIT_INDICATOR', 'CAN_CREATE_INDICATOR',
        'CAN_EDIT_RECURRING_TASK', 'CAN_CREATE_RECURRING_TASK'] as $key) {
        $items[$key] = mcpFixture(HolonPermission::class, ['IDholon' => $hid,
            'IDpermission' => Permission::findByKey($key)->getId(), 'range' => 'self', 'member_type' => 'collective']);
        mcpCheck(!Permission::userCanInOrganization($key, $oid, $uid), 'Fixture must have no personal ' . $key);
    }
    $items['pv'] = mcpFixture(Document::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'IDuser' => $uid, 'IDusercreation' => $uid, 'IDuser_pv_editor' => $uid, 'documenttype' => Document::TYPE_PV,
        'pvstage' => Document::PV_STAGE_MEETING, 'title' => 'Meeting permissions fixture', 'active' => 1]);
    $items['project'] = mcpFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'title' => 'Collective project', 'status' => Project::STATUS_IN_PROGRESS, 'active' => 1]);
    $items['indicator'] = mcpFixture(StatIndicator::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'name' => 'Collective indicator', 'source_type' => StatIndicator::SOURCE_MANUAL, 'active' => 1]);
    $items['activity'] = mcpFixture(ControlActivity::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'title' => 'Collective task', 'frequency' => 'daily', 'schedule' => '09:00', 'active' => 1]);
    $did = (int)$items['pv']->getId();
    $token = bin2hex(random_bytes(16));
    $session = ['currentUser' => $uid, 'currentOrganization' => $oid,
        'omo_pv_editor_tokens' => [$oid . ':' . $did . ':user:' . $uid => $token]];
    $context = ['oid' => $oid, 'cid' => $hid, 'pv_meeting_document_id' => $did, 'pv_meeting_editor_token' => $token];
    $request = ['session' => $session, 'get' => $context, 'post' => [], 'endpoint' => 'projects/index.php'];
    $browser = ['context' => $context, 'html' => []];
    foreach (['projects', 'stats', 'activities'] as $module) {
        $items['tab_' . $module] = mcpFixture(DocumentApplicationTab::class, ['IDdocument' => $did,
            'IDapplication' => $items['app_' . $module]->get('IDapplication')]);
        $request['get']['pv_application_tab_id'] = $items['tab_' . $module]->getId();
        $request['endpoint'] = $module . '/index.php';
        $html = meetingAppRequest($request);
        $browser['html'][$module] = $html;
        $xpath = meetingAppXpath($html);
        $rootId = 'omo-' . $module . '-root';
        mcpCheck($xpath->query('//*[@id="' . $rootId . '"]')->length === 1, $module . ' tab must render.');
        $view = json_decode($xpath->query('//*[@id="' . $rootId . '"]')->item(0)->getAttribute('data-omo-app-view-preferences'), true);
        mcpCheck(!empty($view['isPvApplicationTab']), $module . ' must render as an actual PV application tab.');
        if ($module === 'projects') {
            mcpCheck($xpath->query('//*[@data-omo-project-card and @draggable="true"]')->length === 1,
                'Collective edit permission must make the kanban card draggable.');
        }
        $id = (int)$items[match ($module) { 'projects' => 'project', 'stats' => 'indicator', default => 'activity' }]->getId();
        $request['get']['id'] = $id;
        $request['endpoint'] = $module . '/detail.php';
        $detail = meetingAppRequest($request);
        $browser['html'][$module . '_detail'] = $detail;
        $xpath = meetingAppXpath($detail);
        // Locate the URL itself, independently of each module's edit-button naming.
        $links = $xpath->query('//@*');
        $editUrl = '';
        foreach ($links as $link) {
            if (str_starts_with($link->nodeValue, '/omo/api/' . $module . '/' . ($module === 'projects' ? 'create' : 'edit') . '.php?')) {
                $editUrl = $link->nodeValue; break;
            }
        }
        parse_str((string)parse_url($editUrl, PHP_URL_QUERY), $params);
        mcpCheck(($params['pv_meeting_editor_token'] ?? '') === $token, $module . ' edit link must retain the meeting token.');
        mcpCheck((int)($params['pv_meeting_document_id'] ?? 0) === $did, $module . ' edit link must retain the PV.');
        $request['get'] = $params;
        $request['endpoint'] = $module . '/' . ($module === 'projects' ? 'create' : 'edit') . '.php';
        mcpCheck(str_contains(meetingAppRequest($request), 'name="pv_meeting_editor_token"'), $module . ' form must retain collective rights.');
        $request['get'] = $context;
    }
    $request['endpoint'] = 'projects/action.php';
    $request['post'] = $context + ['id' => $items['project']->getId(), 'project_action' => 'update_status', 'status' => Project::STATUS_DONE];
    $result = json_decode(meetingAppRequest($request), true);
    mcpCheck(!empty($result['status']), 'Collective kanban status update must succeed.');
    $items['project']->load($items['project']->getId(), true);
    mcpCheck($items['project']->get('status') === Project::STATUS_DONE, 'Kanban update must persist.');
    $move = $request;
    $move['post']['project_action'] = 'update_kanban_position';
    $move['post']['group_kind'] = 'holon';
    $move['post']['target_holon_id'] = $items['role']->getId();
    mcpCheck(empty(json_decode(meetingAppRequest($move), true)['status']), 'A kanban move requires creation rights at the destination.');
    $items['CAN_CREATE_PROJECT']->set('range', 'organization'); $items['CAN_CREATE_PROJECT']->save();
    mcpCheck(!empty(json_decode(meetingAppRequest($move), true)['status']), 'Collective creation rights must authorize the destination.');
    $items['project']->load($items['project']->getId(), true);
    mcpCheck((int)$items['project']->get('IDholon') === (int)$items['role']->getId(), 'Kanban holon move must persist.');
    $items['project']->set('IDholon', $hid); $items['project']->save();
    foreach (['stats', 'activities'] as $module) {
        $save = ['session' => $session, 'get' => $context, 'endpoint' => $module . '/action.php',
            'post' => $context + ($module === 'stats'
                ? ['stats_action' => 'save_indicator', 'id' => $items['indicator']->getId(), 'name' => 'Updated collectively', 'source_type' => StatIndicator::SOURCE_MANUAL]
                : ['activity_action' => 'save_activity', 'id' => $items['activity']->getId(), 'title' => 'Updated collectively', 'frequency' => 'daily', 'schedule' => '09:00'])];
        mcpCheck(!empty(json_decode(meetingAppRequest($save), true)['status']), $module . ' collective save must succeed.');
        unset($save['post']['pv_meeting_editor_token']);
        mcpCheck(empty(json_decode(meetingAppRequest($save), true)['status']), $module . ' save must fail without a meeting token.');
    }
    // Negative controls exercise the same real endpoint with no personal permission.
    foreach (['missing_token', 'invalid_token', 'other_editor', 'review', 'out_of_scope', 'revoked'] as $case) {
        $negative = $request;
        $negative['post']['status'] = Project::STATUS_IN_PROGRESS;
        if ($case === 'missing_token') unset($negative['post']['pv_meeting_editor_token']);
        if ($case === 'invalid_token') $negative['post']['pv_meeting_editor_token'] = 'invalid';
        if ($case === 'other_editor') { $items['pv']->set('IDuser_pv_editor', null); $items['pv']->save(); }
        if ($case === 'review') { $items['pv']->set('pvstage', Document::PV_STAGE_REVIEW); $items['pv']->save(); }
        if ($case === 'out_of_scope') { $items['project']->set('IDholon', $items['role']->getId()); $items['project']->save(); }
        if ($case === 'revoked') {
            // Keep the permission configured, so legacy unconfigured defaults cannot reopen it.
            $items['CAN_EDIT_PROJECT']->set('IDholon', $items['role']->getId());
            $items['CAN_EDIT_PROJECT']->save();
        }
        mcpCheck(empty(json_decode(meetingAppRequest($negative), true)['status']), 'Must reject ' . $case);
        $items['pv']->set('IDuser_pv_editor', $uid); $items['pv']->set('pvstage', Document::PV_STAGE_MEETING); $items['pv']->save();
        $items['project']->set('IDholon', $hid); $items['project']->save();
    }
    file_put_contents(dirname(__DIR__) . '/tmp/pv-meeting-app-permissions.json', json_encode($browser, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    echo "pv_meeting_app_permissions_test: OK\n";
} catch (Throwable $error) {
    fwrite(STDERR, (string)$error . "\n");
    throw $error;
} finally {
    $_SESSION = $before;
    mcpCleanup($items);
}
