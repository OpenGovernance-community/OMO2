<?php
namespace dbObject;

class ArrayStatIndicatorGroup extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\StatIndicatorGroup';
    }

    public function loadForContext($organizationId, $holonId = 0, $scope = 'contextual', array $descendantHolonIds = [])
    {
        $this->exchangeArray([]);
        $where = [
            ['field' => 'IDorganization', 'value' => (int)$organizationId],
            ['field' => 'active', 'value' => 1],
        ];
        if ($scope === 'descendants') {
            $ids = array_values(array_filter(array_map('intval', $descendantHolonIds)));
            $where[] = ['field' => 'IDholon', 'op' => 'in', 'value' => $ids];
        } elseif ($scope !== 'global') {
            $where[] = (int)$holonId > 0
                ? ['field' => 'IDholon', 'value' => (int)$holonId]
                : ['field' => 'IDholon', 'op' => 'is null'];
        }
        $this->load(['where' => $where, 'orderBy' => [['field' => 'name', 'dir' => 'ASC']]]);
    }
}
?>
