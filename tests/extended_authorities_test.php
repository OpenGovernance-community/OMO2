<?php
declare(strict_types=1);

namespace dbObject {
    // In-memory persistence; the permission resolver and session helpers are real.
    class DbObject {
        public static array $holons = [], $grants = [], $memo = [];
        protected array $fields = [];
        protected static function memoizeRead(array $key, callable $load) { $key = serialize($key); return self::$memo[$key] ??= $load(); }
        public static function fetchValue($sql, $params = []) {
            if (str_contains($sql, 'FROM `holon`')) return ($params['organization_id'] ?? 0) === 42 ? 1 : 0;
            return str_contains($sql, 'SELECT 1') && ($params['organization_id'] ?? 0) === 42 ? 1 : 0;
        }
        public static function fetchRow($sql, $params = []) {
            if (isset($params['root_holon_id'])) return self::$holons[$params['root_holon_id']] ?? null;
            foreach (self::$grants as $grant) if ($grant['IDholon'] === $params['holon_id'] && $grant['IDpermission'] === $params['permission_id']
                && $grant['member_type'] === $params['member_type'] && $grant['range'] === ($params['range'] ?? $grant['range'])) return $grant;
            return null;
        }
        public static function fetchAll($sql, $params = []) {
            if (str_contains($sql, 'holon_permission')) return array_values(array_filter(self::$grants,
                static fn ($row) => !isset($params['holon_id']) || $row['IDholon'] === $params['holon_id']));
            if (isset($params['root_holon_id_self'])) return array_values(self::$holons);
            return array_values(array_filter(self::$holons, static fn ($row) => in_array($row['IDholon_parent'], $params, true)));
        }
        public function set($field, $value) { $this->fields[$field] = $value; }
        public function get($field) { return $this->fields[$field] ?? null; }
        public function setId($id) { $this->set('id', (int)$id); }
        public function getId() { return (int)$this->get('id'); }
        public function loadFromArray($row) { $this->fields = $row; }
        public function save() {
            $id = $this->getId() ?: count(self::$grants) + 20;
            $this->setId($id);
            $this->set('permission_key', Permission::KEYS[$this->get('IDpermission')]);
            self::$grants[$id] = $this->fields; self::$memo = [];
            return ['status' => true];
        }
        public function delete() { unset(self::$grants[$this->getId()]); self::$memo = []; return true; }
    }
    class Permission extends DbObject {
        public const KEYS = [1 => 'CAN_MOVE_HOLON', 2 => 'CAN_EDIT_HOLON', 3 => 'CAN_DELETE_HOLON'];
        public static function getContextualMap($keys) { return array_fill_keys($keys, true); }
        public static function isPermissionContextual($key, $fallback) { return true; }
        public static function findByKey($key) { $p = new self(); $p->setId(array_search($key, self::KEYS, true)); return $p; }
    }
    class User {
        public function load($id) { return true; }
        public function getParameter($key) { return ''; }
        public static function buildInitials($name) { return substr($name, 0, 1); }
        public function isSiteAdmin() { return false; }
        public function getOrganizationMembership($id) { return null; }
    }
    class UserHolon {
        public static function fetchEffectiveRowsForUserAndHolonIds($user, $ids) { return in_array($user, [7, 9], true) ? [['IDholon' => 3, 'is_admin' => $user === 9]] : []; }
    }
    class History { public static function getLatestOrganizationEntryId($id) { return 10; } }
    class Holon extends DbObject {
        public function load($id) { $this->fields = self::$holons[$id] ?? []; return !!$this->fields; }
        public function isDescendantOf($id, $inclusive = false) {
            $current = $inclusive ? $this->getId() : $this->get('IDholon_parent');
            while ($current) { if ($current === $id) return true; $current = self::$holons[$current]['IDholon_parent']; }
            return false;
        }
    }
    require_once dirname(__DIR__) . '/class/dbobject/holonpermission.class.php';
}

