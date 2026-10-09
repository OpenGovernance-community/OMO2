<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_URI'] = '/omo/api/' . $request['endpoint'];
    $_SERVER['REQUEST_METHOD'] = $_POST ? 'POST' : 'GET';
    require dirname(__DIR__) . '/omo/api/' . $request['endpoint'];
    exit;
}
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{Project, StatIndicator, ProjectIndicator};

function projectIndicatorRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Cannot start endpoint.');
    fclose($pipes[0]); $out = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Endpoint failed: ' . $errors . $out);
    return $out;
}
$items = mcpFixtures();
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId(); $hid = (int)$items['role']->getId();
    foreach (['stats', 'projects'] as $hash) {
        $app = new \dbObject\Application(); mcpCheck($app->load([['hash', $hash]]), 'Missing app.');
        $items['app_' . $hash] = mcpFixture(\dbObject\OrganizationApplication::class,
            ['IDapplication' => $app->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    $items['assignment'] = mcpFixture(\dbObject\UserHolon::class,
        ['IDholon' => $hid, 'IDuser' => $uid, 'active' => 1, 'is_membership' => 1]);
    $items['permission'] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $hid,
        'IDpermission' => \dbObject\Permission::findByKey('CAN_EDIT_INDICATOR')->getId(), 'member_type' => 'member', 'range' => 'self']);
    $items['project'] = $project = mcpFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'IDuser' => $uid, 'title' => 'Indicator project', 'status' => Project::STATUS_IN_PROGRESS, 'active' => 1]);
    $request = ['session' => ['currentUser' => $uid, 'currentOrganization' => $oid],
        'endpoint' => 'stats/edit.php', 'get' => ['oid' => $oid, 'cid' => $hid, 'project_id' => $project->getId()], 'post' => []];
    $editor = projectIndicatorRequest($request);
    foreach (['id="omoProjectIndicatorEditor"', 'name="project_id"', 'name="measurement_frequency"', 'name="reference_type"', 'name="source_type"'] as $needle) {
        mcpCheck(str_contains($editor, $needle), 'Full project editor missing ' . $needle);
    }
    $request['endpoint'] = 'stats/action.php'; $request['get'] = [];
    $request['post'] = ['oid' => $oid, 'cid' => $hid, 'project_id' => $project->getId(), 'stats_action' => 'save_indicator',
        'name' => 'Project measure', 'description' => 'Full editor', 'reference_type' => 'none', 'source_type' => 'manual'];
    $saved = json_decode(projectIndicatorRequest($request), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($saved['success']), 'Creation failed: ' . json_encode($saved));
    $items['indicator'] = $indicator = new StatIndicator(); mcpCheck($indicator->load((int)$saved['id']), 'Missing indicator.');
    $items['link'] = $link = new ProjectIndicator();
    mcpCheck($link->load([['IDproject', $project->getId()], ['IDstatindicator', $indicator->getId()]]), 'Creation must link to the project.');
    $request['post']['id'] = $indicator->getId();
    $savedAgain = json_decode(projectIndicatorRequest($request), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($savedAgain['success']), 'Saving again must retain the existing link.');
    $catalogRequest = $request; $catalogRequest['endpoint'] = 'projects/resources.php'; $catalogRequest['post'] = [];
    $catalogRequest['get'] = ['oid' => $oid, 'cid' => $hid, 'id' => $project->getId(), 'type' => 'indicator', 'picker' => 1];
    $catalog = json_decode(projectIndicatorRequest($catalogRequest), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($catalog['success']) && !in_array($indicator->getId(), array_column($catalog['items'], 'id')), 'Already linked indicator must be excluded from import.');
    unset($catalogRequest['get']['picker']);
    $list = projectIndicatorRequest($catalogRequest);
    mcpCheck(str_contains($list, 'generic-menu--split') && str_contains($list, 'data-omo-project-indicator-editor-url'), 'Indicators must have the split creation button.');
    mcpCheck(str_contains($list, 'Project measure'), 'Created indicator must be visible in the project.');
    $standard = $request; unset($standard['post']['project_id'], $standard['post']['id']);
    $standard['post']['name'] = 'Standalone measure';
    $standardResult = json_decode(projectIndicatorRequest($standard), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($standardResult['success']), 'Standard indicator creation must remain available.');
    $items['standard_indicator'] = new StatIndicator(); $items['standard_indicator']->load((int)$standardResult['id']);
    $items['foreign_project'] = mcpFixture(Project::class, ['IDorganization' => $items['other_org']->getId(),
        'IDholon' => $items['other_root']->getId(), 'title' => 'Foreign project', 'active' => 1]);
    $foreign = $request; $foreign['post']['project_id'] = $items['foreign_project']->getId();
    $foreignResult = json_decode(projectIndicatorRequest($foreign), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(empty($foreignResult['success']), 'Cross-organization project association must be rejected.');
    $project->set('active', 0); $project->save();
    $request['post']['id'] = 0;
    $rejected = json_decode(projectIndicatorRequest($request), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(empty($rejected['success']), 'Archived projects must reject indicator creation.');
    echo "project_indicator_workflow_test: OK\n";
} finally { mcpCleanup($items); }
