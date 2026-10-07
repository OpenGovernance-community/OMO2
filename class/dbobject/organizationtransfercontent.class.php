<?php
namespace dbObject;

/** OMO 2 additions kept separate from the legacy OMO 1 conversion. */
class OrganizationTransferContent
{
    private static function collection(string $class, array $where): array
    {
        $items = new $class();
        $items->load(['where' => $where, 'orderBy' => [['field' => 'id', 'dir' => 'ASC']]]);
        return iterator_to_array($items, false);
    }

    private static function date($value): ?string
    {
        return $value instanceof \DateTimeInterface ? $value->format('c') : null;
    }

    private static function save(DbObject $object): void
    {
        $result = $object->save();
        if (empty($result['status'])) { throw new \RuntimeException('Restauration impossible : '.get_class($object)); }
    }

    private static function points($owner): array
    {
        $result = [];
        foreach ($owner->getReferencePoints() as $point) {
            $result[] = ['positionPercent' => $point->get('position_percent'), 'value' => $point->get('value'), 'pointAt' => self::date($point->get('point_at'))];
        }
        return $result;
    }

    public static function augment(array $payload, Organization $organization): array
    {
        $organizationId = (int)$organization->getId();
        foreach ($payload['modules']['members']['records'] as &$record) {
            $membership = new UserOrganization();
            if (!$membership->load([['IDorganization', $organizationId], ['IDuser', (int)$record['sourceId']]])) { continue; }
            $record['image'] = $membership->getProfilePhotoUrl();
            $record['phone'] = $membership->getScopedPhone();
            $record['skills'] = [];
            $skills = self::collection(ArrayUserCompetence::class, [['field' => 'IDuser', 'value' => (int)$record['sourceId']]]);
            foreach ($skills as $skill) {
                $scope = (int)$skill->get('IDorganization');
                if ($scope !== 0 && $scope !== $organizationId) { continue; }
                $definition = new Competence();
                if (!$definition->load((int)$skill->get('IDcompetence'))) { continue; }
                $record['skills'][] = ['name' => $definition->get('name'), 'category' => $definition->get('category'), 'level' => (int)$skill->get('level'), 'description' => $skill->get('description'), 'createdAt' => self::date($skill->get('datecreation')), 'updatedAt' => self::date($skill->get('datemodification'))];
            }
            foreach ($record['roleAssignments'] as &$assignment) {
                $link = new UserHolon();
                if (!$link->load([['IDuser', (int)$record['sourceId']], ['IDholon', (int)$assignment['sourceHolonId']]])) { continue; }
                $assignment += ['focus' => $link->get('focus'), 'timeBudgetHours' => $link->get('time_budget_hours'), 'timeBudgetRecurrence' => $link->get('time_budget_recurrence'), 'moneyBudget' => $link->get('money_budget'), 'moneyBudgetRecurrence' => $link->get('money_budget_recurrence'), 'assignmentReviewDate' => self::date($link->get('assignment_review_date')), 'isContextAdmin' => $link->isHolonAdmin()];
            }
            unset($assignment);
        }
        unset($record);
        foreach ($payload['modules']['indicators']['records'] as &$record) {
            $indicator = new StatIndicator();
            if (!$indicator->load((int)$record['sourceId'])) { continue; }
            $record['sourceResponsibleUserId'] = (int)$indicator->get('IDuser_responsible');
            $record['referencePoints'] = self::points($indicator);
        }
        unset($record);
        if (!empty($payload['modules']['indicators']['selected'])) {
            $payload['modules']['indicators']['imports'] = [];
            foreach (self::collection(ArrayStatIndicatorImport::class, [['field' => 'IDorganization', 'value' => $organizationId]]) as $import) {
                $payload['modules']['indicators']['imports'][] = ['sourceHolonId' => (int)$import->get('IDholon'), 'sourceIndicatorId' => (int)$import->get('IDstatindicator'), 'active' => (bool)$import->get('active'), 'createdAt' => self::date($import->get('created_at')), 'updatedAt' => self::date($import->get('updated_at'))];
            }
            $payload['modules']['indicators']['groups'] = [];
            foreach (self::collection(ArrayStatIndicatorGroup::class, [['field' => 'IDorganization', 'value' => $organizationId]]) as $group) {
                $items = [];
                foreach ($group->getItems() as $item) { $items[] = ['sourceIndicatorId' => (int)$item->get('IDstatindicator'), 'position' => (int)$item->get('position')]; }
                $payload['modules']['indicators']['groups'][] = ['sourceId' => (int)$group->getId(), 'sourceHolonId' => (int)$group->get('IDholon'), 'sourceUserId' => (int)$group->get('IDuser'), 'name' => $group->get('name'), 'displayMode' => $group->get('display_mode'), 'referenceType' => $group->get('reference_type'), 'chartMinValue' => $group->get('chart_min_value'), 'hideSameHolonSources' => (bool)$group->get('hide_same_holon_sources'), 'active' => (bool)$group->get('active'), 'createdAt' => self::date($group->get('created_at')), 'updatedAt' => self::date($group->get('updated_at')), 'items' => $items, 'referencePoints' => self::points($group)];
            }
        }
        return $payload;
    }

