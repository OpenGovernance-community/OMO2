<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
if (($argv[1] ?? '') === '--request') {
    $request = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
    $_SESSION = $request['session']; $_GET = $request['get']; $_POST = $request['post'];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['HTTP_HOST'] = 'localtest.me';
    $_SERVER['REQUEST_URI'] = '/omo/api/projects/' . $request['endpoint'];
    $_SERVER['REQUEST_METHOD'] = $_POST ? 'POST' : 'GET';
    require dirname(__DIR__) . '/omo/api/projects/' . $request['endpoint'];
    exit;
}
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/omo/translations.php';
require_once dirname(__DIR__) . '/omo/api/projects/shared.php';
use dbObject\{Project, User, UserOrganization, UserHolon, ArrayProject, ObjectVisibility};
function omoApiEscape($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

function projectVisibilityRequest(array $request): string
{
    $process = proc_open([PHP_BINARY, __FILE__, '--request', base64_encode(json_encode($request))],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    mcpCheck(is_resource($process), 'Cannot start project endpoint.');
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    mcpCheck(proc_close($process) === 0 && $errors === '', 'Project endpoint failed: ' . $errors . $output);
    return $output;
}
$items = mcpFixtures();
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId();
    $hid = (int)$items['role']->getId(); $root = (int)$items['root']->getId();
    $app = new \dbObject\Application(); mcpCheck($app->load([['hash', 'projects']]), 'Missing projects application.');
    $items['projects_app'] = mcpFixture(\dbObject\OrganizationApplication::class,
        ['IDapplication' => $app->getId(), 'IDorganization' => $oid, 'active' => 1]);
    foreach (['coworker', 'circle_peer', 'organization_peer', 'assignee'] as $key) {
        $items[$key] = mcpFixture(User::class, ['firstname' => $key, 'email' => $key . '-' . bin2hex(random_bytes(6)) . '@example.invalid', 'active' => 1]);
        $items[$key . '_org'] = mcpFixture(UserOrganization::class,
            ['IDuser' => $items[$key]->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    $items['circle'] = mcpFixture(McpTestHolon::class, ['IDorganization' => $oid, 'IDholon_org' => $root,
        'IDholon_parent' => $root, 'name' => 'Visibility circle', 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
    $circleId = (int)$items['circle']->getId();
    $items['role']->set('IDholon_parent', $circleId); mcpCheck(!empty($items['role']->save()['status']), 'Cannot move fixture role.');
    $items['peer_role'] = mcpFixture(McpTestHolon::class, ['IDorganization' => $oid, 'IDholon_org' => $root,
        'IDholon_parent' => $circleId, 'name' => 'Peer role', 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    foreach (['owner' => [$uid, $hid], 'coworker' => [$items['coworker']->getId(), $hid],
        'circle_peer' => [$items['circle_peer']->getId(), $items['peer_role']->getId()]] as $key => [$userId, $holonId]) {
        $items[$key . '_assignment'] = mcpFixture(UserHolon::class,
            ['IDuser' => $userId, 'IDholon' => $holonId, 'active' => 1, 'is_membership' => 1]);
    }
    foreach (['CAN_CREATE_PROJECT', 'CAN_EDIT_PROJECT', 'CAN_DELETE_PROJECT'] as $key) {
        $items[$key] = mcpFixture(\dbObject\HolonPermission::class, ['IDholon' => $hid,
            'IDpermission' => \dbObject\Permission::findByKey($key)->getId(), 'member_type' => 'member', 'range' => 'self']);
    }
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $items['project'] = $project = mcpFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => $hid,
        'title' => 'Visibility fixture project', 'status' => Project::STATUS_READY, 'active' => 1]);
    $pid = (int)$project->getId();
    mcpCheck((int)$project->get('IDusercreation') === $uid && $project->getVisibilityType() === 'organization', 'Creator and legacy default must be recorded.');
    $viewers = [$uid, (int)$items['coworker']->getId(), (int)$items['circle_peer']->getId(),
        (int)$items['organization_peer']->getId(), 0];
    foreach (['self' => [true, false, false, false, false], 'role' => [true, true, false, false, false],
        'circle' => [true, true, true, false, false], 'organization' => [true, true, true, true, false],
        'everyone' => [true, true, true, true, true]] as $type => $expected) {
        $project->set('visibility_type', $type); mcpCheck(!empty($project->save()['status']), 'Cannot save visibility ' . $type);
        foreach ($viewers as $index => $viewerId) {
            mcpCheck($project->currentViewerCanAccessVisibility($viewerId) === $expected[$index], 'Wrong ' . $type . ' visibility for viewer ' . $index);
        }
    }
    $request = ['session' => $_SESSION, 'endpoint' => 'create.php', 'get' => ['oid' => $oid, 'cid' => $hid, 'id' => $pid], 'post' => []];
    $editor = projectVisibilityRequest($request);
    mcpCheck(str_contains($editor, 'data-omo-visibility-choice') && substr_count($editor, 'name="visibility_type"') === 5, 'Editor must reuse the five-position selector.');
    $request['endpoint'] = 'action.php'; $request['get'] = [];
    $request['post'] = ['oid' => $oid, 'cid' => $hid, 'id' => $pid, 'project_action' => 'save_project',
        'IDholon' => $hid, 'title' => 'Visibility fixture project', 'status' => 'ready', 'visibility_type' => 'self'];
    $saved = json_decode(projectVisibilityRequest($request), true, 512, JSON_THROW_ON_ERROR);
    mcpCheck(!empty($saved['status']) && $project->load($pid, true) && $project->getVisibilityType() === 'self', 'Editor must persist the chosen visibility: ' . json_encode($saved));
    $request['post']['visibility_type'] = 'invalid';
    mcpCheck(empty(json_decode(projectVisibilityRequest($request), true)['status']), 'Invalid visibility must be rejected.');
    $items['owner_assignment']->set('active', 0); mcpCheck(!empty($items['owner_assignment']->save()['status']), 'Cannot remove owner assignment.');
    mcpCheck(!$project->currentViewerCanAccessVisibility($uid), 'Departed creator must lose personal visibility.');
    mcpCheck($project->currentViewerCanAccessVisibility((int)$items['coworker']->getId()), 'Personal visibility must pass to the holon.');
    mcpCheck(!$project->currentViewerCanAccessVisibility((int)$items['circle_peer']->getId()), 'Personal fallback must remain limited to the exact holon.');
    foreach (['self', 'role', 'circle'] as $type) {
        $project->set('visibility_type', $type); $project->set('IDuser', $items['assignee']->getId());
        mcpCheck(!empty($project->save()['status']) && $project->currentViewerCanAccessVisibility((int)$items['assignee']->getId()), 'Assignee must see ' . $type . ' projects without holon membership.');
    }
    $project->set('visibility_type', 'role'); $project->set('IDusercreation', $items['assignee']->getId());
    mcpCheck(!empty($project->save()['status']) && (int)$project->get('IDusercreation') === $uid, 'Editing must preserve the original personal owner.');
    $request = ['session' => ['currentUser' => $uid, 'currentOrganization' => $oid], 'endpoint' => 'detail.php',
        'get' => ['oid' => $oid, 'cid' => $hid, 'id' => $pid], 'post' => []];
    mcpCheck(!str_contains(projectVisibilityRequest($request), 'Visibility fixture project'), 'Direct detail must hide a departed creator project.');
    $request['endpoint'] = 'index.php'; unset($request['get']['id']);
    mcpCheck(!str_contains(projectVisibilityRequest($request), 'Visibility fixture project'), 'Project lists must hide restricted projects.');
    $request['endpoint'] = 'create.php'; $request['get']['id'] = $pid;
    mcpCheck(!str_contains(projectVisibilityRequest($request), 'name="title"'), 'Direct editor must deny hidden projects.');
    $request['endpoint'] = 'action.php';
    $request['post'] = ['oid' => $oid, 'cid' => $hid, 'id' => $pid, 'project_action' => 'save_project', 'title' => 'Forbidden edit'];
    mcpCheck(empty(json_decode(projectVisibilityRequest($request), true)['status']), 'Direct writes must deny hidden projects.');
    $request['post'] = [];
    $request['session']['currentUser'] = (int)$items['assignee']->getId(); $request['endpoint'] = 'detail.php';
    mcpCheck(str_contains(projectVisibilityRequest($request), 'Visibility fixture project'), 'Assigned person must retain direct access.');
    mcpCheck(!isset(ArrayProject::fetchTitlesForOrganization($oid)[$pid]) && !isset(ArrayProject::fetchTitlesForProjectIds($oid, [$pid])[$pid]), 'Title catalogs must hide restricted projects.');
    $viewer = ['type' => 'user', 'userId' => $uid, 'organizationId' => $oid, 'currentHolonId' => $root];
    $results = $items['org']->searchTopbarResults('Visibility fixture', ['projects'], ['viewerContext' => $viewer]);
    mcpCheck(empty($results['results']), 'Search must not reveal restricted project titles or snippets.');
    mcpCheck($items['org']->loadTopbarSearchPreviewObject('projects', $pid, $viewer) === null, 'Search previews must deny restricted projects.');
    $record = (new ReflectionMethod(\dbObject\OrganizationExport::class, 'buildProjectRecord'))->invoke(null, $project);
    mcpCheck($record['visibilityType'] === 'role' && $record['sourceCreatorUserId'] === $uid, 'Exports must preserve visibility and the personal owner.');
    $importIds = []; $stats = ['projects' => 0];
    (new ReflectionMethod(\dbObject\Organization::class, 'omo1ImportProjects'))->invokeArgs(null,
        [$items['other_org'], [$record], $uid, [$uid => (int)$items['coworker']->getId()],
            [$hid => (int)$items['other_root']->getId()], [], [], &$importIds, &$stats]);
    $items['imported_project'] = $imported = new Project();
    mcpCheck($imported->load($importIds[$pid]) && $imported->getVisibilityType() === 'role'
        && (int)$imported->get('IDusercreation') === (int)$items['coworker']->getId(), 'Import must remap the personal owner and preserve visibility.');
    $adminMembership = $items['user']->getOrganizationMembership($oid);
    mcpCheck(!empty($adminMembership->setOrganizationAdmin(true)['status']), 'Cannot enable organization admin membership.');
    mcpCheck(commonSetCurrentUserAdminMode(true, $oid), 'Cannot enable organization admin mode.');
    foreach (['self', 'role', 'circle'] as $type) {
        $project->set('visibility_type', $type); mcpCheck(!empty($project->save()['status']), 'Cannot set admin test visibility.');
        mcpCheck($project->currentViewerCanAccessVisibility($uid), 'Enabled organization admin must see ' . $type . ' projects.');
    }
    $adminRequest = ['session' => $_SESSION, 'endpoint' => 'detail.php', 'get' => ['oid' => $oid, 'cid' => $hid, 'id' => $pid], 'post' => []];
    mcpCheck(str_contains(projectVisibilityRequest($adminRequest), 'Visibility fixture project'), 'Enabled admin mode must retain direct project access.');
    mcpCheck(isset(ArrayProject::fetchTitlesForOrganization($oid)[$pid]), 'Enabled admin mode must expose all project titles.');
    commonSetCurrentUserAdminMode(false, $oid);
    mcpCheck(!$project->currentViewerCanAccessVisibility($uid), 'Admin title alone must not bypass visibility when mode is disabled.');
    $_SESSION['isAdminByOrganization'] = [(int)$items['other_org']->getId() => true];
    mcpCheck(!$project->currentViewerCanAccessVisibility($uid), 'Admin mode must be scoped to its organization.');
    mcpCheck(commonSetCurrentUserAdminMode(true, $oid), 'Cannot reactivate admin mode.');
    mcpCheck(!empty($adminMembership->setOrganizationAdmin(false)['status']), 'Cannot revoke organization admin status.');
    mcpCheck(!$project->currentViewerCanAccessVisibility($uid), 'Revoked admin status must invalidate an enabled session flag.');
    echo "project_visibility_test: OK\n";
} finally { mcpCleanup($items); }
