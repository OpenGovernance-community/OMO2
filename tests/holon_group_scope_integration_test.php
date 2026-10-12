<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/shared_functions.php';
require_once dirname(__DIR__) . '/common/auth.php';
require_once dirname(__DIR__) . '/common/omo_context_scope.php';

use dbObject\{DbObject, Organization, Holon, FAQ, ArrayApplication, OrganizationApplication, Project, ProjectImportanceCalculator};

function groupScopeCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function groupScopeSave($object): void {
    $result = $object->save();
    groupScopeCheck(!empty($result['status']), 'Fixture save failed: ' . json_encode($result));
}

$pdo = DbObject::getPdo();
$pdo->beginTransaction();
try {
    $_SESSION = ['currentUser' => 0];
    $organization = new Organization();
    $organization->set('name', 'Transparent group scopes');
    $organization->set('shortname', 'group-scope-' . bin2hex(random_bytes(4)));
    groupScopeSave($organization);
    $applications = new ArrayApplication();
    $applications->load(['where' => [['field' => 'hash', 'op' => 'in', 'value' => ['structure', 'projects']]]]);
    foreach ($applications as $application) {
        $enabled = new OrganizationApplication();
        $enabled->set('IDorganization', $organization->getId());
        $enabled->set('IDapplication', $application->getId());
        $enabled->set('active', true);
        groupScopeSave($enabled);
    }
    $root = new Holon();
    foreach (['name' => 'Root', 'IDorganization' => $organization->getId(), 'IDtypeholon' => 4, 'active' => true, 'visible' => true] as $key => $value) $root->set($key, $value);
    groupScopeSave($root);
    $root->set('IDholon_org', $root->getId());
    groupScopeSave($root);
    $make = static function (string $name, int $type, Holon $parent) use ($root): Holon {
        $holon = new Holon();
        foreach (['name' => $name, 'IDholon_org' => $root->getId(), 'IDholon_parent' => $parent->getId(), 'IDtypeholon' => $type, 'active' => true, 'visible' => true] as $key => $value) $holon->set($key, $value);
        groupScopeSave($holon);
        return $holon;
    };
    $circle = $make('Circle', 2, $root);
    $group = $make('Group', 3, $circle);
    $nested = $make('Nested group', 3, $group);
    $role = $make('Role', 1, $nested);
    $childCircle = $make('Child circle', 2, $nested);
    $deepRole = $make('Deep role', 1, $childCircle);
    $childRole = $make('Role below role', 1, $role);
    $siblingGroup = $make('Sibling group', 3, $circle);
    $otherCircle = $make('Other circle', 2, $root);
    groupScopeCheck((int)$role->getParentHolon()->getId() === (int)$nested->getId(), 'Physical parent must stay available for tree editing');
    groupScopeCheck((int)$role->getParentHolonThroughGroups()->getId() === (int)$circle->getId(), 'Parent lookup must cross nested groups');
    groupScopeCheck($role->isChildOfThroughGroups($circle) && $role->isChildOfThroughGroups($group), 'A group context and its circle must both contain the role');
    groupScopeCheck(!$deepRole->isChildOfThroughGroups($circle), 'A child circle must remain a scope boundary');
    groupScopeCheck(!$childRole->isChildOfThroughGroups($circle), 'A role must remain a scope boundary');
    $ids = array_map(static fn (Holon $holon): int => (int)$holon->getId(), $circle->getChildrenThroughGroups());
    sort($ids);
    $expected = [(int)$role->getId(), (int)$childCircle->getId()];
    sort($expected);
    groupScopeCheck($ids === $expected, 'Children must flatten groups without flattening roles or circles');
    $scopeIds = omoApiGetDirectChildHolonIds($circle);
    sort($scopeIds);
    groupScopeCheck($scopeIds === $expected, 'Shared content scopes and dbObject scopes must agree');

    // Isolate scope matching from visibility; use the real loaded Holon hierarchy.
    $faqHolon = new class extends Holon { public function canViewDetail() { return true; } };
    $faq = new class extends FAQ {
        public Holon $scopeHolon;
        public function getContextHolon() { return $this->scopeHolon; }
        public function isLinkedApplicationVisibleInOrganization($organizationId) { return true; }
    };
    $faq->scopeHolon = $faqHolon;
    $faq->set('IDorganization', $organization->getId());
    $faq->set('IDholon', $role->getId());
    $faq->set('isactive', true);
    $context = ['organizationId' => (int)$organization->getId(), 'currentHolon' => $circle, 'rootHolon' => $root];
    $faqHolon->load($role->getId(), true);
    groupScopeCheck($faq->canBeViewedInContext($context, 'children'), 'FAQ children scope must include a role in nested groups');
    $faqHolon->load($deepRole->getId(), true);
    groupScopeCheck(!$faq->canBeViewedInContext($context, 'children'), 'FAQ children scope must stop at child circles');
    $faqHolon->load($otherCircle->getId(), true);
    groupScopeCheck(!$faq->canBeViewedInContext($context, 'children'), 'FAQ scope must reject another branch');

    $groupedTemplate = $make('Grouped template', 1, $nested);
    $groupedTemplate->set('visible', false);
    $groupedTemplate->set('templatename', 'Grouped template');
    groupScopeSave($groupedTemplate);
    foreach ([$circle, $siblingGroup, $childCircle] as $destination) {
        groupScopeCheck($organization->isTemplateAvailableInContext($groupedTemplate, $destination->getId()), 'A grouped definition must be available throughout its circle and descendants');
        $availableIds = array_map(static fn (Holon $holon): int => (int)$holon->getId(), $organization->getAvailableTemplateDefinitionHolons($destination->getId()));
        groupScopeCheck(in_array((int)$groupedTemplate->getId(), $availableIds, true), 'Template picker and save validation must use the same scope');
    }
    groupScopeCheck(!$organization->isTemplateAvailableInContext($groupedTemplate, $otherCircle->getId()), 'Grouped templates must not escape their containing circle');
    $templateEditor = $organization->getHolonTemplateEditorData($circle->getId());
    groupScopeCheck(in_array((int)$groupedTemplate->getId(), array_column($templateEditor['templates'], 'id'), true), 'Template management must display definitions inside nested groups');
    $export = $organization->getStructureCompactExportData($siblingGroup);
    $exportIds = [];
    $collectExport = static function (array $nodes) use (&$collectExport, &$exportIds): void {
        foreach ($nodes as $node) {
            $exportIds[] = (int)$node['id'];
            $collectExport($node['children'] ?? []);
        }
    };
    $collectExport($export['holons']);
    groupScopeCheck(in_array((int)$groupedTemplate->getId(), $exportIds, true), 'Compact export must retain grouped definitions available in a sibling group');

    $directRole = $make('Direct role', 1, $circle);
    $projects = [];
    foreach ([$directRole, $role, $deepRole, $nested] as $owner) {
        $project = new Project();
        foreach (['IDorganization' => $organization->getId(), 'IDholon' => $owner->getId(), 'title' => 'Group scope importance', 'importance' => 5, 'project_kind' => Project::KIND_STANDARD, 'active' => true] as $key => $value) $project->set($key, $value);
        groupScopeSave($project);
        $projects[(int)$owner->getId()] = (int)$project->getId();
    }
    $scores = ProjectImportanceCalculator::recalculateOrganization($organization->getId());
    $score = static fn (Holon $owner): float => (float)$scores[$projects[(int)$owner->getId()]];
    groupScopeCheck(abs($score($directRole) - $score($role)) < 0.000001, 'Nested groups must not alter project importance');
    groupScopeCheck(abs($score($role) - exp(-0.15 * 2)) < 0.000001, 'Only circle and role levels must count toward depth');
    groupScopeCheck(abs($score($deepRole) - exp(-0.15 * 3)) < 0.000001, 'Child circles must still increase project depth');
    groupScopeCheck(abs($score($nested) - exp(-0.15)) < 0.000001, 'A project assigned to a group must use its circle depth');
    $projectApp = OrganizationApplication::loadByOrganizationAndDirectory($organization->getId(), 'projects', false);
    groupScopeCheck($projectApp instanceof OrganizationApplication, 'Projects fixture must be enabled');
    $parameters = $projectApp->getParametersArray();
    $parameters['importanceCalculationVersion'] = 3;
    $projectApp->setParametersArray($parameters);
    groupScopeSave($projectApp);
    groupScopeCheck(ProjectImportanceCalculator::ensureOrganizationInitialized($organization->getId()), 'Existing scores must be recalculated with transparent groups');
    groupScopeCheck(!ProjectImportanceCalculator::ensureOrganizationInitialized($organization->getId()), 'Updated scores must not be recalculated on every read');

    $template = $make('Required role template', 1, $root);
    $template->set('visible', false);
    $template->set('templatename', 'Required role template');
    $template->set('mandatory', true);
    groupScopeSave($template);
    foreach ([$role, $deepRole] as $instance) {
        $instance->set('IDholon_template', $template->getId());
        groupScopeSave($instance);
    }
    groupScopeCheck($role->countSiblingTemplateInstances() === 0, 'An instance in a child circle must not satisfy the parent circle obligation');
    groupScopeCheck(!$role->canDeletePreservingMandatoryTemplates(), 'The last required role must remain protected inside nested groups');
    groupScopeCheck(!$group->canDeletePreservingMandatoryTemplates(), 'Deleting the containing group must not bypass the obligation');
    groupScopeCheck(!$nested->canDeletePreservingMandatoryTemplates(), 'Deleting a nested group must not bypass the obligation');
    $hasChild = new ReflectionMethod(Organization::class, 'circleHasTemplateChild');
    groupScopeCheck($hasChild->invoke($organization, $circle, $template->getId()), 'Mandatory initialization must recognize instances already inside groups');
    $blocked = $organization->deleteHolonDefinition($group->getId(), 0, true);
    groupScopeCheck(empty($blocked['status']) && (new Holon())->load($role->getId(), true), 'Collective deletion must also preserve mandatory instances');
    $copyInside = $make('Second copy inside group', 1, $group);
    $copyInside->set('IDholon_template', $template->getId());
    groupScopeSave($copyInside);
    groupScopeCheck($role->canDeletePreservingMandatoryTemplates(), 'A second copy in the same circle must permit removing one role');
    groupScopeCheck(!$group->canDeletePreservingMandatoryTemplates(), 'Two copies removed together must not make group deletion legal');
    $copyOutside = $make('Copy in sibling group', 1, $siblingGroup);
    $copyOutside->set('IDholon_template', $template->getId());
    groupScopeSave($copyOutside);
    groupScopeCheck($group->canDeletePreservingMandatoryTemplates(), 'A surviving copy in a sibling group must satisfy the circle obligation');
    $groupTemplate = $make('Required group template', 3, $root);
    $groupTemplate->set('visible', false);
    $groupTemplate->set('templatename', 'Required group template');
    $groupTemplate->set('mandatory', true);
    groupScopeSave($groupTemplate);
    $nested->set('IDholon_template', $groupTemplate->getId());
    groupScopeSave($nested);
    groupScopeCheck(!$nested->canDeletePreservingMandatoryTemplates(), 'Mandatory group instances must also remain protected');
    groupScopeCheck(!$group->canDeletePreservingMandatoryTemplates(), 'Deleting a container must not remove the last mandatory group');
    $siblingGroup->set('IDholon_template', $groupTemplate->getId());
    groupScopeSave($siblingGroup);
    groupScopeCheck($group->canDeletePreservingMandatoryTemplates(), 'Another mandatory group in the same circle must permit deletion');
    $moveCircle = $make('Move source circle', 2, $root);
    $moveGroup = $make('Move source group', 3, $moveCircle);
    $moveRole = $make('Move required role', 1, $moveGroup);
    $moveRole->set('IDholon_template', $template->getId());
    groupScopeSave($moveRole);
    $moveOrganization = new class extends Organization {
        protected function hasHolonMovePermission(Holon $holon, int $collectiveHolonId = 0, int $userId = 0): bool { return true; }
    };
    $moveOrganization->load($organization->getId());
    $moved = $moveOrganization->moveHolonDefinition($moveRole->getId(), $otherCircle->getId());
    groupScopeCheck(!empty($moved['status']), 'A compatible mandatory role move must succeed');
    groupScopeCheck($hasChild->invoke($organization, $moveCircle, $template->getId()), 'Moving the last grouped instance out must restore the previous circle obligation');
    groupScopeCheck(count($moveGroup->getChildren()) === 0, 'Replacement mandatory children belong to the circle, not its display group');
    echo "holon_group_scope_integration_test: OK\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    DbObject::$preload = [];
}
