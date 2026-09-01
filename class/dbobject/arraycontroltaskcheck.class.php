<?php
namespace dbObject;

class ArrayControlTaskCheck extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ControlTaskCheck';
    }

    public function loadForTask($taskId, $from = null, $to = null)
    {
        $this->exchangeArray([]);
        $taskId = (int)$taskId;
        if ($taskId <= 0) {
            return;
        }
        $where = [['field' => 'IDcontroltask', 'value' => $taskId]];
        if ($from instanceof \DateTimeInterface) {
            $where[] = ['field' => 'scheduled_for', 'op' => '>=', 'value' => $from];
        }
        if ($to instanceof \DateTimeInterface) {
            $where[] = ['field' => 'scheduled_for', 'op' => '<=', 'value' => $to];
        }
        $this->load([
            'where' => $where,
            'orderBy' => [['field' => 'scheduled_for', 'dir' => 'DESC'], ['field' => 'id', 'dir' => 'DESC']],
        ]);
    }

    public function loadForTaskAndScheduledFor($taskId, \DateTimeInterface $scheduledFor)
    {
        $this->exchangeArray([]);
        $taskId = (int)$taskId;
        if ($taskId <= 0) {
            return;
        }
        $this->load([
            'where' => [
                ['field' => 'IDcontroltask', 'value' => $taskId],
                ['field' => 'scheduled_for', 'value' => $scheduledFor],
            ],
            'limit' => 1,
        ]);
    }

    public function loadForTaskByCheckedAt($taskId, $from = null, $to = null, $limit = 0)
    {
        $this->exchangeArray([]);
        $taskId = (int)$taskId;
        if ($taskId <= 0) {
            return;
        }
        $where = [['field' => 'IDcontroltask', 'value' => $taskId]];
        if ($from instanceof \DateTimeInterface) {
            $where[] = ['field' => 'checked_at', 'op' => '>=', 'value' => $from];
        }
        if ($to instanceof \DateTimeInterface) {
            $where[] = ['field' => 'checked_at', 'op' => '<', 'value' => $to];
        }
        $params = [
            'where' => $where,
            'orderBy' => [['field' => 'checked_at', 'dir' => 'DESC'], ['field' => 'id', 'dir' => 'DESC']],
        ];
        if ((int)$limit > 0) {
            $params['limit'] = (int)$limit;
        }
        $this->load($params);
    }
}
?>
