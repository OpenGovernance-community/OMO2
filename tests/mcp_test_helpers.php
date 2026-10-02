<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/mcp/server.php';
$_SERVER['REQUEST_METHOD'] ??= 'GET';

use dbObject\{DbObject, User, Organization, Holon, UserOrganization, OrganizationApplication, ArrayApplication, McpOauthClient, McpOauthGrant};
// Empty fixture objects have no module resources; delete through the base object's existing API.
class McpTestOrganization extends Organization { public function delete() { return DbObject::delete(); } }
class McpTestHolon extends Holon { public function delete() { return DbObject::delete(); } }
function mcpCheck(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function mcpFixture(string $class, array $fields): DbObject
{
    $object = new $class();
    foreach ($fields as $key => $value) $object->set($key, $value);
    mcpCheck(!empty($object->save()['status']), 'Cannot create fixture ' . $class);
    return $object;
}
function mcpFixtures(): array
{
    $nonce = bin2hex(random_bytes(8));
    $items = [];
    try {
        $items['user'] = mcpFixture(User::class, ['firstname' => 'MCP test', 'email' => 'mcp-' . $nonce . '@example.invalid',
            'password' => password_hash('MCP-fixture-password-' . $nonce, PASSWORD_DEFAULT), 'allow_password_login' => 1, 'active' => 1]);
        $items['password'] = 'MCP-fixture-password-' . $nonce;
        $items['org'] = mcpFixture(McpTestOrganization::class, ['name' => 'MCP ' . $nonce, 'shortname' => 'mcp-' . $nonce]);
        $items['membership'] = mcpFixture(UserOrganization::class, ['IDuser' => $items['user']->getId(), 'IDorganization' => $items['org']->getId(), 'active' => 1]);
        $apps = new ArrayApplication();
        $apps->load(['where' => [['field' => 'hash', 'value' => 'structure']], 'limit' => 1]);
        mcpCheck(count($apps) === 1, 'Structure app must exist in the demo seed');
        $items['app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $items['org']->getId(),
            'IDapplication' => $apps[0]->getId(), 'active' => 1]);
        $items['root'] = mcpFixture(McpTestHolon::class, ['IDorganization' => $items['org']->getId(), 'name' => 'MCP root',
            'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
        $items['role'] = mcpFixture(McpTestHolon::class, ['IDorganization' => $items['org']->getId(), 'IDholon_org' => $items['root']->getId(),
            'IDholon_parent' => $items['root']->getId(), 'name' => 'MCP role', 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1,
            'accesskey' => 'never-expose-this-key']);
        $items['hidden'] = mcpFixture(McpTestHolon::class, ['IDorganization' => $items['org']->getId(), 'IDholon_org' => $items['root']->getId(),
            'IDholon_parent' => $items['root']->getId(), 'name' => 'Hidden', 'IDtypeholon' => 1, 'active' => 1, 'visible' => 0]);
        $items['inactive'] = mcpFixture(McpTestHolon::class, ['IDorganization' => $items['org']->getId(), 'IDholon_org' => $items['root']->getId(),
            'name' => 'Inactive', 'IDtypeholon' => 1, 'active' => 0, 'visible' => 1]);
        $items['other_org'] = mcpFixture(McpTestOrganization::class, ['name' => 'Other MCP ' . $nonce, 'shortname' => 'other-mcp-' . $nonce]);
        $items['other_root'] = mcpFixture(McpTestHolon::class, ['IDorganization' => $items['other_org']->getId(), 'name' => 'Other root',
            'IDtypeholon' => 2, 'active' => 1, 'visible' => 1]);
        $items['client'] = McpOauthClient::register('MCP test', ['https://client.example.invalid/callback']);
        return $items;
    } catch (Throwable $error) { mcpCleanup($items); throw $error; }
}
function mcpCleanup(array $items): void
{
    foreach (array_reverse($items) as $item) {
        if ($item instanceof DbObject) $item->delete();
    }
}
function mcpAuthorizationRequest(McpOauthClient $client): array
{
    return ['client_id' => $client->get('client_id'), 'redirect_uri' => 'https://client.example.invalid/callback',
        'resource' => omoMcpPublicUrl(), 'scope' => OMO_MCP_SCOPE, 'state' => 'test-state',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', str_repeat('a', 64), true)), '+/', '-_'), '=')];
}
function mcpExchangeRequest(McpOauthClient $client, string $code): array
{
    return ['grant_type' => 'authorization_code', 'client_id' => $client->get('client_id'), 'code' => $code,
        'redirect_uri' => 'https://client.example.invalid/callback', 'resource' => omoMcpPublicUrl(), 'code_verifier' => str_repeat('a', 64)];
}
