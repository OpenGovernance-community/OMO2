<?php
namespace dbObject;

class ArrayStatIndicator extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\StatIndicator';
    }

    public function loadForContext($organizationId, $holonId = 0, $scope = 'contextual', array $descendantHolonIds = [])
    {
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;
        $scope = trim(mb_strtolower((string)$scope, 'UTF-8'));
        $this->exchangeArray([]);

        if ($organizationId <= 0) {
            return;
        }

        $params = [
            'where' => [
                ['field' => 'IDorganization', 'value' => $organizationId],
                ['field' => 'active', 'value' => 1],
            ],
            'orderBy' => [
                ['field' => 'name', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ];

        if ($scope === 'children' || $scope === 'descendants') {
            $descendantHolonIds = array_values(array_unique(array_filter(array_map('intval', $descendantHolonIds), static function ($candidateId) {
                return $candidateId > 0;
            })));
            $params['where'][] = ['field' => 'IDholon', 'op' => 'in', 'value' => $descendantHolonIds];
        } else {
            $params['where'][] = $holonId > 0
                ? ['field' => 'IDholon', 'value' => $holonId]
                : ['field' => 'IDholon', 'op' => 'is null'];
        }

        $loaded = new self();
        $params['hydrate'] = true;
        $loaded->load($params);

        foreach ($loaded as $indicator) {
            if ($indicator instanceof \dbObject\StatIndicator && !$indicator->isHiddenFromCatalog() && $indicator->canView()) {
                $this[] = $indicator;
            }
        }
    }

    public function loadForOrganization($organizationId)
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return;
        }

        $loaded = new self();
        $loaded->load([
            'where' => [
                ['field' => 'IDorganization', 'value' => $organizationId],
                ['field' => 'active', 'value' => 1],
            ],
            'hydrate' => true,
            'orderBy' => [
                ['field' => 'name', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);

        foreach ($loaded as $indicator) {
            if ($indicator instanceof \dbObject\StatIndicator && $indicator->canView()) {
                $this[] = $indicator;
            }
        }
    }
}

?>
