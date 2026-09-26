<?php
declare(strict_types=1);

// Local MariaDB integration test; all fixtures are rolled back.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';

use dbObject\{DbObject, ArrayOrganization, ArrayRule, Holon, Authority, Rule, Property, HolonProperty, DecisionGovernanceAction};

function scopeCheck($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function scopeSave($object): void {
    $result = $object->save();
    scopeCheck(!empty($result['status']), $result['text'] ?? 'Save failed');
}

$organizations = new ArrayOrganization();
$organizations->load(['limit' => 1]);
$organization = $organizations[0];
$root = $organization->getStructuralRootHolon();
scopeCheck($root instanceof Holon, 'A local organization with a structure is required');
$_SESSION['currentUser'] = 0;
$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $makeHolon = static function (string $name, Holon $parent, int $type = 2) use ($root): Holon {
        $holon = new Holon();
        foreach (['name' => $name, 'IDholon_org' => $root->getId(), 'IDholon_parent' => $parent->getId(), 'IDtypeholon' => $type] as $field => $value) $holon->set($field, $value);
        scopeSave($holon);
        return $holon;
    };
    $circle = $makeHolon('Scope test circle', $root);
    $role = $makeHolon('Scope test role', $circle, 1);
    $sibling = $makeHolon('Scope test sibling', $circle, 1);
    $childCircle = $makeHolon('Scope test child circle', $circle);
    $grandchild = $makeHolon('Scope test grandchild', $childCircle, 1);
    $outside = $makeHolon('Scope test outside', $root, 1);
    $base = ['title' => 'Scope test', 'description' => '<p>Scope rule</p>', 'review_date' => '2026-10-01', 'expiration_date' => '2027-10-01'];
    $makeRule = static function (Holon $holon, string $scope) use ($base): Rule {
        $rule = new Rule();
        foreach ($base + ['IDholon' => $holon->getId(), 'scope' => $scope] as $field => $value) $rule->set($field, $value);
        return $rule;
    };
    $circleRules = [];
    foreach (Rule::scopes() as $scope) {
        $rule = $makeRule($circle, $scope);
        scopeSave($rule);
        $circleRules[$scope] = (int)$rule->getId();
        $rule->load($rule->getId(), true);
        scopeCheck($rule->get('scope') === $scope, 'Scope must survive saving without authorities');
        scopeCheck($rule->appliesToHolon($circle), 'Origin must remain in scope');
        scopeCheck($rule->appliesToHolon($role) === ($scope !== 'local'), 'Direct child coverage');
        scopeCheck($rule->appliesToHolon($grandchild) === in_array($scope, ['descendants', 'global'], true), 'Grandchild coverage');
        scopeCheck($rule->appliesToHolon($outside) === ($scope === 'global'), 'Sibling branch coverage');
        $rules = new ArrayRule();
        $rules->loadForPolicyContexts($organization->getId(), [$role->getId(), $grandchild->getId()]);
        $count = 0;
        foreach ($rules as $item) if ((int)$item->getId() === (int)$rule->getId()) $count++;
        scopeCheck($count === ($scope === 'local' ? 0 : 1), 'Policy list must include each applicable rule once');
    }
    $roleRule = $makeRule($role, 'circle');
    scopeSave($roleRule);
    $outsideRule = $makeRule($outside, 'local');
    scopeSave($outsideRule);
    $viewIds = static function (Holon $holon, string $view) use ($organization): array {
        $rules = new ArrayRule();
        $rules->loadForPolicyContexts($organization->getId(), [$holon->getId()], false, $view);
        $ids = [];
        foreach ($rules as $rule) {
            scopeCheck($rule->getOrganizationId() === (int)$organization->getId(), 'Views must stay inside the organization');
            $ids[] = (int)$rule->getId();
        }
        return $ids;
    };
    $localIds = $viewIds($circle, 'local');
    foreach ($circleRules as $id) scopeCheck(in_array($id, $localIds, true), 'Local includes locally defined rules whatever their scope');
    scopeCheck(!in_array((int)$roleRule->getId(), $localIds, true), 'Local excludes child rules even when applicable');
    scopeCheck(!in_array((int)$outsideRule->getId(), $localIds, true), 'Local excludes other branches');
    $globalIds = $viewIds($role, 'global');
    foreach (array_merge(array_values($circleRules), [(int)$roleRule->getId(), (int)$outsideRule->getId()]) as $id) {
        scopeCheck(in_array($id, $globalIds, true), 'Global includes all scopes from all branches');
    }
    scopeCheck($viewIds($role, 'children') === $viewIds($role, 'contextual'), 'Retired saved filters fall back to contextual');
    $childCircleRule = $makeRule($childCircle, 'circle');
    scopeSave($childCircleRule);
    $deepLocalRule = $makeRule($grandchild, 'local');
    scopeSave($deepLocalRule);
    $contextualIds = $viewIds($role, 'contextual');
    foreach (['circle', 'descendants', 'global'] as $scope) {
        scopeCheck(in_array($circleRules[$scope], $contextualIds, true), 'Contextual includes applicable ancestor rules');
    }
    scopeCheck(!in_array($circleRules['local'], $contextualIds, true), 'Contextual excludes ancestor local rules');
    scopeCheck(in_array((int)$roleRule->getId(), $contextualIds, true), 'Contextual includes rules defined here');
    scopeCheck(in_array((int)$childCircleRule->getId(), $contextualIds, true), 'Contextual includes circle rules of direct holons in the nearest circle');
    scopeCheck(!in_array((int)$outsideRule->getId(), $contextualIds, true), 'Contextual excludes local rules in another branch');
    scopeCheck(!in_array((int)$deepLocalRule->getId(), $contextualIds, true), 'Contextual excludes local rules below');
    scopeCheck(in_array((int)$childCircleRule->getId(), $viewIds($circle, 'contextual'), true), 'A selected circle is its own nearest circle');
    scopeCheck(in_array((int)$roleRule->getId(), $viewIds($childCircle, 'contextual'), true), 'Child circles retain the rules covering them from their parent circle');
    foreach ([$circle, $role, $sibling, $childCircle] as $target) scopeCheck($roleRule->appliesToHolon($target), 'Role circle scope includes its parent and siblings');
    scopeCheck(!$roleRule->appliesToHolon($grandchild) && !$roleRule->appliesToHolon($outside), 'Role circle scope stops at direct children');
    $proposal = DecisionGovernanceAction::validateRuleCreate($base + ['scope' => 'global'], $role->getId());
    scopeCheck(!empty($proposal['status']) && $proposal['state']['scope'] === 'global', 'Deferred creation retains scope without authorities');

    // An empty authority property already enables the authority constraint.
    foreach ([$circle, $role] as $owner) {
        $property = new Property();
        foreach (['name' => 'Scope test authority', 'shortname' => 'scope_test', 'IDholon_organization' => $root->getId(), 'IDpropertyformat' => 2, 'listitemtype' => 'authority'] as $field => $value) $property->set($field, $value);
        scopeSave($property);
        $value = new HolonProperty();
        foreach (['IDholon' => $owner->getId(), 'IDproperty' => $property->getId(), 'value' => '[]', 'active' => true] as $field => $entry) $value->set($field, $entry);
        scopeSave($value);
        $owner->load($owner->getId(), true);
        scopeCheck(Rule::getScopeContext($owner)['usesAuthorities'], 'Empty configured authority list is active');
        foreach (Rule::scopes() as $scope) {
            $allowed = $scope === 'local' || ($scope === 'circle' && (int)$owner->get('IDtypeholon') !== 1);
            $rule = $makeRule($owner, $scope);
            scopeCheck(!empty($rule->save()['status']) === $allowed, 'Save must enforce authority requirement: ' . $scope);
            $validation = DecisionGovernanceAction::validateRuleCreate($base + ['scope' => $scope], $owner->getId());
            scopeCheck(!empty($validation['status']) === $allowed, 'Deferred create must enforce same authority requirement');
        }
    }
    $authority = new Authority();
    foreach (['IDholon' => $role->getId(), 'label' => 'Scope test domain', 'is_local' => true] as $field => $value) $authority->set($field, $value);
    scopeSave($authority);
    foreach (Rule::scopes() as $scope) {
        $rule = $makeRule($role, $scope);
        $rule->set('IDholon', null);
        $rule->set('IDauthority', $authority->getId());
        scopeSave($rule);
        scopeCheck(!empty(DecisionGovernanceAction::validateRuleUpdate($rule, ['scope' => $scope], $role->getId())['status']), 'Deferred update accepts own authority');
        scopeCheck(in_array((int)$rule->getId(), $viewIds($role, 'local'), true), 'Local includes rules attached through the holon authority');
        scopeCheck(empty(DecisionGovernanceAction::validateRuleUpdate($rule, ['scope' => 'global', 'IDauthority' => null], $role->getId())['status']), 'Removing required authority must fail');
    }
    scopeCheck(empty(Rule::validateScopeAttachment('global', $circle, $authority->getId())['status']), 'A foreign context authority must fail');
    scopeCheck(empty($makeRule($outside, 'invalid')->save()['status']), 'Invalid scope must fail');
    scopeCheck(empty(DecisionGovernanceAction::validateRuleCreate($base + ['scope' => 'invalid'], $outside->getId())['status']), 'Invalid deferred scope must fail');
    $invalidDates = array_merge($base, ['review_date' => '2028-01-01', 'expiration_date' => '2027-01-01']);
    scopeCheck(empty(DecisionGovernanceAction::validateRuleCreate($invalidDates, $outside->getId())['status']), 'Deferred create rejects reversed dates');
    echo "rule_scope_integration_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}
