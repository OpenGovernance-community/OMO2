<?php
declare(strict_types=1);

// Local MariaDB integration test. All fixtures and conversions are rolled back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, ArrayOrganization, Holon, Property, HolonProperty, Authority, Rule};

function conversionCheck($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function conversionSave($object): void {
    $result = $object->save();
    conversionCheck(!empty($result['status']), $result['text'] ?? 'Fixture save failed');
}
function conversionReload($object) {
    conversionCheck($object->load((int)$object->getId(), true), 'Reload failed');
    return $object;
}

$organizations = new ArrayOrganization();
$organizations->load(['limit' => 1]);
$root = null;
foreach ($organizations as $organization) { $root = $organization->getStructuralRootHolon(); }
conversionCheck($root instanceof Holon, 'A local organization with a root holon is required');
$_SESSION['currentUser'] = 0;
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $owner = new Holon();
    $owner->set('name', 'Conversion test owner');
    $owner->set('IDholon_org', $root->getId());
    $owner->set('IDholon_parent', $root->getId());
    $owner->set('IDtypeholon', 1);
    conversionSave($owner);
    $other = new Holon();
    $other->set('name', 'Conversion test second holon');
    $other->set('IDholon_org', $root->getId());
    $other->set('IDholon_parent', $root->getId());
    $other->set('IDtypeholon', 1);
    conversionSave($other);

    foreach ([2, 7] as $format) {
        $longLabel = str_repeat("Un domaine \u{00E9}tendu & complet. ", 20) . 'Fin';
        $originalLabels = ['123', 'Same', 'Same', str_repeat('x', 255), $longLabel];
        $encode = static fn(array $items) => json_encode($format === 7 ? ['before' => '<p>Before</p>', 'items' => $items, 'after' => '<p>After</p>'] : $items);
        $property = new Property();
        $property->set('name', 'Conversion test');
        $property->set('shortname', 'conversion_test');
        $property->set('IDholon_organization', $root->getId());
        $property->set('IDpropertyformat', $format);
        $property->set('listitemtype', 'text');
        conversionSave($property);
        $values = [];
        foreach ([$owner, $other] as $index => $holon) {
            $value = new HolonProperty();
            $value->set('IDholon', $holon->getId());
            $value->set('IDproperty', $property->getId());
            $value->set('value', $encode($index ? ['Other'] : $originalLabels));
            $value->set('active', true);
            conversionSave($value);
            $values[] = $value;
        }
        $definitions = [['id' => $property->getId(), 'name' => 'Conversion test', 'formatId' => $format, 'listItemType' => 'authority', 'value' => $values[0]->get('value')]];
        $result = Property::convertListDefinitions($owner, $definitions);
        conversionCheck(empty($result['status']), 'Unconfirmed conversion must fail');
        conversionCheck(conversionReload($property)->get('listitemtype') === 'text', 'Unconfirmed type changed');

        $definitions[0]['listConversionFrom'] = 'text';
        $result = Property::convertListDefinitions($owner, $definitions);
        conversionCheck(!empty($result['status']), $result['message'] ?? 'Text conversion failed');
        $ids = Property::listConversionParts($definitions[0]['value'], $format)['items'];
        // Follow the ordinary editor save path to ensure it does not erase converted IDs.
        $resolved = $owner->syncDirectEditorPropertyDefinitions($definitions, (int)$root->getId());
        $submitted = [(int)$property->getId() => $definitions[0]['value']];
        $syncAuthorities = new ReflectionMethod($organization, 'syncSubmittedAuthorityPropertyValues');
        $args = [$owner, &$submitted, $resolved, 0, []];
        $syncResult = $syncAuthorities->invokeArgs($organization, $args);
        conversionCheck(!empty($syncResult['status']), $syncResult['message'] ?? 'Editor authority sync failed');
        $owner->syncEditorPropertyValues($submitted, $resolved);
        conversionCheck(Property::listConversionParts(conversionReload($values[0])->get('value'), $format)['items'] === $ids, 'Editor overwrote migrated IDs');
        $owner->syncTemplateProperties($definitions, (int)$root->getId());
        conversionCheck(Property::listConversionParts(conversionReload($values[0])->get('value'), $format)['items'] === $ids, 'Template editor overwrote migrated IDs');
        conversionCheck(count(array_unique($ids)) === count($originalLabels), 'Duplicate texts must create separate authorities');
        $longAuthority = new Authority();
        conversionCheck($longAuthority->load($ids[4], true) && $longAuthority->get('label') === $longLabel, 'Long UTF-8 label was truncated');
        $authority = new Authority();
        conversionCheck($authority->load($ids[0]) && $authority->get('label') === '123', 'Numeric text was mistaken for an authority ID');
        conversionCheck((int)$authority->get('IDholon') === (int)$owner->getId(), 'Wrong owner');
        $otherIds = Property::listConversionParts(conversionReload($values[1])->get('value'), $format)['items'];
        $otherAuthority = new Authority();
        conversionCheck($otherAuthority->load($otherIds[0]) && (int)$otherAuthority->get('IDholon') === (int)$other->getId(), 'Shared values were not converted');

        $rule = new Rule();
        $rule->set('IDauthority', $authority->getId());
        $rule->set('title', 'Conversion rule');
        $rule->set('description', '<p>Keep this rule</p>');
        $rule->set('review_date', '2026-09-25');
        $rule->set('expiration_date', '2027-09-25');
        conversionSave($rule);
        $child = new Authority();
        $child->set('IDholon', $other->getId());
        $child->set('IDauthority_parent', $authority->getId());
        $child->set('label', 'Keep this child');
        conversionSave($child);
        $instance = new Authority();
        $instance->set('IDholon', $other->getId());
        $instance->set('IDauthority_template', $authority->getId());
        $instance->set('label', 'Template instance');
        conversionSave($instance);

        $linkedProperty = new Property();
        $linkedProperty->set('name', 'Other authority list');
        $linkedProperty->set('shortname', 'other_list');
        $linkedProperty->set('IDholon_organization', $root->getId());
        $linkedProperty->set('IDpropertyformat', 2);
        $linkedProperty->set('listitemtype', 'authority');
        conversionSave($linkedProperty);
        $reference = new HolonProperty();
        $reference->set('IDholon', $owner->getId());
        $reference->set('IDproperty', $linkedProperty->getId());
        $reference->set('value', json_encode([$authority->getId()]));
        conversionSave($reference);

        $definitions[0]['listItemType'] = 'text';
        $definitions[0]['listConversionFrom'] = 'authority';
        $result = Property::convertListDefinitions($owner, $definitions);
        conversionCheck(empty($result['status']), 'An authority referenced by another list must not be deleted');
        conversionCheck(conversionReload($property)->get('listitemtype') === 'authority', 'Reference conflict changed type');
        conversionCheck(Property::listConversionParts(conversionReload($values[0])->get('value'), $format)['items'] === $ids, 'Reference conflict lost IDs');
        $reference->set('value', '[]');
        conversionSave($reference);

        $values[1]->set('value', $encode([$otherIds[0], '2147483647']));
        conversionSave($values[1]);
        $result = Property::convertListDefinitions($owner, $definitions);
        conversionCheck(empty($result['status']) && str_contains($result['message'], '#2147483647 n existe plus'), 'Missing authority must have a specific error');
        conversionCheck(str_contains($result['message'], 'holon #' . $other->getId()), 'Missing authority error must identify the affected holon');
        conversionCheck((new Authority())->load($ids[0], true), 'Missing reference must roll back earlier conversions');

        $legacyText = $ids[0] . ' anciens domaines en texte';
        $values[1]->set('value', $encode([$otherIds[0], 'Texte deja present', $legacyText, '0']));
        conversionSave($values[1]);
        // Numeric prefixes in old text must not count as references in other lists either.
        $reference->set('value', json_encode([$legacyText]));
        conversionSave($reference);
        $result = Property::convertListDefinitions($owner, $definitions);
        conversionCheck(!empty($result['status']), $result['message'] ?? 'Reverse conversion failed');
        conversionCheck(Property::listConversionParts($definitions[0]['value'], $format)['items'] === $originalLabels, 'Labels/order changed, including long UTF-8 labels');
        conversionCheck(Property::listConversionParts(conversionReload($values[1])->get('value'), $format)['items'] === ['Other', 'Texte deja present', $legacyText, '0'], 'Other holon labels or legacy text lost');
        foreach (array_merge($ids, $otherIds, [(int)$instance->getId()]) as $id) {
            conversionCheck(!(new Authority())->load($id, true), 'Converted authority not deleted');
        }
        conversionReload($rule);
        conversionCheck((int)$rule->get('IDauthority') === 0 && (int)$rule->get('IDholon') === (int)$owner->getId(), 'Rule was not detached into its own holon');
        conversionCheck($rule->get('expiration_date')->format('Y-m-d') === '2027-09-25', 'Rule expiration changed');
        conversionCheck((int)conversionReload($child)->get('IDauthority_parent') === 0, 'Child was not detached');
        if ($format === 7) {
            $parts = json_decode($definitions[0]['value'], true);
            conversionCheck($parts['before'] === '<p>Before</p>' && $parts['after'] === '<p>After</p>', 'Composite HTML lost');
        }

        // A failure after an earlier row was converted rolls back values, type and objects.
        $values[1]->set('value', $encode([['unexpected' => 'Invalid text item']]));
        conversionSave($values[1]);
        $definitions[0]['listItemType'] = 'authority';
        $definitions[0]['listConversionFrom'] = 'text';
        $result = Property::convertListDefinitions($owner, $definitions);
        conversionCheck(empty($result['status']), 'Invalid text item must fail without partial conversion');
        conversionCheck(conversionReload($property)->get('listitemtype') === 'text', 'Failed conversion changed type');
        conversionCheck(Property::listConversionParts(conversionReload($values[0])->get('value'), $format)['items'] === $originalLabels, 'Failed conversion changed an earlier value');

        $definitions[0]['listConversionFrom'] = 'authority';
        $result = Property::convertListDefinitions($owner, $definitions);
        conversionCheck(empty($result['status']), 'Stale conversion confirmation must fail');

        $values[1]->set('value', $encode(['Other']));
        conversionSave($values[1]);
        $definitions[0]['listConversionFrom'] = 'text';
        $result = Property::convertListDefinitions($owner, $definitions);
        conversionCheck(!empty($result['status']), $result['message'] ?? 'Second conversion of long labels failed');
        $roundTripIds = Property::listConversionParts($definitions[0]['value'], $format)['items'];
        conversionCheck($longAuthority->load($roundTripIds[4], true) && $longAuthority->get('label') === $longLabel, 'Authority to text to authority changed a long label');
    }
    echo "property_list_conversion_integration_test: OK (list and HTML list, both directions, long UTF-8 round trip, confirmation, shared values, rules, children, instances, rollback)\n";
} finally {
    $pdo->rollBack();
    DbObject::$preload = [];
}
