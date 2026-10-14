<?php
declare(strict_types=1);
require_once __DIR__ . '/rest_test_helpers.php';
use dbObject\{User, Document, ArrayApplication, OrganizationApplication, McpOauthGrant};
mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Archive HTTP tests require local Docker');
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
$jar = tempnam(sys_get_temp_dir(), 'omo-archive-api-test-');
$items = mcpFixtures();
try {
    $oid = (int)$items['org']->getId(); $uid = (int)$items['user']->getId();
    $apps = new ArrayApplication(); $apps->load(['where' => [['field' => 'hash', 'value' => 'documents']]]);
    $items['documents_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, mcpAuthorizationRequest($items['client']));
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $read = $tokens['access_token'];
    // Read-only REST and MCP archive searches must partition identically and keep their mode when paging/reading.
    $items['archive_role'] = mcpFixture(McpTestHolon::class, ['name' => 'REST archive role', 'IDorganization' => $oid,
        'IDholon_org' => $items['root']->getId(), 'IDholon_parent' => $items['root']->getId(),
        'IDtypeholon' => 1, 'visible' => 1, 'active' => 0, 'archived_at' => new DateTime()]);
    $items['archive_owner'] = mcpFixture(User::class, ['firstname' => 'Private archive owner', 'active' => 1]);
    foreach (['active', 'own', 'space', 'private', 'foreign'] as $kind) {
        $items['archive_doc_' . $kind] = mcpFixture(Document::class, ['title' => 'REST-archive-fixture ' . $kind,
            'IDorganization' => $kind === 'foreign' ? $items['other_org']->getId() : $oid,
            'IDholon' => $kind === 'space' ? $items['archive_role']->getId() : null,
            'IDuser' => $kind === 'private' ? $items['archive_owner']->getId() : $uid,
            'documenttype' => Document::TYPE_HTML, 'content' => 'REST-archive-fixture retained content',
            'active' => in_array($kind, ['active', 'space'], true) ? 1 : 0]);
        if ($kind === 'private') $items['archive_private_visibility'] = mcpFixture(\dbObject\ObjectVisibility::class, [
            'object_type' => 'document', 'object_id' => $items['archive_doc_private']->getId(), 'IDorganization' => $oid,
            'visibility_type' => 'self', 'active' => 1]);
    }
    $query = '/api/v1/search?query=REST-archive-fixture&modules=documents';
    $activePage = restHttp('GET', $query, $read);
    mcpCheck($activePage['status'] === 200 && !$activePage['data']['archived'] && count($activePage['data']['items']) === 1
        && $activePage['data']['items'][0]['record_id'] === (int)$items['archive_doc_active']->getId(), 'Default search excludes both types of archive');
    $archiveIds = [];
    for ($offset = 0; $offset < 2; $offset++) {
        $page = restHttp('GET', $query . '&archived=true&limit=1&offset=' . $offset, $read);
        $mcp = restRpc($read, 'omo_search', ['query' => 'REST-archive-fixture', 'modules' => ['documents'], 'archived' => true, 'limit' => 1, 'offset' => $offset]);
        mcpCheck($page['status'] === 200 && !$mcp['data']['result']['isError'] && $page['data'] === $mcp['data']['result']['structuredContent'], 'Archive REST/MCP parity');
        restCheckSchema('omo_search', $page);
        mcpCheck($page['data']['archived'] && count($page['data']['items']) === 1 && $page['data']['next_offset'] === ($offset === 0 ? 1 : null), 'Archive pagination');
        $item = $page['data']['items'][0]; $archiveIds[] = $item['record_id'];
        mcpCheck($item['archived'] && str_contains($item['url'], 'archive=1'), 'Result preserves its archive source URL');
        $path = '/api/v1/records/documents/' . $item['record_id'];
        mcpCheck(restHttp('GET', $path, $read)['status'] === 422, 'Active read must reject archived result');
        $record = restHttp('GET', $path . '?archived=1', $read);
        $mcpRead = restRpc($read, 'omo_read_record', ['module' => 'documents', 'record_id' => $item['record_id'], 'archived' => true]);
        mcpCheck($record['status'] === 200 && $record['data'] === $mcpRead['data']['result']['structuredContent'], 'Archived read works in both transports');
        restCheckSchema('omo_read_record', $record);
    }
    sort($archiveIds);
    $expectedIds = [(int)$items['archive_doc_own']->getId(), (int)$items['archive_doc_space']->getId()]; sort($expectedIds);
    mcpCheck($archiveIds === $expectedIds, 'Private, foreign and active records excluded from archives');
    mcpCheck(restHttp('GET', $query . '&archived=false', $read)['data'] === $activePage['data'], 'Explicit false equals default');
    mcpCheck(restHttp('GET', $query . '&archived=all', $read)['status'] === 400, 'No mixed archive mode');
    foreach (['private', 'foreign', 'active'] as $kind) mcpCheck(restHttp('GET', '/api/v1/records/documents/'
        . $items['archive_doc_' . $kind]->getId() . '?archived=true', $read)['status'] === 422, 'Wrong partition or permission denied: ' . $kind);
    echo "api_archive_http_test: OK (REST/MCP parity, exclusive modes, pagination, read-only OAuth, private and foreign isolation)\n";
} finally {
    mcpCleanup($items);
    if (is_file($jar)) unlink($jar);
}
