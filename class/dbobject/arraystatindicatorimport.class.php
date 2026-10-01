<?php
namespace dbObject;

class ArrayStatIndicatorImport extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\StatIndicatorImport';
    }

    public function loadForContext($organizationId, $holonId = 0, $scope = 'contextual', array $descendantHolonIds = [], $includeOrganizationItems = false)
    {
        $this->exchangeArray([]);
        $where = [
            ['field' => 'IDorganization', 'value' => (int)$organizationId],
            ['field' => 'active', 'value' => 1],
        ];
        $holonFilter = [];
        if ($scope === 'children' || $scope === 'descendants') {
            $ids = array_values(array_filter(array_map('intval', $descendantHolonIds)));
            if ($ids !== []) {
                $holonFilter[] = ['field' => 'IDholon', 'op' => 'in', 'value' => $ids];
            }
        } else {
            $holonFilter[] = (int)$holonId > 0
                ? ['field' => 'IDholon', 'value' => (int)$holonId]
                : ['field' => 'IDholon', 'op' => 'is null'];
        }
        if ($includeOrganizationItems) {
            $holonFilter[] = ['field' => 'IDholon', 'op' => 'is null'];
        }
        if ($holonFilter === [] || (int)$organizationId <= 0) {
            return;
        }
        $params = ['where' => $where, 'orderBy' => [['field' => 'id', 'dir' => 'ASC']]];
        if ($includeOrganizationItems) {
            $params['whereAny'] = $holonFilter;
        } else {
            $params['where'][] = $holonFilter[0];
        }
        $this->load($params);
    }
}
?>
