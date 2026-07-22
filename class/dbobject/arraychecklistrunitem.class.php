<?php
namespace dbObject;

class ArrayChecklistRunItem extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ChecklistRunItem';
    }

    public function loadForRun($runId)
    {
        $this->exchangeArray([]);
        $runId = (int)$runId;
        if ($runId <= 0) {
            return;
        }
        $this->load([
            'where' => [['field' => 'IDchecklistrun', 'value' => $runId]],
            'orderBy' => [
                ['field' => 'activation_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }

    public function loadPending($dateTime, $limit = 50)
    {
        $this->exchangeArray([]);
        if (!($dateTime instanceof \DateTimeInterface)) {
            $dateTime = new \DateTime((string)$dateTime);
        }
        $this->load([
            'where' => [
                ['field' => 'state', 'value' => ChecklistRunItem::STATE_WAITING],
                ['field' => 'activation_at', 'op' => '<=', 'value' => $dateTime],
            ],
            'orderBy' => [
                ['field' => 'activation_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
            'limit' => max(1, min(500, (int)$limit)),
        ]);
    }
}
?>
