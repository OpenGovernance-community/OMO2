<?php
namespace dbObject;

class ArrayStatIndicatorGroupItem extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\StatIndicatorGroupItem';
    }

    public function loadForGroup($groupId)
    {
        $this->exchangeArray([]);
        $this->load([
            'where' => [['field' => 'IDstatindicatorgroup', 'value' => (int)$groupId]],
            'orderBy' => [
                ['field' => 'position', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }
}
?>
