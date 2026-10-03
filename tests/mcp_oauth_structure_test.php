<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
use dbObject\{McpOauthGrant, McpStructure};
$before = $_SESSION;
$_ENV['MCP_PUBLIC_URL'] = 'https://mcp.example.invalid/mcp';
$items = mcpFixtures();
try {
    $_SESSION = ['currentUser' => (int)$items['user']->getId(), 'currentOrganization' => (int)$items['org']->getId()];
    $request = mcpAuthorizationRequest($items['client']);
    $code = McpOauthGrant::issueCode($items['client'], (int)$items['user']->getId(), (int)$items['org']->getId(), $request);
    $exchange = mcpExchangeRequest($items['client'], $code);
    mcpCheck(McpOauthGrant::exchange($items['client'], array_replace($exchange, ['code_verifier' => str_repeat('b', 64)])) === null, 'Wrong PKCE rejected');
    mcpCheck(McpOauthGrant::exchange($items['client'], array_replace($exchange, ['redirect_uri' => 'https://evil.invalid'])) === null, 'Wrong callback rejected');
    mcpCheck(McpOauthGrant::exchange($items['client'], array_replace($exchange, ['resource' => 'https://evil.invalid/mcp'])) === null, 'Wrong audience rejected');
    $tokens = McpOauthGrant::exchange($items['client'], $exchange);
    mcpCheck($tokens !== null, 'PKCE exchange succeeds');
    $grant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    mcpCheck($grant !== null && (int)$grant['IDuser'] === (int)$items['user']->getId(), 'Bearer identity');
    mcpCheck(McpOauthGrant::authenticate($tokens['access_token'], 'https://evil.invalid/mcp') === null, 'Bearer audience checked');
    $info = McpStructure::connectionInfo($grant);
    mcpCheck($info['organization']['id'] === (int)$items['org']->getId(), 'Consent organization');
    $page1 = McpStructure::list($grant, 0, 1, null);
    $page2 = McpStructure::list($grant, $page1['next_after_id'], 1, null);
    mcpCheck(count($page1['items']) === 1 && count($page2['items']) === 1 && $page2['next_after_id'] === null, 'Structure pagination excludes hidden/inactive');
    $role = McpStructure::read($grant, (int)$items['role']->getId());
    mcpCheck(!isset($role['accesskey'], $role['parameters']) && !str_contains(json_encode($role), 'never-expose'), 'No secret serialization');
    foreach (['hidden', 'inactive', 'other_root'] as $key) {
        $denied = false;
        try { McpStructure::read($grant, (int)$items[$key]->getId()); } catch (DomainException $error) { $denied = true; }
        mcpCheck($denied, 'Hidden/inactive/foreign IDs rejected');
    }
    $refresh = ['grant_type' => 'refresh_token', 'resource' => omoMcpPublicUrl(), 'refresh_token' => $tokens['refresh_token']];
    $renewed = McpOauthGrant::exchange($items['client'], $refresh);
    mcpCheck($renewed !== null && $renewed['refresh_token'] !== $tokens['refresh_token'], 'Refresh rotates');
    mcpCheck(McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl()) === null, 'Old access invalidated on rotation');
    mcpCheck(McpOauthGrant::exchange($items['client'], $refresh) === null, 'Refresh replay rejected');
    mcpCheck(McpOauthGrant::authenticate($renewed['access_token'], omoMcpPublicUrl()) === null, 'Refresh replay revokes the whole grant');
    mcpCheck(McpOauthGrant::exchange($items['client'], $exchange) === null, 'Code replay rejected');
    mcpCheck(McpOauthGrant::authenticate($renewed['access_token'], omoMcpPublicUrl()) === null, 'Code replay revokes derived tokens');
    $code2 = McpOauthGrant::issueCode($items['client'], (int)$items['user']->getId(), (int)$items['org']->getId(), $request);
    $tokens2 = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code2));
    $grant2 = McpOauthGrant::authenticate($tokens2['access_token'], omoMcpPublicUrl());
    $stored = new McpOauthGrant();
    $stored->load((int)$grant2['id'], true);
    $stored->set('access_expires_at', time() - 1);
    $stored->save();
    mcpCheck(McpOauthGrant::authenticate($tokens2['access_token'], omoMcpPublicUrl()) === null, 'Expired access rejected');
    $stored->set('access_expires_at', time() + 3600);
    $stored->save();
    McpOauthGrant::revokeOwned((int)$grant2['id'], (int)$items['user']->getId() + 1000000);
    mcpCheck(McpOauthGrant::authenticate($tokens2['access_token'], omoMcpPublicUrl()) !== null, 'Another user cannot revoke grant');
    McpOauthGrant::revokeOwned((int)$grant2['id'], (int)$items['user']->getId());
    mcpCheck(McpOauthGrant::authenticate($tokens2['access_token'], omoMcpPublicUrl()) === null, 'Owner revocation enforced');
    mcpCheck(McpOauthGrant::exchange($items['client'], ['grant_type' => 'refresh_token', 'resource' => omoMcpPublicUrl(),
        'refresh_token' => $tokens2['refresh_token']]) === null, 'Revocation also blocks renewal');
    $code3 = McpOauthGrant::issueCode($items['client'], (int)$items['user']->getId(), (int)$items['org']->getId(), $request);
    $tokens3 = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code3));
    $items['membership']->set('active', 0);
    $items['membership']->save();
    mcpCheck(McpOauthGrant::authenticate($tokens3['access_token'], omoMcpPublicUrl()) === null, 'Membership removal takes effect immediately');
    mcpCheck(McpOauthGrant::exchange($items['client'], ['grant_type' => 'refresh_token', 'resource' => omoMcpPublicUrl(),
        'refresh_token' => $tokens3['refresh_token']]) === null, 'Removed member cannot renew');
} finally { mcpCleanup($items); $_SESSION = $before; }
echo "mcp_oauth_structure_test: OK\n";
