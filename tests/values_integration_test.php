<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/choice/rule-value-fields.php';

use dbObject\{DbObject, ArrayOrganization, Holon, Property, PropertyFormat, HolonProperty, Valeur, Rule, DecisionGovernanceAction, HolonPermission, Permission};

function valueCheck($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function valueSave($object): void {
    $result = $object->save();
    valueCheck(!empty($result['status']), $result['text'] ?? 'Save failed');
}
$organizations = new ArrayOrganization();
$organizations->load();
$root = null;
foreach ($organizations as $organization) {
    $root = $organization->getStructuralRootHolon();
    if ($root instanceof Holon) break;
}
valueCheck($root instanceof Holon, 'A local organization with a structure is required');
$organizationId = (int)$organization->getId();
$_SESSION['currentUser'] = 0;
$_SESSION['currentOrganization'] = $organizationId;
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $makeHolon = static function (string $name) use ($root): Holon {
        $holon = new Holon();
        foreach (['name' => $name, 'IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(), 'IDtypeholon' => 2, 'active' => true] as $field => $value) $holon->set($field, $value);
        valueSave($holon);
        return $holon;
    };
    $holon = $makeHolon('Value integration source');
    $child = $makeHolon('Value integration child');
    $property = new Property();
    foreach (['name' => 'Values test', 'shortname' => 'values_test', 'IDholon_organization' => $root->getId(), 'IDpropertyformat' => 2, 'listitemtype' => 'value'] as $field => $value) $property->set($field, $value);
    valueSave($property);
    valueCheck(Property::normalizeListItemType('value') === Property::LIST_ITEM_VALUE, 'Value type must be supported');
    valueCheck(in_array('value', array_column(Property::getTemplateListItemTypeOptions(), 'id'), true), 'Templates must offer values');
    $link = new HolonProperty();
    $link->set('IDholon', $holon->getId());
    $link->set('IDproperty', $property->getId());
    $link->set('active', true);
    $link->set('value', json_encode([['title' => 'Transparency <test>', 'principle' => '<p>Share <strong>information</strong>.</p><script>bad()</script>']]));
    valueSave($link);
    $items = json_decode($link->get('value'), true);
    $id = $items[0]['id'];
    valueCheck($id > 0, 'Saving a property must create a dedicated value row');
    $value = Valeur::loadInOrganization($id, $organizationId);
    valueCheck($value instanceof Valeur && $value->get('title') === 'Transparency <test>', 'Value must persist');
    valueCheck(!str_contains($value->get('principle'), '<script>') && str_contains($value->get('principle'), '<strong>'), 'Principles must retain safe formatting');
    valueCheck(!Valeur::loadInOrganization($id, $organizationId + 1000000), 'References cannot cross organizations');
    $html = omoRuleValueRender($value);
    valueCheck(str_contains($html, '<details') && str_contains($html, '&lt;test&gt;') && str_contains($html, '<strong>'), 'Value display must escape titles and expand formatted principles');
    $items[0]['title'] = 'Openness';
    $link->set('value', json_encode($items));
    valueSave($link);
    $items = json_decode($link->get('value'), true);
    valueCheck($items[0]['id'] === $id, 'Editing a local value must keep its identity');
    $value = new Valeur();
    $value->load($id, true);
    valueCheck($value->get('title') === 'Openness', 'Value title must be updated');
    $inherited = new HolonProperty();
    $inherited->set('IDholon', $child->getId());
    $inherited->set('IDproperty', $property->getId());
    $inherited->set('value', json_encode($items));
    valueSave($inherited);
    valueCheck(json_decode($inherited->get('value'), true)[0]['id'] === $id, 'Unchanged inherited values retain their reference');
    $items[0]['principle'] = '<p>Local principle</p>';
    $inherited->set('value', json_encode($items));
    valueSave($inherited);
    $localId = json_decode($inherited->get('value'), true)[0]['id'];
    valueCheck($localId !== $id, 'Editing an inherited value creates a local value');
    $value->load($id, true);
    valueCheck(!str_contains($value->get('principle'), 'Local principle'), 'The inherited source must remain unchanged');
    $beforeCount = count(Valeur::findForOrganization($organizationId));
    $inherited->set('value', json_encode([['title' => 'Must roll back', 'principle' => ''], ['id' => 2147483647, 'title' => 'Missing', 'principle' => '']]));
    valueCheck(empty($inherited->save()['status']), 'Invalid references must fail');
    valueCheck(count(Valeur::findForOrganization($organizationId)) === $beforeCount, 'Failed property saves must roll back value creation');
    $inherited->set('value', json_encode([['title' => 'Title without principle', 'principle' => '']]));
    valueSave($inherited);
    valueCheck(json_decode($inherited->get('value'), true)[0]['id'] > 0, 'Principles are optional');
    $property->set('IDpropertyformat', PropertyFormat::FORMAT_HTML_LIST);
    valueSave($property);
    $link->set('value', json_encode(['before' => '<p>Before</p>', 'items' => [['id' => $id, 'title' => 'Openness', 'principle' => $value->get('principle')]], 'after' => '<p>After</p>']));
    valueSave($link);
    $parts = json_decode($link->get('value'), true);
    valueCheck($parts['items'][0]['id'] === $id && $parts['before'] === '<p>Before</p>' && $parts['after'] === '<p>After</p>', 'Composite lists retain their framing and references');
    $state = ['title' => 'Share decisions', 'description' => '<p>Publish the decisions.</p>', 'intention' => '', 'IDvaleur' => $id, 'scope' => 'local', 'review_date' => '2027-01-01', 'expiration_date' => '2028-01-01'];
    $validation = DecisionGovernanceAction::validateRuleCreate($state, $holon->getId());
    valueCheck(!empty($validation['status']) && $validation['state']['IDvaleur'] === $id, 'Governance validation retains value references');
    $rule = new Rule();
    $result = $rule->applyGovernanceState($validation['state']);
    valueCheck(!empty($result['status']), $result['text'] ?? 'Rule creation failed');
    $rule->load($rule->getId(), true);
    valueCheck((int)$rule->get('IDvaleur') === $id && $rule->get('intention') === null, 'A rule can link a value without free intention text');
    valueCheck(DecisionGovernanceAction::captureRuleState($rule)['IDvaleur'] === $id, 'Captured proposals retain the link');
    $result = DecisionGovernanceAction::validateRuleUpdate($rule, ['IDvaleur' => 2147483647], $holon->getId());
    valueCheck(empty($result['status']), 'Governance rejects unavailable values');
    $rule->set('IDvaleur', 2147483647);
    valueCheck(empty($rule->save()['status']), 'Direct rule saving rejects unavailable values');
    $rule->set('IDvaleur', null);
    valueSave($rule);
    $rule->load($rule->getId(), true);
    valueCheck($rule->get('IDvaleur') === null, 'The optional link can be cleared');
    // Exercise the full editor path with the existing collective property permissions.
    foreach (['CAN_CREATE_TYPE1_PROPERTIES', 'CAN_EDIT_TYPE1_PROPERTIES'] as $permissionKey) {
        $grant = new HolonPermission();
        $grant->set('IDholon', $holon->getId());
        $grant->set('IDpermission', Permission::findByKey($permissionKey)->getId());
        $grant->set('range', HolonPermission::RANGE_ORGANIZATION);
        $grant->set('member_type', HolonPermission::MEMBER_TYPE_COLLECTIVE);
        valueSave($grant);
    }
    $editorHolon = $makeHolon('Full value editor test');
    $editorTemplate = $makeHolon('Value editor template');
    $editorTemplate->set('templatename', 'Value editor template');
    valueSave($editorTemplate);
    $editorHolon->set('IDholon_template', $editorTemplate->getId());
    valueSave($editorHolon);
    $payload = DecisionGovernanceAction::captureHolonEditorState($editorHolon, $organization)['editor_payload'];
    $payload['properties'][] = ['id' => 0, 'type' => 'type1', 'isDirectProperty' => true, 'name' => 'Full editor values', 'formatId' => 2, 'listItemType' => 'value', 'value' => json_encode([['title' => 'Responsibility', 'principle' => '<p>Own our choices.</p>']])];
    $result = $organization->saveHolonEditorDefinition($payload, 0, (int)$root->getId(), (int)$editorHolon->getId(), true, (int)$holon->getId());
    valueCheck(!empty($result['status']), 'Full editor must create values: ' . json_encode($result));
    $definitions = $editorHolon->getHolonEditorPropertyDefinitions();
    valueCheck(count($definitions) === 1, 'Full editor must create its property');
    $editorItems = json_decode($definitions[0]['value'], true);
    valueCheck($editorItems[0]['id'] > 0, 'Full editor must persist the object reference');
    $payload['properties'] = $definitions;
    $editorItems[0]['title'] = 'Must roll back update';
    $payload['properties'][0]['value'] = json_encode($editorItems);
    $payload['properties'][] = ['id' => 0, 'type' => 'type1', 'isDirectProperty' => true, 'name' => 'Must roll back definition', 'formatId' => 2, 'listItemType' => 'value', 'value' => json_encode([['id' => 2147483647, 'title' => 'Unavailable', 'principle' => '']])];
    $result = $organization->saveHolonEditorDefinition($payload, 0, (int)$root->getId(), (int)$editorHolon->getId(), true, (int)$holon->getId());
    valueCheck(empty($result['status']), 'Invalid full editor submissions must fail');
    $editorHolon->load($editorHolon->getId(), true);
    valueCheck(count($editorHolon->getHolonEditorPropertyDefinitions()) === 1, 'Failed editor saves must roll back property definitions');
    $unchanged = new Valeur();
    $unchanged->load($editorItems[0]['id'], true);
    valueCheck($unchanged->get('title') === 'Responsibility', 'Failed editor saves must roll back previous value updates');
    echo "values_integration_test: OK\n";
} finally {
    $pdo->rollBack();
}
