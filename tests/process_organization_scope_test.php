<?php
declare(strict_types=1);

namespace dbObject {
    class ArrayDbObject extends \ArrayObject {}

    class Holon
    {
        public function canViewDetail(): bool { return true; }
        public function isAllowed($key, $cache, $userId): bool { return false; }
    }

    class Organization
    {
        public function load($id): bool { return (int)$id === 42; }
        public function getId(): int { return 42; }
        public function canViewDetail(): bool { return true; }
        public function getEnabledStructuralRootHolon(): ?Holon { return null; }
    }

    class Project
    {
        public function __construct(private ?int $holonId = null) {}
        public function get($field) { return $field === 'IDholon' ? $this->holonId : null; }
        public function getHolon(): ?Holon { return null; }
    }

    class ChecklistItemRecurrence
    {
        public function get($field) { return ['enabled' => 1, 'frequency' => 'weekly', 'schedule' => '1'][$field] ?? null; }
    }

    class ChecklistItem
    {
        public function __construct(private Project $project) {}
        public function getProjectTemplate(): Project { return $this->project; }
        public function getRecurrence(): ChecklistItemRecurrence { return new ChecklistItemRecurrence(); }
    }

    class RecurrenceSchedule
    {
        public static function normalizeFrequency($value): ?string { return $value === 'weekly' ? $value : null; }
        public static function normalizeSchedule($frequency, $value): ?string { return $frequency === 'weekly' && $value === '1' ? $value : null; }
    }

    class Checklist
    {
        public const STATUS_PUBLISHED = 'published';
        public function __construct(private Project $root) {}
        public function getTemplateRoot(): Project { return $this->root; }
        public function getOrganization(): Organization { return new Organization(); }
        public function getHolon(): ?Holon { return null; }
        public function get($field) { return ['IDorganization' => 42, 'status' => 'published'][$field] ?? null; }
        public static function normalizeStatus($status): string { return (string)$status; }
    }

    class ChecklistTrigger
    {
        public const TYPE_MANUAL = 'manual';
        public function get($field) { return ['trigger_type' => 'manual', 'enabled' => 1][$field] ?? null; }
        public static function normalizeTriggerType($value): string { return (string)$value; }
    }

    class Permission
    {
        public static array $grants = [];
        public static function userCanInOrganization(string $key, int $organizationId, int $userId): bool
        {
            return $organizationId === 42 && $userId === 7 && !empty(self::$grants[$key]);
        }
    }
}

namespace {
    function commonGetCurrentUserId(): int { return 7; }
    function processOrganizationAssert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    require_once dirname(__DIR__) . '/class/dbObject/arraychecklist.class.php';
    require_once dirname(__DIR__) . '/omo/api/checklist/shared.php';

    $context = omoChecklistResolveContext(42);
    processOrganizationAssert($context['status'] && $context['currentHolon'] === null, 'Organization context must allow a missing holon.');

    $organizationProcess = new \dbObject\Checklist(new \dbObject\Project());
    $orphanProcess = new \dbObject\Checklist(new \dbObject\Project(99));
    $processes = new class($organizationProcess, $orphanProcess) extends \dbObject\ArrayChecklist {
        public function __construct(private \dbObject\Checklist $organizationProcess, private \dbObject\Checklist $orphanProcess) {}
        public function loadForOrganization($organizationId, $activeOnly = true, $hydrate = false)
        {
            $this->exchangeArray([$this->organizationProcess, $this->orphanProcess]);
        }
    };
    $processes->loadForContext(42, 0);
    processOrganizationAssert(count($processes) === 1 && $processes[0] === $organizationProcess, 'Only organization processes must appear without a holon.');
    $processes->loadForContext(42, 99);
    processOrganizationAssert(count($processes) === 1 && $processes[0] === $orphanProcess, 'Holon context must retain its own processes.');
    $processes->loadForContext(42, 99, 'contextual', [], true);
    processOrganizationAssert(count($processes) === 2, 'Root holon must include organization processes alongside its own.');

    \dbObject\Permission::$grants = [
        'CAN_CREATE_PROCESS' => true,
        'CAN_EDIT_PROCESS' => true,
        'CAN_CREATE_PROJECT' => true,
        'CAN_CREATE_RECURRING_TASK' => true,
    ];
    processOrganizationAssert(omoChecklistCanCreateContext($context), 'Creation must use organization permission.');
    processOrganizationAssert(omoChecklistCanView($organizationProcess), 'Organization process must be visible.');
    processOrganizationAssert(omoChecklistCanManage($organizationProcess), 'Edit must use organization permission.');
    processOrganizationAssert(!omoChecklistCanDelete($organizationProcess), 'Edit must not imply delete.');
    processOrganizationAssert(omoChecklistCanActivate($organizationProcess, new \dbObject\ChecklistTrigger()), 'Project creation must use organization permission.');
    processOrganizationAssert(
        omoChecklistCanConvertItemToActivity($organizationProcess, new \dbObject\ChecklistItem(new \dbObject\Project())),
        'Organization process item must convert to an organization recurring task.'
    );
    processOrganizationAssert(!omoChecklistCanView($orphanProcess), 'A missing referenced holon must not become an organization process.');

    echo "process_organization_scope_test: OK\n";
}
