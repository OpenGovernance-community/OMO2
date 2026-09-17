<?php
declare(strict_types=1);

// Standalone authorization tests: no connection to the application database.
namespace dbObject {
    final class User {
        private int $id = 0;
        public function load($id): bool { $this->id = (int)$id; return $this->id > 0; }
        public function isSiteAdmin(): bool { return $this->id === 2; }
        public function getOrganizationMembership($organizationId): object {
            $admin = $this->id === 1 && (int)$organizationId === 42;
            return new class($admin) {
                public function __construct(private bool $admin) {}
                public function get($field): bool { return $field === 'active'; }
                public function isOrganizationAdmin(): bool { return $this->admin; }
            };
        }
    }
    final class HolonPermission {
        public static array $grants = [];
        public static function userHasPermissionForHolonContext($userId, $organizationId, $key, $holonId): bool {
            return !empty(self::$grants[$userId][$organizationId][$key]);
        }
    }
    final class Organization {
        public function load($id): bool { return false; }
    }
}

namespace {
    require_once dirname(__DIR__) . '/common/auth.php';
    require_once dirname(__DIR__) . '/class/dbobject/dbobject.class.php';
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'dbObject\\')) {
            $path = dirname(__DIR__) . '/class/dbobject/' . strtolower(substr($class, 9)) . '.class.php';
            if (is_file($path)) require_once $path;
        }
    });

    final class CrudTestHolon extends \dbObject\Holon {
        protected function resolveOrganizationId() { return 42; }
        public function getId() { return 100; }
    }
    final class CrudTestRule extends \dbObject\Rule {
        public function getHolon() { return new CrudTestHolon(); }
    }
    final class CrudTestIndicator extends \dbObject\StatIndicator {
        public function getHolon() { return new CrudTestHolon(); }
        public function getOrganization() { return null; }
    }
    function crudAssert(bool $condition, string $message): void {
        if (!$condition) throw new \RuntimeException($message);
    }

    $_SERVER['HTTP_HOST'] = 'omo.localtest.me';
    $_SESSION = ['currentUser' => 1, 'currentOrganization' => 42];
    $holon = new CrudTestHolon();
    $catalog = \dbObject\Permission::getBuiltInCatalog();
    $expected = [
        'PROJECT' => 'projects', 'RULE' => 'policy', 'INDICATOR' => 'stats',
        'DOCUMENT' => 'documents', 'DECISION' => 'decisions', 'FAQ' => 'faq',
        'EVENT' => 'calendar', 'CHECKLIST' => 'checklists', 'CONTROL_ACTIVITY' => 'activities',
    ];
    foreach ($expected as $object => $group) {
        foreach (['CREATE', 'EDIT', 'DELETE'] as $action) {
            $key = 'CAN_' . $action . '_' . $object;
            crudAssert(isset($catalog[$key]), 'Missing permission: ' . $key);
            crudAssert($catalog[$key]['group'] === $group, 'Incorrect application group: ' . $key);
            crudAssert(!$holon->isAllowed($key, false), 'Inactive org admin must not bypass ' . $key);
            $_SESSION['isAdminByOrganization'][42] = true;
            crudAssert($holon->isAllowed($key, false), 'Active org admin must bypass ' . $key);
            unset($_SESSION['isAdminByOrganization']);
        }
    }
    crudAssert(!commonUserHasAdminOverride(1, 43), 'Org admin must not extend to another org.');
    $_SESSION['isAdminByOrganization'][42] = true;
    crudAssert(!commonUserHasAdminOverride(1, 43), 'Active org admin must stay in its org.');
    crudAssert(!commonUserHasAdminOverride(2, 42), 'Override must belong to the current user.');
    unset($_SESSION['isAdminByOrganization']);

    $rule = new CrudTestRule();
    $indicator = new CrudTestIndicator();
    foreach ([['RULE', $rule], ['INDICATOR', $indicator]] as [$object, $instance]) {
        \dbObject\HolonPermission::$grants = [1 => [42 => ['CAN_CREATE_' . $object => true]]];
        crudAssert(!$instance->canEdit() && !$instance->canDelete(), 'Create must not imply edit/delete: ' . $object);
        \dbObject\HolonPermission::$grants = [1 => [42 => ['CAN_EDIT_' . $object => true]]];
        crudAssert($instance->canEdit() && !$instance->canDelete(), 'Edit must not imply delete: ' . $object);
        \dbObject\HolonPermission::$grants = [1 => [42 => ['CAN_DELETE_' . $object => true]]];
        crudAssert(!$instance->canEdit() && $instance->canDelete(), 'Delete must not require edit: ' . $object);
    }
    \dbObject\HolonPermission::$grants = [];
    crudAssert(!\dbObject\Permission::userCanInOrganization('CAN_EDIT_PROJECT', 42, 1), 'No fallback to inactive admin status.');
    $_SESSION['isAdminByOrganization'][42] = true;
    crudAssert(\dbObject\Permission::userCanInOrganization('CAN_EDIT_PROJECT', 42, 1), 'Active org admin without holon.');
    $_SESSION = ['currentUser' => 2, 'currentOrganization' => 42];
    crudAssert(!commonUserHasAdminOverride(2, 42), 'Inactive superadmin must have no override.');
    $_SESSION['isSiteAdminModeEnabled'] = true;
    foreach ([0, 42, 43] as $orgId) {
        crudAssert(commonUserHasAdminOverride(2, $orgId), 'Active superadmin covers every context.');
        $document = new \dbObject\Document();
        $document->set('IDorganization', $orgId);
        crudAssert($document->hasObjectPermission('CAN_EDIT_DOCUMENT', 2), 'Superadmin can edit documents.');
        crudAssert($document->canDeleteInOrganizationContext($orgId, 2), 'Superadmin can delete documents.');
        $decision = new \dbObject\DecisionProcess();
        $decision->set('IDorganization', $orgId);
        crudAssert($decision->canUseManagementPermission('CAN_DELETE_DECISION', 2), 'Superadmin can delete decisions.');
    }
    $_SESSION['isSiteAdminModeEnabled'] = false;
    crudAssert(!$holon->isAllowed('CAN_EDIT_PROJECT', false), 'Disabling superadmin must take effect immediately.');

    $migration = (string)file_get_contents(dirname(__DIR__) . '/sql/2026-09-17-01-object-crud-permissions.sql');
    $seed = (string)file_get_contents(dirname(__DIR__) . '/docker/db/init/00-base.seed.sql');
    foreach (['CAN_EDIT_PROJECT', 'CAN_CREATE_RULE', 'CAN_EDIT_RULE', 'CAN_DELETE_RULE',
        'CAN_EDIT_INDICATOR', 'CAN_DELETE_INDICATOR', 'CAN_EDIT_DOCUMENT', 'CAN_DELETE_DOCUMENT',
        'CAN_EDIT_MEMBER_ASSIGNMENT', 'CAN_DELETE_MEMBER', 'CAN_EDIT_DECISION', 'CAN_DELETE_DECISION',
        'CAN_EDIT_FAQ', 'CAN_DELETE_FAQ'] as $key) {
        crudAssert(str_contains($migration, $key) && str_contains($seed, $key), 'Migration and seed must contain ' . $key);
    }
    echo "object_crud_permissions_test: OK\n";
}
