<?php
declare(strict_types=1);
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/api/rest.php';

$tools = array_column(omoMcpTools(), null, 'name');
$spec = omoRestOpenApi();
foreach (['omo_search' => ['query' => 'budget'], 'omo_read_record' => ['module' => 'documents', 'record_id' => 42]] as $name => $base) {
    $property = $tools[$name]['inputSchema']['properties']['archived'];
    mcpCheck($property['type'] === 'boolean' && $property['default'] === false, 'Archive schema defaults to active only');
    foreach ([false, true] as $value) mcpCheck(omoMcpToolArguments($name, (object)($base + ['archived' => $value]))['archived'] === $value, 'MCP boolean preserved');
    foreach (['true', 'false', 1, 0, null, [], 'all'] as $value) {
        $denied = false;
        try { omoMcpToolArguments($name, (object)($base + ['archived' => $value])); } catch (InvalidArgumentException $error) { $denied = true; }
        mcpCheck($denied, 'MCP must reject non-boolean archive values');
    }
}
$route = omoRestMatchRoute('/search', 'GET');
foreach (['true' => true, 'false' => false, '1' => true, '0' => false] as $raw => $value) {
    mcpCheck(omoRestArguments($route, 'query=budget&archived=' . $raw)['archived'] === $value, 'REST boolean conversion');
}
foreach (['', 'all', 'yes', '2', 'false&archived=true', 'true&archived[]=false'] as $raw) {
    $denied = false;
    try { omoRestArguments($route, 'query=budget&archived=' . $raw); } catch (InvalidArgumentException $error) { $denied = true; }
    mcpCheck($denied, 'REST must reject invalid or ambiguous archive modes');
}
foreach (['/search', '/records/{module}/{record_id}'] as $path) {
    $params = array_column($spec['paths'][$path]['get']['parameters'], null, 'name');
    mcpCheck($params['archived']['schema']['type'] === 'boolean' && !$params['archived']['required'], 'OpenAPI exposes optional archive parameter');
}
mcpCheck(isset($spec['components']['schemas']['SearchPage']['properties']['archived']), 'OpenAPI describes the response mode');
echo "api_archive_contract_test: OK\n";
