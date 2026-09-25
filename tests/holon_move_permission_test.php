<?php
declare(strict_types=1);

// Exercise the real move service against an in-memory structural tree.
namespace dbObject {
    require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
    final class Holon
    {
        public static array $rows = [];
        public static array $personal = [];
        public static bool $saveFails = false;
        private int $id = 0;
        private array $fields = [];
        public function load($id): bool {
            $this->id = (int)$id;
            $this->fields = self::$rows[$this->id] ?? [];
            return $this->fields !== [];
        }
        public function getId() { return $this->id; }
        public function get($field) { return $this->fields[$field] ?? null; }
        public function set($field, $value) { $this->fields[$field] = $value; }
        public function save() {
            if (self::$saveFails) return ['status' => false];
            self::$rows[$this->id] = $this->fields;
            return ['status' => true];
        }
        public function isAllowed($key, $cache = true, $userId = 0) {
            return $key === 'CAN_MOVE_HOLON' && !empty(self::$personal[(int)$userId ?: 7][$this->id]);
        }
        public function canEdit() { throw new \RuntimeException('Moving must not require edit rights.'); }
        public function isTemplateNode($root) { return !empty($this->fields['template']); }
        public function getDisplayName() { return (string)$this->get('name'); }
        public function getFullDisplayName() { return $this->getDisplayName(); }
        public function getTypeLabel() { return 'Cercle'; }
        public function getTemplateLabel() { return 'Cercle'; }
        public function getParentHolon() {
            $parent = new self();
            return $parent->load($this->get('IDholon_parent')) ? $parent : null;
        }
        public function getPathHolons() { return [$this]; }
        public function getChildren() {
            $children = [];
            foreach (self::$rows as $id => $row) if (($row['IDholon_parent'] ?? 0) === $this->id) {
                $child = new self(); $child->load($id); $children[] = $child;
            }
            return $children;
        }
        public function isDescendantOf($ancestor, $inclusive = false) {
            $id = $ancestor instanceof self ? $ancestor->getId() : (int)$ancestor;
            $node = $inclusive ? $this : $this->getParentHolon();
            while ($node) { if ($node->getId() === $id) return true; $node = $node->getParentHolon(); }
            return false;
        }
    }
    final class HolonPermission {
        public static array $scopes = [];
        public static function holonHasCollectivePermissionForHolonContext($organization, $collective, $key, $target) {
            if ($organization !== 42 || $key !== 'CAN_MOVE_HOLON') return false;
            $holon = new Holon();
            if (!$holon->load($target)) return false;
            foreach (self::$scopes[$collective] ?? [] as $root) if ($holon->isDescendantOf($root, true)) return true;
            return false;
        }
    }
    final class ProjectImportanceCalculator {
        public static int $calls = 0;
        public static function recalculateForHolonHierarchyChange($id) { self::$calls++; }
    }
    require_once dirname(__DIR__) . '/class/dbobject/organization.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/decisiongovernanceaction.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/deferredproposal.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/permission.class.php';

    final class MoveTestOrganization extends Organization {
        public function getId() { return 42; }
        public function get($field) { return $field === 'name' ? 'Test' : null; }
        public function getStructuralRootHolon() { $root = new Holon(); $root->load(1); return $root; }
        public function containsHolon($holon): bool { return (int)$holon->get('IDorganization') === 42 && (bool)$holon->get('active'); }
        public function isTemplateAvailableInContext(Holon $template, $contextHolonId = 0) { return $contextHolonId !== 4; }
        protected function isTemplateAvailableForHolonCreation(Holon $template, Holon $contextHolon, $excludedHolonId = 0) { return true; }
        protected function createMandatoryChildrenForCircle(Holon $circleHolon, $rootHolonId, $userId = 0, array $excludedTemplateIds = []) {}
    }
}

