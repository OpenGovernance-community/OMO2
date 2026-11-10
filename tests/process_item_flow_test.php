<?php
declare(strict_types=1);

namespace dbObject {
    class DbObject
    {
        public function __construct(private array $fields = []) {}
        public function get($field) { return $this->fields[$field] ?? null; }
        public function getId() { return $this->get('id'); }
    }

    class ArrayDbObject extends \ArrayObject {}

    class ArrayChecklistItemDependency extends \ArrayObject
    {
        public static array $fixtures = [];

        public function loadForItem($itemId): void
        {
            $this->loadForItems([(int)$itemId]);
        }

        public function loadForItems($itemIds): void
        {
            $this->exchangeArray(array_values(array_filter(self::$fixtures, static fn ($dependency) =>
                in_array((int)$dependency->get('IDchecklistitem'), $itemIds, true)
            )));
        }
    }
}

namespace {
    use dbObject\ArrayChecklistItem;
    use dbObject\ArrayChecklistItemDependency;
    use dbObject\ChecklistItem;
    use dbObject\ChecklistItemDependency;

    require_once dirname(__DIR__) . '/class/dbobject/checklistitem.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/checklistitemdependency.class.php';
    require_once dirname(__DIR__) . '/class/dbobject/arraychecklistitem.class.php';
    require_once dirname(__DIR__) . '/omo/api/checklist/shared.php';

    function assertFlow(array $items, array $expected, string $message): void
    {
        $actual = array_map(static fn ($row) => [(int)$row['item']->getId(), $row['depth']], omoChecklistBuildItemFlow($items));
        if ($actual !== $expected) {
            throw new RuntimeException($message . ': ' . json_encode($actual));
        }
    }

    $makeItem = static fn (int $id, string $activation, int $delay = 0, string $unit = 'day') => new ChecklistItem([
        'id' => $id, 'activation_type' => $activation, 'delay_value' => $delay, 'delay_unit' => $unit,
    ]);
    $makeDependency = static fn (int $id, int $requiredId, int $delay = 0) => new ChecklistItemDependency([
        'IDchecklistitem' => $id, 'IDchecklistitem_required' => $requiredId, 'delay_value' => $delay, 'delay_unit' => 'day',
    ]);
    ArrayChecklistItemDependency::$fixtures = [
        $makeDependency(4, 1, 30), $makeDependency(5, 4), $makeDependency(6, 1, 1), $makeDependency(7, 2),
    ];
    $items = new ArrayChecklistItem([
        $makeItem(3, 'after_start', 1, 'week'),
        $makeItem(4, 'after_completion'),
        $makeItem(1, 'immediate'),
        $makeItem(2, 'after_start', -2),
        $makeItem(5, 'after_completion'),
        $makeItem(6, 'after_completion'),
        $makeItem(7, 'after_completion'),
        $makeItem(8, 'after_start', 24, 'hour'),
        $makeItem(9, 'immediate'),
    ]);
    // Exercise the real chronological sort before building the display tree.
    $items->sortForExecution();
    assertFlow($items->getArrayCopy(), [
        [1, 0], [6, 1], [4, 1], [5, 2], [9, 0], [2, 0], [7, 1], [8, 0], [3, 0],
    ], 'Immediate branches first, descendants grouped, reference dates still chronological');
    $flow = omoChecklistBuildItemFlow($items->getArrayCopy());
    if ($flow[1]['dependency'] !== ArrayChecklistItemDependency::$fixtures[2]) {
        throw new RuntimeException('The display must retain the dependency used for its label and delay.');
    }

    ArrayChecklistItemDependency::$fixtures = [$makeDependency(1, 2), $makeDependency(2, 1), $makeDependency(3, 99)];
    assertFlow([
        $makeItem(1, 'after_completion'), $makeItem(2, 'after_completion'), $makeItem(3, 'after_completion'),
    ], [[3, 0], [1, 0], [2, 1]], 'Missing references and cycles must not hide or duplicate steps');
    assertFlow([], [], 'Empty processes');
    echo "process_item_flow_test: OK\n";
}
