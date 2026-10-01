<?php
namespace dbObject;

class ArrayChecklistTrigger extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ChecklistTrigger';
    }

    public function loadForChecklist($checklistId, $enabledOnly = false)
    {
        $this->exchangeArray([]);
        $checklistId = (int)$checklistId;
        if ($checklistId <= 0) {
            return;
        }
        $where = [['field' => 'IDchecklist', 'value' => $checklistId]];
        if ($enabledOnly) {
            $where[] = ['field' => 'enabled', 'value' => 1];
        }
        $this->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'created_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }

    public function loadDue($dateTime)
    {
        $this->exchangeArray([]);
        if (!($dateTime instanceof \DateTimeInterface)) {
            $dateTime = new \DateTime((string)$dateTime);
        }
        $this->load([
            'where' => [
                ['field' => 'trigger_type', 'value' => ChecklistTrigger::TYPE_SCHEDULED],
                ['field' => 'enabled', 'value' => 1],
                ['field' => 'next_trigger_at', 'op' => '<=', 'value' => $dateTime],
            ],
            'orderBy' => [
                ['field' => 'next_trigger_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }
}
?>
