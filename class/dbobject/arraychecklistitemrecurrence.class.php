<?php
namespace dbObject;

class ArrayChecklistItemRecurrence extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ChecklistItemRecurrence';
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
            'limit' => 1,
        ]);
    }

    public function loadDue($dateTime, $limit = 50)
    {
        $this->exchangeArray([]);
        if (!($dateTime instanceof \DateTimeInterface)) {
            $dateTime = new \DateTime((string)$dateTime);
        }
        $this->load([
            'where' => [
                ['field' => 'enabled', 'value' => 1],
                ['field' => 'next_trigger_at', 'op' => '<=', 'value' => $dateTime],
            ],
            'orderBy' => [
                ['field' => 'next_trigger_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
            'limit' => max(1, min(500, (int)$limit)),
        ]);
    }
}
?>
