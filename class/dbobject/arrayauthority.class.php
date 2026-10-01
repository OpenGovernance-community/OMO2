<?php
namespace dbObject;

class ArrayAuthority extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\Authority';
    }

    public function loadForHolon($holonId)
    {
        $this->exchangeArray([]);
        $holonId = (int)$holonId;
        if ($holonId <= 0) {
            return;
        }

        $this->load([
            'where' => [['field' => 'IDholon', 'value' => $holonId]],
            'orderBy' => [
                ['field' => 'label', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }

    public function loadForParent($parentId)
    {
        $this->exchangeArray([]);
        $parentId = (int)$parentId;
        if ($parentId <= 0) {
            return;
        }

        $this->load([
            'where' => [['field' => 'IDauthority_parent', 'value' => $parentId]],
            'orderBy' => [
                ['field' => 'label', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }
}

?>
