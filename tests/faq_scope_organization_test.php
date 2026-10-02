<?php
declare(strict_types=1);

namespace dbObject {
    // In-memory persistence; exercise the real Holon and FAQ scope resolution.
    class DbObject
    {
        public static array $records = [];
        protected array $fields = [];
        public function load($id): bool
        {
            $record = self::$records[static::tableName()][(int)$id] ?? null;
            if ($record === null) return false;
            $this->fields = $record + ['id' => (int)$id];
            return true;
        }
        public function get($field) { return $this->fields[$field] ?? null; }
        public function set($field, $value): void { $this->fields[$field] = $value; }
        public function getId(): int { return (int)$this->get('id'); }
    }
    class Organization extends DbObject
    {
        public static function tableName(): string { return 'organization'; }
        public static function formatLexiconText($text): string { return $text; }
    }
}

namespace {
    function commonGetCurrentUserId(): int { return 5; }
    function commonCurrentUserIsSiteAdmin(): bool { return $GLOBALS['faqTestSiteAdmin']; }
    function commonCurrentUserCanUseAdminMode($organizationId): bool { return (int)$organizationId === 7; }
    function faqScopeCheck(bool $condition, string $message): void
    {
        if (!$condition) throw new \RuntimeException($message);
    }

    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'dbObject\\')) {
            $file = dirname(__DIR__) . '/class/dbobject/' . strtolower(substr($class, 9)) . '.class.php';
            if (is_file($file)) require_once $file;
        }
    });
    require_once dirname(__DIR__) . '/common/faq_popup_helper.php';

    \dbObject\DbObject::$records = [
        'organization' => [7 => [], 9 => []],
        'holon' => [
            101 => ['IDorganization' => 7, 'IDholon_org' => 101],
            102 => ['IDorganization' => null, 'IDholon_org' => 101],
            201 => ['IDorganization' => 9, 'IDholon_org' => 201],
            202 => ['IDorganization' => null, 'IDholon_org' => 201],
            303 => ['IDorganization' => null, 'IDholon_org' => null],
            304 => ['IDorganization' => null, 'IDholon_org' => 999],
        ],
    ];
    $child = new \dbObject\Holon();
    $child->load(102);
    faqScopeCheck($child->get('IDorganization') === null, 'Reproduce an inherited organization association.');
    $context = ['organizationId' => 7, 'currentHolon' => $child];

    foreach ([true, false] as $siteAdmin) {
        $GLOBALS['faqTestSiteAdmin'] = $siteAdmin;
        foreach ([101, 102] as $holonId) {
            $scope = faqPopupResolveSubmittedScope($context, [
                'IDorganization' => 7, 'IDholon' => $holonId, 'faq_scope_kind' => 'organization',
            ]);
            faqScopeCheck($scope['status'] && $scope['organizationId'] === 7 && $scope['holonId'] === $holonId,
                'Site and organization admins must accept root and inherited space attachments.');
        }
        foreach ([201, 202, 303, 304, 999] as $holonId) {
            $scope = faqPopupResolveSubmittedScope($context, [
                'IDorganization' => 7, 'IDholon' => $holonId, 'faq_scope_kind' => 'organization',
            ]);
            faqScopeCheck(!$scope['status'], 'Foreign, unattached and missing spaces must remain rejected.');
        }
        $scope = faqPopupResolveSubmittedScope($context, [
            'IDorganization' => 7, 'faq_scope_kind' => 'organization',
        ]);
        faqScopeCheck($scope['status'] && $scope['holonId'] === null, 'Organization FAQ without a space must remain valid.');
    }

    foreach ([101 => 7, 102 => 7, 202 => 9, 303 => 0, 304 => 0, 999 => 0] as $holonId => $expected) {
        $faq = new \dbObject\FAQ();
        $faq->set('IDholon', $holonId);
        faqScopeCheck($faq->getResolvedOrganizationId() === $expected, 'Existing FAQ must resolve the same inherited organization.');
    }
    $faq->set('IDorganization', 7);
    faqScopeCheck($faq->getResolvedOrganizationId() === 7, 'Explicit FAQ organization must keep priority.');
    echo "faq_scope_organization_test: OK\n";
}
