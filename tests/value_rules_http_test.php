<?php
declare(strict_types=1);
// Local Docker only; run as www-data. Remove only the fixtures created here.
$_SERVER['HTTP_HOST'] = 'localtest.me';
$_SERVER['HTTPS'] = 'on';
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{User, UserOrganization, UserHolon, HolonPermission, Permission, Application, OrganizationApplication, Property, HolonProperty, Valeur, Rule, ArrayRule};

function valueRulesHttp(string $path): array {
    $cookie = session_name() . '=' . session_id();
    session_write_close();
    $curl = curl_init('https://localhost/omo/api/' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Host: localtest.me', 'Cookie: ' . $cookie]]);
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    session_start();
    mcpCheck(is_string($body), 'HTTP request failed');
    mcpCheck(!preg_match('/Warning:|Fatal error:|Deprecated:/', $body), 'HTTP rendering diagnostics: ' . $body);
    return ['status' => $status, 'body' => $body];
}

$before = $_SESSION;
$items = [];
try {
    $nonce = bin2hex(random_bytes(6));
    $items['org'] = mcpFixture(McpTestOrganization::class, ['name' => 'Value popup test', 'shortname' => 'value-popup-' . $nonce]);
    $oid = (int)$items['org']->getId();
    $items['user'] = mcpFixture(User::class, ['firstname' => 'Value test', 'email' => 'value-popup-' . $nonce . '@example.invalid', 'active' => 1]);
    $items['membership'] = mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $items['user']->getId(), 'active' => 1]);
    foreach (['structure', 'policy'] as $hash) {
        $app = new Application();
        if ($app->load(['hash', $hash])) $items['app_' . $hash] = mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    }
    $items['root'] = mcpFixture(McpTestHolon::class, ['name' => 'Value root', 'IDorganization' => $oid, 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $rid = (int)$items['root']->getId();
    $items['root']->set('IDholon_org', $rid); $items['root']->save();
    $items['value'] = mcpFixture(Valeur::class, ['IDholon' => $rid, 'title' => 'Transparency <test>', 'principle' => '<p>Share <strong>information</strong>.</p>']);
    $vid = (int)$items['value']->getId();
    $items['property'] = mcpFixture(Property::class, ['name' => 'Our values', 'shortname' => 'values', 'type' => 'type1', 'IDholon_organization' => $rid, 'IDpropertyformat' => 2, 'listitemtype' => 'value', 'active' => 1]);
    $items['holonproperty'] = mcpFixture(HolonProperty::class, ['IDholon' => $rid, 'IDproperty' => $items['property']->getId(), 'active' => 1,
        'value' => json_encode([['id' => $vid, 'title' => 'Transparency <test>', 'principle' => '<p>Share <strong>information</strong>.</p>']])]);
    $base = ['IDholon' => $rid, 'IDvaleur' => $vid, 'description' => '<p>Publish our decisions.</p>', 'intention' => '<p>Make decisions understandable.</p>', 'scope' => 'local', 'review_date' => '2027-01-01', 'expiration_date' => '2028-01-01'];
    for ($i = 1; $i <= 4; $i++) $items['rule_' . $i] = mcpFixture(Rule::class, ['title' => 'Associated rule ' . $i] + $base);
    $items['unlinked'] = mcpFixture(Rule::class, ['title' => 'Unlinked rule', 'IDvaleur' => null] + $base);
    $items['archived_holon'] = mcpFixture(McpTestHolon::class, ['name' => 'Archived value rules', 'IDholon_org' => $rid, 'IDholon_parent' => $rid, 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1, 'archived_at' => new DateTime()]);
    $items['archived_rule'] = mcpFixture(Rule::class, ['title' => 'Archived rule', 'IDholon' => $items['archived_holon']->getId()] + $base);
    mcpCheck((ArrayRule::countByValue($oid)[$vid] ?? 0) === 4, 'Count must exclude unlinked and archived rules');
    $rules = new ArrayRule(); $rules->loadForValue($vid, $oid);
    mcpCheck(count($rules) === 4, 'Popup query and counter must match');
    $rules->loadForValue($vid, $oid + 1000000);
    mcpCheck(count($rules) === 0 && ArrayRule::countByValue($oid + 1000000) === [], 'Rule queries must respect organization boundaries');
    $_SESSION = ['currentUser' => (int)$items['user']->getId(), 'currentOrganization' => $oid, 'auth_security_version' => (int)$items['user']->get('security_version')];
    $response = valueRulesHttp('getOrg.php?oid=' . $oid . '&cid=' . $rid);
    mcpCheck($response['status'] === 200, 'Structure must be readable');
    $structure = $response['body'];
    mcpCheck(str_contains($structure, 'section-list--values') && str_contains($structure, 'data-value-rules-popup'), 'Values must use a list without bullets and expose the popup link');
    mcpCheck(str_contains($structure, '4 regles associees'), 'Structure must show the associated count');
    $response = valueRulesHttp('policy/value_rules.php?id=' . $vid);
    mcpCheck($response['status'] === 200 && substr_count($response['body'], 'data-policy-rule-card') === 4, 'Popup must render exactly the associated rules');
    mcpCheck(!str_contains($response['body'], 'Unlinked rule') && !str_contains($response['body'], 'Archived rule'), 'Popup must exclude other rules');
    mcpCheck(str_contains($response['body'], '&lt;test&gt;') && str_contains($response['body'], 'Make decisions understandable.'), 'Popup must escape value titles and retain intention content');
    mcpCheck(!str_contains($response['body'], 'data-policy-rule-edit') && !str_contains($response['body'], 'data-value-rules-popup'), 'Popup cards must not introduce recursive links or unsupported edit actions');
    file_put_contents(dirname(__DIR__) . '/tmp/value-rules-popup-fixture.html', $response['body']);
    file_put_contents(dirname(__DIR__) . '/tmp/value-rules-structure-fixture.html', $structure);
    $response = valueRulesHttp('policy/index.php?oid=' . $oid . '&cid=' . $rid . '&policy_scope=global');
    mcpCheck($response['status'] === 200 && substr_count($response['body'], 'data-policy-rule-card') === 5, 'The policy app must retain its rule cards after sharing the renderer');
    $items['assignment'] = mcpFixture(UserHolon::class, ['IDuser' => $items['user']->getId(), 'IDholon' => $rid, 'active' => 1, 'is_membership' => 1]);
    $items['grant'] = mcpFixture(HolonPermission::class, ['IDholon' => $rid, 'IDpermission' => Permission::findByKey('CAN_EDIT_RULE')->getId(), 'range' => HolonPermission::RANGE_SELF, 'member_type' => HolonPermission::MEMBER_TYPE_MEMBER]);
    $response = valueRulesHttp('policy/edit.php?oid=' . $oid . '&cid=' . $rid . '&rule_id=' . $items['rule_1']->getId());
    mcpCheck($response['status'] === 200, 'Rule editor must remain accessible with its normal edit permission');
    $descriptionOffset = strpos($response['body'], 'name="description"');
    $intentionOffset = strpos($response['body'], 'name="intention"');
    $valueOffset = strpos($response['body'], 'name="IDvaleur"');
    mcpCheck($descriptionOffset !== false && $intentionOffset !== false && $valueOffset !== false && $descriptionOffset < $intentionOffset && $intentionOffset < $valueOffset, 'Editor fields must appear in rule, intention, value order');
    $items['other_org'] = mcpFixture(McpTestOrganization::class, ['name' => 'Foreign value test', 'shortname' => 'foreign-value-' . $nonce]);
    $items['other_root'] = mcpFixture(McpTestHolon::class, ['name' => 'Foreign root', 'IDorganization' => $items['other_org']->getId(), 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $items['other_value'] = mcpFixture(Valeur::class, ['IDholon' => $items['other_root']->getId(), 'title' => 'Foreign value']);
    $response = valueRulesHttp('policy/value_rules.php?id=' . $items['other_value']->getId());
    mcpCheck($response['status'] === 404 && !str_contains($response['body'], 'Foreign value'), 'Popup must not reveal values from another organization');
    for ($i = 2; $i <= 4; $i++) { $items['rule_' . $i]->set('IDvaleur', null); $items['rule_' . $i]->save(); }
    $response = valueRulesHttp('getOrg.php?oid=' . $oid . '&cid=' . $rid);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], '1 regle associee'), 'Counter must refresh and use the singular');
    $items['rule_1']->set('IDvaleur', null); $items['rule_1']->save();
    $response = valueRulesHttp('getOrg.php?oid=' . $oid . '&cid=' . $rid);
    mcpCheck($response['status'] === 200 && !str_contains($response['body'], 'data-value-rules-popup') && str_contains($response['body'], 'Share <strong>information</strong>.'), 'Values without rules must retain their principle without a popup link');
    $response = valueRulesHttp('policy/value_rules.php?id=' . $vid);
    mcpCheck($response['status'] === 200 && str_contains($response['body'], 'Aucune regle associee') && !str_contains($response['body'], 'data-policy-rule-card'), 'Popup must support values without rules');
    $response = valueRulesHttp('policy/value_rules.php?id=2147483647');
    mcpCheck($response['status'] === 404, 'Unavailable values must return 404');
    $items['membership']->set('active', false); $items['membership']->save();
    $response = valueRulesHttp('policy/value_rules.php?id=' . $vid);
    mcpCheck($response['status'] === 403, 'Revoked organization access must deny the popup');
    echo "value_rules_http_test: OK (structure, count, popup, archive, escaping, organization access)\n";
} finally {
    mcpCleanup($items);
    $_SESSION = $before;
}