namespace {
    require_once dirname(__DIR__) . '/common/auth.php';
    use dbObject\DbObject;
    use dbObject\HolonPermission as HP;
    function extendedAssert(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
    $_SERVER['HTTP_HOST'] = 'omo.localtest.me';
    $_SESSION = ['currentUser' => 7, 'currentOrganization' => 42];
    foreach ([1 => [0, 4, 0], 2 => [1, 2, 0], 3 => [2, 1, 9], 4 => [2, 2, 0], 5 => [4, 1, 0], 9 => [1, 1, 0]] as $id => [$parent, $type, $template]) {
        DbObject::$holons[$id] = ['id' => $id, 'name' => 'Holon ' . $id, 'IDholon_parent' => $parent, 'IDholon_template' => $template,
            'IDtypeholon' => $type, 'IDorganization' => 42, 'IDholon_org' => 1, 'active' => 1];
    }
    foreach ([[1, 'parent_circle', 'member', false], [1, 'parent_circle_descendants', 'member', true],
        [2, 'organization', 'member', true], [3, 'self', 'admin', true], [1, 'self', 'collective', false]] as $index => [$permission, $range, $profile, $extended]) {
        $id = $index + 1;
        DbObject::$grants[$id] = ['id' => $id, 'IDholon' => 9, 'IDpermission' => $permission,
            'permission_key' => \dbObject\Permission::KEYS[$permission], 'range' => $range, 'member_type' => $profile, 'is_extended' => $extended];
    }
    $can = static fn (int $target) => HP::userHasPermissionForHolonContext(7, 42, 'CAN_MOVE_HOLON', $target);
    extendedAssert(commonCurrentUserCanUseExtendedAuthorities(42), 'Inherited extended grants must make activation available.');
    extendedAssert(!commonCurrentUserIsExtendedAuthoritiesEnabled(42), 'Mode must be off by default.');
    extendedAssert($can(2) && !$can(4), 'Ordinary circle permission must work without descendant extension.');
    extendedAssert(!HP::userHasPermissionForHolonContext(7, 42, 'CAN_EDIT_HOLON', 4), 'Extended-only configuration must not fall through to open-by-default access.');
    $offDetails = HP::buildEffectivePermissionDetailsForOrganization(7, 42);
    extendedAssert(count($offDetails['rows']) === 1, 'Normal matrix details must omit dormant grants.');
    $collective = HP::buildHolonCollectivePermissionSetForOrganization(42, 3);
    extendedAssert(commonSetCurrentUserExtendedAuthorities(true, 42), 'Eligible user must be able to activate.');
    extendedAssert($can(4) && $can(5), 'Activation must include permitted descendants immediately, even with memoization.');
    extendedAssert(!HP::userHasPermissionForHolonContext(7, 42, 'CAN_DELETE_HOLON', 3), 'Member activation must not include admin-profile grants.');
    extendedAssert(!empty($_SESSION['permissionCacheByOrganization'][42]['extendedAuthoritiesActive']), 'Activation must rebuild the session matrix.');
    extendedAssert($collective === HP::buildHolonCollectivePermissionSetForOrganization(42, 3), 'Personal mode must not change collective rights.');
    extendedAssert(!commonCurrentUserCanUseExtendedAuthorities(43), 'Availability must stay within its organization.');
    extendedAssert(!commonCurrentUserIsExtendedAuthoritiesEnabled(43), 'Activation must stay within its organization.');
    $_SESSION['currentUser'] = 9;
    extendedAssert(commonSetCurrentUserExtendedAuthorities(true, 42), 'Assigned role administrator can activate their extensions.');
    extendedAssert(HP::userHasPermissionForHolonContext(9, 42, 'CAN_DELETE_HOLON', 3), 'Role admin grants are included only for its administrator.');
    $_SESSION['currentUser'] = 8;
    extendedAssert(!commonCurrentUserIsExtendedAuthoritiesEnabled(42), 'Activation must not leak across users.');
    extendedAssert(!commonSetCurrentUserExtendedAuthorities(true, 42), 'User without an assigned role cannot activate.');
    $_SESSION['currentUser'] = 7;
    commonSetCurrentUserExtendedAuthorities(true, 42);
    $activeCache = $_SESSION['permissionCacheByOrganization'][42];
    commonSetCurrentUserExtendedAuthorities(false, 42);
    extendedAssert(!$can(4) && $can(2), 'Deactivation must remove extensions and preserve ordinary grants.');
    extendedAssert(!commonIsCurrentUserPermissionCacheEntryFresh($activeCache, 42, 7), 'Cached active matrix must not be reusable after deactivation.');
    $map = HP::getAssignmentKeyMapForHolon(9);
    extendedAssert(HP::isExtendedAssignment($map['member']['CAN_MOVE_HOLON'][1]), 'Editor payload must retain the extended flag.');
    extendedAssert(HP::syncAssignmentsForHolon(9, $map), 'Editor payload must save.');
    extendedAssert(HP::getAssignmentKeyMapForHolon(9) === $map, 'Saving unchanged assignments must preserve ordinary, extended and collective grants.');
    $map['member']['CAN_MOVE_HOLON'][1] = 'parent_circle_descendants';
    HP::syncAssignmentsForHolon(9, $map);
    extendedAssert($can(4), 'Unchecking super power must turn that assignment into an ordinary grant.');
    commonSetCurrentUserExtendedAuthorities(true, 42);
    commonClearCurrentUserAllAdminModes();
    extendedAssert(!commonCurrentUserIsExtendedAuthoritiesEnabled(42), 'New login must clear extended mode alongside admin modes.');

    // The real topbar must hide the control when unavailable and put it before admin mode.
    require_once dirname(__DIR__) . '/common/topbar.php';
    $options = ['userName' => 'Test', 'profile' => [
        'data' => ['userId' => 7, 'displayName' => 'Test', 'email' => 'test@example.test', 'initials' => 'T'],
        'adminMode' => ['enabled' => true],
        'extendedAuthorities' => ['enabled' => false, 'organizationId' => 42, 'enableLabel' => 'Enable extended',
            'disableLabel' => 'Disable extended', 'notice' => 'Temporary help', 'csrfToken' => 'test-token'],
    ]];
    ob_start(); commonRenderTopbar($options); $html = ob_get_clean();
    extendedAssert(!str_contains($html, 'data-admin-mode-url="/common/extended_authorities.php"'), 'No assigned extended authority means no button.');
    $options['profile']['extendedAuthorities']['enabled'] = true;
    ob_start(); commonRenderTopbar($options); $html = ob_get_clean();
    $extendedPosition = strpos($html, 'data-admin-mode-url="/common/extended_authorities.php"');
    $adminPosition = strpos($html, 'data-admin-mode-url="/common/admin_mode.php"');
    extendedAssert($extendedPosition !== false && $adminPosition !== false && $extendedPosition < $adminPosition, 'Extended toggle must be above the organization admin toggle.');
    extendedAssert(str_contains($html, '>Enable extended</button>'), 'Inactive mode must offer activation.');
    $options['profile']['extendedAuthorities']['active'] = true;
    ob_start(); commonRenderTopbar($options); $html = ob_get_clean();
    extendedAssert(str_contains($html, '>Disable extended</button>'), 'Active mode must offer deactivation.');
    echo "extended_authorities_test: OK\n";
}
