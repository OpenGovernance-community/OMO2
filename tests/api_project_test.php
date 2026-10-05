<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/api/rest.php';
use dbObject\{McpProjectWrite, McpOauthGrant, ArrayApplication, OrganizationApplication, UserHolon,
    ArrayPermission, HolonPermission, Project, ArrayProject, History};

function projectApiHttp(string $token, string $method, string $path, ?array $body = null): array
{
    mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Project HTTP fixtures only run on local Docker');
    $curl = curl_init(omoMcpIssuer() . $path); $headers = [];
    curl_setopt_array($curl, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
        CURLOPT_HEADERFUNCTION => static function($handle, string $line) use (&$headers): int {
            $parts = explode(':', trim($line), 2); if (count($parts) === 2) $headers[strtolower($parts[0])] = trim($parts[1]); return strlen($line);
        }]);
    if ($body !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode((object)$body, JSON_THROW_ON_ERROR));
    $raw = curl_exec($curl); if ($raw === false) throw new RuntimeException(curl_error($curl));
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); unset($curl);
    return ['status' => $status, 'data' => json_decode($raw, true, 64, JSON_THROW_ON_ERROR), 'headers' => $headers];
}
function projectApiRejected(callable $action, string $message): void
{
    $rejected = false; try { $action(); } catch (DomainException | InvalidArgumentException $error) { $rejected = true; }
    mcpCheck($rejected, $message);
}
$before = $_SESSION; $items = mcpFixtures(); $projects = [];
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId(); $hid = (int)$items['role']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $apps = new ArrayApplication(); $apps->load(['where' => [['field' => 'hash', 'op' => 'IN', 'value' => ['projects', 'team']]]]);
    foreach ($apps as $app) $items['app_' . $app->get('hash')] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $items['assignment'] = mcpFixture(UserHolon::class, ['IDholon' => $hid, 'IDuser' => $uid, 'active' => 1, 'is_membership' => 1]);
    $permissions = new ArrayPermission(); $permissions->load(['where' => [['field' => 'permission_key', 'op' => 'IN', 'value' => ['CAN_CREATE_PROJECT', 'CAN_EDIT_PROJECT']]]]);
    mcpCheck(count($permissions) === 2, 'Native project permissions exist');
    foreach ($permissions as $permission) $items[$permission->get('permission_key')] = mcpFixture(HolonPermission::class,
        ['IDholon' => $hid, 'IDpermission' => $permission->getId(), 'range' => 'self', 'member_type' => 'member']);
    $request = mcpAuthorizationRequest($items['client']); $request['scope'] .= ' ' . OMO_MCP_PROJECT_SCOPE;
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $request);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $grant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    $readRequest = mcpAuthorizationRequest($items['client']);
    $readCode = McpOauthGrant::issueCode($items['client'], $uid, $oid, $readRequest);
    $readTokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $readCode));
    $base = ['holon_id' => $hid, 'title' => 'Project through integration', 'description' => '<script>plain text</script>', 'parent_id' => 0,
        'responsible_user_id' => null, 'status' => 'ready', 'priority' => 2, 'importance' => 4,
        'planned_start_date' => null, 'planned_end_date' => null, 'request_key' => 'api-native-project-create'];
    $spaces = McpProjectWrite::spaces($grant, ['kind' => 'holons', 'limit' => 1]);
    mcpCheck(count($spaces['items']) === 1 && $spaces['items'][0]['holon_id'] === $hid && count($spaces['statuses']) === 6, 'Discovery includes native permissions and statuses');
    mcpCheck(McpProjectWrite::spaces($grant, ['kind' => 'holons', 'after_id' => $spaces['next_after_id']])['complete'], 'Project space cursor finishes');
    mcpCheck(McpProjectWrite::spaces($grant, [])['items'] === [], 'Organization root permission required');
    foreach (['holon_id', 'parent_id', 'responsible_user_id', 'priority', 'importance', 'planned_end_date', 'status'] as $missing) {
        $args = $base; unset($args[$missing]); projectApiRejected(fn() => omoApiProjectValidate('omo_create_project', $args), 'Missing planning choices cannot be guessed: ' . $missing);
    }
    foreach (['hidden', 'inactive', 'other_root', 'root'] as $fixture) projectApiRejected(fn() => McpProjectWrite::write($grant,
        array_replace($base, ['holon_id' => (int)$items[$fixture]->getId()]), true), 'Unauthorized destination rejected');
    $denied = projectApiHttp($readTokens['access_token'], 'POST', '/api/v1/projects', $base);
    mcpCheck($denied['status'] === 403 && $denied['data']['required_scope'] === OMO_MCP_PROJECT_SCOPE, 'Read OAuth token cannot write');
    $created = projectApiHttp($tokens['access_token'], 'POST', '/api/v1/projects', $base);
    mcpCheck($created['status'] === 201, 'REST creates native project: ' . json_encode($created['data']));
    $id = $created['data']['project']['project_id']; $project = new Project(); $project->load($id); $projects[] = $project;
    mcpCheck($created['data']['created'] && !$created['data']['replayed'] && $created['data']['project']['can_edit']
        && $project->get('priority') == 2 && $project->get('importance') == 4 && !str_contains($project->get('description'), '<script>'), 'Native fields and safe description saved');
    $httpRead = projectApiHttp($readTokens['access_token'], 'GET', '/api/v1/projects/' . $id);
    mcpCheck($httpRead['status'] === 200 && $httpRead['data'] === McpProjectWrite::read($grant, $id), 'Read-only token exposes structured project');
    $edit = ['project_id' => $id, 'expected_version' => $httpRead['data']['project']['version'], 'status' => 'in_progress', 'request_key' => 'api-native-project-start'];
    $patch = $edit; unset($patch['project_id']);
    mcpCheck(projectApiHttp($tokens['access_token'], 'PATCH', '/api/v1/projects/' . $id, $edit)['status'] === 400, 'Path ID cannot be overridden in JSON');
    $started = projectApiHttp($tokens['access_token'], 'PATCH', '/api/v1/projects/' . $id, $patch);
    mcpCheck($started['status'] === 200 && $started['data']['project']['status'] === 'in_progress'
        && $started['data']['project']['planned_start_date'] === (new DateTimeImmutable('today'))->format('Y-m-d'), 'PATCH preserves fields and applies native start date');
    $blocked = ['project_id' => $id, 'expected_version' => $started['data']['project']['version'], 'status' => 'blocked', 'request_key' => 'api-native-project-block'];
    foreach ([[], ['blocked_reason' => 'Waiting'], ['blocked_until' => '2030-01-04']] as $missing) projectApiRejected(fn() => McpProjectWrite::write($grant, $blocked + $missing, false), 'Both blocking details required');
    mcpCheck(McpProjectWrite::read($grant, $id)['project']['status'] === 'in_progress', 'Incomplete blocking does not mutate project');
    $blocked += ['blocked_reason' => 'Waiting for budget approval', 'blocked_until' => '2030-01-04'];
    $saved = McpProjectWrite::write($grant, $blocked, false);
    mcpCheck($saved['project']['blocked_until'] === '2030-01-04' && !$saved['project']['blocked_auto_reactivate'], 'Blocking reason, reconsideration date and manual reactivation saved');
    $stale = projectApiHttp($tokens['access_token'], 'PATCH', '/api/v1/projects/' . $id,
        ['expected_version' => $started['data']['project']['version'], 'status' => 'ready', 'request_key' => 'api-native-project-stale']);
    mcpCheck($stale['status'] === 409 && $stale['data']['error'] === 'project_changed', 'Stale version prevents lost update');
    $rpc = projectApiHttp($tokens['access_token'], 'POST', parse_url(omoMcpPublicUrl(), PHP_URL_PATH),
        ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'omo_update_project', 'arguments' => (object)$blocked]]);
    mcpCheck(empty($rpc['data']['result']['isError']) && $rpc['data']['result']['structuredContent']['replayed'], 'REST/MCP share update retries');
    $unblocked = McpProjectWrite::write($grant, ['project_id' => $id, 'expected_version' => $saved['project']['version'], 'status' => 'ready', 'request_key' => 'api-native-project-unblock'], false);
    mcpCheck($unblocked['project']['blocked_reason'] === null && $unblocked['project']['blocked_until'] === null, 'Leaving blocked clears native details');
    mcpCheck(McpProjectWrite::write($grant, $blocked, false)['project']['status'] === 'ready', 'Old retry never reapplies blocked status');
    $replay = McpProjectWrite::write($grant, array_reverse($base, true), true);
    mcpCheck($replay['replayed'] && $replay['project']['project_id'] === $id && $replay['project']['status'] === 'ready', 'Creation retry returns current project without duplicates');
    projectApiRejected(fn() => McpProjectWrite::write($grant, array_replace($base, ['title' => 'Changed']), true), 'Changed payload cannot reuse request key');
    foreach ([['priority' => 6], ['importance' => 0], ['planned_end_date' => '2030-02-30'], ['unexpected' => true], ['status' => 'unknown']] as $bad) {
        projectApiRejected(fn() => McpProjectWrite::write($grant, array_replace($base, $bad), true), 'Malformed project fields rejected');
    }
    $parentData = McpProjectWrite::write($grant, array_replace($base, ['title' => 'Parent', 'planned_end_date' => '2030-01-01', 'request_key' => 'api-native-project-parent']), true);
    $parentId = $parentData['project']['project_id']; $parent = new Project(); $parent->load($parentId); $projects[] = $parent;
    $childData = McpProjectWrite::write($grant, array_replace($base, ['title' => 'Child', 'parent_id' => $parentId, 'request_key' => 'api-native-project-child']), true);
    $childId = $childData['project']['project_id']; $child = new Project(); $child->load($childId); $projects[] = $child;
    mcpCheck($childData['project']['planned_end_date'] === '2030-01-01', 'Native parent deadline inherited');
    projectApiRejected(fn() => McpProjectWrite::write($grant, ['project_id' => $parentId, 'expected_version' => McpProjectWrite::read($grant, $parentId)['project']['version'], 'parent_id' => $childId, 'request_key' => 'api-native-project-cycle'], false), 'Parent cycle rejected');
    projectApiRejected(fn() => McpProjectWrite::write($grant, array_replace($base, ['parent_id' => $parentId, 'planned_start_date' => '2031-01-01', 'request_key' => 'api-native-project-bad-dates']), true), 'Inherited deadline before start rolls back');
    projectApiRejected(fn() => McpProjectWrite::write($grant, array_replace($base, ['parent_id' => $parentId, 'planned_end_date' => '2031-01-01', 'request_key' => 'api-native-project-late-child']), true), 'Native child deadline cannot exceed parent deadline');
    $all = new ArrayProject(); $all->loadForOrganization($oid);
    mcpCheck(count($all) === 3, 'Failed writes leave no orphan projects');
    $history = History::fetchProjectFeedPage($oid, $id);
    mcpCheck(count($history['items']) >= 4, 'Native create/status history retained');
    $foreign = mcpFixture(Project::class, ['IDorganization' => (int)$items['other_org']->getId(), 'IDholon' => (int)$items['other_root']->getId(), 'title' => 'Other organization project', 'active' => 1]);
    $projects[] = $foreign;
    projectApiRejected(fn() => McpProjectWrite::read($grant, (int)$foreign->getId()), 'Foreign project cannot be read');
    projectApiRejected(fn() => McpProjectWrite::write($grant, array_replace($base, ['parent_id' => (int)$foreign->getId(), 'request_key' => 'api-native-project-foreign']), true), 'Foreign parent cannot be linked');
    $hiddenProject = mcpFixture(Project::class, ['IDorganization' => $oid, 'IDholon' => (int)$items['hidden']->getId(), 'title' => 'Hidden project', 'active' => 1]);
    $projects[] = $hiddenProject;
    projectApiRejected(fn() => McpProjectWrite::read($grant, (int)$hiddenProject->getId()), 'Hidden project context cannot be read');
    projectApiRejected(fn() => McpProjectWrite::write($grant, array_replace($base, ['parent_id' => (int)$hiddenProject->getId(), 'request_key' => 'api-native-project-hidden']), true), 'Hidden parent cannot be linked');
    $nativeVersion = McpProjectWrite::read($grant, $id)['project']['version'];
    $project->load($id, true); $project->set('title', 'Native UI edit'); mcpCheck(!empty($project->save()['status']), 'Save native project edit');
    projectApiRejected(fn() => McpProjectWrite::write($grant, ['project_id' => $id, 'expected_version' => $nativeVersion, 'priority' => 1, 'request_key' => 'api-native-project-ui-conflict'], false), 'Native edits also invalidate API version');
    $items['CAN_EDIT_PROJECT']->set('IDholon', $items['root']->getId());
    mcpCheck(!empty($items['CAN_EDIT_PROJECT']->save()['status']), 'Move edit permission outside the creator role');
    $createOnlyArgs = array_replace($base, ['title' => 'Creation without edit permission', 'request_key' => 'api-native-project-create-only']);
    $createOnly = McpProjectWrite::write($grant, $createOnlyArgs, true); $createOnlyProject = new Project(); $createOnlyProject->load($createOnly['project']['project_id']); $projects[] = $createOnlyProject;
    mcpCheck(!$createOnly['project']['can_edit'] && McpProjectWrite::write($grant, $createOnlyArgs, true)['replayed'], 'Creation-only permission can recover a create retry');
    $items['assignment']->set('active', 0); $items['assignment']->save();
    projectApiRejected(fn() => McpProjectWrite::write($grant, ['project_id' => $id, 'expected_version' => $unblocked['project']['version'], 'status' => 'done', 'request_key' => 'api-native-project-denied'], false), 'Removed edit rights prevent writes');
    mcpCheck(McpProjectWrite::spaces($grant, ['kind' => 'holons'])['items'] === [], 'Discovery uses fresh current permissions');
    $project->load($id, true); $project->set('IDuser', $uid); mcpCheck(!empty($project->save()['status']), 'Assign native responsible person');
    $owned = McpProjectWrite::read($grant, $id);
    mcpCheck($owned['project']['can_edit'], 'Native responsible person remains allowed to manage');
    $finished = McpProjectWrite::write($grant, ['project_id' => $id, 'expected_version' => $owned['project']['version'], 'status' => 'done', 'request_key' => 'api-native-project-finish'], false);
    mcpCheck($finished['project']['status'] === 'done' && $finished['project']['planned_end_date'] !== null, 'Responsible person can finish project using native rules');
    McpOauthGrant::revokeOwned((int)$grant['id'], $uid);
    projectApiRejected(fn() => McpProjectWrite::write($grant, $base, true), 'Revoked grants cannot write or replay');
    echo "api_project_test: OK (REST/MCP, native planning/history, statuses/blocking, optimistic versions, retry safety, permissions and rollback)\n";
} finally {
    foreach (array_reverse($projects) as $project) $project->delete();
    mcpCleanup($items); $_SESSION = $before;
}