    public static function restoreMembers(Organization $organization, array $records, array $userMap, array $holonMap): void
    {
        foreach ($records as $record) {
            $userId = (int)($userMap[(int)($record['sourceId'] ?? 0)] ?? 0);
            if (!$userId) { continue; }
            $membership = new UserOrganization();
            if ($membership->load([['IDorganization', (int)$organization->getId()], ['IDuser', $userId]])) {
                foreach (['image', 'phone'] as $field) { if (array_key_exists($field, $record)) { $membership->set($field, $record[$field]); } }
                self::save($membership);
            }
            foreach ($record['skills'] ?? [] as $skill) {
                $competence = Competence::findOrCreate($skill['name'] ?? '', $skill['category'] ?? '', (int)$organization->getId());
                if (!$competence) { throw new \RuntimeException('Competence importee invalide.'); }
                $link = new UserCompetence();
                $link->set('IDuser', $userId);
                $link->set('IDorganization', (int)$organization->getId());
                $link->set('IDcompetence', (int)$competence->getId());
                $link->set('level', UserCompetence::normalizeLevel($skill['level'] ?? 0));
                $link->set('description', $skill['description'] ?? null);
                foreach (['createdAt' => 'datecreation', 'updatedAt' => 'datemodification'] as $source => $field) { if (!empty($skill[$source])) { $link->set($field, $skill[$source]); } }
                self::save($link);
            }
            foreach ($record['roleAssignments'] ?? [] as $assignment) {
                $holonId = (int)($holonMap[(int)($assignment['sourceHolonId'] ?? 0)] ?? 0);
                $link = new UserHolon();
                if (!$holonId || !$link->load([['IDuser', $userId], ['IDholon', $holonId]])) { continue; }
                foreach (['timeBudgetHours' => 'time_budget_hours', 'timeBudgetRecurrence' => 'time_budget_recurrence', 'moneyBudget' => 'money_budget', 'moneyBudgetRecurrence' => 'money_budget_recurrence', 'assignmentReviewDate' => 'assignment_review_date', 'createdAt' => 'datecreation', 'lastConnectionAt' => 'dateconnexion'] as $source => $field) { if (array_key_exists($source, $assignment)) { $link->set($field, $assignment[$source]); } }
                self::save($link);
            }
        }
    }

    public static function restorePoints(array $records, int $indicatorId = 0, int $groupId = 0): void
    {
        foreach ($records as $record) {
            $point = new StatIndicatorReferencePoint();
            $point->set('IDstatindicator', $indicatorId ?: null);
            $point->set('IDstatindicatorgroup', $groupId ?: null);
            $point->set('position_percent', $record['positionPercent'] ?? 0);
            $point->set('value', $record['value'] ?? 0);
            $point->set('point_at', $record['pointAt'] ?? null);
            self::save($point);
        }
    }

    public static function restoreIndicatorViews(Organization $organization, array $module, array $indicatorMap, array $holonMap, array $userMap): void
    {
        foreach ($module['imports'] ?? [] as $record) {
            $indicatorId = (int)($indicatorMap[(int)($record['sourceIndicatorId'] ?? 0)] ?? 0);
            $holonId = (int)($holonMap[(int)($record['sourceHolonId'] ?? 0)] ?? 0);
            if (!$indicatorId || !$holonId) { continue; }
            $import = new StatIndicatorImport();
            $import->set('IDorganization', (int)$organization->getId());
            $import->set('IDholon', $holonId);
            $import->set('IDstatindicator', $indicatorId);
            $import->set('active', !empty($record['active']));
            $import->set('created_at', $record['createdAt'] ?? null);
            $import->set('updated_at', $record['updatedAt'] ?? null);
            self::save($import);
        }
        foreach ($module['groups'] ?? [] as $record) {
            $group = new StatIndicatorGroup();
            $group->set('IDorganization', (int)$organization->getId());
            $group->set('IDholon', (int)($holonMap[(int)($record['sourceHolonId'] ?? 0)] ?? 0) ?: null);
            $group->set('IDuser', (int)($userMap[(int)($record['sourceUserId'] ?? 0)] ?? 0) ?: null);
            foreach (['name' => 'name', 'displayMode' => 'display_mode', 'referenceType' => 'reference_type', 'chartMinValue' => 'chart_min_value', 'hideSameHolonSources' => 'hide_same_holon_sources', 'active' => 'active', 'createdAt' => 'created_at', 'updatedAt' => 'updated_at'] as $source => $field) { $group->set($field, $record[$source] ?? null); }
            self::save($group);
            foreach ($record['items'] ?? [] as $recordItem) {
                $indicatorId = (int)($indicatorMap[(int)($recordItem['sourceIndicatorId'] ?? 0)] ?? 0);
                if (!$indicatorId) { continue; }
                $item = new StatIndicatorGroupItem();
                $item->set('IDstatindicatorgroup', (int)$group->getId());
                $item->set('IDstatindicator', $indicatorId);
                $item->set('position', (int)($recordItem['position'] ?? 0));
                self::save($item);
            }
            self::restorePoints($record['referencePoints'] ?? [], 0, (int)$group->getId());
        }
    }
}
