<?php
namespace dbObject;

class ArrayChecklist extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\Checklist';
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
            'orderBy' => [
                ['field' => 'updated_at', 'dir' => 'DESC'],
                ['field' => 'id', 'dir' => 'DESC'],
            ],
        ]);
    }

    public function loadForContext($organizationId, $holonId, $scope = 'contextual', array $scopeHolonIds = [])
    {
        $this->loadForOrganization((int)$organizationId, true);

        $holonId = (int)$holonId;
        $scope = trim(mb_strtolower((string)$scope, 'UTF-8'));
        $allowedHolonIds = $scope === 'contextual'
            ? [$holonId]
            : array_values(array_unique(array_filter(array_map('intval', $scopeHolonIds), static function ($candidateId) {
                return (int)$candidateId > 0;
            })));
        $allowedHolonMap = count($allowedHolonIds) > 0 ? array_fill_keys($allowedHolonIds, true) : [];

        $matches = [];
        foreach ($this as $checklist) {
            if (!($checklist instanceof Checklist)) {
                continue;
            }
            $templateRoot = $checklist->getTemplateRoot();
            $templateHolonId = $templateRoot instanceof Project ? (int)$templateRoot->get('IDholon') : 0;
            if (isset($allowedHolonMap[$templateHolonId])) {
                $matches[] = $checklist;
            }
        }
        $this->exchangeArray($matches);
    }
}
?>
