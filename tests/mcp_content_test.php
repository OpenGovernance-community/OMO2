<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{McpContent, McpOauthGrant, ArrayApplication, OrganizationApplication, User, Document, DocumentPvPoint, ObjectVisibility};
$before = $_SESSION;
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp';
$items = mcpFixtures();
try {
    $_SESSION = ['currentUser' => (int)$items['user']->getId(), 'currentOrganization' => (int)$items['org']->getId()];
    $apps = new ArrayApplication();
    $apps->load(['where' => [['field' => 'hash', 'value' => 'documents']], 'limit' => 1]);
    mcpCheck(count($apps) === 1, 'Documents app in seed');
    $items['documents_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $items['org']->getId(),
        'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    $items['owner'] = mcpFixture(User::class, ['firstname' => 'Other owner', 'active' => 1]);
    foreach (['document', 'private', 'foreign', 'pv'] as $key) {
        $items[$key] = mcpFixture(Document::class, ['title' => 'MCP-content-' . $key,
            'IDorganization' => $items[$key === 'foreign' ? 'other_org' : 'org']->getId(),
            'IDuser' => $items['owner']->getId(), 'IDusercreation' => $items['owner']->getId(),
            'documenttype' => $key === 'pv' ? Document::TYPE_PV : Document::TYPE_HTML,
            'pvstage' => Document::PV_STAGE_VALIDATED, 'description' => 'MCP-content summary',
            'content' => $key === 'private' ? 'PRIVATE-CONTENT-NEVER-EXPOSE' : '<p>' . str_repeat('Text data. ', 3000) . '</p>',
            'active' => 1, 'codeview' => 'NEVER-EXPOSE-SHARE-CODE']);
        $items[$key . '_visibility'] = mcpFixture(ObjectVisibility::class, ['object_type' => 'document', 'object_id' => $items[$key]->getId(),
            'IDorganization' => $items[$key === 'foreign' ? 'other_org' : 'org']->getId(),
            'visibility_type' => $key === 'private' ? ObjectVisibility::TYPE_SELF : ObjectVisibility::TYPE_ORGANIZATION, 'active' => 1]);
    }
    foreach (['public_point' => 0, 'confidential_point' => 1] as $key => $confidential) {
        $items[$key] = mcpFixture(DocumentPvPoint::class, ['IDdocument' => $items['pv']->getId(),
            'title' => $key, 'content' => $key === 'public_point' ? 'VISIBLE-PV-CONTENT' : 'CONFIDENTIAL-PV-CONTENT',
            'pointtype' => 'information', 'item_type' => 'point', 'IDuser_author' => $items['owner']->getId(),
            'active' => 1, 'is_handled' => 1, 'is_confidential' => $confidential]);
    }
    $request = mcpAuthorizationRequest($items['client']);
    $code = McpOauthGrant::issueCode($items['client'], (int)$items['user']->getId(), (int)$items['org']->getId(), $request);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $grant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    mcpCheck($grant !== null, 'Broad read grant');
    $search = McpContent::search($grant, 'MCP-content', ['documents', 'pv'], null, 0, 1);
    mcpCheck(count($search['items']) === 1 && $search['next_offset'] === 1 && !$search['exhaustive'], 'Selected result pagination');
    $next = McpContent::search($grant, 'MCP-content', ['documents', 'pv'], null, 1, 50);
    $results = array_merge($search['items'], $next['items']);
    $ids = array_column($results, 'record_id');
    mcpCheck(in_array((int)$items['document']->getId(), $ids, true) && in_array((int)$items['pv']->getId(), $ids, true), 'Visible documents searched');
    mcpCheck(!in_array((int)$items['private']->getId(), $ids, true) && !in_array((int)$items['foreign']->getId(), $ids, true), 'Private and foreign search records excluded');
    $first = McpContent::read($grant, 'documents', (int)$items['document']->getId(), null, 0, 0, 500);
    $nextText = McpContent::read($grant, 'documents', (int)$items['document']->getId(), null, 0, $first['next_offset'], 20000);
    mcpCheck(mb_strlen($first['text']) === 500 && $nextText['offset'] === 500 && $nextText['next_offset'] === 20500, 'Full text chunks are continuous');
    mcpCheck(!str_contains(json_encode($first), 'NEVER-EXPOSE') && !str_contains($first['text'], '<p>'), 'Text projection excludes HTML and share keys');
    $pv = McpContent::read($grant, 'pv', (int)$items['pv']->getId(), null, 0, 0, 12000);
    mcpCheck(str_contains($pv['text'], 'VISIBLE-PV-CONTENT') && !str_contains($pv['text'], 'CONFIDENTIAL-PV-CONTENT'), 'PV confidentiality respected');
    foreach ([['documents', (int)$items['private']->getId(), null], ['documents', (int)$items['foreign']->getId(), null],
        ['structure', (int)$items['hidden']->getId(), null], ['documents', (int)$items['document']->getId(), (int)$items['other_root']->getId()]] as [$module, $id, $context]) {
        $denied = false;
        try { McpContent::read($grant, $module, $id, $context, 0, 0, 1000); } catch (DomainException $error) { $denied = true; }
        mcpCheck($denied, 'Private/foreign/hidden record or foreign context rejected');
    }
    $disabledSearch = McpContent::search($grant, 'MCP', ['calendar'], null, 0, 20);
    mcpCheck($disabledSearch['items'] === [] && $disabledSearch['modules'] === [], 'Disabled filter never falls back to other modules');
    // An older narrow authorization must never acquire the new wider access by refresh.
    $stored = new McpOauthGrant();
    $stored->load((int)$grant['id'], true);
    $stored->set('scope', 'structure:read'); $stored->save();
    mcpCheck(McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl()) === null, 'Old scope requires renewed consent');
    mcpCheck(McpOauthGrant::exchange($items['client'], ['grant_type' => 'refresh_token', 'resource' => omoMcpPublicUrl(),
        'refresh_token' => $tokens['refresh_token']]) === null, 'Refresh cannot widen the old scope');
} finally { mcpCleanup($items); $_SESSION = $before; }
echo "mcp_content_test: OK\n";
