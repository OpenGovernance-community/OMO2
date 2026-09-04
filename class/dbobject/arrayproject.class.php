<?php
namespace dbObject;

class ArrayProject extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\Project';
    }

    public static function fetchTitlesForOrganization($organizationId)
    {
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return [];
        }

        $rows = \dbObject\DbObject::fetchAll(
            "SELECT id, title
            FROM project
            WHERE IDorganization = :organization_id
              AND active = 1
              AND project_kind = :project_kind
            ORDER BY id ASC",
            [
                'organization_id' => $organizationId,
                'project_kind' => Project::KIND_STANDARD,
            ]
        );

        if (!is_array($rows)) {
            return [];
        }

        $titles = [];
        foreach ($rows as $row) {
            $projectId = (int)($row['id'] ?? 0);
            if ($projectId > 0) {
                $titles[$projectId] = trim((string)($row['title'] ?? ''));
            }
        }

        return $titles;
    }

    public function loadForOrganization($organizationId, $activeOnly = true, $projectKind = Project::KIND_STANDARD, $hydrate = false)
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return;
        }

        $where = [
            ['field' => 'IDorganization', 'value' => $organizationId],
            ['field' => 'project_kind', 'value' => Project::normalizeKind($projectKind)],
        ];
        if ($activeOnly) {
            $where[] = ['field' => 'active', 'value' => 1];
        }

        $params = [
            'where' => $where,
            'orderBy' => [
                ['field' => 'calculated_importance', 'dir' => 'DESC'],
                ['field' => 'created_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ];
        if ($hydrate !== false) {
            $params['hydrate'] = $hydrate;
        }

        $this->load($params);
    }

    public function loadArchivedForOrganization($organizationId, $projectKind = Project::KIND_STANDARD)
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        if ($organizationId <= 0) {
            return;
        }

        $this->load([
            'where' => [
                ['field' => 'IDorganization', 'value' => $organizationId],
                ['field' => 'project_kind', 'value' => Project::normalizeKind($projectKind)],
                ['field' => 'active', 'value' => 0],
            ],
            'orderBy' => [
                ['field' => 'archived_at', 'dir' => 'DESC'],
                ['field' => 'updated_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);

    }

    public function loadForParent($parentId, $activeOnly = true, $projectKind = Project::KIND_STANDARD)
    {
        $this->exchangeArray([]);
        $parentId = (int)$parentId;
        if ($parentId <= 0) {
            return;
        }

        $where = [
            ['field' => 'IDproject_parent', 'value' => $parentId],
            ['field' => 'project_kind', 'value' => Project::normalizeKind($projectKind)],
        ];
        if ($activeOnly) {
            $where[] = ['field' => 'active', 'value' => 1];
        }

        $this->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'calculated_importance', 'dir' => 'DESC'],
                ['field' => 'created_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
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
            ['field' => 'project_kind', 'value' => Project::KIND_STANDARD],
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
        } else {
            $where[] = $holonId > 0
                ? ['field' => 'IDholon', 'value' => $holonId]
                : ['field' => 'IDholon', 'op' => 'is null'];
        }

        $this->load([
            'where' => $where,
            'orderBy' => [
                ['field' => 'calculated_importance', 'dir' => 'DESC'],
                ['field' => 'created_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);
    }

    public function loadForWorkTimeHolon($organizationId, $holonId, $status = Project::STATUS_IN_PROGRESS)
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        $holonId = (int)$holonId;
        $status = Project::normalizeStatus($status);
        if ($organizationId <= 0 || $holonId <= 0 || !in_array($status, Project::getWorkTimeStatuses(), true)) {
            return;
        }

        $this->load([
            'hydrate' => ['title', 'priority'],
            'where' => [
                ['field' => 'IDorganization', 'value' => $organizationId],
                ['field' => 'IDholon', 'value' => $holonId],
                ['field' => 'status', 'value' => $status],
                ['field' => 'active', 'value' => 1],
                ['field' => 'project_kind', 'value' => Project::KIND_STANDARD],
            ],
            'orderBy' => [
                ['field' => 'calculated_importance', 'dir' => 'DESC'],
                ['field' => 'created_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);

        $this->uasort(static function (Project $left, Project $right): int {
            $leftPriority = (int)$left->get('priority');
            $rightPriority = (int)$right->get('priority');
            $leftRank = $leftPriority > 0 ? $leftPriority : PHP_INT_MAX;
            $rightRank = $rightPriority > 0 ? $rightPriority : PHP_INT_MAX;
            if ($leftRank !== $rightRank) {
                return $leftRank <=> $rightRank;
            }

            return (int)$left->getId() <=> (int)$right->getId();
        });
    }

    public function loadTemplatesForOrganization($organizationId, $activeOnly = true)
    {
        $this->loadForOrganization($organizationId, $activeOnly, Project::KIND_CHECKLIST_TEMPLATE);
    }
}
?>
