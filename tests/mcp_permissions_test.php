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
    $template = $makeHolon('template', 'Memory template', 1, $root, ['templatename' => 'Memory template']);
    $role = $makeHolon('memory', 'Memoire', 1, $circle, ['IDholon_template' => $template]);
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
    echo "mcp_permissions_test: OK (person/space/right, templates, scopes, defaults, fallback, normal/extended/admin, visibility, pagination, contracts, HTTP REST/MCP parity)\n";
} finally {
    DbObject::enableReadOnlyMemoization(false);
    $_SESSION = $before;
    mcpCleanup($items);
    if (is_file($jar)) unlink($jar);
}
