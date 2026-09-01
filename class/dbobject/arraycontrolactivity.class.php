<?php
namespace dbObject;

class ArrayControlActivity extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ControlActivity';
    }

    public function loadForContext($organizationId, $holonIds, $activeOnly = true)
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        $holonIds = array_values(array_unique(array_filter(array_map('intval', (array)$holonIds), static function ($id) { return $id > 0; })));
        if ($organizationId <= 0 || count($holonIds) === 0) { return; }
        $where = [
            ['field' => 'IDorganization', 'value' => $organizationId],
            ['field' => 'IDholon', 'op' => 'in', 'value' => $holonIds],
        ];
        if ($activeOnly) { $where[] = ['field' => 'active', 'value' => 1]; }
        $this->load([
            'where' => $where,
            'orderBy' => [['field' => 'position', 'dir' => 'ASC'], ['field' => 'title', 'dir' => 'ASC'], ['field' => 'id', 'dir' => 'ASC']],
        ]);
    }
}
?>
