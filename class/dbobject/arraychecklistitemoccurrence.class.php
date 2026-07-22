<?php
namespace dbObject;

class ArrayChecklistItemOccurrence extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ChecklistItemOccurrence';
    }

    public function loadForItemAndScheduledFor($itemId, \DateTimeInterface $scheduledFor)
    {
        $this->exchangeArray([]);
        $itemId = (int)$itemId;
        if ($itemId <= 0) {
            return;
        }
        $this->load([
            'where' => [
                ['field' => 'IDchecklistitem', 'value' => $itemId],
                ['field' => 'scheduled_for', 'value' => $scheduledFor],
            ],
            'limit' => 1,
        ]);
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
            'orderBy' => [
                ['field' => 'scheduled_for', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);
    }
}
?>