namespace {
    use dbObject\Holon;
    use dbObject\DeferredProposal;
    function moveAssert(bool $value, string $message): void { if (!$value) throw new RuntimeException($message); }
    foreach ([1 => 0, 2 => 1, 3 => 2, 4 => 2, 5 => 3, 6 => 1, 7 => 5, 8 => 1] as $id => $parent) {
        Holon::$rows[$id] = ['IDholon_parent' => $parent, 'IDtypeholon' => $id === 1 ? 4 : 2, 'IDorganization' => $id === 8 ? 43 : 42,
            'IDholon_template' => 0, 'active' => 1, 'name' => 'Holon ' . $id];
    }
    $organization = new \dbObject\MoveTestOrganization();
    $holon = new Holon(); $holon->load(5);
    $destination = new Holon(); $destination->load(4);
    moveAssert(isset(\dbObject\Permission::getBuiltInCatalog()['CAN_MOVE_HOLON']), 'Move permission must be configurable.');
    moveAssert(DeferredProposal::getHolonOperationPermissionKey('move') === 'CAN_MOVE_HOLON', 'Deferred move must use its dedicated permission.');
    moveAssert(!$organization->canMoveHolonToParent($holon, $destination), 'No grants must deny a move.');
    Holon::$personal[7] = [5 => true];
    moveAssert(!$organization->canMoveHolonToParent($holon, $destination), 'Source alone must not authorize the destination.');
    Holon::$personal[7] = [4 => true];
    moveAssert(!$organization->canMoveHolonToParent($holon, $destination), 'Destination alone must not authorize the source.');
    Holon::$personal[7] = [5 => true, 4 => true, 7 => true, 8 => true];
    moveAssert($organization->canMoveHolonToParent($holon, $destination), 'Move rights must suffice without create, delete or edit rights.');
    moveAssert(!$organization->canMoveHolonToParent($holon, $destination, null, 0, 99), 'Explicit actor must be honored.');
    $editor = $organization->getHolonMoveEditorData(5);
    moveAssert(array_column($editor['destinations'], 'id') === [4], 'Catalog must exclude descendants, foreign organization and unauthorized destinations.');
    moveAssert(empty($organization->moveHolonDefinition(5, 6, 7)['status']), 'Forged out-of-scope destination must be rejected by the mutation.');
    moveAssert(empty($organization->moveHolonDefinition(5, 7, 7)['status']), 'Cycles must be rejected by the mutation.');
    Holon::$saveFails = true;
    moveAssert(empty($organization->moveHolonDefinition(5, 4, 7)['status']), 'Failed persistence must not report success.');
    moveAssert(\dbObject\ProjectImportanceCalculator::$calls === 0, 'Failed persistence must not trigger recalculation.');
    Holon::$saveFails = false;
    moveAssert(!empty($organization->moveHolonDefinition(5, 4, 7)['status']), 'Authorized immediate move must succeed.');
    moveAssert(Holon::$rows[5]['IDholon_parent'] === 4, 'Move must change the parent.');
    Holon::$rows[5]['IDholon_parent'] = 3; $holon->load(5);
    Holon::$personal = [];
    \dbObject\HolonPermission::$scopes = [2 => [2]];
    $collectiveEditor = $organization->getHolonMoveEditorData(5, 2);
    moveAssert(array_column($collectiveEditor['destinations'], 'id') === [2, 3, 4], 'Collective subtree scope must include self and subcircles without personal grants.');
    $validation = DeferredProposal::validateHolonMove($organization, $holon, 4, 2);
    moveAssert(!empty($validation['status']) && $validation['state']['parent_id'] === 4, 'Collective move must normalize its destination.');
    moveAssert(Holon::$rows[5]['IDholon_parent'] === 3, 'Proposing a move must not apply it.');
    moveAssert(empty(DeferredProposal::validateHolonMove($organization, $holon, 6, 2)['status']), 'Collective cannot move outside its scope.');
    moveAssert(empty(DeferredProposal::validateHolonMove($organization, $holon, 3, 2)['status']), 'Unchanged destination must be rejected.');
    moveAssert(empty(DeferredProposal::validateHolonMove($organization, $holon, 4, 0)['status']), 'Deferred move must fail closed without a collective.');
    Holon::$rows[4]['template'] = true; $destination->load(4);
    moveAssert(!$organization->canMoveHolonToParent($holon, $destination, null, 2), 'Template destinations must be rejected.');
    Holon::$rows[4]['template'] = false; $destination->load(4);
    Holon::$rows[5]['IDholon_template'] = 8; $holon->load(5);
    moveAssert(!$organization->canMoveHolonToParent($holon, $destination, null, 2), 'Template availability restrictions must remain enforced.');
    echo "holon_move_permission_test: OK\n";
}
