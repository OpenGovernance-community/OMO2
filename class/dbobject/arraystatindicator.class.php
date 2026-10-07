<?php
namespace dbObject;

class ArrayStatIndicator extends ArrayDbObject
{
    /** Only the indicators already present in visible points need value controls at startup. */
    public static function editableIdsReferencedByPvPoints(int $organizationId, iterable $points, bool $isPvEditor): array
    {
        if ($organizationId <= 0) return [];
        $ids = [];
        foreach ($points as $point) {
            if (!$point instanceof DocumentPvPoint) continue;
            preg_match_all('/data-omo-indicator-id\s*=\s*["\']([0-9]+)["\']/i', (string)$point->get('content'), $matches);
            foreach ($matches[1] as $id) if ((int)$id > 0) $ids[(int)$id] = (int)$id;
        }
        if ($ids === []) return [];
        $allowed = [];
        foreach (array_chunk(array_values($ids), 500) as $chunk) {
            $indicators = new self();
            $indicators->loadHydrated(['where' => [
                ['field' => 'id', 'op' => 'in', 'value' => $chunk],
                ['field' => 'IDorganization', 'value' => $organizationId],
                ['field' => 'active', 'value' => 1],
            ]]);
            foreach ($indicators as $indicator) {
                if (!$indicator->isHiddenFromCatalog() && $indicator->canView() && ($isPvEditor || $indicator->canEdit())) {
                    $allowed[] = (int)$indicator->getId();
                }
            }
        }
        return $allowed;
    }

    public static function objectName()
    {
        return '\\dbObject\\StatIndicator';
    }

    public function loadForContext($organizationId, $holonId = 0, $scope = 'contextual', array $descendantHolonIds = [], $includeOrganizationItems = false, $archivedOnly = false)
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
                ['field' => 'active', 'value' => $archivedOnly ? 0 : 1],
            ],
            'orderBy' => [
                ['field' => 'name', 'dir' => 'ASC'],
                ['field' => 'id', 'dir' => 'ASC'],
            ],
        ];

        if ($archivedOnly) {
            $params['where'][] = ['field' => 'archived_at', 'op' => 'is not null'];
        }

        if ($scope === 'children' || $scope === 'descendants') {
            $descendantHolonIds = array_values(array_unique(array_filter(array_map('intval', $descendantHolonIds), static function ($candidateId) {
                return $candidateId > 0;
            })));
            if ($descendantHolonIds === [] && !$includeOrganizationItems) {
                return;
            }
            if ($descendantHolonIds !== []) {
                $params[$includeOrganizationItems ? 'whereAny' : 'where'][] = ['field' => 'IDholon', 'op' => 'in', 'value' => $descendantHolonIds];
            }
        } else {
            $params[$includeOrganizationItems ? 'whereAny' : 'where'][] = $holonId > 0
                ? ['field' => 'IDholon', 'value' => $holonId]
                : ['field' => 'IDholon', 'op' => 'is null'];
        }
        if ($includeOrganizationItems) {
            $params['whereAny'][] = ['field' => 'IDholon', 'op' => 'is null'];
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
