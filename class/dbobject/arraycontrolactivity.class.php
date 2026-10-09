<?php
namespace dbObject;

class ArrayControlActivity extends ArrayDbObject
{
    public static function objectName()
    {
        return '\\dbObject\\ControlActivity';
    }

    public function loadForContext($organizationId, $holonIds, $activeOnly = true, $includeOrganizationTasks = false, $archivedOnly = false)
    {
        $this->exchangeArray([]);
        $organizationId = (int)$organizationId;
        $holonIds = array_values(array_unique(array_filter(array_map('intval', (array)$holonIds), static function ($id) { return $id > 0; })));
        if ($organizationId <= 0 || (count($holonIds) === 0 && !$includeOrganizationTasks)) { return; }
        $where = [
            ['field' => 'IDorganization', 'value' => $organizationId],
        ];
        $holonFilter = count($holonIds) > 0
            ? [['field' => 'IDholon', 'op' => 'in', 'value' => $holonIds]]
            : [];
        if ($includeOrganizationTasks) {
            $holonFilter[] = ['field' => 'IDholon', 'op' => 'is null'];
        }
        if ($activeOnly || $archivedOnly) { $where[] = ['field' => 'active', 'value' => $archivedOnly ? 0 : 1]; }
        if ($archivedOnly) { $where[] = ['field' => 'archived_at', 'op' => 'is not null']; }
        $this->load([
            'where' => $where,
            'whereAny' => $holonFilter,
            'orderBy' => [['field' => 'position', 'dir' => 'ASC'], ['field' => 'title', 'dir' => 'ASC'], ['field' => 'id', 'dir' => 'ASC']],
            'hydrate' => true,
        ]);
        $this->filterProjectOnlyTasks();
    }

    protected function filterProjectOnlyTasks(): void
    {
        $candidates = [];
        foreach ($this as $task) {
            if ((int)$task->get('project_visible_in_holon') !== 1) $candidates[] = (int)$task->getId();
        }
        if (!$candidates) return;
        $links = new ArrayProjectRecurringTask();
        $links->load(['where' => [['field' => 'IDrecurringtask', 'op' => 'in', 'value' => $candidates]], 'hydrate' => ['IDrecurringtask']]);
        $linkCounts = [];
        foreach ($links as $link) {
            $id = (int)$link->get('IDrecurringtask');
            $linkCounts[$id] = ($linkCounts[$id] ?? 0) + 1;
        }
        $this->exchangeArray(array_values(array_filter($this->getArrayCopy(), static function ($task) use ($linkCounts) {
            return ($linkCounts[(int)$task->getId()] ?? 0) !== 1;
        })));
    }
}
?>
