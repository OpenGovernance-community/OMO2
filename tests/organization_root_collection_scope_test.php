<?php
declare(strict_types=1);

namespace dbObject {
    class ArrayDbObject extends \ArrayObject
    {
        public static array $lastLoad = [];
        public function load($params = null): void { self::$lastLoad = $params; }
    }

    class Project { public const KIND_STANDARD = 'standard'; }
    class StatIndicator {}
    class Document { public static function getCollectionHydrationFields(): array { return []; } }
}

namespace {
    require_once dirname(__DIR__) . '/class/dbObject/arrayproject.class.php';
    require_once dirname(__DIR__) . '/class/dbObject/arraystatindicator.class.php';
    require_once dirname(__DIR__) . '/class/dbObject/arraystatindicatorgroup.class.php';
    require_once dirname(__DIR__) . '/class/dbObject/arraystatindicatorimport.class.php';
    require_once dirname(__DIR__) . '/class/dbObject/arraydocument.class.php';

    function assertRootFilter(array $params, int $organizationId, string $message): void
    {
        if (($params['where'][0] ?? null) !== ['field' => 'IDorganization', 'value' => $organizationId]
            || !in_array(['field' => 'IDholon', 'op' => 'is null'], $params['whereAny'] ?? [], true)) {
            throw new \RuntimeException($message);
        }
    }

    $projects = new \dbObject\ArrayProject();
    $projects->loadForContext(42, 9, 'contextual', [], true);
    assertRootFilter(\dbObject\ArrayDbObject::$lastLoad, 42, 'Root project list must include organization projects.');
    if (!in_array(['field' => 'IDholon', 'value' => 9], \dbObject\ArrayDbObject::$lastLoad['whereAny'], true)) {
        throw new \RuntimeException('Root project list must retain projects attached to the root holon.');
    }
    $projects->loadForContext(42, 10, 'contextual');
    if (isset(\dbObject\ArrayDbObject::$lastLoad['whereAny'])) {
        throw new \RuntimeException('Child project list must exclude organization projects.');
    }

    $collections = [
        new \dbObject\ArrayStatIndicator(),
        new \dbObject\ArrayStatIndicatorGroup(),
        new \dbObject\ArrayStatIndicatorImport(),
    ];
    foreach ($collections as $collection) {
        $collection->loadForContext(42, 9, 'descendants', [10], true);
        assertRootFilter(\dbObject\ArrayDbObject::$lastLoad, 42, get_class($collection) . ' must include organization items at root.');
        if (!in_array(['field' => 'IDholon', 'op' => 'in', 'value' => [10]], \dbObject\ArrayDbObject::$lastLoad['whereAny'], true)) {
            throw new \RuntimeException(get_class($collection) . ' must retain descendant items.');
        }
    }

    $documents = new class extends \dbObject\ArrayDocument {
        protected function filterProjectDocumentsNotVisibleInHolon(): void {}
        public function filterVisibleForCurrentViewer($organizationId = 0, ?array $ruleMap = null) { return []; }
    };
    $documents->loadVisibleForOrganizationContext(42, 9, 'children', [], true);
    assertRootFilter(\dbObject\ArrayDbObject::$lastLoad, 42, 'Root document list must include organization documents with no child holons.');

    echo "organization_root_collection_scope_test: OK\n";
}
