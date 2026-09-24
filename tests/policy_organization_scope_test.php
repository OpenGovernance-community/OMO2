<?php
declare(strict_types=1);

namespace dbObject {
    class DbObject
    {
        public static array $records = [];
        protected array $fields = [];
        protected int $id = 0;
        public function get($field) { return $this->fields[$field] ?? null; }
        public function set($field, $value): void { $this->fields[$field] = $value; }
        public function getId(): int { return $this->id; }
        public function load($id): bool
        {
            if (!isset(self::$records[(int)$id])) return false;
            $this->id = (int)$id;
            $this->fields = self::$records[$this->id];
            return true;
        }
        public function save() { return ['status' => true]; }
        public static function fetchAll($sql, $params = []): array { return [['id' => 1], ['id' => 2]]; }
    }
    class ArrayDbObject extends \ArrayObject {}
    class ArrayAuthority extends \ArrayObject
    {
        public function loadForHolon($holonId): void {}
    }
    class Authority extends DbObject {}
    class Organization
    {
        public function load($id): bool { return (int)$id === 42; }
        public function getId(): int { return 42; }
        public function canViewDetail(): bool { return true; }
        public function getEnabledStructuralRootHolon(): ?Holon { return null; }
        public function ensureTemplateAuthorityInstancesForHolon(Holon $holon): void {}
    }
    class Holon
    {
        private int $id = 0;
        public function load($id): bool { $this->id = (int)$id; return $this->id === 9; }
        public function getId(): int { return $this->id; }
        public function get($field) { return $field === 'IDorganization' ? 42 : null; }
        public function isDescendantOf($id, $includeSelf = false): bool { return $includeSelf && (int)$id === $this->id; }
    }
    class Permission
    {
        public static function userCanInOrganization(string $key, int $organizationId, int $userId): bool
        {
            return $organizationId === 42 && $userId === 7
                && in_array($key, ['CAN_CREATE_RULE', 'CAN_EDIT_RULE', 'CAN_DELETE_RULE'], true);
        }
    }
    class PropertyFormat { public static function sanitizeHtml($html): string { return (string)$html; } }
}

namespace {
    function commonGetCurrentUserId(): int { return 7; }
    function policyAssert(bool $condition, string $message): void
    {
        if (!$condition) throw new \RuntimeException($message);
    }

    require_once dirname(__DIR__) . '/class/dbObject/rule.class.php';
    require_once dirname(__DIR__) . '/class/dbObject/arrayrule.class.php';
    require_once dirname(__DIR__) . '/omo/api/policy/shared.php';

    $context = omoPolicyResolveContext(42);
    policyAssert($context['status'] && $context['currentHolon'] === null, 'Policy must load without a structure.');
    policyAssert(omoPolicyCanCreateLocalRule($context), 'Organization permission must allow rule creation.');
    policyAssert(count(omoPolicyGetDirectAuthorities(null)) === 0, 'No structure must have no authority selector.');
    $holonWithoutAuthorities = new \dbObject\Holon();
    $holonWithoutAuthorities->load(9);
    policyAssert(count(omoPolicyGetDirectAuthorities($holonWithoutAuthorities, new \dbObject\Organization())) === 0,
        'A structure without authorities must have no authority selector.');

    $rule = new \dbObject\Rule();
    foreach (['IDorganization' => 42, 'title' => 'Test', 'description' => '<p>Rule</p>',
        'review_date' => '2026-10-01', 'expiration_date' => '2027-10-01'] as $field => $value) {
        $rule->set($field, $value);
    }
    policyAssert(!empty($rule->save()['status']), 'Organization rule must save without a holon or authority.');
    policyAssert($rule->get('IDholon') === null && $rule->get('IDauthority') === null, 'Organization rule must keep null attachments.');
    policyAssert($rule->canEdit() && $rule->canDelete(), 'Organization permissions must allow editing and deleting its rule.');
    $localRule = new \dbObject\Rule();
    foreach (['IDholon' => 9, 'title' => 'Local', 'description' => '<p>Rule</p>',
        'review_date' => '2026-10-01', 'expiration_date' => '2027-10-01'] as $field => $value) {
        $localRule->set($field, $value);
    }
    policyAssert(!empty($localRule->save()['status']) && (int)$localRule->get('IDorganization') === 42,
        'A local holon rule must keep working without an authority.');

    \dbObject\DbObject::$records = [
        1 => ['IDorganization' => 42, 'IDholon' => null, 'IDauthority' => null, 'scope' => 'local'],
        2 => ['IDorganization' => 42, 'IDholon' => 9, 'IDauthority' => null, 'scope' => 'local'],
    ];
    $rules = new \dbObject\ArrayRule();
    $rules->loadForPolicyContexts(42, [], true);
    policyAssert(count($rules) === 1 && $rules[0]->getId() === 1, 'No structure must list organization rules.');
    $rules->loadForPolicyContexts(42, [9], true);
    policyAssert(count($rules) === 2, 'Root holon must list its own and organization rules.');
    $rules->loadForPolicyContexts(42, [9]);
    policyAssert(count($rules) === 1 && $rules[0]->getId() === 2, 'Child context must exclude organization rules.');

    echo "policy_organization_scope_test: OK\n";
}
