<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/mcp_test_helpers.php';

use dbObject\{DbObject, Organization, Holon, Authority, Rule, Property, PropertyFormat, HolonProperty,
    User, UserOrganization, Application, OrganizationApplication, DeferredProposal, DecisionGovernanceAction};

class FailingArchiveHolon extends Holon {
    public bool $failArchive = false;
    public function save() {
        return $this->failArchive && !$this->get('active') ? ['status' => false] : parent::save();
    }
}

$pdo = DbObject::getPdo();
$beforeSession = $_SESSION;
$pdo->beginTransaction();
try {
    $org = mcpFixture(Organization::class, ['name' => 'Removal test', 'shortname' => 'removal-' . bin2hex(random_bytes(5))]);
    $oid = (int)$org->getId();
    $app = new Application(); mcpCheck((bool)$app->load(['hash', 'structure']), 'Structure app');
    mcpFixture(OrganizationApplication::class, ['IDorganization' => $oid, 'IDapplication' => $app->getId(), 'active' => 1]);
    $user = mcpFixture(User::class, ['email' => 'removal-' . bin2hex(random_bytes(5)) . '@example.invalid', 'active' => 1]);
    mcpFixture(UserOrganization::class, ['IDorganization' => $oid, 'IDuser' => $user->getId(), 'active' => 1, 'parameters' => ['isAdmin' => true]]);
    $_SESSION['currentUser'] = $user->getId(); $_SESSION['currentOrganization'] = $oid;
    mcpCheck(commonSetCurrentUserAdminMode(true, $oid), 'Admin fixture');
    $root = mcpFixture(Holon::class, ['name' => 'Root', 'IDorganization' => $oid, 'IDtypeholon' => 4, 'active' => 1, 'visible' => 1]);
    $root->set('IDholon_org', $root->getId()); $root->save();
    $makeHolon = static fn(string $name, Holon $parent): Holon => mcpFixture(Holon::class,
        ['name' => $name, 'IDholon_org' => $root->getId(), 'IDholon_parent' => $parent->getId(), 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    $makeAuthority = static fn(Holon $owner, string $label, ?Authority $parent = null): Authority => mcpFixture(Authority::class,
        ['IDholon' => $owner->getId(), 'label' => $label, 'description' => 'Preserved description', 'IDauthority_parent' => $parent ? $parent->getId() : null, 'is_local' => !$parent]);
    $makeRule = static fn(Authority $owner): Rule => mcpFixture(Rule::class,
        ['IDauthority' => $owner->getId(), 'title' => 'Rule ' . $owner->getId(), 'description' => '<p>Preserved rule</p>', 'scope' => Rule::SCOPE_LOCAL,
            'review_date' => '2027-01-01', 'expiration_date' => '2028-01-01']);
    $list = mcpFixture(Property::class, ['name' => 'Authority list', 'shortname' => 'removal_list', 'IDholon_organization' => $root->getId(),
        'IDpropertyformat' => PropertyFormat::FORMAT_LIST, 'listitemtype' => Property::LIST_ITEM_AUTHORITY]);
    foreach ([true, false] as $archive) {
        foreach (Authority::holonRemovalChoices() as $choice) {
            $owner = $makeHolon('Source ' . $choice, $root);
            $child = $makeHolon('Child', $owner);
            $externalOwner = $makeHolon('Authority owner outside structural parent', $root);
            $parent = $makeAuthority($externalOwner, 'Parent authority');
            $authority = $makeAuthority($owner, 'Authority', $parent);
            $nested = $makeAuthority($child, 'Nested authority', $authority);
            $local = $makeAuthority($owner, 'Local authority');
            $outsideChild = $makeAuthority($externalOwner, 'Outside child', $authority);
            $rule = $makeRule($authority); $nestedRule = $makeRule($nested); $localRule = $makeRule($local);
            $property = mcpFixture(HolonProperty::class, ['IDholon' => $owner->getId(), 'IDproperty' => $list->getId(),
                'value' => json_encode([$authority->getId(), $local->getId()]), 'active' => 1]);
            $missing = $archive ? $owner->changeArchiveState($oid) : $org->deleteHolonDefinition($owner->getId(), $user->getId());
            mcpCheck(empty($missing['status']), 'A missing choice must never mutate');
            mcpCheck($authority->load($authority->getId(), true) && (int)$authority->get('IDholon') === (int)$owner->getId(), 'Missing choice preserves authorities');
            $result = $archive ? $owner->changeArchiveState($oid, false, $choice)
                : $org->deleteHolonDefinition($owner->getId(), $user->getId(), false, $choice);
            mcpCheck(!empty($result['status']), 'Removal failed ' . json_encode([$archive, $choice, $result]));
            foreach ([$authority, $nested] as $item) {
                $exists = (bool)$item->load($item->getId(), true);
                mcpCheck($exists === ($choice === 'reassign'), 'Authority survival follows selected option');
                if ($exists) mcpCheck((int)$item->get('IDholon') === (int)$externalOwner->getId(), 'Use authority ancestry, not structural parent');
            }
            foreach ([$rule, $nestedRule] as $item) {
                $exists = (bool)$item->load($item->getId(), true);
                mcpCheck($exists === ($choice !== 'delete_all'), 'Rule survival follows selected option');
                if ($exists && $choice === 'delete_keep_rules') mcpCheck((int)$item->get('IDauthority') === (int)$parent->getId(), 'Rules go to nearest surviving outside authority');
            }
            mcpCheck($outsideChild->load($outsideChild->getId(), true), 'Unrelated owner keeps its authority');
            mcpCheck((int)$outsideChild->get('IDauthority_parent') === (int)($choice === 'reassign' ? $authority->getId() : $parent->getId()), 'External child has no dangling parent');
            $localSurvives = $archive && $choice !== 'delete_all';
            mcpCheck((bool)$local->load($local->getId(), true) === $localSurvives, 'Local authority has no artificial destination');
            mcpCheck((bool)$localRule->load($localRule->getId(), true) === $localSurvives, 'Local rule follows local authority');
            if ($archive) {
                mcpCheck($property->load($property->getId(), true), 'Archived property retained');
                $ids = Property::listConversionParts($property->get('value'), PropertyFormat::FORMAT_LIST)['items'];
                mcpCheck(!in_array($authority->getId(), $ids), 'No obsolete authority reference in source');
                mcpCheck(!empty($owner->changeArchiveState($oid, true)['status']), 'Restore holon');
                mcpCheck((bool)$authority->load($authority->getId(), true) === ($choice === 'reassign'), 'Restore never recreates deleted authorities');
                if ($choice === 'reassign') mcpCheck((int)$authority->get('IDholon') === (int)$externalOwner->getId(), 'Restore never pulls back moved authorities');
            }
        }
    }
    foreach (Authority::holonRemovalChoices() as $choice) {
        $owner = $makeHolon('Complete delegation source', $root);
        $parent = $makeAuthority($root, 'Source shell');
        $copy = $makeAuthority($owner, 'Delegated copy', $parent);
        $rule = $makeRule($copy);
        $parent->set('is_shell', true); $parent->save();
        $result = $owner->changeArchiveState($oid, false, $choice);
        mcpCheck(!empty($result['status']), 'Complete delegation removal ' . $choice);
        mcpCheck($parent->load($parent->getId(), true) && !$parent->get('is_shell'), 'Source shell reactivated');
        mcpCheck(!(new Authority())->load($copy->getId(), true), 'No duplicate of source shell');
        $ruleExists = (bool)$rule->load($rule->getId(), true);
        mcpCheck($ruleExists === ($choice !== 'delete_all'), 'Explicit rule disposition honored for a complete delegation');
        if ($ruleExists) mcpCheck((int)$rule->get('IDauthority') === (int)$parent->getId(), 'Rules restored to source shell');
    }
    $failing = mcpFixture(FailingArchiveHolon::class, ['name' => 'Rollback', 'IDholon_org' => $root->getId(),
        'IDholon_parent' => $root->getId(), 'IDtypeholon' => 1, 'active' => 1, 'visible' => 1]);
    $authority = $makeAuthority($failing, 'Rollback authority'); $rule = $makeRule($authority);
    $failing->failArchive = true;
    mcpCheck(empty($failing->changeArchiveState($oid, false, 'delete_all')['status']), 'Forced holon failure');
    mcpCheck($authority->load($authority->getId(), true) && $rule->load($rule->getId(), true), 'Authority and rule deletions roll back with holon failure');
    mcpCheck($failing->load($failing->getId(), true) && $failing->get('active'), 'Holon remains active');

    // Deferred PV/decision execution uses the stored choice, without a default for older proposals.
    foreach (Authority::holonRemovalChoices() as $choice) {
        $owner = $makeHolon('Deferred source', $root);
        $parent = $makeAuthority($root, 'Deferred parent');
        $authority = $makeAuthority($owner, 'Deferred authority', $parent);
        $rule = $makeRule($authority);
        $proposal = mcpFixture(DeferredProposal::class, ['IDorganization' => $oid, 'IDholon' => $root->getId(),
            'IDuser_author' => $user->getId(), 'target_type' => 'holon', 'operation' => 'delete', 'target_id' => $owner->getId(),
            'before_state' => DecisionGovernanceAction::captureHolonState($owner), 'after_state' => [], 'status' => 'pending']);
        mcpCheck(empty(DecisionGovernanceAction::applyDeferredProposal($proposal, (int)$root->getId())['status']), 'Legacy proposal needs an explicit choice');
        $proposal->set('after_state', ['authority_choice' => $choice]); $proposal->save(); $proposal->load($proposal->getId(), true);
        mcpCheck($authority->load($authority->getId(), true), 'Saving a proposal never applies it');
        mcpCheck(!empty(DecisionGovernanceAction::applyDeferredProposal($proposal, (int)$root->getId())['status']), 'Deferred choice applies');
        mcpCheck((bool)$rule->load($rule->getId(), true) === ($choice !== 'delete_all'), 'Deferred rule handling');
    }
    echo "holon_authority_removal_test: OK\n";
} finally {
    $pdo->rollBack();
    $_SESSION = $beforeSession;
}
