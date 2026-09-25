<?php
declare(strict_types=1);

namespace dbObject {
    require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
    final class Holon {
        private int $id = 0;
        public static array $rows = [];
        public function load($id, $forced = false) {
            if (!isset(self::$rows[$id])) return false;
            $this->id = (int)$id;
            return true;
        }
        public function getId() { return $this->id; }
        public function get($field) { return self::$rows[$this->id][$field] ?? null; }
        public function getParentHolon() { $parent = new self(); return $parent->load($this->get('IDholon_parent')) ? $parent : null; }
        public function getFullDisplayName() { return $this->get('name'); }
    }
    final class Organization extends DbObject {
        public static function rules() { return []; }
        public static function attributeLabels() { return []; }
        public static bool $allowed = true;
        public static array $calls = [];
        public function load($id, $forced = false) { return (int)$id === 42; }
        public function canMoveHolonToParent(Holon $holon, Holon $destination, $root, $collective) {
            self::$calls[] = ['validate', $collective];
            return self::$allowed && $collective === 2 && $destination->getId() === 4;
        }
        public function moveHolonDefinition($id, $parent, $user, $collective) {
            self::$calls[] = ['move', $collective];
            Holon::$rows[$id]['IDholon_parent'] = $parent;
            return ['status' => true];
        }
    }
    require_once dirname(__DIR__) . '/class/dbobject/decisiongovernanceaction.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/deferredproposal.class.php';
    final class MoveApplicationProbe extends DeferredProposal {
        public static function apply(DeferredProposal $proposal, int $collective): array { return parent::applyOne($proposal, $collective); }
    }
}

namespace {
    use dbObject\DeferredProposal;
    use dbObject\Holon;
    use dbObject\Organization;
    use dbObject\MoveApplicationProbe;
    function applicationAssert(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
    Holon::$rows = [
        3 => ['name' => 'Source', 'IDholon_parent' => 2, 'IDholon_template' => 0],
        4 => ['name' => 'Destination', 'IDholon_parent' => 2, 'IDholon_template' => 0],
        5 => ['name' => 'Role', 'nomcomplet' => '', 'color' => '', 'IDholon_parent' => 3, 'IDholon_template' => 0],
    ];
    $holon = new Holon(); $holon->load(5);
    $before = DeferredProposal::captureHolonMoveState($holon);
    $proposal = new DeferredProposal();
    foreach (['IDorganization' => 42, 'IDholon' => 3, 'target_type' => 'holon', 'operation' => 'move', 'target_id' => 5,
        'before_state' => $before, 'after_state' => array_replace($before, ['parent_id' => 4, 'parent_label' => 'Destination'])] as $key => $value) $proposal->set($key, $value);
    Organization::$allowed = false;
    applicationAssert(empty(MoveApplicationProbe::apply($proposal, 2)['status']), 'Revoked collective permission must block application.');
    applicationAssert(Holon::$rows[5]['IDholon_parent'] === 3, 'Failed application must leave the parent unchanged.');
    Organization::$allowed = true;
    applicationAssert(empty(MoveApplicationProbe::apply($proposal, 3)['status']), 'Source parent must not be substituted for the deciding collective.');
    Holon::$rows[5]['IDholon_parent'] = 4;
    applicationAssert(!empty(MoveApplicationProbe::apply($proposal, 2)['conflict']), 'An intervening move must be detected.');
    Holon::$rows[5]['IDholon_parent'] = 3;
    Holon::$rows[5]['IDholon_template'] = 9;
    applicationAssert(!empty(MoveApplicationProbe::apply($proposal, 2)['conflict']), 'Template changes must be detected.');
    Holon::$rows[5]['IDholon_template'] = 0;
    $destination = Holon::$rows[4]; unset(Holon::$rows[4]);
    applicationAssert(empty(MoveApplicationProbe::apply($proposal, 2)['status']), 'A removed destination must block application.');
    Holon::$rows[4] = $destination;
    Organization::$calls = [];
    applicationAssert(!empty(MoveApplicationProbe::apply($proposal, 2)['status']), 'Validated move must apply.');
    applicationAssert(Organization::$calls === [['validate', 2], ['move', 2]], 'Application must retain the collective through validation and mutation.');
    applicationAssert(Holon::$rows[5]['IDholon_parent'] === 4, 'Successful application must change the parent.');
    echo "deferred_holon_move_application_test: OK\n";
}
