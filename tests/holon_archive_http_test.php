<?php
declare(strict_types=1);
// Local Docker only; run as www-data. Only the fixtures created here are removed.
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{Holon, User, UserOrganization, UserHolon, Application, OrganizationApplication, HolonPermission, Permission};

function holonArchiveHttp(string $path, ?array $post = null, bool $csrf = true): array {
    if ($post !== null && $csrf) $post['_csrf'] = commonCsrfToken();
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $curl = curl_init('https://localhost/omo/api/' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    if ($post !== null) curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    mcpCheck(is_string($body), 'HTTP request failed');
    return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
}

$before = $_SESSION;
$items = [];
try {
    $nonce = bin2hex(random_bytes(6));
    $items['org'] = mcpFixture(McpTestOrganization::class, ['name' => 'Archive HTTP', 'shortname' => 'archive-http-' . $nonce]);
    $oid = (int)$items['org']->getId();
    $items['user'] = mcpFixture(User::class, ['email' => 'archive-http-' . $nonce . '@example.invalid', 'active' => 1]);
    $uid = (int)$items['user']->getId();
    $items['membership'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $uid, 'active' => 1]);
    $app = new Application(); mcpCheck((bool)$app->load(['hash', 'structure']), 'Structure app required');
    $items['app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $items['root'] = mcpFixture(McpTestHolon::class, ['name' => 'Root', 'IDorganization' => $oid, 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $rid = (int)$items['root']->getId();
    $items['root']->set('IDholon_org', $rid); $items['root']->save();
    $items['role'] = mcpFixture(McpTestHolon::class, ['name' => 'Archive <role>', 'IDholon_org' => $rid, 'IDholon_parent' => $rid, 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    $hid = (int)$items['role']->getId();
    $items['manager_role'] = mcpFixture(McpTestHolon::class, ['name' => 'Archive manager', 'IDholon_org' => $rid, 'IDholon_parent' => $rid, 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    $items['permission'] = mcpFixture(HolonPermission::class, ['IDholon' => $items['manager_role']->getId(), 'IDpermission' => Permission::findByKey('CAN_DELETE_HOLON')->getId(), 'range' => 'parent_circle_descendants', 'member_type' => 'member']);
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid, 'auth_security_version' => (int)$items['user']->get('security_version')];
    $response = holonArchiveHttp('holons/archive.php?hid=' . $hid);
    mcpCheck($response['status'] === 403, 'Members without the configured delete permission cannot open archive action: ' . $response['status'] . ' ' . substr($response['body'], 0, 600));
    $response = holonArchiveHttp('holons/archive.php', ['hid' => $hid, 'action' => 'archive', 'authority_choice' => 'reassign']);
    mcpCheck($response['status'] === 422 && empty($response['json']['status']), 'Forged archive action must be rejected');
    $items['assignment'] = mcpFixture(UserHolon::class, ['IDholon' => $items['manager_role']->getId(), 'IDuser' => $uid, 'active' => 1, 'is_membership' => 1]);
    $items['preview_child'] = mcpFixture(McpTestHolon::class, ['name' => 'Archive child', 'IDholon_org' => $rid, 'IDholon_parent' => $hid, 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    $items['preview_authority'] = mcpFixture(\dbObject\Authority::class, ['IDholon' => $hid, 'label' => 'Budget <authority>', 'description' => 'Prepare the budget', 'is_local' => 1]);
    $items['preview_child_authority'] = mcpFixture(\dbObject\Authority::class, ['IDholon' => $items['preview_child']->getId(), 'label' => 'Budget follow-up',
        'description' => 'Monthly review', 'IDauthority_parent' => $items['preview_authority']->getId()]);
    foreach (['text' => ['First expected result', 'Second <result>'], 'detail' => [['title' => 'Review report', 'description' => 'Every month']],
        'authority' => [$items['preview_authority']->getId()]] as $kind => $values) {
        $items['preview_definition_' . $kind] = mcpFixture(\dbObject\Property::class, ['IDholon_organization' => $rid,
            'name' => 'Preview ' . $kind, 'shortname' => 'preview_' . $kind, 'IDpropertyformat' => \dbObject\PropertyFormat::FORMAT_LIST, 'listitemtype' => $kind]);
        $items['preview_value_' . $kind] = mcpFixture(\dbObject\HolonProperty::class, ['IDholon' => $hid,
            'IDproperty' => $items['preview_definition_' . $kind]->getId(), 'active' => 1, 'value' => json_encode($values)]);
    }
    $items['preview_child_value'] = mcpFixture(\dbObject\HolonProperty::class, ['IDholon' => $items['preview_child']->getId(),
        'IDproperty' => $items['preview_definition_authority']->getId(), 'active' => 1, 'value' => json_encode([$items['preview_child_authority']->getId()])]);
    commonClearCurrentUserPermissionCache();
    $response = holonArchiveHttp('getOrg.php?oid=' . $oid . '&cid=' . $hid);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'data-holon-archive="' . $hid . '"'), 'Authorized member sees archive menu');
    $response = holonArchiveHttp('holons/archive.php?hid=' . $hid);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'Archive &lt;role&gt;') && str_contains($response['body'], 'value="archive"'), 'Confirmation preview must be escaped and actionable');
    mcpCheck(str_contains($response['body'], '<li>') && str_contains($response['body'], 'First expected result')
        && str_contains($response['body'], 'Second &lt;result&gt;') && str_contains($response['body'], 'Every month')
        && str_contains($response['body'], 'Budget &lt;authority&gt;') && str_contains($response['body'], 'Prepare the budget'), 'Archive confirmation renders lists, details and authority labels safely');
    mcpCheck(str_contains($response['body'], 'name="authority_choice"') && !preg_match('/value="(?:delete_all|delete_keep_rules|reassign)" selected/', $response['body']), 'Archive requires an explicit choice without a default');
    $response = holonArchiveHttp('holons/archive.php', ['hid' => $hid, 'action' => 'archive']);
    mcpCheck($response['status'] === 422 && empty($response['json']['status']), 'Missing authority choice must be rejected');
    $response = holonArchiveHttp('holons/delete_popup.php?hid=' . $hid);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'name="authority_choice"'), 'Delete offers the same authority choice');
    $response = holonArchiveHttp('holons/delete_popup.php?hid=' . $hid, ['authority_choice' => 'invalid']);
    mcpCheck($response['status'] === 422 && empty($response['json']['status']), 'Invalid deletion choice must be rejected');
    $response = holonArchiveHttp('holons/archive.php', ['hid' => $hid, 'action' => 'archive', 'authority_choice' => 'reassign'], false);
    mcpCheck($response['status'] === 403, 'CSRF protection required');
    $response = holonArchiveHttp('holons/archive.php', ['hid' => $hid, 'action' => 'archive', 'authority_choice' => 'reassign']);
    mcpCheck($response['status'] === 200 && !empty($response['json']['status']), 'Archive HTTP action: ' . $response['body']);
    mcpCheck((int)$response['json']['targetId'] === $rid, 'Archive returns parent navigation');
    $response = holonArchiveHttp('getOrg.php?oid=' . $oid . '&cid=' . $hid);
    mcpCheck($response['status'] === 403, 'Archived role direct URL must be inaccessible');
    $response = holonArchiveHttp('getOrg.php?oid=' . $oid);
    mcpCheck(!str_contains($response['body'], 'data-cid="' . $hid . '"'), 'Archived role must disappear from current parent navigation');
    $response = holonArchiveHttp('holons/archive.php');
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'data-archive-open="/omo/api/holons/archive.php?hid=' . $hid . '"'), 'Archive list must expose the saved role');
    $response = holonArchiveHttp('holons/archive.php?hid=' . $hid);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'value="restore"'), 'Archived preview must allow restoration');
    mcpCheck(str_contains($response['body'], 'Budget follow-up') && str_contains($response['body'], 'Monthly review')
        && str_contains($response['body'], 'Budget &lt;authority&gt;'), 'Archived descendants retain readable authorities and their parent labels');
    if ($previewFile = getenv('HOLON_ARCHIVE_PREVIEW_HTML')) file_put_contents($previewFile, $response['body']);
    $response = holonArchiveHttp('search_popup.php?oid=' . $oid . '&archive=1');
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'data-omo-search-popup-archive="1"')
        && preg_match('/data-topbar-search-archive\s+checked/', $response['body']), 'Archive switch must render checked');
    $response = holonArchiveHttp('search/preview.php?oid=' . $oid . '&module=structure&id=' . $hid);
    mcpCheck($response['status'] === 404, 'Normal preview must exclude archived holons');
    $response = holonArchiveHttp('search/preview.php?oid=' . $oid . '&module=structure&id=' . $hid . '&archive=1');
    mcpCheck($response['status'] === 200 && isset($response['json']['html']), 'Archive preview must be readable');
    $organization = new \dbObject\Organization(); $organization->load($oid);
    $items['search_job'] = \dbObject\SearchJob::createTopbarJob($organization, 'Archive', ['structure'],
        \dbObject\SearchJob::buildViewerContextFromGlobals($oid, $rid) + ['archived' => true]);
    mcpCheck((bool)$items['search_job'] && \dbObject\SearchJob::processJobById((int)$items['search_job']->getId()), 'Archive search job');
    $response = holonArchiveHttp('search_popup.php?oid=' . $oid . '&archive=0&restore_job_id=' . $items['search_job']->getId()
        . '&restore_job_token=' . $items['search_job']->get('requesttoken'));
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'data-omo-search-popup-archive="1"')
        && str_contains($response['body'], 'data-omo-search-preview-id="' . $hid . '"'), 'Restored job must retain its archive mode and results');
    $items['assignment']->set('active', 0); $items['assignment']->save();
    commonClearCurrentUserPermissionCache();
    $response = holonArchiveHttp('holons/archive.php?hid=' . $hid);
    mcpCheck($response['status'] === 200 && !str_contains($response['body'], 'type="submit"'), 'Organization members can consult archives without restore rights');
    $response = holonArchiveHttp('holons/archive.php', ['hid' => $hid, 'action' => 'restore']);
    mcpCheck($response['status'] === 422 && empty($response['json']['status']), 'Revoked delete permission must block restoration');
    $items['assignment']->set('active', 1); $items['assignment']->save();
    commonClearCurrentUserPermissionCache();
    $response = holonArchiveHttp('holons/archive.php', ['hid' => $hid, 'action' => 'restore']);
    mcpCheck($response['status'] === 200 && !empty($response['json']['status']), 'Restore HTTP action: ' . $response['body']);
    $response = holonArchiveHttp('getOrg.php?oid=' . $oid . '&cid=' . $hid);
    mcpCheck($response['status'] === 200, 'Restored role must be visible again');
    echo "holon_archive_http_test: OK\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
