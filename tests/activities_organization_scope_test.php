<?php
declare(strict_types=1);

namespace dbObject {
    class ArrayDbObject extends \ArrayObject
    {
        public array $loadParams = [];
        public function load($params = null): void { $this->loadParams = $params; }
    }

    class Organization
    {
        public function load($id): bool { return (int)$id === 42; }
        public function canViewDetail(): bool { return true; }
        public function getEnabledStructuralRootHolon(): ?Holon { return null; }
        public function get($field): string { return $field === 'name' ? 'Test organization' : ''; }
    }

    class Holon {}

    class ControlActivity
    {
        public function __construct(private array $fields = []) {}
        public function get($field) { return $this->fields[$field] ?? null; }
        public function getOrganization(): Organization { return new Organization(); }
        public function getHolon(): ?Holon { return null; }
    }

    class Permission
    {
        public static array $grants = [];
        public static function userCanInOrganization(string $key, int $organizationId, int $userId): bool
        {
            return $organizationId === 42 && $userId === 7 && !empty(self::$grants[$key]);
        }
    }

    class UserOrganization
    {
        public static function hasActiveMembership($userId, $organizationId): bool
        {
            return (int)$userId === 7 && (int)$organizationId === 42;
        }
    }
}

namespace {
    function commonGetCurrentUserId(): int { return 7; }
    function activityOrganizationAssert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    require_once dirname(__DIR__) . '/class/dbObject/arraycontrolactivity.class.php';
    require_once dirname(__DIR__) . '/omo/api/activities/shared.php';

    $context = omoActivityResolveContext(42);
    activityOrganizationAssert($context['status'] && $context['currentHolon'] === null, 'Organization context must allow a missing holon.');

    $tasks = new \dbObject\ArrayControlActivity();
    $tasks->loadForContext(42, [], true, true);
    activityOrganizationAssert(
        $tasks->loadParams['whereAny'] === [['field' => 'IDholon', 'op' => 'is null']],
        'Organization tasks must load by a null holon.'
    );
    activityOrganizationAssert($tasks->loadParams['where'][0]['value'] === 42, 'Organization filter must remain active.');
    $tasks->loadForContext(42, [9], true);
    activityOrganizationAssert(
        $tasks->loadParams['whereAny'] === [['field' => 'IDholon', 'op' => 'in', 'value' => [9]]],
        'Holon context must exclude organization tasks.'
    );
    $tasks->loadForContext(42, [9], true, true);
    activityOrganizationAssert(
        $tasks->loadParams['whereAny'] === [
            ['field' => 'IDholon', 'op' => 'in', 'value' => [9]],
            ['field' => 'IDholon', 'op' => 'is null'],
        ],
        'Root holon must also include organization tasks.'
    );

    $task = new \dbObject\ControlActivity(['IDorganization' => 42, 'IDholon' => null]);
    activityOrganizationAssert(omoActivityCanView($task), 'Organization task must be visible in the organization.');
    activityOrganizationAssert(omoActivityMatchesAssignment($task, 'spaces', 7, 42), 'Organization member must see organization tasks in their spaces.');
    activityOrganizationAssert(omoActivityMatchesAssignment($task, 'mine', 7, 42), 'Unassigned organization task must appear under mine.');

    \dbObject\Permission::$grants = ['CAN_CREATE_RECURRING_TASK' => true, 'CAN_EDIT_RECURRING_TASK' => true];
    activityOrganizationAssert(omoActivityCanUsePermission(null, 'CAN_CREATE_RECURRING_TASK', 42), 'Creation must use organization permission.');
    activityOrganizationAssert(omoActivityCanEdit($task), 'Edit must use organization permission.');
    activityOrganizationAssert(!omoActivityCanDelete($task), 'Edit must not imply delete.');

    echo "activities_organization_scope_test: OK\n";
}
