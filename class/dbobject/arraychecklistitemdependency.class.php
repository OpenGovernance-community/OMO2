<?php
namespace dbObject;

class ArrayChecklistItemDependency extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ChecklistItemDependency';
    }

    public function loadForItem($itemId)
    {
        $this->exchangeArray([]);
        $itemId = (int)$itemId;
        if ($itemId <= 0) {
            return;
        }
        $this->load([
            'where' => [['field' => 'IDchecklistitem', 'value' => $itemId]],
            'orderBy' => [['field' => 'id', 'dir' => 'ASC']],
        ]);
    }

    public function loadForRequiredItem($itemId)
    {
        $this->exchangeArray([]);
        $itemId = (int)$itemId;
        if ($itemId <= 0) {
            return;
        }
        $this->load([
            'where' => [['field' => 'IDchecklistitem_required', 'value' => $itemId]],
            'orderBy' => [['field' => 'id', 'dir' => 'ASC']],
        ]);
    }

    public function loadForItems(array $itemIds)
    {
        $this->exchangeArray([]);
        $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds), static function ($itemId) {
            return $itemId > 0;
        })));
        if (count($itemIds) === 0) {
            return;
        }
        $this->load([
            'where' => [[
                'field' => 'IDchecklistitem',
                'op' => 'in',
                'value' => $itemIds,
            ]],
            'orderBy' => [['field' => 'id', 'dir' => 'ASC']],
        ]);
    }
}
?>
