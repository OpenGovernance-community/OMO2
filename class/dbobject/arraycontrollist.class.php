<?php
namespace dbObject;

class ArrayControlList extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ControlList';
    }

    public function loadForOrganization($organizationId, $activeOnly = true)
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return;
        }
        $where = [['field' => 'IDorganization', 'value' => $organizationId]];
        if ($activeOnly) {
            $where[] = ['field' => 'active', 'value' => 1];
        }
        $this->load([
            'where' => $where,
            'orderBy' => [['field' => 'title', 'dir' => 'ASC'], ['field' => 'id', 'dir' => 'ASC']],
        ]);
    }

    public function loadForContext($organizationId, $holonId, $scope = 'contextual', array $scopeHolonIds = [])
    {
        $this->loadForOrganization((int)$organizationId, true);
        $scope = trim(mb_strtolower((string)$scope, 'UTF-8'));
        $allowedHolonIds = $scope === 'contextual'
            ? [(int)$holonId]
            : array_values(array_unique(array_filter(array_map('intval', $scopeHolonIds), static function ($id) {
                return $id > 0;
            })));
        $allowed = count($allowedHolonIds) > 0 ? array_fill_keys($allowedHolonIds, true) : [];
        $matches = [];
        foreach ($this as $list) {
            if ($list instanceof ControlList && isset($allowed[(int)$list->get('IDholon')])) {
                $matches[] = $list;
            }
        }
        $this->exchangeArray($matches);
    }
}
?>
