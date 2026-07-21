<?php
namespace dbObject;

class ArrayProject extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\Project';
    }

    public function loadForOrganization($organizationId, $activeOnly = true)
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return;
        }

        $where = [
            ['field' => 'IDorganization', 'value' => $organizationId],
        ];
        if ($activeOnly) {
            $where[] = ['field' => 'active', 'value' => 1];
        }

        $this->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'created_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);
    }

    public function loadForParent($parentId, $activeOnly = true)
    {
        $this->exchangeArray([]);
        $parentId = (int)$parentId;
        if ($parentId <= 0) {
            return;
        }

        $where = [
            ['field' => 'IDproject_parent', 'value' => $parentId],
        ];
        if ($activeOnly) {
            $where[] = ['field' => 'active', 'value' => 1];
        }

        $this->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'created_at', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ]);
    }

    public function loadForContext($organizationId, $holonId = 0, $scope = 'contextual', array $descendantHolonIds = [])
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;
        $scope = trim(mb_strtolower((string)$scope, 'UTF-8'));
        if ($scope === 'global') {
            $scope = 'descendants';
        }
        if ($organizationId <= 0) {
            return;
        }

        $where = [
            ['field' => 'IDorganization', 'value' => $organizationId],
            ['field' => 'active', 'value' => 1],
        ];

        if ($scope === 'descendants') {
            $holonIds = array_values(array_unique(array_filter(array_merge(
                [$holonId],
                array_map('intval', $descendantHolonIds)
            ), static function ($candidateHolonId) {
                return (int)$candidateHolonId > 0;
            })));

            if (count($holonIds) === 0) {
                return;
            }

            $where[] = ['field' => 'IDholon', 'op' => 'in', 'value' => $holonIds];
        } elseif ($scope === 'children') {
            $holonIds = array_values(array_unique(array_filter(array_map('intval', $descendantHolonIds), static function ($candidateHolonId) {
                return (int)$candidateHolonId > 0;
            })));

            if (count($holonIds) === 0) {
                return;
            }

            $where[] = ['field' => 'IDholon', 'op' => 'in', 'value' => $holonIds];
        } else {
            $where[] = $holonId > 0
                ? ['field' => 'IDholon', 'value' => $holonId]
                : ['field' => 'IDholon', 'op' => 'is null'];
        }

        $this->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'created_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);
    }
}
?>
