<?php
namespace dbObject;

class ArrayProjectUser extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ProjectUser';
    }

    public function loadForProject($projectId, $activeOnly = true)
    {
        $this->exchangeArray([]);
        $projectId = (int)$projectId;
        if ($projectId <= 0) {
            return;
        }

        $where = [
            ['field' => 'IDproject', 'value' => $projectId],
        ];
        if ($activeOnly) {
            $where[] = ['field' => 'active', 'value' => 1];
        }

        $this->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'datecreation', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }

    public function loadForUser($userId, $activeOnly = true)
    {
        $this->exchangeArray([]);
        $userId = (int)$userId;
        if ($userId <= 0) {
            return;
        }

        $where = [
            ['field' => 'IDuser', 'value' => $userId],
        ];
        if ($activeOnly) {
            $where[] = ['field' => 'active', 'value' => 1];
        }

        $this->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'datecreation', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);
    }
}
?>
