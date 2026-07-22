<?php
namespace dbObject;

class ArrayChecklistRun extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ChecklistRun';
    }

    public function loadForChecklist($checklistId)
    {
        $this->exchangeArray([]);
        $checklistId = (int)$checklistId;
        if ($checklistId <= 0) {
            return;
        }
        $this->load([
            'where' => [['field' => 'IDchecklist', 'value' => $checklistId]],
            'orderBy' => [
                ['field' => 'created_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);
    }

    public function loadOpenForChecklist($checklistId)
    {
        $this->exchangeArray([]);
        $checklistId = (int)$checklistId;
        if ($checklistId <= 0) {
            return;
        }
        $this->load([
            'where' => [
                ['field' => 'IDchecklist', 'value' => $checklistId],
                ['field' => 'status', 'value' => ChecklistRun::STATUS_RUNNING],
            ],
            'orderBy' => [
                ['field' => 'created_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);
    }

    public function loadRunning($limit = 100)
    {
        $this->exchangeArray([]);
        $this->load([
            'where' => [['field' => 'status', 'value' => ChecklistRun::STATUS_RUNNING]],
            'orderBy' => [
                ['field' => 'updated_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
            'limit' => max(1, min(500, (int)$limit)),
        ]);
    }
}
?>
