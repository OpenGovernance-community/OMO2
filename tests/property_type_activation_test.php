<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/user_permission_ui.php';
use dbObject\{DbObject, ArrayOrganization, Organization, OrganizationExport, Holon, Property, HolonProperty, HolonPermission, Permission, User, UserOrganization};

function activationCheck(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function activationSave($object): void {
    $result = $object->save();
    activationCheck(!empty($result['status']), json_encode($result));
}
class ActivationOrganization extends Organization {
    public function cloneStructure(Holon $root, int $user): array { return $this->initializeStructureFromTemplate($root, $user); }
    public function syncPermissions(int $holon, array $payload): bool { return $this->syncEditorPermissionAssignments($holon, $payload); }
}

$_SERVER['HTTP_HOST'] = 'omo.localtest.me';
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $fresh = new ActivationOrganization();
    $fresh->set('name', 'Type activation new organization');
    activationSave($fresh);
    activationCheck(array_column(Property::getTypeOptions($fresh->getLexicon()), 'id') === ['type1'], 'New organizations only enable type1');
    activationCheck($fresh->getLexicon()['type1']['label'] === "d\u{00e9}fini par le parent", 'New type1 label');
    $legacy = Organization::normalizeLexicon(['type2' => ['label' => 'Existing label']]);
    activationCheck(array_column(Property::getTypeOptions($legacy), 'id') === ['type1', 'type2', 'type3'], 'Legacy organizations retain exactly their three types');
    activationCheck($legacy['type2']['label'] === 'Existing label', 'Legacy labels are preserved');

    $organizations = new ArrayOrganization();
    $organizations->load(['limit' => 1]);
    $org = new ActivationOrganization();
    foreach ($organizations as $candidate) $org->load($candidate->getId());
    $root = $org->getStructuralRootHolon();
    activationCheck($root instanceof Holon, 'Existing fixture root required');
    $user = new User();
    $user->set('email', 'type-activation-' . bin2hex(random_bytes(8)) . '@example.invalid');
    $user->set('active', true);
    activationSave($user);
    $membership = new UserOrganization();
    $membership->set('IDuser', $user->getId());
    $membership->set('IDorganization', $org->getId());
    $membership->set('active', true);
    $membership->setOrganizationAdmin(true);
    activationSave($membership);
    $_SESSION['currentUser'] = (int)$user->getId();
    $_SESSION['currentOrganization'] = (int)$org->getId();
    $_SESSION['isAdminByOrganization'][(int)$org->getId()] = true;

    $lexicon = $org->getLexicon();
    foreach (Property::TYPES as $type) $lexicon[$type] = ['label' => 'Custom ' . $type, 'enabled' => true];
    $org->setLexicon($lexicon);
    activationSave($org);
    $template = new Holon();
    foreach (['name' => 'Activation test', 'templatename' => 'Activation test', 'IDholon_org' => $root->getId(), 'IDholon_parent' => $root->getId(), 'IDtypeholon' => 1, 'active' => true, 'visible' => true] as $key => $value) $template->set($key, $value);
    activationSave($template);
    $definitions = [];
    foreach (Property::TYPES as $type) $definitions[] = ['id' => 0, 'type' => $type, 'name' => 'Hidden content ' . $type, 'formatId' => 1, 'value' => 'Original ' . $type];
    $template->syncTemplateProperties($definitions, (int)$root->getId());
    $raw = $template->getTemplatePropertyDefinitions();
    activationCheck(count($raw) === 5, 'All five property types persist');
    $grant = new HolonPermission();
    foreach (['IDholon' => $template->getId(), 'IDpermission' => Permission::findByKey('CAN_EDIT_TYPE4_PROPERTIES')->getId(), 'range' => 'self', 'member_type' => 'member', 'is_extended' => true] as $key => $value) $grant->set($key, $value);
    activationSave($grant);

    $lexicon['type2']['enabled'] = false;
    $lexicon['type4']['enabled'] = false;
    $lexicon['type5']['enabled'] = false;
    $org->setLexicon($lexicon);
    activationSave($org);
    activationCheck(array_column(Property::getTypeOptions($template->getPropertyTypeLexicon(), $template), 'id') === ['type1', 'type3'], 'Options update immediately after disabling');
    $catalog = $org->getPermissionEditorCatalog();
    activationCheck(!in_array('CAN_EDIT_TYPE4_PROPERTIES', array_column($catalog, 'key'), true), 'Disabled type rights are hidden');
    activationCheck(!isset(commonUserPermissionBuildCatalogMap((int)$org->getId())['CAN_EDIT_TYPE4_PROPERTIES']), 'Member rights screen hides disabled types too');
    $editor = $org->getHolonTemplateEditorData();
    $findNode = function (array $nodes) use (&$findNode, $template): ?array {
        foreach ($nodes as $node) { if ((int)$node['id'] === (int)$template->getId()) return $node; if ($found = $findNode($node['children'] ?? [])) return $found; }
        return null;
    };
    $node = $findNode($editor['templates']);
    activationCheck($node !== null && array_column($node['properties'], 'type') === ['type1', 'type3'], 'Editor omits disabled properties');
    activationCheck(array_column($template->getPropertyEntries(), 'type') === ['type1', 'type3'], 'Detail screen omits disabled properties');
    activationCheck(array_column(array_values($template->getRepresentationData()), 'type') === ['type1', 'type3'], 'Structure rendering omits disabled properties');
    $_SESSION['currentUser'] = 0;
    activationCheck(!Organization::getLexiconForOrganizationId($org->getId())['type4']['enabled'], 'Public views respect disabled types');
    $_SESSION['currentUser'] = (int)$user->getId();
    $payload = $node;
    $payload['properties'] = $node['properties'];
    $payload['permissions'] = [];
    $payload['editablePermissionKeys'] = array_column($catalog, 'key');
    $save = $org->saveHolonTemplateDefinition($payload, (int)$user->getId(), (int)$root->getId());
    activationCheck(!empty($save['status']), 'Template editor saves with disabled properties: ' . json_encode($save));
    $forged = $payload;
    $forged['properties'][] = ['id' => 0, 'type' => 'type5', 'name' => 'Unavailable', 'formatId' => 1, 'value' => 'Hidden'];
    activationCheck(empty($org->saveHolonTemplateDefinition($forged, (int)$user->getId(), (int)$root->getId())['status']), 'Disabled types cannot be created through a forged request');
    $visible = Property::filterEnabledDefinitions($raw, $lexicon);
    $visible[0]['value'] = 'Updated visible value';
    $template->syncTemplateProperties($visible, (int)$root->getId(), true);
    $template->syncEditorPropertyValues([], $visible, true);
    $after = array_column($template->getTemplatePropertyDefinitions(), null, 'type');
    foreach (['type2', 'type4', 'type5'] as $type) activationCheck($after[$type]['value'] === 'Original ' . $type, 'Hidden values survive saves: ' . $type);
    activationCheck($org->syncPermissions((int)$template->getId(), ['permissions' => [], 'editablePermissionKeys' => array_column($catalog, 'key')]), 'Visible permission save succeeds');
    $grant->load((int)$grant->getId(), true);
    activationCheck($grant->get('is_extended') && $grant->get('IDpermission') === Permission::findByKey('CAN_EDIT_TYPE4_PROPERTIES')->getId(), 'Hidden grant is preserved');

    $compact = $org->getStructureCompactExportData($root);
    activationCheck($compact['propertyTypes'] === $org->getPropertyTypeSettings(), 'Compact export contains labels and switches');
    activationCheck(in_array('type5', array_column($compact['propertyDefinitions'], 'type'), true), 'Exports retain hidden property definitions');
    $export = OrganizationExport::build($org, ['structure' => true]);
    activationCheck($export['propertyTypes'] === $compact['propertyTypes'], 'Organization duplication export includes type settings');
    $result = $fresh->cloneStructure($root, (int)$user->getId());
    activationCheck(!empty($result['status']), 'Structure initialization succeeds: ' . json_encode($result));
    activationCheck($fresh->getPropertyTypeSettings() === $org->getPropertyTypeSettings(), 'Initialization copies all labels and switches');

    $lexicon['type4']['enabled'] = true;
    $org->setLexicon($lexicon);
    activationSave($org);
    activationCheck(in_array('type4', array_column(Property::getTypeOptions($template->getPropertyTypeLexicon()), 'id'), true), 'Reactivation restores type');
    activationCheck(in_array('CAN_EDIT_TYPE4_PROPERTIES', array_column($org->getPermissionEditorCatalog(), 'key'), true), 'Reactivation restores permission UI');
    activationCheck(in_array('type4', array_column($template->getPropertyEntries(), 'type'), true), 'Reactivation restores content');
    foreach (Property::TYPES as $type) $lexicon[$type]['enabled'] = false;
    $org->setLexicon($lexicon);
    activationSave($org);
    activationCheck(Property::getTypeOptions($template->getPropertyTypeLexicon(), $template) === [] && !Property::canCreateAnyType($template), 'All types can be disabled without retaining a phantom choice');
    activationCheck($template->getPropertyEntries() === [], 'All disabled properties disappear');
    activationCheck(!in_array('properties', array_column($org->getPermissionEditorCatalog(), 'group'), true), 'Empty property permission group disappears');
    echo "property_type_activation_test: OK (defaults, legacy, 5 types, visibility, preservation, reactivation, model initialization, export, rollback)\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
