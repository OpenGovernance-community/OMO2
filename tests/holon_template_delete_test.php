<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{DbObject, Organization, Holon, User, UserOrganization, Application, OrganizationApplication};

$pdo = DbObject::getPdo();
$before = $_SESSION;
$pdo->beginTransaction();
try {
    $nonce = bin2hex(random_bytes(6));
    $org = mcpFixture(Organization::class, ['name' => 'Template deletion test', 'shortname' => 'template-delete-' . $nonce,
        'interface_level' => Organization::INTERFACE_LEVEL_EXPERT]);
    $oid = (int)$org->getId();
    $user = mcpFixture(User::class, ['email' => 'template-delete-' . $nonce . '@example.invalid', 'active' => 1]);
    mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $user->getId(), 'active' => 1, 'parameters' => ['isAdmin' => true]]);
    $app = new Application();
    mcpCheck((bool)$app->load(['hash', 'structure']), 'Structure app required');
    mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $_SESSION['currentUser'] = $user->getId();
    $_SESSION['currentOrganization'] = $oid;
    mcpCheck(commonSetCurrentUserAdminMode(true, $oid), 'Admin mode required');
    $root = mcpFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $oid, 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $rid = (int)$root->getId();
    $root->set('IDholon_org', $rid);
    $root->save();
    $makeHolon = static function (string $name, array $fields = []) use ($rid): Holon {
        return mcpFixture(Holon::class, array_replace(['name' => $name, 'IDholon_org' => $rid,
            'IDholon_parent' => $rid, 'IDtypeholon' => 2, 'active' => 1, 'visible' => 1], $fields));
    };
    $base = $makeHolon('Circle template', ['visible' => 0]);
    $tid = (int)$base->getId();
    $child = $makeHolon('Child template', ['IDholon_parent' => $tid, 'visible' => 0]);
    $childId = (int)$child->getId();
    $derived = $makeHolon('Derived template', ['IDholon_template' => $tid, 'visible' => 0]);
    $direct = $makeHolon('Direct instance', ['IDholon_template' => $tid]);
    $nested = $makeHolon('Child instance', ['IDholon_template' => $childId]);
    $indirect = $makeHolon('Indirect instance', ['IDholon_template' => $derived->getId()]);
    $archive = $makeHolon('Archived instance', ['IDholon_template' => $childId, 'active' => 0, 'archived_at' => new DateTimeImmutable()]);
    $unrelated = $makeHolon('Unrelated template', ['visible' => 0]);
    $delete = static fn(?array $confirmation = null, bool $preview = false): array =>
        $org->deleteHolonTemplateDefinition($tid, $user->getId(), $rid, 'descendants', $confirmation, $preview);
    $preview = $delete(null, true);
    mcpCheck(!empty($preview['status']), 'Preview must succeed ' . json_encode($preview));
    mcpCheck($preview['impact']['count'] === 5, 'Count direct, child, derived, indirect and archived instances once');
    mcpCheck($preview['impact']['deletedCount'] === 2, 'Count the entire deleted subtree separately');
    mcpCheck($base->load($tid, true) && $child->load($childId, true), 'Preview must preserve templates');
    foreach ([null, ['count' => '', 'signature' => $preview['impact']['signature']],
        ['count' => '4', 'signature' => $preview['impact']['signature']],
        ['count' => '05', 'signature' => $preview['impact']['signature']],
        ['count' => ['5'], 'signature' => $preview['impact']['signature']]] as $invalid) {
        $result = $delete($invalid);
        mcpCheck(empty($result['status']) && $result['reason'] === 'confirmation_required', 'Missing or incorrect count must block deletion');
        mcpCheck($base->load($tid, true), 'Rejected deletion must preserve the template');
    }
    $result = $delete(['count' => '5']);
    mcpCheck(empty($result['status']) && $result['reason'] === 'impact_changed', 'A count alone cannot bypass the preview');

    // Replace one affected instance while preserving the total count.
    $direct->set('IDholon_template', $unrelated->getId());
    $direct->save();
    $replacement = $makeHolon('Replacement instance', ['IDholon_template' => $tid]);
    $result = $delete(['count' => '5', 'signature' => $preview['impact']['signature']]);
    mcpCheck(empty($result['status']) && $result['reason'] === 'impact_changed', 'A stale preview with the same count must block deletion');
    mcpCheck($base->load($tid, true) && $child->load($childId, true), 'Stale confirmation must preserve the subtree');
    $preview = $delete(null, true);
    $result = $delete(['count' => '5', 'signature' => $preview['impact']['signature']]);
    mcpCheck(!empty($result['status']), 'Exact current confirmation must delete ' . json_encode($result));
    mcpCheck(!$base->load($tid, true) && !$child->load($childId, true), 'Confirmed deletion removes templates');
    foreach ([$derived, $replacement, $nested, $archive] as $instance) {
        mcpCheck((bool)$instance->load($instance->getId(), true), 'Instances must survive');
        mcpCheck((int)$instance->get('IDholon_template') === 0, 'Deleted template references must be cleared');
    }
    mcpCheck($indirect->load($indirect->getId(), true) && (int)$indirect->get('IDholon_template') === (int)$derived->getId(), 'Indirect instances retain their derived template');
    mcpCheck($direct->load($direct->getId(), true) && (int)$direct->get('IDholon_template') === (int)$unrelated->getId(), 'Unrelated inheritance must survive');

    $empty = $makeHolon('Unused template', ['visible' => 0]);
    $emptyId = (int)$empty->getId();
    $preview = $org->deleteHolonTemplateDefinition($emptyId, $user->getId(), $rid, 'descendants', null, true);
    mcpCheck(!empty($preview['status']) && $preview['impact']['count'] === 0, 'Unused templates have zero affected holons');
    $result = $org->deleteHolonTemplateDefinition($emptyId, $user->getId(), $rid, 'descendants', ['count' => '', 'signature' => $preview['impact']['signature']]);
    mcpCheck(empty($result['status']) && $empty->load($emptyId, true), 'Empty input must never confirm zero');
    $result = $org->deleteHolonTemplateDefinition($emptyId, $user->getId(), $rid, 'descendants', ['count' => '0', 'signature' => $preview['impact']['signature']]);
    mcpCheck(!empty($result['status']) && !$empty->load($emptyId, true), 'Zero must be explicitly typed');
    echo "holon_template_delete_test: OK\n";
} finally {
    $pdo->rollBack();
    $_SESSION = $before;
    commonClearCurrentUserPermissionCache();
}
