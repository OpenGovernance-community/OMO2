<?php
declare(strict_types=1);
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';
require_once dirname(__DIR__) . '/common/api/rest.php';
require_once __DIR__ . '/rest_test_helpers.php';
use dbObject\{McpValues, McpContent, McpOauthGrant, ArrayApplication, OrganizationApplication, Valeur, Rule, Property, HolonProperty};

function valueApiReject(callable $action, string $message): void
{
    $rejected = false;
    try { $action(); } catch (DomainException | InvalidArgumentException $error) { $rejected = true; }
    mcpCheck($rejected, $message);
}
function valueApiHttp(string $token, string $path, ?array $body = null): array
{
    mcpCheck(parse_url(omoMcpPublicUrl(), PHP_URL_HOST) === 'localtest.me', 'Only run HTTP fixtures in local Docker');
    $curl = curl_init(omoMcpIssuer() . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_RESOLVE => ['localtest.me:443:127.0.0.1'],
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token]]);
    if ($body !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
    $raw = curl_exec($curl);
    if ($raw === false) throw new RuntimeException(curl_error($curl));
    return ['status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'data' => json_decode($raw, true, 64, JSON_THROW_ON_ERROR)];
}

$before = $_SESSION; $items = mcpFixtures();
try {
    $uid = (int)$items['user']->getId(); $oid = (int)$items['org']->getId();
    $hid = (int)$items['root']->getId(); $role = (int)$items['role']->getId();
    $_SESSION = ['currentUser' => $uid, 'currentOrganization' => $oid];
    $apps = new ArrayApplication(); $apps->load(['where' => [['field' => 'hash', 'value' => 'policy']], 'limit' => 1]);
    $items['policy_app'] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $apps[0]->getId(), 'active' => 1]);
    foreach (['root', 'role', 'hidden', 'inactive', 'other_root'] as $owner) {
        $items['value_' . $owner] = mcpFixture(Valeur::class, ['IDholon' => $items[$owner]->getId(),
            'title' => 'Value ' . $owner, 'principle' => '<p>Current principle ' . $owner . '.</p><p>Respect <strong>people</strong>.</p>']);
    }
    $vid = (int)$items['value_root']->getId();
    $base = ['IDholon' => $role, 'title' => 'Value rule', 'description' => '<p>Describe the action.</p>',
        'intention' => '<p>Explain the intention.</p>', 'scope' => 'local', 'IDvaleur' => $vid, 'review_date' => '2019-01-01', 'expiration_date' => '2030-01-01'];
    $items['rule'] = mcpFixture(Rule::class, $base);
    $items['expired_rule'] = mcpFixture(Rule::class, ['title' => 'Expired value rule', 'expiration_date' => '2020-01-01'] + $base);
    $items['no_value_rule'] = mcpFixture(Rule::class, ['title' => 'Rule without value', 'IDvaleur' => null] + $base);
    $items['hidden_rule'] = mcpFixture(Rule::class, ['IDholon' => $items['hidden']->getId(), 'title' => 'Hidden rule'] + $base);
    $items['other_rule'] = mcpFixture(Rule::class, ['IDholon' => $items['other_root']->getId(), 'IDvaleur' => $items['value_other_root']->getId()] + $base);
    $items['unavailable_value_rule'] = mcpFixture(Rule::class, ['IDvaleur' => $items['value_inactive']->getId()] + $base);
    $items['archived'] = mcpFixture(McpTestHolon::class, ['name' => 'Archived', 'IDorganization' => $oid,
        'IDholon_org' => $hid, 'IDholon_parent' => $hid, 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1, 'archived_at' => new DateTime()]);
    $items['archived_value'] = mcpFixture(Valeur::class, ['IDholon' => $items['archived']->getId(), 'title' => 'Archived value']);
    $items['archived_rule'] = mcpFixture(Rule::class, ['IDholon' => $items['archived']->getId()] + $base);
    $items['property'] = mcpFixture(Property::class, ['name' => 'Our values', 'shortname' => 'api_values', 'type' => 'type1',
        'IDholon_organization' => $hid, 'IDpropertyformat' => 2, 'listitemtype' => 'value', 'active' => 1]);
    $items['holon_property'] = mcpFixture(HolonProperty::class, ['IDholon' => $hid, 'IDproperty' => $items['property']->getId(),
        'value' => json_encode([['id' => $vid, 'title' => 'Value root', 'principle' => $items['value_root']->get('principle')]]), 'active' => 1]);
    foreach (['hidden', 'role'] as $owner) {
        $items['property_' . $owner] = mcpFixture(HolonProperty::class, ['IDholon' => $items[$owner]->getId(),
            'IDproperty' => $items['property']->getId(), 'active' => 1,
            'value' => json_encode([['id' => (int)$items['value_' . $owner]->getId(), 'title' => $items['value_' . $owner]->get('title'), 'principle' => $items['value_' . $owner]->get('principle')]])]);
    }
    $items['role']->set('IDholon_template', $items['hidden']->getId()); $items['role']->save();
    $items['html_property'] = mcpFixture(Property::class, ['name' => 'Framed values', 'shortname' => 'framed_values', 'type' => 'type1',
        'IDholon_organization' => $hid, 'IDpropertyformat' => 7, 'listitemtype' => 'value', 'active' => 1]);
    $items['html_holon_property'] = mcpFixture(HolonProperty::class, ['IDholon' => $hid, 'IDproperty' => $items['html_property']->getId(),
        'value' => json_encode(['before' => '<p>Before values.</p>', 'items' => [$vid], 'after' => '<p>After values.</p>']), 'active' => 1]);
    // Existing snapshots deliberately become stale after the standalone value changes.
    $items['value_root']->set('title', 'Transparency');
    $items['value_root']->set('principle', '<p>Live principle.</p><p>Share <strong>decisions</strong>.</p>');
    $items['value_root']->save();

    $request = mcpAuthorizationRequest($items['client']);
    $code = McpOauthGrant::issueCode($items['client'], $uid, $oid, $request);
    $tokens = McpOauthGrant::exchange($items['client'], mcpExchangeRequest($items['client'], $code));
    $grant = McpOauthGrant::authenticate($tokens['access_token'], omoMcpPublicUrl());
    mcpCheck($grant !== null, 'Read-only OAuth grant');
    $call = static fn(string $name, array $args = []) => omoApiExecuteOperation($name, omoMcpToolArguments($name, (object)$args), $grant);
    $page = $call('omo_list_values', ['limit' => 1]);
    mcpCheck(count($page['items']) === 1 && $page['items'][0]['value_id'] === $vid && !$page['complete'], 'Stable value cursor');
    $next = $call('omo_list_values', ['after_id' => $page['next_after_id']]);
    mcpCheck(count($next['items']) === 2 && $next['items'][0]['value_id'] === (int)$items['value_role']->getId() && $next['complete'], 'Readable template values included; inactive, archived and foreign values omitted: ' . json_encode($next));
    mcpCheck(count($call('omo_list_values', ['query' => 'TRANSPARENCY'])['items']) === 1, 'Case-insensitive value lookup');
    $effective = $call('omo_list_values', ['holon_id' => $role]);
    mcpCheck(count($effective['items']) === 2 && in_array((int)$items['value_hidden']->getId(), array_column($effective['items'], 'value_id'), true), 'Holon filter includes local and template values');
    mcpCheck(array_column($call('omo_list_values', ['holon_id' => $hid])['items'], 'value_id') === [$vid], 'Organization-circle values are distinct from organization-wide catalog');
    $value = $call('omo_get_value', ['value_id' => $vid]);
    mcpCheck($value['value']['title'] === 'Transparency' && str_contains($value['value']['principle'], 'Live principle.')
        && !str_contains($value['value']['principle'], '<p>') && str_contains($value['value']['principle_html'], '<strong>decisions</strong>'), 'Current title, plain principle and HTML');
    $rules = $call('omo_list_value_rules', ['value_id' => $vid, 'limit' => 1]);
    $rules2 = $call('omo_list_value_rules', ['value_id' => $vid, 'after_id' => $rules['next_after_id']]);
    mcpCheck(count($rules['items']) === 1 && count($rules2['items']) === 1 && $rules2['complete'], 'Associated rules pagination includes expired, excludes hidden/archived/unlinked');
    mcpCheck($call('omo_list_value_rules', ['value_id' => (int)$items['value_role']->getId()])['items'] === [], 'Value without rules has empty list');
    $rid = (int)$items['rule']->getId();
    $reverse = $call('omo_get_rule_value', ['rule_id' => $rid]);
    mcpCheck($reverse['value_state'] === 'linked' && $reverse['value'] === $value['value'], 'Reverse association includes full live principle');
    mcpCheck($call('omo_get_rule_value', ['rule_id' => (int)$items['no_value_rule']->getId()])['value_state'] === 'none', 'Unlinked rule explicit');
    $unavailable = $call('omo_get_rule_value', ['rule_id' => (int)$items['unavailable_value_rule']->getId()]);
    mcpCheck($unavailable['value_state'] === 'unavailable' && $unavailable['value'] === null, 'Unavailable value distinguished from no value without leaking details');
    $read = $call('omo_read_record', ['module' => 'rules', 'record_id' => $rid, 'context_holon_id' => $role]);
    mcpCheck($read['record']['value']['value_id'] === $vid && str_contains($read['text'], 'Live principle.') && str_contains($read['text'], 'Explain the intention.'), 'Existing rule reader includes value and intention');
    $structure = $call('omo_read_record', ['module' => 'structure', 'record_id' => $hid]);
    mcpCheck(str_contains($structure['text'], 'Transparency') && str_contains($structure['text'], 'Live principle.')
        && !str_contains($structure['text'], 'Current principle root') && !str_contains($structure['text'], '"principle"')
        && $structure['record']['values'][0]['value_id'] === $vid, 'Holon property resolves live references instead of stale JSON snapshots');
    mcpCheck(str_contains($structure['text'], 'Before values.') && str_contains($structure['text'], 'After values.') && count($structure['record']['values']) === 1, 'HTML list framing retained and repeated value references deduplicated');
    $inherited = $call('omo_read_record', ['module' => 'structure', 'record_id' => $role]);
    mcpCheck(str_contains($inherited['text'], 'Current principle hidden.') && count($inherited['record']['values']) === 2, 'Inherited values readable from the holon');
    foreach ([['omo_read_record', $structure], ['omo_read_record', $read], ['omo_catalog', $call('omo_catalog')]] as [$operation, $data]) {
        restCheckSchema($operation, ['status' => 200, 'data' => $data]);
    }
    foreach (['inactive', 'other_root'] as $owner) valueApiReject(fn() => $call('omo_get_value', ['value_id' => (int)$items['value_' . $owner]->getId()]), 'Inaccessible value rejected');
    valueApiReject(fn() => $call('omo_get_value', ['value_id' => (int)$items['archived_value']->getId()]), 'Archived value rejected');
    foreach (['hidden_rule', 'archived_rule', 'other_rule'] as $key) valueApiReject(fn() => $call('omo_get_rule_value', ['rule_id' => (int)$items[$key]->getId()]), 'Inaccessible rule rejected');
    foreach ([['omo_get_value', []], ['omo_get_rule_value', ['rule_id' => 0]], ['omo_list_values', ['limit' => 51]],
        ['omo_list_records', ['module' => 'structure', 'value_id' => $vid]], ['omo_list_value_rules', ['value_id' => '1']]] as [$name, $args]) {
        valueApiReject(fn() => omoMcpToolArguments($name, (object)$args), 'Invalid tool arguments rejected');
    }
    valueApiReject(fn() => omoApiExecuteOperation('omo_get_value', ['value_id' => $vid], array_replace($grant, ['scope' => 'structure:read'])), 'No OAuth scope expansion');

    $spec = omoRestOpenApi();
    foreach (['/values', '/values/{value_id}', '/values/{value_id}/rules', '/rules/{rule_id}/value'] as $path) mcpCheck(isset($spec['paths'][$path]['get']), 'OpenAPI documents value routes');
    mcpCheck(isset($spec['components']['schemas']['ValueRulePage'], $spec['components']['schemas']['RecordSummary']['properties']['value']), 'OpenAPI response contracts');
    $route = omoRestMatchRoute('/values/' . $vid . '/rules', 'GET');
    mcpCheck(omoRestArguments($route, 'limit=1')['value_id'] === $vid, 'REST path and query validation');
    valueApiReject(fn() => omoRestArguments($route, 'value_id=2'), 'Cannot override path ID');
    foreach ([['/values?query=Transparency', 'omo_list_values', ['query' => 'Transparency']],
        ['/values/' . $vid, 'omo_get_value', ['value_id' => $vid]],
        ['/values/' . $vid . '/rules', 'omo_list_value_rules', ['value_id' => $vid]],
        ['/rules/' . $rid . '/value', 'omo_get_rule_value', ['rule_id' => $rid]]] as [$path, $tool, $args]) {
        $rest = valueApiHttp($tokens['access_token'], '/api/v1' . $path);
        restCheckSchema($tool, $rest);
        $rpc = valueApiHttp($tokens['access_token'], parse_url(omoMcpPublicUrl(), PHP_URL_PATH),
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => $tool, 'arguments' => (object)$args]]);
        mcpCheck($rest['status'] === 200 && empty($rpc['data']['result']['isError'])
            && $rest['data'] === $rpc['data']['result']['structuredContent'] && $rest['data'] === $call($tool, $args), 'REST/MCP parity: ' . $tool . ' ' . json_encode($rest));
    }
    mcpCheck(valueApiHttp($tokens['access_token'], '/api/v1/values/' . $items['value_other_root']->getId())['status'] === 422, 'Foreign value rejected by REST');
    mcpCheck(valueApiHttp($tokens['access_token'], '/api/v1/values?holon_id=' . $hid)['data']['items'][0]['value_id'] === $vid, 'REST supports the organization-circle filter');
    $items['policy_app']->set('active', 0); $items['policy_app']->save();
    valueApiReject(fn() => $call('omo_get_rule_value', ['rule_id' => $rid]), 'Disabled Policy blocks reverse lookup');
    valueApiReject(fn() => $call('omo_list_value_rules', ['value_id' => $vid]), 'Disabled Policy blocks rule list');
    $items['app']->set('active', 0); $items['app']->save();
    valueApiReject(fn() => $call('omo_get_value', ['value_id' => $vid]), 'Disabled Structure blocks value details');
    $items['membership']->set('active', 0); $items['membership']->save();
    valueApiReject(fn() => $call('omo_list_values'), 'Revoked membership blocks access');
    echo "api_values_test: OK (live values, rules, reverse association, pagination, visibility, OAuth, REST/MCP parity)\n";
} finally { mcpCleanup($items); $_SESSION = $before; }
