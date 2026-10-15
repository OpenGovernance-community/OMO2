<?php
declare(strict_types=1);
require_once __DIR__ . '/rest_test_helpers.php';

use dbObject\{DbObject, McpPermissions, HolonPermission, Permission, User, UserOrganization, UserHolon,
    ArrayApplication, OrganizationApplication, McpOauthGrant};

function permissionPages(array $grant, array $args, int $limit = 2): array
{
    $items = []; $cursor = null; $cursors = [];
    do {
        $page = omoApiExecuteOperation('omo_list_permissions', $args + ['limit' => $limit] + ($cursor === null ? [] : ['cursor' => $cursor]), $grant);
        mcpCheck(restSchemaMatches(json_decode(json_encode($page)), omoApiResponseSchemas()['PermissionPage'], omoApiResponseSchemas()), 'Permission response contract');
        array_push($items, ...$page['items']);
        $cursor = $page['next_cursor'];
        mcpCheck($page['complete'] === ($cursor === null), 'Completion agrees with cursor');
        mcpCheck($cursor === null || !isset($cursors[$cursor]), 'Cursor must advance');
        if ($cursor !== null) $cursors[$cursor] = true;
        mcpCheck(count($cursors) < 200, 'Unexpected pagination loop');
    } while ($cursor !== null);
    return $items;
}

mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Permission HTTP tests only run against local Docker');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$jar = tempnam(sys_get_temp_dir(), 'omo-permissions-test-');
$before = $_SESSION; $items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId(); $viewer = (int)$items['user']->getId(); $root = (int)$items['root']->getId();
    $_SESSION = ['currentUser' => $viewer, 'currentOrganization' => $oid];
    $grant = ['IDuser' => $viewer, 'IDorganization' => $oid, 'scope' => OMO_MCP_SCOPE];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'op' => 'IN', 'value' => ['team', 'documents']]]]);
    foreach ($apps as $app) $items['app_' . $app->get('hash')] = mcpFixture(OrganizationApplication::class,
        ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $makeHolon = static function (string $key, string $name, int $type, int $parent, array $extra = []) use (&$items, $oid, $root): int {
        $items[$key] = mcpFixture(McpTestHolon::class, $extra + ['IDorganization' => $oid, 'IDholon_org' => $root,
            'IDholon_parent' => $parent, 'IDtypeholon' => $type, 'name' => $name, 'active' => 1, 'visible' => 1]);
        return (int)$items[$key]->getId();
    };
    $circle = $makeHolon('communication', 'Communication', 2, $root);
    $budget = $makeHolon('budget', 'Budget', 2, $root);
    $nested = $makeHolon('cuisine', 'Cuisine', 2, $circle);
    $template = $makeHolon('template', 'Memory template', 1, $root, ['templatename' => 'Memory template', 'visible' => 0]);
    $intermediateTemplate = $makeHolon('intermediate_template', 'Inherited memory template', 1, $root,
        ['visible' => 0, 'IDholon_template' => $template]);
    $role = $makeHolon('memory', 'Memoire', 1, $circle, ['IDholon_template' => $intermediateTemplate]);
    $pilot = $makeHolon('pilot', 'Lien pilotage', 1, $circle);
    foreach (['germain' => 'Germain', 'luc' => 'Luc', 'inactive_member' => 'Inactive member'] as $key => $name) {
        $items[$key] = mcpFixture(User::class, ['firstname' => $name, 'active' => 1]);
        $items[$key . '_org'] = mcpFixture(UserOrganization::class,
            ['IDorganization' => $oid, 'IDuser' => $items[$key]->getId(), 'active' => $key !== 'inactive_member']);
    }
    $germain = (int)$items['germain']->getId(); $luc = (int)$items['luc']->getId();
    $items['germain_org']->setOrganizationAdmin(true);
    foreach (['germain_role' => [$germain, $role], 'luc_role' => [$luc, $pilot],
        'germain_hidden' => [$germain, (int)$items['hidden']->getId()],
        'inactive_role' => [(int)$items['inactive_member']->getId(), $role]] as $key => [$uid, $hid]) {
        $items[$key] = mcpFixture(UserHolon::class, ['IDuser' => $uid, 'IDholon' => $hid, 'active' => 1, 'is_membership' => 1]);
    }
    $makeGrant = static function (string $key, int $source, string $permission, string $range, string $profile = 'member', bool $extended = false) use (&$items): void {
        $definition = Permission::findByKey($permission);
        mcpCheck($definition !== null, 'Missing fixture permission ' . $permission);
        $items[$key] = mcpFixture(HolonPermission::class, ['IDholon' => $source, 'IDpermission' => $definition->getId(),
            'range' => $range, 'member_type' => $profile, 'is_extended' => $extended]);
    };
    $makeGrant('create_template', $template, 'CAN_CREATE_DOCUMENT', 'parent_circle');
    $makeGrant('create_pilot', $pilot, 'CAN_CREATE_DOCUMENT', 'parent_circle');
    $makeGrant('create_extended', $template, 'CAN_CREATE_DOCUMENT', 'organization', 'member', true);
    $makeGrant('edit_template', $template, 'CAN_EDIT_DOCUMENT', 'parent_circle_descendants');
    $makeGrant('edit_hidden', (int)$items['hidden']->getId(), 'CAN_EDIT_DOCUMENT', 'organization');
    $makeGrant('delete_collective', $template, 'CAN_DELETE_DOCUMENT', 'organization', 'collective');
    $makeGrant('delete_admin', $template, 'CAN_DELETE_PROJECT', 'self', 'admin');
    $makeGrant('edit_holon', $template, 'CAN_EDIT_HOLON', 'self');
    $makeGrant('extended_only', $template, 'CAN_MOVE_HOLON', 'organization', 'member', true);
    $makeGrant('global_right', $template, 'CAN_ADD_APP', 'organization');

    $catalog = McpPermissions::catalog($grant, ['query' => 'CREATE_DOCUMENT']);
    mcpCheck(array_column($catalog['items'], 'permission_key') === ['CAN_CREATE_DOCUMENT'], 'Search permission by key');
    $catalogKeys = []; $offset = 0;
    do {
        $page = McpPermissions::catalog($grant, ['offset' => $offset, 'limit' => 7]);
        array_push($catalogKeys, ...array_column($page['items'], 'permission_key'));
        $offset = $page['next_offset'];
    } while ($offset !== null);
    mcpCheck(count($catalogKeys) === count(array_unique($catalogKeys)) && in_array('CAN_EDIT_DOCUMENT', $catalogKeys, true), 'Catalog pagination is complete and unique');

    $holders = permissionPages($grant, ['holon_id' => $circle, 'permission_key' => 'CAN_CREATE_DOCUMENT'], 1);
    mcpCheck(array_column(array_column($holders, 'member'), 'user_id') === [$germain, $luc], 'Circle create rights belong to Germain and Luc only');
    mcpCheck($holders[0]['assigned_holon']['holon_id'] === $role && $holders[0]['source_holon']['holon_id'] === $template,
        'Inherited permission retains assigned role and template source');
    mcpCheck($holders[0]['kind'] === 'normal' && !$holders[0]['requires_activation'], 'Default response excludes extensions');
    $byPerson = permissionPages($grant, ['user_id' => $germain, 'permission_key' => 'CAN_CREATE_DOCUMENT']);
    mcpCheck(count($byPerson) === 1 && $byPerson[0]['space']['holon_id'] === $circle, 'Person view resolves target circle');
    $bySpace = permissionPages($grant, ['user_id' => $germain, 'holon_id' => $circle], 50);
    mcpCheck(in_array('CAN_CREATE_DOCUMENT', array_column(array_column($bySpace, 'permission'), 'permission_key'), true), 'Space view lists all relevant rights');
    $edited = permissionPages($grant, ['user_id' => $germain, 'permission_key' => 'CAN_EDIT_DOCUMENT']);
    $spaces = array_column(array_column($edited, 'space'), 'holon_id'); sort($spaces);
    $expected = [$nested, $role, $pilot]; sort($expected);
    mcpCheck($spaces === $expected && !in_array($circle, $spaces, true) && !in_array($budget, $spaces, true),
        'Parent descendants include child subtrees, not parent or sibling circles; hidden grants omitted');
    mcpCheck(permissionPages($grant, ['user_id' => $germain, 'permission_key' => 'CAN_DELETE_DOCUMENT']) === [], 'Collective rights are not personal and do not reopen unconfigured defaults');
    mcpCheck(permissionPages($grant, ['user_id' => $germain, 'permission_key' => 'CAN_MOVE_HOLON']) === [], 'Extended-only configuration does not become open by default');
    mcpCheck(permissionPages($grant, ['user_id' => $germain, 'permission_key' => 'CAN_EDIT_DECISION']) === [], 'Explicit-assignment-only unconfigured rights remain absent');
    $defaults = permissionPages($grant, ['user_id' => $germain, 'holon_id' => $budget, 'permission_key' => 'CAN_CREATE_EVENT']);
    mcpCheck(count($defaults) === 1 && $defaults[0]['origin'] === 'default_membership' && $defaults[0]['assigned_holon'] === null, 'Unconfigured membership default has an honest explanation');
    $fallback = permissionPages($grant, ['user_id' => $germain, 'permission_key' => 'CAN_EDIT_TYPE1_PROPERTIES']);
    mcpCheck(count($fallback) === 1 && $fallback[0]['fallback_permission_key'] === 'CAN_EDIT_HOLON'
        && $fallback[0]['assigned_holon']['holon_id'] === $role, 'Property fallback preserves provenance');
    $global = permissionPages($grant, ['user_id' => $germain, 'permission_key' => 'CAN_ADD_APP']);
    mcpCheck(count($global) === 1 && !$global[0]['permission']['is_contextual'], 'Global permission is not duplicated per space');
    mcpCheck(permissionPages($grant, ['user_id' => $germain, 'permission_key' => 'CAN_DELETE_PROJECT']) === [], 'Admin-profile right is withheld from ordinary role member');
    $items['germain_role']->setHolonAdmin(true);
    $admin = permissionPages($grant, ['user_id' => $germain, 'permission_key' => 'CAN_DELETE_PROJECT']);
    mcpCheck(count($admin) === 1 && $admin[0]['member_type'] === 'admin' && $admin[0]['kind'] === 'normal', 'Configured normal role-admin right is not an exceptional override');

    $extendedArgs = ['user_id' => $germain, 'holon_id' => $budget, 'permission_key' => 'CAN_CREATE_DOCUMENT', 'mode' => 'extended'];
    $extended = permissionPages($grant, $extendedArgs);
    mcpCheck(count($extended) === 1 && $extended[0]['kind'] === 'extended' && $extended[0]['requires_activation'], 'Explicit inspection lists dormant extensions');
    mcpCheck(permissionPages($grant, array_replace($extendedArgs, ['mode' => 'normal'])) === [], 'Budget is not a normal grant');
    $all = permissionPages($grant, array_replace($extendedArgs, ['holon_id' => $circle, 'mode' => 'all']));
    mcpCheck(count($all) === 2 && array_unique(array_column($all, 'kind')) === ['normal', 'extended'], 'All mode keeps both grant categories');
    $_SESSION = ['currentUser' => $germain, 'currentOrganization' => $oid];
    $subjectGrant = array_replace($grant, ['IDuser' => $germain]);
    mcpCheck(commonSetCurrentUserExtendedAuthorities(true, $oid), 'Fixture activates extended mode');
    mcpCheck(permissionPages($subjectGrant, array_replace($extendedArgs, ['mode' => 'normal'])) === [], 'Own normal inspection ignores activated extensions');
    mcpCheck(commonCurrentUserIsExtendedAuthoritiesEnabled($oid), 'Inspection never changes extension mode');
    mcpCheck(commonSetCurrentUserAdminMode(true, $oid), 'Fixture activates administrator override');
    mcpCheck(permissionPages($subjectGrant, array_replace($extendedArgs, ['mode' => 'normal'])) === [], 'Own normal inspection ignores administrator override');
    mcpCheck(commonUserHasAdminOverride($germain, $oid), 'Inspection never changes administrator mode');
    $_SESSION = ['currentUser' => $viewer, 'currentOrganization' => $oid];
    mcpCheck(!DbObject::isReadOnlyMemoizationEnabled(), 'Inspection restores request memoization mode');

    foreach ([['user_id' => $items['inactive_member']->getId()], ['user_id' => 2147483647],
        ['holon_id' => (int)$items['other_root']->getId()], ['holon_id' => (int)$items['hidden']->getId()],
        ['holon_id' => (int)$items['inactive']->getId()]] as $args) {
        $denied = false;
        try { McpPermissions::permissions($grant, $args); } catch (DomainException $error) { $denied = true; }
        mcpCheck($denied, 'Inaccessible subject/context must be denied');
    }
    foreach ([[], ['user_id' => '1'], ['holon_id' => -1], ['permission_key' => 'can_create_document'],
        ['user_id' => 1, 'mode' => true], ['user_id' => 1, 'mode' => 'active'], ['user_id' => 1, 'cursor' => 'bad'],
        ['user_id' => 1, 'organization_id' => $oid], ['user_id' => 1, 'limit' => 51]] as $args) {
        $denied = false;
        try { omoMcpToolArguments('omo_list_permissions', (object)$args); } catch (InvalidArgumentException $error) { $denied = true; }
        mcpCheck($denied, 'Invalid inspection input must be rejected');
    }
    $route = omoRestMatchRoute('/permissions', 'GET');
    mcpCheck(omoRestArguments($route, 'holon_id=0&mode=normal') === ['holon_id' => 0, 'mode' => 'normal'], 'REST organization context is accepted');
    mcpCheck(permissionPages($grant, ['user_id' => $germain, 'holon_id' => 0, 'permission_key' => 'CAN_CREATE_DOCUMENT']) === [], 'Organization space is distinct from root circle');
    $tools = array_column(omoMcpTools(), null, 'name');
    foreach (['omo_list_permissions', 'omo_permission_catalog'] as $name) {
        mcpCheck($tools[$name]['annotations']['readOnlyHint'] && $tools[$name]['securitySchemes'][0]['scopes'] === [OMO_MCP_SCOPE], 'Inspection requires read consent only');
    }
    $code = McpOauthGrant::issueCode($items['client'], $viewer, $oid, mcpAuthorizationRequest($items['client']));
    $token = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code))['access_token'];
    // Target-first discovery must find rights even for roles with no current holder.
    $anchorMemory = $makeHolon('anchor_memory', 'Memoire ancrage', 1, $root, ['IDholon_template' => $template]);
    $anchorPilot = $makeHolon('anchor_pilot', 'Lien pilotage ancrage', 1, $root);
    $vacant = $makeHolon('vacant', 'Vacant memory', 1, $root, ['IDholon_template' => $template]);
    $remote = $makeHolon('remote_role', 'Role in Budget', 1, $budget);
    $makeGrant('meeting_template', $template, 'CAN_CREATE_EVENT', 'parent_circle');
    $makeGrant('meeting_pilot', $anchorPilot, 'CAN_CREATE_EVENT', 'parent_circle');
    $makeGrant('meeting_remote', $remote, 'CAN_CREATE_EVENT', 'organization_root');
    $makeGrant('meeting_extended', $pilot, 'CAN_CREATE_EVENT', 'organization', 'member', true);
    $makeGrant('meeting_hidden', (int)$items['hidden']->getId(), 'CAN_CREATE_EVENT', 'organization');
    $makeGrant('meeting_collective', $role, 'CAN_CREATE_EVENT', 'organization', 'collective');
    foreach (['anchor_germain' => [$germain, $anchorMemory], 'anchor_luc' => [$luc, $anchorPilot]] as $key => [$uid, $hid]) {
        $items[$key] = mcpFixture(UserHolon::class, ['IDuser' => $uid, 'IDholon' => $hid, 'active' => 1, 'is_membership' => 1]);
    }
    $targetArgs = ['holon_id' => $root, 'permission_key' => 'CAN_CREATE_EVENT'];
    $roleRows = []; $offset = 0;
    do {
        $page = omoApiExecuteOperation('omo_get_holon_permissions', $targetArgs + ['offset' => $offset, 'limit' => 1], $grant);
        mcpCheck(restSchemaMatches(json_decode(json_encode($page)), omoApiResponseSchemas()['HolonPermissionPage'], omoApiResponseSchemas()), 'Target permission response contract');
        array_push($roleRows, ...$page['items']);
        $next = $page['next_offset'];
        mcpCheck($next === null || $next > $offset, 'Role pagination advances');
        $offset = $next;
    } while ($offset !== null);
    $expectedRoles = [$anchorMemory, $anchorPilot, $vacant, $remote]; sort($expectedRoles);
    $actualRoles = array_column(array_column($roleRows, 'assigned_holon'), 'holon_id'); sort($actualRoles);
    mcpCheck($actualRoles === $expectedRoles, 'Anchor meeting rights cover inherited, vacant and remote roles, excluding unrelated ranges, hidden sources, templates and collective/extended grants');
    foreach ($roleRows as $row) {
        $navigation = $row['holders'];
        $validated = omoMcpToolArguments($navigation['tool'], (object)$navigation['arguments']);
        $people = permissionPages($grant, $validated);
        $expectedUsers = match ($row['assigned_holon']['holon_id']) { $anchorMemory => [$germain], $anchorPilot => [$luc], default => [] };
        mcpCheck(array_column(array_column($people, 'member'), 'user_id') === $expectedUsers, 'Role holders navigation resolves the actual people without guessing occupancy');
    }
    $allTarget = McpPermissions::holonPermissions($grant, $targetArgs + ['mode' => 'all']);
    mcpCheck(count($allTarget['items']) === 5, 'Explicit all mode adds the extended role separately');
    $extendedTarget = McpPermissions::holonPermissions($grant, $targetArgs + ['mode' => 'extended']);
    mcpCheck(count($extendedTarget['items']) === 1 && $extendedTarget['items'][0]['requires_activation'], 'Target extended mode only lists exceptional grants');
    $defaultTarget = McpPermissions::holonPermissions($grant, ['holon_id' => $root, 'permission_key' => 'CAN_CREATE_RULE']);
    mcpCheck(count($defaultTarget['items']) === 1 && $defaultTarget['items'][0]['origin'] === 'default_membership', 'Target unconfigured default is explained without inventing roles');
    $fallbackTarget = McpPermissions::holonPermissions($grant, ['holon_id' => $role, 'permission_key' => 'CAN_EDIT_TYPE1_PROPERTIES']);
    mcpCheck(count($fallbackTarget['items']) === 1 && $fallbackTarget['items'][0]['fallback_permission_key'] === 'CAN_EDIT_HOLON', 'Target fallback retains role provenance');
    mcpCheck(McpPermissions::holonPermissions($grant, ['holon_id' => $root, 'permission_key' => 'CAN_EDIT_DECISION'])['items'] === [], 'Target explicit-only right remains closed');
    mcpCheck(McpPermissions::holonPermissions($grant, ['holon_id' => $root, 'permission_key' => 'CAN_DELETE_DOCUMENT'])['items'] === [], 'Target collective-only right does not reopen defaults');
    $adminTarget = McpPermissions::holonPermissions($grant, ['holon_id' => $role, 'permission_key' => 'CAN_DELETE_PROJECT']);
    mcpCheck(count($adminTarget['items']) === 1 && $adminTarget['items'][0]['member_type'] === 'admin', 'Target identifies admin-only membership profile');
    $items['germain_role']->setHolonAdmin(false);
    mcpCheck(permissionPages($grant, $adminTarget['items'][0]['holders']['arguments']) === [], 'Holder lookup excludes ordinary members of an admin-only role grant');
    // Compare target-first discovery to the native person resolver for every supported scope.
    $makeGrant('scope_probe', $role, 'CAN_EDIT_EVENT', 'self');
    foreach (array_keys(HolonPermission::getRangeLabels()) as $range) {
        $items['scope_probe']->set('range', $range); $items['scope_probe']->save();
        $native = HolonPermission::inspectUserPermissionsForOrganization($germain, $oid, ['CAN_EDIT_EVENT']);
        foreach ([0, $root, $circle, $nested, $role, $pilot, $budget] as $target) {
            $expected = false;
            foreach ($native as $row) if (HolonPermission::permissionInspectionContextIds($oid, $row, $target)) $expected = true;
            $actual = McpPermissions::holonPermissions($grant, ['holon_id' => $target, 'permission_key' => 'CAN_EDIT_EVENT']);
            mcpCheck((bool)$actual['items'] === $expected, 'Reverse scope matches native evaluation: ' . $range . '/' . $target);
        }
    }
    $structure = \dbObject\McpStructure::read($grant, $root);
    mcpCheck($structure['permissions'] === ['tool' => 'omo_get_holon_permissions', 'arguments' => ['holon_id' => $root]], 'Holon detail exposes permission entry point');
    $tools = array_column(omoMcpTools(), null, 'name');
    foreach (['omo_get_holon_permissions', 'omo_read_record', 'omo_search', 'omo_permission_catalog'] as $name) {
        mcpCheck(str_contains($tools[$name]['description'], omoApiAgentPermissionInstructions()), 'Permission precedence is part of discovery and descriptive-read contracts');
    }
    mcpCheck($tools['omo_get_holon_permissions']['annotations']['readOnlyHint'] && $tools['omo_get_holon_permissions']['securitySchemes'][0]['scopes'] === [OMO_MCP_SCOPE], 'Target inspection uses existing read consent');
    $initialized = omoMcpDispatch(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => (object)[
        'protocolVersion' => OMO_MCP_VERSIONS[0], 'capabilities' => new stdClass(), 'clientInfo' => (object)['name' => 'permissions-test', 'version' => '1']]], $grant);
    mcpCheck(str_starts_with($initialized['result']['instructions'], omoApiAgentPermissionInstructions()), 'Permission guidance precedes general exploration guidance');
    foreach ([[], ['holon_id' => '1'], ['holon_id' => -1], ['holon_id' => 0, 'mode' => 'active'], ['holon_id' => 0, 'permission_key' => 'bad'], ['holon_id' => 0, 'offset' => -1]] as $args) {
        $denied = false;
        try { omoMcpToolArguments('omo_get_holon_permissions', (object)$args); } catch (InvalidArgumentException $error) { $denied = true; }
        mcpCheck($denied, 'Target permission validation rejects invalid arguments');
    }
    foreach ([$targetArgs, $targetArgs + ['mode' => 'extended'], ['holon_id' => $root, 'limit' => 1]] as $args) {
        $response = restHttp('GET', '/api/v1/structure/' . $root . '/permissions?' . http_build_query(array_diff_key($args, ['holon_id' => true])), $token);
        mcpCheck($response['status'] === 200, 'Direct holon REST entry point is available');
        restCheckSchema('omo_get_holon_permissions', $response);
        $rpc = restRpc($token, 'omo_get_holon_permissions', $args);
        mcpCheck(($rpc['data']['result']['structuredContent'] ?? null) === $response['data'], 'Target REST/MCP parity');
    }
    foreach (['hidden', 'inactive', 'other_root'] as $key) {
        mcpCheck(restHttp('GET', '/api/v1/structure/' . $items[$key]->getId() . '/permissions', $token)['status'] === 422, 'Target access remains isolated');
    }
    mcpCheck(restHttp('POST', '/api/v1/structure/' . $root . '/permissions', $token)['status'] === 405, 'Target entry point is read-only');
    mcpCheck(restHttp('GET', '/api/v1/structure/' . $root . '/permissions')['status'] === 401, 'Target entry point requires authentication');
    foreach ([
        ['omo_permission_catalog', '/permissions/catalog', ['query' => 'DOCUMENT', 'limit' => 2]],
        ['omo_list_permissions', '/permissions', ['user_id' => $germain, 'permission_key' => 'CAN_CREATE_DOCUMENT']],
        ['omo_list_permissions', '/permissions', ['holon_id' => $circle, 'permission_key' => 'CAN_CREATE_DOCUMENT', 'limit' => 1]],
        ['omo_list_permissions', '/permissions', $extendedArgs],
        ['omo_list_permissions', '/permissions', array_replace($extendedArgs, ['holon_id' => $circle, 'mode' => 'all'])],
    ] as [$operation, $path, $args]) {
        $response = restHttp('GET', '/api/v1' . $path . '?' . http_build_query($args), $token);
        mcpCheck($response['status'] === 200, 'Read consent authorizes permission REST operation');
        restCheckSchema($operation, $response);
        $rpc = restRpc($token, $operation, $args);
        mcpCheck($rpc['status'] === 200 && ($rpc['data']['result']['structuredContent'] ?? null) === $response['data'], 'REST and MCP return identical explanations');
        if ($operation === 'omo_list_permissions' && $response['data']['next_cursor'] !== null) {
            $nextArgs = $args + ['cursor' => $response['data']['next_cursor']];
            $nextResponse = restHttp('GET', '/api/v1/permissions?' . http_build_query($nextArgs), $token);
            mcpCheck($nextResponse['status'] === 200 && $nextResponse['data']['items'] !== $response['data']['items'], 'HTTP continuation advances');
        }
    }
    mcpCheck(restHttp('GET', '/api/v1/permissions?user_id=' . $germain)['status'] === 401, 'Permission inspection requires authentication');
    mcpCheck(restHttp('GET', '/api/v1/permissions?holon_id=' . $items['other_root']->getId(), $token)['status'] === 422, 'HTTP rejects another organization');
    mcpCheck(restHttp('GET', '/api/v1/permissions?holon_id=' . $items['hidden']->getId(), $token)['status'] === 422, 'HTTP rejects hidden spaces');
    mcpCheck(restHttp('GET', '/api/v1/permissions?permission_key=UNKNOWN', $token)['status'] === 400, 'Unknown keys do not report open default rights');
    foreach (['mode=normal', 'user_id=' . $germain . '&mode=active', 'holon_id=0&holon_id=1', 'holon_id=0&organization_id=' . $oid] as $query) {
        mcpCheck(restHttp('GET', '/api/v1/permissions?' . $query, $token)['status'] === 400, 'HTTP rejects invalid or conflicting arguments');
    }
    foreach (['POST', 'PATCH', 'DELETE'] as $method) {
        mcpCheck(restHttp($method, '/api/v1/permissions', $token)['status'] === 405, 'Permission endpoint is read-only');
    }
    // Production-shaped case, using isolated local IDs: member -> role -> hidden template -> parent only.
    $swiss = $makeHolon('swiss', 'Suisse', 2, $root);
    $memoryTemplate = $makeHolon('swiss_template', 'Memory model', 1, $root, ['visible' => 0]);
    $memory = $makeHolon('swiss_memory', 'Memoire Suisse', 1, $swiss, ['IDholon_template' => $memoryTemplate]);
    $items['benjamin'] = mcpFixture(User::class, ['firstname' => 'Benjamin', 'active' => 1]);
    $benjamin = (int)$items['benjamin']->getId();
    $items['benjamin_org'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $benjamin, 'active' => 1]);
    $items['benjamin_memory'] = mcpFixture(UserHolon::class, ['IDuser' => $benjamin, 'IDholon' => $memory, 'active' => 1, 'is_membership' => 1]);
    foreach (['CAN_CREATE_EVENT', 'CAN_EDIT_EVENT', 'CAN_DELETE_EVENT'] as $permissionKey) {
        $makeGrant('swiss_' . $permissionKey, $memoryTemplate, $permissionKey, 'parent_circle');
        $args = ['user_id' => $benjamin, 'permission_key' => $permissionKey];
        $personRows = permissionPages($grant, $args);
        mcpCheck(count($personRows) === 1 && $personRows[0]['space']['holon_id'] === $swiss
            && $personRows[0]['assigned_holon']['holon_id'] === $memory && $personRows[0]['source_holon']['holon_id'] === $memoryTemplate
            && $personRows[0]['member_type'] === 'member' && $personRows[0]['kind'] === 'normal',
            'Hidden template grants explain ordinary member event CRUD on the parent circle');
        mcpCheck(HolonPermission::userHasPermissionForHolonContext($benjamin, $oid, $permissionKey, $swiss), 'Inspection agrees with native parent authorization');
        mcpCheck(permissionPages($grant, $args + ['holon_id' => $memory]) === [], 'Parent-only rights do not apply to the assigned role itself');
        $target = McpPermissions::holonPermissions($grant, ['holon_id' => $swiss, 'permission_key' => $permissionKey]);
        $matching = array_values(array_filter($target['items'], static fn(array $row): bool => ($row['assigned_holon']['holon_id'] ?? 0) === $memory));
        mcpCheck(count($matching) === 1 && $matching[0]['source_holon']['holon_id'] === $memoryTemplate, 'Target discovery retains the hidden template source');
        mcpCheck(permissionPages($grant, $matching[0]['holders']['arguments']) === $personRows, 'Circle holder navigation finds the member through the template');
        foreach ([['omo_list_permissions', '/permissions', $args],
            ['omo_get_holon_permissions', '/structure/' . $swiss . '/permissions', ['holon_id' => $swiss, 'permission_key' => $permissionKey]]] as [$operation, $path, $httpArgs]) {
            $response = restHttp('GET', '/api/v1' . $path . '?' . http_build_query(array_diff_key($httpArgs, ['holon_id' => true])), $token);
            mcpCheck($response['status'] === 200 && count($response['data']['items']) > 0, 'Inherited parent rights are exposed over HTTP');
            restCheckSchema($operation, $response);
            mcpCheck((restRpc($token, $operation, $httpArgs)['data']['result']['structuredContent'] ?? null) === $response['data'], 'Inherited parent CRUD REST/MCP parity');
        }
    }
    // Reading a source model must not put it in the target-space cache or expose hidden memberships.
    $makeGrant('swiss_extension', $memoryTemplate, 'CAN_CREATE_EVENT', 'organization', 'member', true);
    $extended = permissionPages($grant, ['user_id' => $benjamin, 'permission_key' => 'CAN_CREATE_EVENT', 'mode' => 'extended']);
    mcpCheck($extended && !in_array($memoryTemplate, array_column(array_column($extended, 'space'), 'holon_id'), true), 'Source access does not leak hidden template destinations');
    mcpCheck(count(permissionPages($grant, ['user_id' => $benjamin, 'permission_key' => 'CAN_CREATE_EVENT'])) === 1, 'Normal mode still excludes hidden-template extensions');
    mcpCheck(restHttp('GET', '/api/v1/structure/' . $memoryTemplate . '/permissions', $token)['status'] === 422, 'Hidden models remain unavailable as target spaces');
    foreach (['active' => 0, 'IDorganization' => $items['other_org']->getId(), 'archived_at' => new DateTimeImmutable()] as $field => $invalidValue) {
        $original = $items['swiss_template']->get($field);
        $items['swiss_template']->set($field, $invalidValue); $items['swiss_template']->save();
        mcpCheck(permissionPages($grant, ['user_id' => $benjamin, 'permission_key' => 'CAN_CREATE_EVENT']) === [], 'Unreadable/inactive/foreign template is not disclosed');
        $target = McpPermissions::holonPermissions($grant, ['holon_id' => $swiss, 'permission_key' => 'CAN_CREATE_EVENT']);
        mcpCheck(!in_array($memory, array_column(array_column($target['items'], 'assigned_holon'), 'holon_id'), true), 'Target discovery also rejects inaccessible template sources');
        $items['swiss_template']->set($field, $original); $items['swiss_template']->save();
    }
    // Exercise the scan-boundary empty page, then find a holder on a later page.
    for ($i = 0; $i < 21; $i++) {
        $items['extra_' . $i] = mcpFixture(User::class, ['firstname' => 'Extra ' . $i, 'active' => 1]);
        $items['extra_org_' . $i] = mcpFixture(UserOrganization::class,
            ['IDuser' => $items['extra_' . $i]->getId(), 'IDorganization' => $oid, 'active' => 1]);
    }
    $last = (int)$items['extra_20']->getId();
    $items['late_assignment'] = mcpFixture(UserHolon::class, ['IDuser' => $last, 'IDholon' => $pilot, 'active' => 1, 'is_membership' => 1]);
    $firstPage = McpPermissions::permissions($grant, ['permission_key' => 'CAN_EDIT_DECISION']);
    mcpCheck($firstPage['items'] === [] && !$firstPage['complete'] && $firstPage['next_cursor'] !== null, 'Empty scan page still offers continuation');
    $holders = permissionPages($grant, ['holon_id' => $circle, 'permission_key' => 'CAN_CREATE_DOCUMENT']);
    mcpCheck(array_column(array_column($holders, 'member'), 'user_id') === [$germain, $luc, $last], 'Pagination reaches later members without duplicates');
    echo "mcp_permissions_test: OK (person/space/right, target holon roles and holders, vacant roles, all ranges, permission-first guidance, defaults, normal/extended/admin, visibility, pagination, contracts, HTTP REST/MCP parity)\n";
} finally {
    DbObject::enableReadOnlyMemoization(false);
    $_SESSION = $before;
    mcpCleanup($items);
    if (is_file($jar)) unlink($jar);
}
