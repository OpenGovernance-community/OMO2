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
use dbObject\{Project, ControlActivity, ProjectRecurringTask};

function projectRecurringTaskRequest(array $request): string
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
    foreach (['activities', 'projects'] as $hash) {
        $app = new \dbObject\Application(); mcpCheck($app->load([['hash', $hash]]), 'Missing app.');
        $items['app_' . $hash] = mcpFixture(\dbObject\OrganizationApplication::class,
            ['IDapplication' => $app->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    $items['assignment'] = mcpFixture(\dbObject\UserHolon::class,
        ['IDholon' => $hid, 'IDuser' => $uid, 'active' => 1, 'is_membership' => 1]);
    $items['permission'] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $hid,
        'IDpermission' => \dbObject\Permission::findByKey('CAN_CREATE_RECURRING_TASK')->getId(), 'member_type' => 'member', 'range' => 'self']);
    $items['project'] = $project = mcpFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'IDuser' => $uid, 'title' => 'Recurring task project', 'status' => Project::STATUS_IN_PROGRESS, 'active' => 1]);
    $request = ['session' => ['currentUser' => $uid, 'currentOrganization' => $oid],
        'endpoint' => 'activities/edit.php', 'get' => ['oid' => $oid, 'cid' => $hid, 'project_id' => $project->getId()], 'post' => []];
    $editor = projectRecurringTaskRequest($request);
    foreach (['id="omo-project-activity-editor-form"', 'name="project_id"', 'name="IDuser_responsible"', 'name="display_lead_value"', 'name="execution_duration_value"'] as $needle) {
        mcpCheck(str_contains($editor, $needle), 'Full project editor missing ' . $needle);
    }
    $request['endpoint'] = 'activities/action.php'; $request['get'] = [];
    $request['post'] = ['oid' => $oid, 'cid' => $hid, 'project_id' => $project->getId(), 'activity_action' => 'save_activity',
        'title' => 'Project task', 'description' => '<p>Full editor</p>', 'IDuser_responsible' => $uid, 'frequency' => 'weekly', 'schedule' => '1', 'display_lead_value' => 3, 'display_lead_unit' => 'day', 'execution_duration_value' => 2, 'execution_duration_unit' => 'day'];
    $saved = json_decode(projectRecurringTaskRequest($request), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($saved['status']), 'Creation failed: ' . json_encode($saved));
    $items['task'] = $task = new ControlActivity(); mcpCheck($task->load((int)$saved['id']), 'Missing task.');
    mcpCheck((int)$task->get('IDuser_responsible') === $uid && (int)$task->get('display_lead_value') === 3
        && (int)$task->get('execution_duration_value') === 2 && $task->get('description') === '<p>Full editor</p>',
        'The full task settings must be preserved.');
    $items['link'] = $link = new ProjectRecurringTask();
    mcpCheck($link->load([['IDproject', $project->getId()], ['IDrecurringtask', $task->getId()]]), 'Creation must link to the project.');
    $request['post']['id'] = $task->getId();
    $items['edit_permission'] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $hid, 'IDpermission' => \dbObject\Permission::findByKey('CAN_EDIT_RECURRING_TASK')->getId(), 'member_type' => 'member', 'range' => 'self']);
    $savedAgain = json_decode(projectRecurringTaskRequest($request), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($savedAgain['status']), 'Saving again must retain the existing link.');
    $catalogRequest = $request; $catalogRequest['endpoint'] = 'projects/resources.php'; $catalogRequest['post'] = [];
    $catalogRequest['get'] = ['oid' => $oid, 'cid' => $hid, 'id' => $project->getId(), 'type' => 'recurring_task', 'picker' => 1];
    $catalog = json_decode(projectRecurringTaskRequest($catalogRequest), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($catalog['success']) && !in_array($task->getId(), array_column($catalog['items'], 'id')), 'Already linked task must be excluded from import.');
    unset($catalogRequest['get']['picker']);
    $list = projectRecurringTaskRequest($catalogRequest);
    mcpCheck(str_contains($list, 'generic-menu--split') && str_contains($list, 'data-omo-project-recurring-task-editor-url'), 'Recurring tasks must have the split creation button.');
    mcpCheck(str_contains($list, 'Project task'), 'Created task must be visible in the project.');
    $standard = $request; unset($standard['post']['project_id'], $standard['post']['id']);
    $standard['post']['title'] = 'Standalone task';
    $standardResult = json_decode(projectRecurringTaskRequest($standard), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($standardResult['status']), 'Standard task creation must remain available.');
    $items['standard_task'] = new ControlActivity(); $items['standard_task']->load((int)$standardResult['id']);
    $items['foreign_project'] = mcpFixture(Project::class, ['IDorganization' => $items['other_org']->getId(),
        'IDholon' => $items['other_root']->getId(), 'title' => 'Foreign project', 'active' => 1]);
    $foreign = $request; $foreign['post']['project_id'] = $items['foreign_project']->getId();
    $foreignResult = json_decode(projectRecurringTaskRequest($foreign), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(empty($foreignResult['status']), 'Cross-organization project association must be rejected.');
    $project->set('active', 0); $project->save();
    $request['post']['id'] = 0;
    $rejected = json_decode(projectRecurringTaskRequest($request), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(empty($rejected['status']), 'Archived projects must reject task creation.');
    echo "project_recurring_task_workflow_test: OK\n";
} finally { mcpCleanup($items); }
