<?php
namespace dbObject;

class ArrayControlTask extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ControlTask';
    }

    public function loadForList($listId, $activeOnly = true)
    {
        $this->exchangeArray([]);
        $listId = (int)$listId;
        if ($listId <= 0) {
            return;
        }
        $where = [['field' => 'IDcontrollist', 'value' => $listId]];
        if ($activeOnly) {
            $where[] = ['field' => 'active', 'value' => 1];
        }
        $this->load([
            'where' => $where,
            'orderBy' => [['field' => 'position', 'dir' => 'ASC'], ['field' => 'id', 'dir' => 'ASC']],
        ]);
    }
}
?>
