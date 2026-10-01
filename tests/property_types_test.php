<?php
declare(strict_types=1);

// Authorization matrix, independent from the application database.
require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'dbObject\\')) {
        $path = dirname(__DIR__) . '/class/dbobject/' . strtolower(substr($class, 9)) . '.class.php';
        if (is_file($path)) require_once $path;
    }
});

use dbObject\{Holon, Organization, Property, Permission};

final class PropertyTypeTestHolon extends Holon {
    public array $grants = [];
    public function getPropertyTypeLexicon(): array { return (new PropertyTypeTestOrganization())->getLexicon(); }
    public function isAllowed($key, $cache = true, $userId = 0) { return in_array($key, $this->grants, true); }
    public function getId() { return 0; }
}
final class PropertyTypeTestOrganization extends Organization {
    public function getLexicon(): array {
        $lexicon = parent::getLexicon();
        foreach (Property::TYPES as $type) $lexicon[$type]['enabled'] = true;
        return $lexicon;
    }
    public function check(Holon $holon, array $before, array $after, array $inherited = []): bool {
        return !empty($this->canApplyPropertyDefinitionChanges($holon, $this->getPropertyDefinitionPermissionOperations($before, $after, $inherited), 'HOLON')['status']);
    }
    public function values(Holon $holon, array $values, array $definitions): bool {
        return !empty($this->canEditSubmittedTemplatePropertyValues($holon, $holon, $values, $definitions)['status']);
    }
}
function propertyTypeCheck(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$org = new PropertyTypeTestOrganization();
$holon = new PropertyTypeTestHolon();
$definitions = [];
foreach (Property::TYPES as $i => $type) {
    $definitions[] = ['id' => $i + 1, 'type' => $type, 'name' => 'Property ' . $type, 'formatId' => 1, 'value' => ''];
    foreach (['CREATE', 'EDIT', 'DELETE'] as $operation) {
        propertyTypeCheck(isset(Permission::getBuiltInCatalog()[Property::permissionKey($operation, $type)]), 'Permission missing');
        propertyTypeCheck(Permission::requiresExplicitAssignment(Property::permissionKey($operation, $type)) === ($operation !== 'EDIT'), 'Only property creation and deletion require explicit assignment');
        propertyTypeCheck(Permission::getUnconfiguredFallbackPermissionKey(Property::permissionKey($operation, $type)) === ($operation === 'EDIT' ? 'CAN_EDIT_HOLON' : null), 'Only value editing falls back to holon editing');
        propertyTypeCheck(Permission::getUnconfiguredFallbackPermissionKey(Property::permissionKey($operation, $type), true) === ($operation === 'EDIT' ? 'CAN_ADD_HOLON' : null), 'Creation uses holon creation only for values');
    }
}
propertyTypeCheck(!Property::canCreateAnyType($holon), 'No grants must disable creation');
propertyTypeCheck(!Permission::requiresExplicitAssignment('CAN_EDIT_HOLON'), 'Existing default policy must be preserved for unrelated permissions');
propertyTypeCheck($org->check($holon, $definitions, $definitions), 'Unchanged properties must save without edit rights');
foreach ($definitions as $definition) {
    $type = $definition['type'];
    $new = array_replace($definition, ['id' => 0, 'value' => 'Initial content']);
    $edited = array_replace($definition, ['value' => 'Changed']);
    $holon->grants = [Property::permissionKey('CREATE', $type)];
    propertyTypeCheck(Property::canCreateAnyType($holon), 'A single creatable type must enable creation');
    propertyTypeCheck($org->check($holon, [], [$new]), 'Creation must permit initial content');
    propertyTypeCheck(!$org->check($holon, [$definition], [$edited]), 'Create must not grant edit');
    propertyTypeCheck(!$org->check($holon, [$definition], []), 'Create must not grant delete');
    $structureChanges = [
        ['name' => 'Renamed'], ['formatId' => 5], ['shortname' => 'alias'],
        ['listItemType' => 'number'], ['listHolonTypeIds' => [1, 2]],
        ['mandatory' => true], ['locked' => true],
    ];
    foreach ($structureChanges as $change) {
        propertyTypeCheck($org->check($holon, [$definition], [array_replace($definition, $change)]), 'Create must allow structure edits');
        propertyTypeCheck(!$org->check($holon, [$definition], [array_replace($edited, $change)]), 'Create alone must not allow simultaneous value edits');
    }
    $holon->grants = [Property::permissionKey('EDIT', $type)];
    foreach ($structureChanges as $change) {
        propertyTypeCheck(!$org->check($holon, [$definition], [array_replace($definition, $change)]), 'Edit must not allow structure edits');
    }
    propertyTypeCheck($org->check($holon, [$definition], [$edited]), 'Correct edit type must work');
    propertyTypeCheck($org->values($holon, [$definition['id'] => 'Local content'], [$definition]), 'Inherited value must use type edit permission');
    $locked = array_replace($definition, ['effectiveLocked' => true]);
    propertyTypeCheck(!$org->values($holon, [$definition['id'] => 'Local content'], [$locked]), 'Inherited lock must still apply');
    $holon->grants = [Property::permissionKey('DELETE', $type)];
    $retained = array_values(array_filter($definitions, static fn ($other) => $other['id'] !== $definition['id']));
    propertyTypeCheck($org->check($holon, $definitions, $retained), 'Removing one type must not need edit permission on retained types');
    foreach ($definitions as $other) {
        if ($other['type'] === $type) continue;
        propertyTypeCheck(!$org->check($holon, [$other], []), 'Delete must remain scoped to its type');
    }
}
$holon->grants = ['CAN_EDIT_HOLON', 'CAN_EDIT_HOLON_PROPERTIES', 'CAN_EDIT_TEMPLATE_PROPERTIES'];
propertyTypeCheck(!$org->values($holon, [1 => 'Forged'], [$definitions[0]]), 'Legacy or generic edit must not bypass type permission');
$changedType = array_replace($definitions[0], ['type' => 'type3']);
$holon->grants = ['CAN_CREATE_TYPE3_PROPERTIES'];
propertyTypeCheck(!$org->check($holon, [$definitions[0]], [$changedType]), 'Reclassification needs source create permission');
$holon->grants[] = 'CAN_CREATE_TYPE1_PROPERTIES';
propertyTypeCheck($org->check($holon, [$definitions[0]], [$changedType]), 'Reclassification with both permissions must work');
$holon->grants = ['CAN_EDIT_TYPE1_PROPERTIES'];
propertyTypeCheck(!$org->check($holon, [$definitions[0]], [$changedType]), 'Reclassification needs target create permission');
$holon->grants = ['CAN_CREATE_TYPE3_PROPERTIES'];
propertyTypeCheck(!$org->check($holon, [], [$changedType]), 'Foreign property IDs must not be modified as new properties');
propertyTypeCheck(!$org->check($holon, [], [['id' => 0, 'type' => 'type99', 'name' => 'Invalid']]), 'Unknown types must fail');
$inherited = array_replace($definitions[0], ['isInherited' => true, 'isLocal' => false]);
$holon->grants = [];
propertyTypeCheck($org->check($holon, [], [$inherited], [$definitions[0]]), 'Simply inheriting a property must not require creation');
$holon->grants = ['CAN_EDIT_TYPE1_PROPERTIES', 'CAN_CREATE_TYPE3_PROPERTIES'];
propertyTypeCheck(!$org->check($holon, [$inherited], [array_replace($inherited, ['type' => 'type3'])]), 'Inherited types must stay defined by their source');
$holon->grants = ['CAN_EDIT_TYPE1_PROPERTIES'];
propertyTypeCheck($org->check($holon, [$inherited], [array_replace($inherited, ['value' => 'Local', 'isLocal' => true])]), 'A local value override must not require create');
$holon->grants = ['CAN_CREATE_TYPE1_PROPERTIES'];
$withContent = array_replace($definitions[0], ['value' => '<b>Existing content</b>', 'shortname' => 'custom']);
$formatOnly = array_replace($withContent, ['formatId' => 5]);
unset($formatOnly['shortname']);
propertyTypeCheck($org->check($holon, [$withContent], [$formatOnly]), 'Format changes and omitted internal names must not require value edit');
$holon->grants = ['CAN_EDIT_TYPE1_PROPERTIES', 'CAN_CREATE_TYPE3_PROPERTIES'];
$lexicon = Organization::normalizeLexicon(['type2' => ['label' => 'Strategie']]);
$options = Property::getTypeOptions($lexicon, $holon);
propertyTypeCheck($options[1]['name'] === 'Strategie' && !$options[1]['canCreate'], 'Lexicon names and permission choices must agree');
propertyTypeCheck($options[2]['canCreate'], 'Only explicitly creatable types must be offered');
echo "property_types_test: OK\n";
